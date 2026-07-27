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

namespace block_oerexchangeshares\local;

/**
 * Data access for the "your shares" block: no HTML, plain data only, so it
 * can be unit-tested independently of get_content().
 *
 * @package    block_oerexchangeshares
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content_builder {
    /**
     * Fetch the given user's own shared resources, most recent first, with
     * a version count per resource.
     *
     * @param int $userid Exchange-local userid (creatorid on the resource).
     * @param int $limit Maximum number of resources to return.
     * @return array list of stdClass rows: id, type, title, licenseshortname,
     *     activitytype, status, downloadcount, importcount, timeshared,
     *     versioncount.
     */
    public static function get_shares_for_user(int $userid, int $limit = 8): array {
        global $DB;

        $resources = $DB->get_records(
            'local_oerexchange_resources',
            ['creatorid' => $userid],
            'timeshared DESC, id DESC',
            'id, type, title, licenseshortname, activitytype, status, downloadcount, importcount, timeshared',
            0,
            $limit
        );

        if (!$resources) {
            return [];
        }

        $ids = array_keys($resources);
        [$insql, $inparams] = $DB->get_in_or_equal($ids);
        // Counted: versions that are being served ('ready'), were served
        // ('superseded' history) or are on their way ('parsing'). A 'failed'
        // upload never became a version in any user-meaningful sense —
        // counting it showed "1 version(s)" on a resource that has nothing
        // servable at all.
        $counts = $DB->get_records_sql(
            "SELECT resourceid, COUNT(id) AS versioncount
               FROM {local_oerexchange_versions}
              WHERE resourceid $insql AND status <> 'failed'
           GROUP BY resourceid",
            $inparams
        );

        $shares = [];
        foreach ($resources as $resource) {
            $resource->versioncount = isset($counts[$resource->id]) ? (int) $counts[$resource->id]->versioncount : 0;
            $shares[] = $resource;
        }

        return $shares;
    }
}
