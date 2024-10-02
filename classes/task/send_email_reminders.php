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

namespace bbbext_bnemail\task;

use bbbext_bnemail\subscription_utils;
use bbbext_bnemail\utils;
use core\task\adhoc_task;
use core_user;
use mod_bigbluebuttonbn\instance;

/**
 * This adhoc task will send emails to guest users with the meeting's details
 *
 * @package   bbbext_bnemail
 * @copyright 2024 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
class send_email_reminders extends adhoc_task {

    /**
     * Execute the task
     *
     * @return void
     */
    public function execute() {
        $customdata = $this->get_custom_data();
        $emailsubject = $customdata->subject;
        $emailhtmlmessage = $customdata->htmlmessage;
        $instance = instance::get_from_instanceid($customdata->instanceid);
        $cmid = $instance->get_cm_id();
        foreach ($customdata->emails as $email) {
            $user = core_user::get_noreply_user();
            $user->email = $email;
            $user->mailformat = FORMAT_HTML; // HTML format.
            $unsubscribeurl = subscription_utils::get_unsubscribe_url($cmid, $email);
            $fullmessage = $emailhtmlmessage . '<br><br>'
                . get_string('email_unsubscribe_message', 'bbbext_bnemail', [
                        'unsubscribeurl' => $unsubscribeurl->out(false),
                    ]
                );
            email_to_user(
                $user,
                core_user::get_noreply_user(),
                $emailsubject,
                html_to_text($fullmessage),
                $fullmessage
            );
        }
    }
}
