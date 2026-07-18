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

/**
 * Tests for the block's rendered output (get_content): only published
 * resources are linked, since the catalogue detail page is not viewable
 * for hidden/removed resources by their own creator.
 *
 * @package    block_oerexchangeshares
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_oerexchangeshares
 */
final class render_test extends \advanced_testcase {
    /**
     * Insert a fake local_oerexchange_resources row.
     *
     * @param int $creatorid
     * @param string $title
     * @param string $status
     * @return int the new resource id
     */
    protected function create_resource(int $creatorid, string $title, string $status): int {
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
            'timeshared' => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * Published resources link to the catalogue detail page; hidden and
     * removed resources are shown as plain text (no link), because
     * resource.php returns "not found" for them to their own creator.
     */
    public function test_only_published_resources_are_linked(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $publishedid = $this->create_resource((int) $user->id, 'Published resource', 'published');
        $hiddenid = $this->create_resource((int) $user->id, 'Hidden resource', 'hidden');
        $removedid = $this->create_resource((int) $user->id, 'Removed resource', 'removed');

        $block = block_instance('oerexchangeshares');
        $content = $block->get_content();

        // All three titles are shown.
        $this->assertStringContainsString('Published resource', $content->text);
        $this->assertStringContainsString('Hidden resource', $content->text);
        $this->assertStringContainsString('Removed resource', $content->text);

        // Only the published one links to resource.php.
        $this->assertStringContainsString('resource.php?id=' . $publishedid, $content->text);
        $this->assertStringNotContainsString('resource.php?id=' . $hiddenid, $content->text);
        $this->assertStringNotContainsString('resource.php?id=' . $removedid, $content->text);
    }

    /**
     * The empty state renders the friendly "nothing shared" message and no
     * list.
     */
    public function test_empty_state_message(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $block = block_instance('oerexchangeshares');
        $content = $block->get_content();

        $this->assertStringContainsString(
            get_string('noshares', 'block_oerexchangeshares'),
            $content->text
        );
        $this->assertStringNotContainsString('<ul', $content->text);
    }
}
