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

/**
 * Upgrade steps for BigBlueButton BN Reminders
 *
 * Documentation: {@link https://moodledev.io/docs/guides/upgrade}
 *
 * @package    bbbext_bnreminders
 * @category   upgrade
 * @copyright  2025 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute the plugin upgrade steps from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_bbbext_bnreminders_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2025101500) {
        // Define field remindertoguestsenabled to be dropped from bbbext_bnreminders.
        $table = new xmldb_table('bbbext_bnreminders');
        $field = new xmldb_field('remindertoguestsenabled');

        // Conditionally launch drop field remindertoguestsenabled.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define table bbbext_bnreminders_guests to be dropped.
        $table = new xmldb_table('bbbext_bnreminders_guests');

        // Conditionally launch drop table for bbbext_bnreminders_guests.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // BN reminders savepoint reached.
        upgrade_plugin_savepoint(true, 2025101500, 'bbbext', 'bnreminders');
    }
    return true;
}
