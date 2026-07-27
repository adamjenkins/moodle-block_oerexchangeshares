<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace block_oerexchangeshares;

use block_oerexchangeshares\local\content_builder;

/**
 * Tests for content_builder: scoping by creatorid, ordering, empty state,
 * version counts.
 *
 * @package    block_oerexchangeshares
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(content_builder::class)]
final class content_builder_test extends \advanced_testcase {
    /**
     * Insert a fake local_oerexchange_resources row, matching the field set
     * used by local_oerexchange's own resource_manager_test.
     *
     * @param int $creatorid
     * @param string $title
     * @param int $timeshared
     * @param string $status
     * @return int the new resource id
     */
    protected function create_resource(int $creatorid, string $title, int $timeshared, string $status = 'published'): int {
        global $DB;

        return $DB->insert_record('local_oerexchange_resources', (object) [
            'type' => 'course',
            'title' => $title,
            'summary' => '',
            'language' => 'en',
            'tags' => '',
            'licenseshortname' => 'cc-4.0',
            'activitytype' => null,
            'courseformat' => 'topics',
            'creatorid' => $creatorid,
            'siteid' => 1,
            'status' => $status,
            'downloadcount' => 0,
            'importcount' => 0,
            'forkedfromid' => null,
            'timeshared' => $timeshared,
            'timemodified' => $timeshared,
        ]);
    }

    /**
     * Insert a fake local_oerexchange_versions row.
     *
     * @param int $resourceid
     * @param string $status version status, e.g. 'ready' or 'failed'
     * @return int the new version id
     */
    protected function create_version(int $resourceid, string $status = 'ready'): int {
        global $DB;

        return $DB->insert_record('local_oerexchange_versions', (object) [
            'resourceid' => $resourceid,
            'versionnumber' => 1,
            'itemid' => 1,
            'filename' => 'test.mbz',
            'filesize' => 100,
            'moodleversion' => '5.2',
            'backupversion' => '2026071800',
            'structurejson' => null,
            'requiredplugins' => null,
            'status' => $status,
            'parseerror' => null,
            'timecreated' => time(),
        ]);
    }

    public function test_empty_state_returns_empty_array(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $shares = content_builder::get_shares_for_user((int) $user->id);

        $this->assertSame([], $shares);
    }

    public function test_scoped_to_creatorid(): void {
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->create_resource((int) $user1->id, 'User 1 resource', time());
        $this->create_resource((int) $user2->id, 'User 2 resource', time());

        $shares = content_builder::get_shares_for_user((int) $user1->id);

        $this->assertCount(1, $shares);
        $this->assertSame('User 1 resource', $shares[0]->title);
    }

    public function test_ordered_by_timeshared_desc(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $now = time();
        $this->create_resource((int) $user->id, 'Oldest', $now - 200);
        $this->create_resource((int) $user->id, 'Newest', $now);
        $this->create_resource((int) $user->id, 'Middle', $now - 100);

        $shares = content_builder::get_shares_for_user((int) $user->id);

        $this->assertCount(3, $shares);
        $this->assertSame('Newest', $shares[0]->title);
        $this->assertSame('Middle', $shares[1]->title);
        $this->assertSame('Oldest', $shares[2]->title);
    }

    public function test_limit_is_respected(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        for ($i = 0; $i < 5; $i++) {
            $this->create_resource((int) $user->id, "Resource $i", time() + $i);
        }

        $shares = content_builder::get_shares_for_user((int) $user->id, 2);

        $this->assertCount(2, $shares);
    }

    public function test_version_count_per_resource(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $resourceid1 = $this->create_resource((int) $user->id, 'Two versions', time());
        $resourceid2 = $this->create_resource((int) $user->id, 'No versions', time() - 10);

        $this->create_version($resourceid1);
        $this->create_version($resourceid1);

        $shares = content_builder::get_shares_for_user((int) $user->id);

        $byid = [];
        foreach ($shares as $share) {
            $byid[$share->id] = $share;
        }

        $this->assertSame(2, $byid[$resourceid1]->versioncount);
        $this->assertSame(0, $byid[$resourceid2]->versioncount);
    }

    /**
     * A failed upload never became a version in any user-meaningful sense:
     * counting it showed "1 version(s)" on a resource with nothing
     * servable at all.
     */
    public function test_failed_uploads_do_not_count_as_versions(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $resourceid = $this->create_resource((int) $user->id, 'Only a failed upload', time());
        $this->create_version($resourceid, 'failed');
        $this->create_version($resourceid, 'ready');
        $this->create_version($resourceid, 'superseded');

        $shares = content_builder::get_shares_for_user((int) $user->id);

        $this->assertSame(2, $shares[0]->versioncount, 'ready + superseded count; failed must not');
    }

    public function test_status_is_returned_verbatim(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->create_resource((int) $user->id, 'Hidden resource', time(), 'hidden');

        $shares = content_builder::get_shares_for_user((int) $user->id);

        $this->assertSame('hidden', $shares[0]->status);
    }
}
