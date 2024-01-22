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
// Project implemented by the "Recovery, Transformation and Resilience Plan.
// Funded by the European Union - Next GenerationEU".
//
// Produced by the UNIMOODLE University Group: Universities of
// Valladolid, Complutense de Madrid, UPV/EHU, León, Salamanca,
// Illes Balears, Valencia, Rey Juan Carlos, La Laguna, Zaragoza, Málaga,
// Córdoba, Extremadura, Vigo, Las Palmas de Gran Canaria y Burgos.

/**
 * Version details
 *
 * @package    message_localmail
 * @copyright  2024 Proyecto UNIMOODLE
 * @author     UNIMOODLE Group (Coordinator) <direccion.area.estrategia.digital@uva.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace message_localmail;

use local_mail\course;
use local_mail\message;
use local_mail\message_search;
use local_mail\user;

/**
 * @covers \message_output_localmail
 */
class message_output_test extends \advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    public function test_send_message() {
        global $CFG, $USER;

        $this->setAdminUser();
        $fs = get_file_storage();
        $generator = $this->getDataGenerator();
        $course = new course($generator->create_course());
        $user1 = new user($generator->create_user());
        $user2 = new user($generator->create_user());

        $processor = get_message_processor('localmail');
        $search = new message_search($user2);
        $search->course = $course;

        // Notification with full message HTML.

        $eventdata = new \stdClass();
        $eventdata->component = 'moodle';
        $eventdata->courseid = $course->id;
        $eventdata->userfrom = \core_user::get_user($user1->id);
        $eventdata->userto = \core_user::get_user($user2->id);
        $eventdata->subject = 'Subject';
        $eventdata->fullmessage = "Full message &";
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = "<p>Full message</p>";
        $eventdata->smallmessage = 'Small message &';
        $eventdata->timecreated = make_timestamp(2021, 10, 11, 12, 0);

        message::delete_course($course->get_context());
        self::assertTrue($processor->send_message($eventdata));

        $message = current($search->get(0, 1));
        self::assertNotFalse($message);
        self::assertEquals($course, $message->get_course());
        self::assertEquals('Subject', $message->subject);
        self::assertEquals("<p>Full message</p>", $message->content);
        self::assertEquals(FORMAT_HTML, $message->format);
        self::assertEquals(0, $message->attachments);
        self::assertFalse($message->draft);
        self::assertEquals($eventdata->timecreated, $message->time);
        self::assertEquals([], $message->get_references());
        self::assertEquals($user1, $message->get_sender());
        self::assertEquals([$user2], $message->get_recipients(message::ROLE_TO));
        self::assertEquals([], $message->get_recipients(message::ROLE_CC));
        self::assertEquals([], $message->get_recipients(message::ROLE_BCC));
        self::assertFalse($message->unread($user1));
        self::assertTrue($message->unread($user2));
        self::assertFalse($message->starred($user1));
        self::assertFalse($message->starred($user2));
        self::assertEquals($message->deleted($user1), message::NOT_DELETED);
        self::assertEquals($message->deleted($user2), message::NOT_DELETED);
        self::assertEquals([], $message->get_labels($user1));
        self::assertEquals([], $message->get_labels($user2));

        // Notification with no subject.

        foreach (['ca', 'en', 'es', 'eu', 'gl'] as $lang) {
            // Simulate language pack is installed.
            $langfolder = "$CFG->dataroot/lang/$lang";
            check_dir_exists($langfolder);
            file_put_contents("$langfolder/langconfig.php", '<?php');
            $stringmanager = get_string_manager();
            $stringmanager->reset_caches(true);

            $eventdata = new \stdClass();
            $eventdata->component = 'moodle';
            $eventdata->courseid = $course->id;
            $eventdata->userfrom = \core_user::get_user($user1->id);
            $eventdata->userto = \core_user::get_user($user2->id);
            $eventdata->userto->lang = $lang;
            $eventdata->subject = '';

            message::delete_course($course->get_context());
            self::assertTrue($processor->send_message($eventdata));

            $message = current($search->get(0, 1));
            self::assertNotFalse($message);
            $expected = $stringmanager->get_string('notification', 'message_localmail', null, $lang);
            self::assertEquals($expected, $message->subject);
        }

        // Notification with no full message HTML.

        $eventdata = new \stdClass();
        $eventdata->component = 'moodle';
        $eventdata->courseid = $course->id;
        $eventdata->userfrom = \core_user::get_user($user1->id);
        $eventdata->userto = \core_user::get_user($user2->id);
        $eventdata->subject = 'Subject';
        $eventdata->fullmessage = 'Full message &';
        $eventdata->fullmessageformat = FORMAT_PLAIN;

        message::delete_course($course->get_context());
        self::assertTrue($processor->send_message($eventdata));

        $message = current($search->get(0, 1));
        self::assertNotFalse($message);
        self::assertEquals("Full message &amp;", $message->content);
        self::assertEquals(FORMAT_HTML, $message->format);

        // Notification with no full message.

        $eventdata = new \stdClass();
        $eventdata->component = 'moodle';
        $eventdata->courseid = $course->id;
        $eventdata->userfrom = \core_user::get_user($user1->id);
        $eventdata->userto = \core_user::get_user($user2->id);
        $eventdata->subject = 'Subject';
        $eventdata->fullmessage = '';
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->smallmessage = 'Small message &';

        message::delete_course($course->get_context());
        self::assertTrue($processor->send_message($eventdata));

        $message = current($search->get(0, 1));
        self::assertNotFalse($message);
        self::assertEquals("Small message &amp;", $message->content);
        self::assertEquals(FORMAT_HTML, $message->format);

        // Notification with attachment.

        $eventdata = new \stdClass();
        $eventdata->component = 'moodle';
        $eventdata->courseid = $course->id;
        $eventdata->userfrom = \core_user::get_user($user1->id);
        $eventdata->userto = \core_user::get_user($user2->id);
        $eventdata->timecreated = make_timestamp(2021, 10, 11, 12, 0);
        $filerecord = [
            'contextid' => \context_user::instance($user1->id)->id,
            'component' => 'user',
            'filearea' => 'private',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'file.txt',
        ];
        $eventdata->attachment = $fs->create_file_from_string($filerecord, 'file content');
        $eventdata->attachname = 'attachment.txt';

        message::delete_course($course->get_context());
        self::assertTrue($processor->send_message($eventdata));

        $message = current($search->get(0, 1));
        self::assertNotFalse($message);
        self::assertEquals(1, $message->attachments);
        $files = $fs->get_area_files($course->get_context()->id, 'local_mail', 'message', $message->id, 'id', false);
        self::assertCount(1, $files);
        $file = current($files);
        self::assertEquals('attachment.txt', $file->get_filename());
        self::assertEquals('file content', $file->get_content());
        self::assertEquals([], $fs->get_area_files(\context_user::instance($USER->id)->id, 'user', 'draft', false, 'id', false));

        // Invalid course.

        $eventdata = new \stdClass();
        $eventdata->component = 'moodle';
        $eventdata->courseid = SITEID;
        $eventdata->userfrom = \core_user::get_user($user1->id);
        $eventdata->userto = \core_user::get_user($user2->id);
        $eventdata->subject = 'Subject';

        self::assertTrue($processor->send_message($eventdata));

        // Invalid sender.

        $eventdata = new \stdClass();
        $eventdata->component = 'moodle';
        $eventdata->courseid = $course->id;
        $eventdata->userfrom = \core_user::get_user(\core_user::NOREPLY_USER);
        $eventdata->userto = \core_user::get_user($user2->id);

        message::delete_course($course->get_context());
        self::assertTrue($processor->send_message($eventdata));

        self::assertEquals(0, $search->count());

        // Invalid recipient.

        $eventdata = new \stdClass();
        $eventdata->component = 'moodle';
        $eventdata->courseid = $course->id;
        $eventdata->userfrom = \core_user::get_user($user1->id);
        $eventdata->userto = \core_user::get_user(\core_user::NOREPLY_USER);

        message::delete_course($course->get_context());
        self::assertTrue($processor->send_message($eventdata));

        self::assertEquals(0, $search->count());

        // Same sender and recipient.

        $eventdata = new \stdClass();
        $eventdata->component = 'moodle';
        $eventdata->courseid = $course->id;
        $eventdata->userfrom = \core_user::get_user($user2->id);
        $eventdata->userto = \core_user::get_user($user2->id);

        message::delete_course($course->get_context());
        self::assertTrue($processor->send_message($eventdata));

        self::assertEquals(0, $search->count());

        // Component is local_mail.

        $eventdata = new \stdClass();
        $eventdata->component = 'local_mail';
        $eventdata->courseid = $course->id;
        $eventdata->userfrom = \core_user::get_user($user1->id);
        $eventdata->userto = \core_user::get_user($user2->id);

        message::delete_course($course->get_context());
        self::assertTrue($processor->send_message($eventdata));

        self::assertEquals(0, $search->count());
    }

    public function test_load_data() {
        $processor = get_message_processor('localmail');
        $preferences = new \stdClass();
        self::assertNull($processor->load_data($preferences, 0));
        self::assertEquals(new \stdClass(), $preferences);
    }

    public function test_config_form() {
        $processor = get_message_processor('localmail');
        self::assertNull($processor->config_form([]));
    }

    public function test_process_form() {
        $processor = get_message_processor('localmail');
        $form = new \stdClass();
        $preferences = new \stdClass();
        self::assertNull($processor->process_form($form, $preferences));
        self::assertEquals(new \stdClass(), $preferences);
    }

    public function test_get_default_messaging_settings() {
        $processor = get_message_processor('localmail');
        self::assertEquals(MESSAGE_DISALLOWED, $processor->get_default_messaging_settings());
    }

    public function test_has_message_preferences() {
        $processor = get_message_processor('localmail');
        self::assertFalse($processor->has_message_preferences());
    }
}
