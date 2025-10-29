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

use core_date;
use DateInterval;
use DateTime;
use mod_bigbluebuttonbn\instance;
use ReflectionClass;

/**
 * Check the mod instance helper class.
 *
 * @package   bbbext_bnreminders
 * @copyright 2024 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 * @coversDefaultClass  \bbbext_bnreminders\bigbluebuttonbn\mod_instance_helper
 */
final class mod_instance_helper_test extends \advanced_testcase {
    /**
     * Test sync additional parameters.
     *
     * @return void
     * @covers ::sync_additional_params
     */
    public function test_sync_additional_params(): void {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $bbbgenerator = $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn');
        $time = new DateTime("now", core_date::get_user_timezone_object());
        $time->add(new DateInterval("PT1H"));
        $bbbinstance = instance::get_from_instanceid(
            $bbbgenerator->create_instance([
                'course' => $course,
            ])->id
        );

        $bnremindersgenerator = $this->getDataGenerator()->get_plugin_generator('bbbext_bnreminders');
        $bnremindersgenerator->enable_reminder($bbbinstance->get_instance_id());
        $bnremindersgenerator->add_reminder([
            'bigbluebuttonbnid' => $bbbinstance->get_instance_id(),
            'timespan' => 'PT1H',
        ]);
        $bnremindersgenerator->add_reminder([
            'bigbluebuttonbnid' => $bbbinstance->get_instance_id(),
            'timespan' => 'PT2H',
            'lastsent' => time(),
        ]);
        $modinstancehelper = new mod_instance_helper();
        $modinstancehelperref = new ReflectionClass($modinstancehelper);
        $synparametersref = $modinstancehelperref->getMethod('sync_additional_params');
        $synparametersref->setAccessible(true);
        // Simulate form sent.
        $data = $bbbinstance->get_instance_data();
        $data->bnreminders_openingtime = time();
        $data->bnreminders_reminderenabled = true;
        $data->bnreminders_paramcount = 3;
        $data->bnreminders_timespan = ['PT1H', 'PT2H', 'PT1D'];
        $synparametersref->invokeArgs($modinstancehelper, [$data]);

        // Three.
        $existingreminders = $DB->get_records(
            mod_instance_helper::SUBPLUGIN_REMINDERS_TABLE,
            ['bigbluebuttonbnid' => $bbbinstance->get_instance_id()]
        );
        $this->assertCount(3, $existingreminders);
        $this->assertEquals(
            ['PT1H', 'PT2H', 'PT1D'],
            array_values(array_map(fn($reminder) => $reminder->timespan, $existingreminders))
        );

        $data = $bbbinstance->get_instance_data();
        $data->bnreminders_paramcount = 2;
        $data->bnreminders_timespan = ['PT1H', 'PT2D'];
        $synparametersref->invokeArgs($modinstancehelper, [$data]);

        // Two disappeared and one added.
        $existingreminders = $DB->get_records(
            mod_instance_helper::SUBPLUGIN_REMINDERS_TABLE,
            ['bigbluebuttonbnid' => $bbbinstance->get_instance_id()]
        );
        $this->assertCount(2, $existingreminders);
        $this->assertEquals(
            ['PT1H', 'PT2D'],
            array_values(array_map(fn($reminder) => $reminder->timespan, $existingreminders))
        );
    }
}
