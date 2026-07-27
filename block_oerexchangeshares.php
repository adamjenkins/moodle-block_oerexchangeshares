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

use block_oerexchangeshares\local\content_builder;

/**
 * "OER Exchange: your shares" block: shows the logged-in Exchange account's
 * own shared resources (title, status, version count) on the Dashboard, so
 * they don't need to navigate to the catalogue to see what they've shared.
 *
 * @package    block_oerexchangeshares
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_oerexchangeshares extends block_base {
    /** @var int Number of resources to show. */
    const RESOURCE_LIMIT = 8;

    /**
     * Initialize class member variables.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_oerexchangeshares');
    }

    /**
     * Locations where block can be displayed.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['my' => true];
    }

    /**
     * This block cannot be added more than once to the same page.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Build the block content. Kept thin: all data access lives in
     * content_builder so it can be unit-tested without rendering HTML.
     *
     * @return stdClass
     */
    public function get_content() {
        global $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = html_writer::link(
            new moodle_url('/local/oerexchange/share_new.php'),
            get_string('sharenewheading', 'local_oerexchange'),
            ['class' => 'btn btn-sm btn-outline-primary']
        );

        $shares = content_builder::get_shares_for_user((int) $USER->id, self::RESOURCE_LIMIT);

        if (empty($shares)) {
            $this->content->text = html_writer::tag(
                'p',
                get_string('noshares', 'block_oerexchangeshares'),
                ['class' => 'text-muted']
            );
            return $this->content;
        }

        // No 'deleted' entry: the tombstone flow zeroes creatorid, so this
        // creator-scoped list can never contain a deleted resource.
        $statusstrings = [
            'hidden' => get_string('status_hidden', 'block_oerexchangeshares'),
            'modhidden' => get_string('status_modhidden', 'block_oerexchangeshares'),
            'pending' => get_string('status_pending', 'block_oerexchangeshares'),
            'published' => get_string('status_published', 'block_oerexchangeshares'),
            'removed' => get_string('status_removed', 'block_oerexchangeshares'),
        ];

        // One query for every thumbnail on show, not one per row.
        $coverurls = \local_oerexchange\local\cover_image::urls_for(array_map(fn($s) => $s->id, $shares));

        $html = html_writer::start_tag('ul', ['class' => 'list-unstyled oerexchangeshares-list']);
        foreach ($shares as $share) {
            $statuslabel = $statusstrings[$share->status] ?? s($share->status);
            $title = format_string($share->title, true, ['context' => \core\context\system::instance()]);

            // Always link the title: resource.php admits a resource's own
            // creator for every status (user_can_view_resource() — "hiding
            // your own resource must not lock you out of the only page that
            // can unhide it"), and this block's audience IS the creator.
            // An earlier revision rendered non-published titles unlinked on
            // the false premise that the detail page 404s for them — that
            // hid the only page carrying the author's own controls.
            $url = new moodle_url('/local/oerexchange/resource.php', ['id' => $share->id]);
            $line = html_writer::link($url, $title);

            $line .= html_writer::tag('span', $statuslabel, ['class' => 'badge bg-secondary ms-2']);
            $line .= html_writer::tag(
                'span',
                get_string('versioncount', 'block_oerexchangeshares', $share->versioncount),
                ['class' => 'small text-muted ms-2']
            );

            // Thumbnail left, title/status/version count right. Hidden from
            // assistive tech (the title link beside it says the same thing)
            // but still a click target.
            $thumb = html_writer::link(
                $url,
                \local_oerexchange\local\cover_image::listitem($coverurls[$share->id] ?? null),
                ['tabindex' => '-1', 'aria-hidden' => 'true', 'class' => 'flex-shrink-0']
            );

            $html .= html_writer::tag(
                'li',
                $thumb . html_writer::div($line, 'oerexchangeshares-text flex-grow-1', ['style' => 'min-width:0;']),
                ['class' => 'oerexchangeshares-item d-flex gap-2 align-items-start mb-3']
            );
        }
        $html .= html_writer::end_tag('ul');

        $this->content->text = $html;

        return $this->content;
    }
}
