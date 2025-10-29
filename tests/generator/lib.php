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

use bbbext_bnreminders\bigbluebuttonbn\mod_instance_helper;
use bbbext_bnreminders\utils;

/**
 * Generator class
 *
 * @package   bbbext_bnreminders
 * @copyright 2024 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
class bbbext_bnreminders_generator extends \component_generator_base {
    /**
     * Enable reminder.
     *
     * @param int $bbbinstanceid
     * @return void
     */
    public function enable_reminder(int $bbbinstanceid): void {
        global $DB;
        $existingreminder = $DB->get_record(mod_instance_helper::SUBPLUGIN_TABLE, ['bigbluebuttonbnid' => $bbbinstanceid]);
        if ($existingreminder) {
            $existingreminder->reminderenabled = 1;
            $DB->update_record(mod_instance_helper::SUBPLUGIN_TABLE, $existingreminder);
        } else {
            $reminder = new stdClass();
            $reminder->bigbluebuttonbnid = $bbbinstanceid;
            $reminder->reminderenabled = 1;
            $DB->insert_record(mod_instance_helper::SUBPLUGIN_TABLE, $reminder);
        }
    }

    /**
     * Disable reminder for instance
     *
     * @param int $bbbinstanceid
     * @return void
     * @throws \dml_exception
     */
    public function disable_reminder(int $bbbinstanceid): void {
        global $DB;
        $existingreminder = $DB->get_record(mod_instance_helper::SUBPLUGIN_TABLE, ['bigbluebuttonbnid' => $bbbinstanceid]);
        if ($existingreminder) {
            $existingreminder->reminderenabled = 0;
            $DB->update_record(mod_instance_helper::SUBPLUGIN_TABLE, $existingreminder);
        } else {
            $reminder = new stdClass();
            $reminder->bigbluebuttonbnid = $bbbinstanceid;
            $reminder->reminderenabled = 0;
            $DB->insert_record(mod_instance_helper::SUBPLUGIN_TABLE, $reminder);
        }
    }

    /**
     * Add reminder for instance
     *
     * @param object|array $record
     * @return stdClass
     * @throws \dml_exception
     */
    public function add_reminder($record): stdClass {
        global $DB;
        if (is_object($record)) {
            $record = (array) $record;
        }
        $reminder = (object) array_merge([
            'timespan' => utils::ONE_HOUR,
        ], $record);
        $reminder->id = $DB->insert_record(mod_instance_helper::SUBPLUGIN_REMINDERS_TABLE, $reminder);
        return $reminder;
    }
}
