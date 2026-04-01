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

namespace bbbext_bnreminders;

/**
 * Callbacks invoked by parent plugin observers for bnreminders lifecycle events.
 *
 * @package   bbbext_bnreminders
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class plugininfo_callbacks {
    /**
     * Prevent enabling bnreminders when BNX migration baseline is present.
     *
     * @return void
     */
    public static function on_enable(): void {
        $plugins = \core_plugin_manager::instance()->get_installed_plugins('bbbext');
        $bnxversion = (int)($plugins['bnx'] ?? 0);

        if ($bnxversion < 2026040100) {
            return;
        }

        $oldvalue = get_config('bbbext_bnreminders', 'disabled');
        if (!empty($oldvalue)) {
            return;
        }

        set_config('disabled', 1, 'bbbext_bnreminders');
        add_to_config_log('disabled', $oldvalue, 1, 'bbbext_bnreminders');
        \core_plugin_manager::reset_caches();
    }
}
