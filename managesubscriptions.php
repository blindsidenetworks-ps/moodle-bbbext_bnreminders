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
 * Get and set subscription status for a user or an email.
 *
 * @package   bbbext_bnreminders
 * @copyright 2022 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent [at] call-learning [dt] fr)
 */

use bbbext_bnreminders\subscription_utils;
use core\notification;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\exceptions\server_not_available_exception;
use mod_bigbluebuttonbn\local\proxy\bigbluebutton_proxy;

require(__DIR__ . '/../../../../config.php');
require_login();
global $PAGE, $OUTPUT, $USER;
$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_url(
    '/mod/bigbluebuttonbn/extension/bnreminders/managesubscriptions.php',
);
$title = get_string(
    'unsubscribe:title',
    'bbbext_bnreminders'
);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');
$subscriptions = new \bbbext_bnreminders\output\subscriptions($USER->id);
$cmid = optional_param('cmid', null, PARAM_INT);
$state = optional_param('state', null, PARAM_BOOL);

echo $OUTPUT->header();
if (!empty($cmid) && !is_null($state)) {
    $instance = instance::get_from_cmid($cmid);
    if ($instance) {
        subscription_utils::change_reminder_subcription_user($state, $USER->id, $instance);
        $message = get_string(
            $state ? 'subscribed:success' : 'unsubscribed:success',
            'bbbext_bnreminders',
            [
                'name' => $instance->get_meeting_name(),
            ]
        );
        notification::add($message, notification::SUCCESS);
    }
}
echo $OUTPUT->render($subscriptions);
echo $OUTPUT->footer();
