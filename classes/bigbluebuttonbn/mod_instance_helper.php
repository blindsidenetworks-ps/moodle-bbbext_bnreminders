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

namespace bbbext_bnreminders\bigbluebuttonbn;

use stdClass;

/**
 * Class defining a way to deal with instance save/update/delete in extension
 *
 * @package   bbbext_bnreminders
 * @copyright 2024 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
class mod_instance_helper extends \mod_bigbluebuttonbn\local\extension\mod_instance_helper {
    /**
     * This is the name of the table that will be used to store additional data for the instance.
     */
    const SUBPLUGIN_TABLE = 'bbbext_bnreminders';

    /**
     * This is the name of the table that will be used to store reminders.
     */
    const SUBPLUGIN_REMINDERS_TABLE = 'bbbext_bnreminders_rem';

    /**
     * Runs any processes that must run before a bigbluebuttonbn insert/update.
     *
     * @param stdClass $bigbluebuttonbn BigBlueButtonBN form data
     **/
    public function add_instance(stdClass $bigbluebuttonbn) {
        $this->sync_additional_params($bigbluebuttonbn);
    }

    /**
     * Make sure that the bbbext_bnreminders has the right parameters (and not more)
     *
     * @param stdClass $bigbluebuttonbn
     * @return void
     */
    private function sync_additional_params(stdClass $bigbluebuttonbn): void {
        global $DB;
        // Checks first.
        $count = $bigbluebuttonbn->bnreminders_paramcount ?? 0;
        if (!empty($bigbluebuttonbn->bnreminders_timespan)) {
            $bigbluebuttonbn->bnreminders_timespan = clean_param_array(
                $bigbluebuttonbn->bnreminders_timespan,
                PARAM_TEXT,
                true
            );
            if (
                empty($bigbluebuttonbn->bnreminders_timespan)
                && (
                    !(defined('PHPUNIT_TEST') && PHPUNIT_TEST)
                    && !defined('BEHAT_SITE_RUNNING')
                )
            ) {
                debugging('bnreminders : The reminder contains invalid value.');
            }
        }
        if (
            !isset($bigbluebuttonbn->bnreminders_reminderenabled)
            || clean_param(
                $bigbluebuttonbn->bnreminders_reminderenabled,
                PARAM_BOOL
            ) != $bigbluebuttonbn->bnreminders_reminderenabled
        ) {
            if (
                !(defined('PHPUNIT_TEST') && PHPUNIT_TEST)
                && !defined('BEHAT_SITE_RUNNING')
            ) {
                debugging('bnreminders : The enabled type contains invalid value.');
            }
            return;
        }
        // First remove unwanted values.
        $rs = $DB->get_recordset(self::SUBPLUGIN_REMINDERS_TABLE, ['bigbluebuttonbnid' => $bigbluebuttonbn->id]);
        foreach ($rs as $existingreminder) {
            if (
                empty($bigbluebuttonbn->bnreminders_timespan)
                || !in_array(
                    $existingreminder->timespan,
                    $bigbluebuttonbn->bnreminders_timespan
                )
            ) {
                $DB->delete_records(
                    self::SUBPLUGIN_REMINDERS_TABLE,
                    ['id' => $existingreminder->id]
                );
            }
        }
        $rs->close();
        for ($index = 0; $index < $count; $index++) {
            $queryfields = [];
            $queryfields['timespan'] = $bigbluebuttonbn->bnreminders_timespan[$index];
            $queryfields['bigbluebuttonbnid'] = $bigbluebuttonbn->id;

            // Fetch a single record using get_record.
            $rem = $DB->get_record(
                self::SUBPLUGIN_REMINDERS_TABLE,
                $queryfields
            );

            // Check if the recordset is empty.
            if (!$rem) {
                // If no record exists, insert a new one.
                $DB->insert_record(
                    self::SUBPLUGIN_REMINDERS_TABLE,
                    (object) $queryfields
                );
            } else {
                // Check if openingtime has changed.
                if ($bigbluebuttonbn->bnreminders_openingtime != $bigbluebuttonbn->openingtime) {
                    // If record exists, reset lastsent to 0 if opentime was updated.

                    // Prepare the update object including the id.
                    $updatefields = new stdClass();
                    $updatefields->id = $rem->id; // Ensure 'id' field is set.
                    $updatefields->lastsent = 0;

                    // Update the record in the database.
                    $DB->update_record(
                        self::SUBPLUGIN_REMINDERS_TABLE,
                        $updatefields
                    );
                }
            }
        }
        $existingrecord = $DB->get_record(
            self::SUBPLUGIN_TABLE,
            ['bigbluebuttonbnid' => $bigbluebuttonbn->id]
        );
        if ($existingrecord) {
            $existingrecord->reminderenabled = $bigbluebuttonbn->bnreminders_reminderenabled ?? false;
            $DB->update_record(
                self::SUBPLUGIN_TABLE,
                $existingrecord
            );
        } else {
            $DB->insert_record(
                self::SUBPLUGIN_TABLE,
                [
                    'bigbluebuttonbnid' => $bigbluebuttonbn->id,
                    'reminderenabled' => $bigbluebuttonbn->bnreminders_reminderenabled ?? false,
                ]
            );
        }
    }

    /**
     * Runs any processes that must be run after a bigbluebuttonbn insert/update.
     *
     * @param stdClass $bigbluebuttonbn BigBlueButtonBN form data
     **/
    public function update_instance(stdClass $bigbluebuttonbn): void {
        $this->sync_additional_params($bigbluebuttonbn);
    }

    /**
     * Runs any processes that must be run after a bigbluebuttonbn delete.
     *
     * @param int $id
     */
    public function delete_instance(int $id): void {
        global $DB;
        $DB->delete_records(self::SUBPLUGIN_TABLE, [
            'bigbluebuttonbnid' => $id,
        ]);
        $DB->delete_records(self::SUBPLUGIN_REMINDERS_TABLE, [
            'bigbluebuttonbnid' => $id,
        ]);
    }

    /**
     * Get any join table name that is used to store additional data for the instance.
     *
     * @return array
     */
    public function get_join_tables(): array {
        return ['bbbext_bnreminders'];
    }
}
