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

use core_user;
use moodle_url;
use mod_bigbluebuttonbn\instance;

/**
 * Utility class for email subscription related operations.
 *
 * @package   bbbext_bnreminders
 * @copyright 2024 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
class subscription_utils {
    /**
     * Updates reminder preferences for a given user and a meeting or all meetings.
     *
     * @param bool $status
     * @param int $userid
     * @param instance $instance
     * @return void
     */
    public static function change_reminder_subcription_user(bool $status, int $userid, instance $instance): void {
        $user = core_user::get_user($userid);
        if ($user) {
            $userprefs = get_user_preferences('bbbext_bnreminders', '', $userid);
            $userprefs = json_decode($userprefs, true);
            if (empty($userprefs)) {
                $userprefs = [];
            }
            $userprefs[$instance->get_instance_id()] = $status;
            set_user_preference('bbbext_bnreminders', json_encode($userprefs), $user->id);
        }
    }

    /**
     * Get reminder subscription status for a user and a meeting.
     *
     * @param int $userid
     * @param instance $instance
     * @return bool true is enable, false is disabled (true is set by default)
     */
    public static function is_user_subscribed(int $userid, instance $instance): bool {
        $userprefs = get_user_preferences('bbbext_bnreminders', '', $userid);
        if (!empty($userprefs)) {
            $userprefs = json_decode($userprefs, true);
            $instanceid = $instance->get_instance_id();
            if (isset($userprefs[$instanceid])) {
                return $userprefs[$instanceid];
            }
        }
        // By default user is subscribed if no preference set.
        return true;
    }

    /**
     * Get unsubscribe URL
     *
     * @param int|null $cmid
     * @param int|null $userid
     * @return moodle_url
     */
    public static function get_unsubscribe_url(?int $cmid, ?int $userid = null): moodle_url {
        $params = ['userid' => $userid, 'cmid' => $cmid];
        $params = array_filter($params, fn($value) => !is_null($value));
        return new moodle_url('/mod/bigbluebuttonbn/extension/bnreminders/subscription.php', $params);
    }
}
