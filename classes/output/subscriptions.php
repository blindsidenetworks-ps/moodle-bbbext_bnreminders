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

namespace bbbext_bnreminders\output;

use bbbext_bnreminders\subscription_utils;
use mod_bigbluebuttonbn\instance;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Get and set subscription status for a user or an email.
 *
 * @package   bbbext_bnreminders
 * @copyright 2024 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
class subscriptions implements renderable, templatable {
    /** @var int */
    protected $userid;

    /**
     * Constructor for the index renderable.
     *
     * @param int $userid
     */
    public function __construct(int $userid) {
        $this->userid = $userid;
    }

    /**
     * Export for template
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        $courses = enrol_get_users_courses($this->userid);
        $instances = [];
        foreach ($courses as $course) {
            $modules = get_course_mods($course->id);
            foreach ($modules as $module) {
                if ($module->modname == 'bigbluebuttonbn') {
                    $bbbinstance = instance::get_from_cmid($module->id);
                    if ($bbbinstance->get_instance_var('reminderenabled') !== '1') {
                        continue;
                    }
                    $meetingname = $bbbinstance->get_meeting_name();
                    $coursename = $bbbinstance->get_course()->fullname;
                    $issubscribed = subscription_utils::is_user_subscribed($this->userid, $bbbinstance);
                    // Unsubscription toggle.
                    $toggle = [
                        'id' => 'toggle-subscription-' . $module->id,
                        'label' => $issubscribed ? get_string('subscribed', 'bbbext_bnreminders') :
                            get_string('unsubscribed', 'bbbext_bnreminders'),
                        'checked' => $issubscribed,
                        'url' => new \moodle_url('/mod/bigbluebuttonbn/extension/bnreminders/managesubscriptions.php'),
                        'cmid' => $module->id,
                        'name' => 'state',
                        'value' => !$issubscribed,
                        'disabled' => false,
                    ];
                    $instance = new stdClass();
                    $instance->id = $module->id;
                    $instance->name = $meetingname;
                    $instance->coursename = $coursename;
                    $instance->url = new moodle_url('/mod/bigbluebuttonbn/view.php', ['id' => $module->id]);
                    $instance->toggle = $toggle;
                    $instance->subscribed = $issubscribed;
                    $instances[] = $instance;
                }
            }
        }
        $data = new stdClass();
        $data->instances = $instances;
        return $data;
    }
}
