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
 * Tests for the block's rendered output (get_content): every share links to
 * its detail page (the catalogue admits a resource's own creator for every
 * status), statuses render as translated labels, and output is escaped.
 *
 * @package    block_oerexchangeshares
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\block_oerexchangeshares::class)]
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
     * Every share links to its detail page whatever its status:
     * resource.php admits the resource's own creator for every status
     * (that page carries the author's own unhide/delete controls), and the
     * creator is exactly who sees this block.
     */
    public function test_every_share_is_linked_regardless_of_status(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $ids = [
            $this->create_resource((int) $user->id, 'Published resource', 'published'),
            $this->create_resource((int) $user->id, 'Hidden resource', 'hidden'),
            $this->create_resource((int) $user->id, 'Removed resource', 'removed'),
            $this->create_resource((int) $user->id, 'Taken down resource', 'modhidden'),
            $this->create_resource((int) $user->id, 'Pending resource', 'pending'),
        ];

        $block = block_instance('oerexchangeshares');
        $content = $block->get_content();

        foreach ($ids as $id) {
            $this->assertStringContainsString('resource.php?id=' . $id, $content->text);
        }
    }

    /**
     * Every status the creator can encounter renders as its translated
     * label, never as the raw machine token.
     */
    public function test_statuses_render_as_translated_labels(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        foreach (['published', 'hidden', 'modhidden', 'pending', 'removed'] as $status) {
            $this->create_resource((int) $user->id, "Resource {$status}", $status);
        }

        $content = block_instance('oerexchangeshares')->get_content();

        foreach (['published', 'hidden', 'modhidden', 'pending', 'removed'] as $status) {
            $this->assertStringContainsString(
                get_string('status_' . $status, 'block_oerexchangeshares'),
                $content->text
            );
        }
        $this->assertStringNotContainsString('>modhidden<', $content->text);
    }

    /**
     * A title containing markup is escaped on output, and an unknown
     * status value falls back to an escaped raw token rather than markup.
     */
    public function test_titles_and_unknown_statuses_are_escaped(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->create_resource((int) $user->id, '<script>alert(1)</script>Evil', 'published');
        $this->create_resource((int) $user->id, 'Odd status', '<b>weird</b>');

        $content = block_instance('oerexchangeshares')->get_content();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $content->text);
        $this->assertStringNotContainsString('<b>weird</b>', $content->text);
    }

    /**
     * Regression guard for the multilang-rendering bug fixed across the
     * Exchange blocks: this block's title sink
     * (block_oerexchangeshares.php:104) must stay
     * format_string($share->title, true, ['context' => system]) and never
     * revert to s(), which would render a multilang-marked-up title as
     * visible literal `<span lang="en" class="multilang">...` markup
     * instead of collapsing it to the viewer's language.
     *
     * Enables the filter trio locally rather than relying on site config,
     * and pins the double-escape guard (a title containing `&` must be
     * escaped exactly once, not left raw and not turned into `&amp;amp;`).
     */
    public function test_titles_render_through_multilang_and_escape_ampersand_once(): void {
        $this->resetAfterTest();
        filter_set_global_state('multilang', TEXTFILTER_ON);
        set_config('filterall', 1);
        set_config('stringfilters', 'multilang');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->create_resource(
            (int) $user->id,
            '<span lang="en" class="multilang">Arts &amp; Crafts</span>'
                . '<span lang="ja" class="multilang">工芸</span>',
            'published'
        );

        $content = block_instance('oerexchangeshares')->get_content();

        $this->assertStringContainsString('Arts &amp; Crafts', $content->text);
        $this->assertStringNotContainsString('工芸', $content->text);
        $this->assertStringNotContainsString('multilang', $content->text);
        $this->assertStringNotContainsString('&amp;amp;', $content->text);
        $this->assertSame(1, substr_count($content->text, '&amp;'));
    }

    /**
     * The footer always offers sharing a new resource.
     */
    public function test_footer_links_to_sharing_a_new_resource(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $content = block_instance('oerexchangeshares')->get_content();

        $this->assertStringContainsString('share_new.php', $content->footer);
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
