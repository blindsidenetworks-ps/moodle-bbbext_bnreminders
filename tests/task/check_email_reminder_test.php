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

use bbbext_bnreminders\subscription_utils;
use bbbext_bnreminders\utils;
use core_date;
use DateInterval;
use DateTime;
use mod_bigbluebuttonbn\instance;

/**
 * Check email reminder class.
 *
 * @package   bbbext_bnreminders
 * @copyright 2024 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 * @coversDefaultClass  \bbbext_bnreminders\task\check_emails_reminder
 */
final class check_email_reminder_test extends \advanced_testcase {
    /**
     * @var instance|null
     */
    private $bbbinstance = null;

    /**
     * @var \stdClass|null
     */
    private $students = null;

    /**
     * @var \stdClass|null
     */
    private $teacher = null;

    /**
     * Data provider for test_reminder_enabled_sent.
     *
     * @return array[]
     */
    public static function reminder_enabled_sent_provider(): array {
        return [
            '1 Hour before' => [
                'reminderinterval' => utils::ONE_HOUR,
                'openingtimespan' => 'PT1H',
                'expectedemails' => [
                    'teacher@example.com',
                    'username2@example.com',
                    'username3@example.com',
                ],
                'nosubscriptions' => ['username1@example.com'],
            ],
            '1 Day before' => [
                'reminderinterval' => utils::ONE_HOUR,
                'openingtimespan' => 'P1D',
                'expectedemails' => [],
            ],
        ];
    }

    /**
     * Set up test.
     */
    public function setUp(): void {
        parent::setUp();

        $this->preventResetByRollback(); // If not messages are not sent as we are using transactions.
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $bbbgenerator = $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn');
        $time = new DateTime("now", core_date::get_user_timezone_object());
        $time->add(new DateInterval("PT1H"));
        $this->bbbinstance = instance::get_from_instanceid($bbbgenerator->create_instance([
            'course' => $course,
        ])->id);

        for ($i = 1; $i < 4; $i++) {
            $this->students[] = $generator->create_and_enrol(
                $course,
                'student',
                ['email' => 'username' . $i . '@example.com', 'username' => 'username' . $i]
            );
        }
        $this->teacher =
            $generator->create_and_enrol($course, 'teacher', ['email' => 'teacher@example.com', 'username' => 'teacher']);
    }

    /**
     * Test that reminder when reminder is enabled the email is sent.
     *
     * @param string $reminderinterval
     * @param string $openingtimespan
     * @param array $expectedemails
     * @param array|null $nosubscriptions
     * @return void
     * @throws \dml_exception
     * @dataProvider reminder_enabled_sent_provider
     * @covers ::execute
     */
    public function test_reminder_enabled_sent(
        string $reminderinterval,
        string $openingtimespan,
        array $expectedemails,
        ?array $nosubscriptions = []
    ): void {
        global $DB;
        $emailsink = $this->redirectEmails();
        \phpunit_util::stop_message_redirection();
        $bnremindersgenerator = $this->getDataGenerator()->get_plugin_generator('bbbext_bnreminders');
        $bnremindersgenerator->enable_reminder($this->bbbinstance->get_instance_id());
        $bnremindersgenerator->add_reminder([
            'bigbluebuttonbnid' => $this->bbbinstance->get_instance_id(),
            'timespan' => $reminderinterval,
        ]);
        $time = new DateTime("now", core_date::get_user_timezone_object());
        $time->add(new DateInterval($openingtimespan));
        $DB->set_field('bigbluebuttonbn', 'openingtime', $time->getTimestamp(), ['id' => $this->bbbinstance->get_instance_id()]);

        foreach ($this->students as $student) {
            if (in_array($student->email, $nosubscriptions)) {
                subscription_utils::change_reminder_subcription_user(false, $student->id, $this->bbbinstance);
            }
        }
        $task = new check_emails_reminder();
        $task->execute();
        $task->execute(); // Execute twice so we can test that the email is sent only once.
        $this->runAdhocTasks();
        $this->assertEquals(count($expectedemails), $emailsink->count());
        $emailsto = array_map(function ($email) {
            return $email->to;
        }, $emailsink->get_messages());
        sort($expectedemails);
        sort($emailsto);
        $this->assertEquals($expectedemails, $emailsto);
    }
}
