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

namespace bbbext_bnreminders\task;

use bbbext_bnreminders\bigbluebuttonbn\mod_instance_helper;
use bbbext_bnreminders\subscription_utils;
use bbbext_bnreminders\utils;
use core\task\scheduled_task;
use moodle_url;
use DateInterval;
use DateTime;
use mod_bigbluebuttonbn\instance;

/**
 * This adhoc task will send emails to users with the meeting's details
 *
 * @package   bbbext_bnreminders
 * @copyright 2024 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
class check_emails_reminder extends scheduled_task {
    /**
     * Maximum number of emails per task.
     */
    const MAX_EMAIL_PER_TASK = 100;

    /**
     * Get name
     *
     * @return \lang_string|string
     * @throws \coding_exception
     */
    public function get_name() {
        return get_string('check_emails_reminder', 'bbbext_bnreminders');
    }

    /**
     * Send all the emails
     */
    public function execute() {
        global $DB;
        $allinstancesreminder = $DB->get_recordset(mod_instance_helper::SUBPLUGIN_TABLE, ['reminderenabled' => 1]);
        $enabled = \core_plugin_manager::instance()->get_plugin_info('bbbext_bnreminders')->is_enabled();
        if (!$enabled) {
            return;
        }
        foreach ($allinstancesreminder as $instancereminder) {
            $instance = instance::get_from_instanceid($instancereminder->bigbluebuttonbnid);
            if (empty($instance->get_instance_var('openingtime'))) {
                continue;
            }
            $allreminders = $DB->get_recordset(
                mod_instance_helper::SUBPLUGIN_REMINDERS_TABLE,
                ['bigbluebuttonbnid' => $instancereminder->bigbluebuttonbnid]
            );

            $emailsubject = $this->get_subject($instance);
            $emailhtmlmessage = $this->get_html_message($instance);
            $emailfooter = $this->get_footer($instance);
            foreach ($allreminders as $reminder) {
                $interval = new DateInterval($reminder->timespan);
                $openingtime = $instance->get_instance_var('openingtime');
                if (empty($openingtime)) {
                    continue;
                }
                $reminderstart = new DateTime();
                $reminderstart->setTimezone(\core_date::get_server_timezone_object());
                $reminderstart->setTimestamp($openingtime);
                $reminderstart->sub($interval);
                $now = new DateTime('now', \core_date::get_server_timezone_object());
                // Calculate the difference between now and reminder start.
                $diff = $now->diff($reminderstart);
                if ($diff->invert == 1 && empty($reminder->lastsent)) {
                    $allusers = get_enrolled_users($instance->get_context(), 'mod/bigbluebuttonbn:join');
                    $allemails = [];
                    $userstoemail = [];
                    foreach ($allusers as $user) {
                        if (!subscription_utils::is_user_subscribed($user->id, $instance)) {
                            continue;
                        }
                        $userstoemail[] = $user;
                    }
                    // Do it in batch.
                    $useremails = count($userstoemail);
                    for ($i = 0; $i < $useremails; $i += self::MAX_EMAIL_PER_TASK) {
                        $emailreminder = new send_email_reminders_message();
                        $emailreminder->set_custom_data(
                            [
                                'usersid' => array_map(fn($user) => $user->id, array_slice($userstoemail, $i, 100)),
                                'instanceid' => $instance->get_instance_id(),
                                'reminderid' => $reminder->id,
                                'subject' => $emailsubject,
                                'htmlmessage' => $emailhtmlmessage,
                                'emailfooter' => $emailfooter,
                            ]
                        );
                        \core\task\manager::queue_adhoc_task($emailreminder);
                    }
                    $DB->set_field(mod_instance_helper::SUBPLUGIN_REMINDERS_TABLE, 'lastsent', time(), ['id' => $reminder->id]);
                }
            }
        }
    }

    /**
     * Get the subject of the notification.
     *
     * @param instance $instance
     * @return string
     */
    protected function get_subject(instance $instance): string {
        $htmlsubject = $this->get_email_content(
            'emailsubject',
            $instance
        );
        return $htmlsubject;
    }

    /**
     * Get variables to make available to strings.
     *
     * @param instance $instance
     * @return array
     */
    protected function get_string_vars(instance $instance): array {
        return [
            'course_fullname' => $instance->get_course()->fullname,
            'course_shortname' => $instance->get_course()->shortname,
            'name' => $instance->get_cm()->name,
            'url' => (
                new \moodle_url(
                    '/mod/bigbluebuttonbn/view.php',
                    ['id' => $instance->get_cm_id()]
                )
            )->out(false),
            'date' => userdate($instance->get_instance_var('openingtime')),
        ];
    }

    /**
     * Get the HTML message content.
     *
     * @param instance $instance
     * @return string
     */
    protected function get_html_message(instance $instance): string {
        $htmlmessage = $this->get_email_content(
            'emailtemplate',
            $instance
        );
        return $htmlmessage;
    }

    /**
     * Get the footer of the email.
     *
     * @param instance $instance
     * @return string
     */
    protected function get_footer(instance $instance): string {
        $htmlfooter = $this->get_email_content(
            'emailfooter',
            $instance
        );
        if (!empty($htmlfooter)) {
            $htmlfooter = '<br>' . $htmlfooter;
        }
        return $htmlfooter ?? '';
    }

    /**
     * Get the processed message content.
     * @param string $config The configuration setting
     * @param instance $instance
     * @return string
     */
    protected function get_email_content(string $config, instance $instance): string {
        $text = get_config('bbbext_bnreminders', $config);
        $vars = $this->get_string_vars($instance);
        $emailcontent = utils::replace_vars_in_text(
            $vars,
            $text
        );
        return $emailcontent;
    }
}
