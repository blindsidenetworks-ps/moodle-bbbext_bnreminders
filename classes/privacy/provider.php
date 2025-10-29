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

namespace bbbext_bnreminders\privacy;

use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use core_privacy\local\metadata\collection;

/**
 * Privacy class for requesting user data.
 *
 * @package   bbbext_bnreminders
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class provider implements
    // This plugin has data to export.
    \core_privacy\local\metadata\provider,
    // This plugin has user preferences to export.
    \core_privacy\local\request\user_preference_provider {
    /**
     * Provides metadata about the personal data stored by the plugin.
     *
     * @param collection $collection The metadata collection to be updated.
     * @return collection The updated metadata collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference(
            'bbbext_bnreminders',
            'privacy:metadata:preference:bbbext_bnreminders'
        );
        return $collection;
    }

    /**
     * Export the user preference in a readable way per activity.
     * @param int $userid The user to export data for.
     */
    public static function export_user_preferences(int $userid): void {
        $userpref = get_user_preferences('bbbext_bnreminders', '', $userid);
        $desc = '';
        if ($userpref !== null) {
            $preferences = json_decode($userpref, true);
            foreach ($preferences as $bbbinstanceid => $enabled) {
                $preference = $enabled ? 'privacy:reminderpreferenceyes' : 'privacy:reminderpreferenceno';
                $userprefdescription = get_string($preference, 'bbbext_bnreminders', ['activityid' => $bbbinstanceid]);
                $desc .= $userprefdescription . " ";
            }
            writer::export_user_preference(
                'bbbext_bnreminders',
                'bbbext_bnreminders',
                $userpref,
                rtrim($desc)
            );
        }
    }
}
