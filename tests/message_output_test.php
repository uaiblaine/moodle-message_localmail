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
 * Unit tests for the Local Mail message processor.
 *
 * @package    message_localmail
 * @copyright  2024 Proyecto UNIMOODLE
 * @copyright  2025 Albert Gasset <albertgasset@fsfe.org>
 * @copyright  2026 Anderson Blaine
 * @author     UNIMOODLE Group (Coordinator) <direccion.area.estrategia.digital@uva.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace message_localmail;

use local_mail\course;
use local_mail\message;
use local_mail\message_search;
use local_mail\user;

/**
 * Unit tests for Local Mail message processor.
 *
 * @covers \message_output_localmail
 */
final class message_output_test extends \advanced_testcase {
    /** @var \message_output $processor The processor under test. */
    private $processor;

    /** @var course $course Course the fixture users are enrolled in. */
    private course $course;

    /** @var user $sender Enrolled sender. */
    private user $sender;

    /** @var user $recipient Enrolled recipient. */
    private user $recipient;

    /**
     * Builds a course with two enrolled users and resolves the processor.
     *
     * Both users are enrolled on purpose: an unenrolled recipient cannot use mail
     * in the course, so a message delivered to them is written to the database but
     * is invisible in their mailbox, and every assertion about delivery would pass
     * while the recipient saw nothing.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $courserecord = $generator->create_course();
        $senderrecord = $generator->create_user();
        $recipientrecord = $generator->create_user();
        $generator->enrol_user($senderrecord->id, $courserecord->id, 'editingteacher');
        $generator->enrol_user($recipientrecord->id, $courserecord->id, 'student');

        $this->course = new course($courserecord);
        $this->sender = new user($senderrecord);
        $this->recipient = new user($recipientrecord);
        $this->processor = get_message_processor('localmail');
    }

    /**
     * Builds the event data exactly as core hands it to a processor.
     *
     * Going through \core\message\message rather than a hand-built stdClass is the
     * point of this helper: properties the caller never sets arrive as null, which
     * is how the unset course id defect reaches the processor in production.
     *
     * @param array $properties Properties to set on the message, keyed by name.
     * @return \stdClass Event data for the localmail processor.
     */
    private function eventdata(array $properties): \stdClass {
        $message = new \core\message\message();
        $message->component = 'moodle';
        $message->name = 'instantmessage';
        $message->userfrom = \core_user::get_user($this->sender->id);
        $message->userto = \core_user::get_user($this->recipient->id);
        $message->notification = 1;
        foreach ($properties as $name => $value) {
            $message->$name = $value;
        }
        return $message->get_eventobject_for_processor('localmail');
    }

    /**
     * Returns the number of Local Mail messages in the fixture course.
     *
     * Counting by course rather than through a recipient-bound search keeps the
     * negative assertions honest: a search bound to one user reports zero for a
     * message that was delivered to somebody else.
     *
     * @return int Number of messages stored in the course.
     */
    private function count_messages(): int {
        global $DB;

        return $DB->count_records('local_mail_messages', ['courseid' => $this->course->id]);
    }

    /**
     * Returns the single message delivered to the recipient in the fixture course.
     *
     * @return message The delivered message.
     */
    private function delivered_message(): message {
        $search = new message_search($this->recipient);
        $search->course = $this->course;
        $message = current($search->get(0, 1));
        self::assertNotFalse($message, 'Expected exactly one message delivered to the recipient');
        return $message;
    }

    public function test_send_message_delivers_html_notification(): void {
        $eventdata = $this->eventdata([
            'courseid' => $this->course->id,
            'subject' => 'Subject',
            'fullmessage' => 'Full message &',
            'fullmessageformat' => FORMAT_PLAIN,
            'fullmessagehtml' => '<p>Full message</p>',
            'smallmessage' => 'Small message &',
            'timecreated' => make_timestamp(2021, 10, 11, 12, 0),
        ]);

        self::assertTrue($this->processor->send_message($eventdata));

        $message = $this->delivered_message();
        self::assertEquals($this->course, $message->course);
        self::assertEquals('Subject', $message->subject);
        self::assertEquals('<p>Full message</p>', $message->content);
        self::assertEquals(FORMAT_HTML, $message->format);
        self::assertEquals(0, $message->attachments);
        self::assertFalse($message->draft);
        self::assertEquals($eventdata->timecreated, $message->time);
        self::assertEquals('moodle', $message->component);
        self::assertEquals(message::CATEGORY_UPDATES, $message->category());
        self::assertEquals([], $message->get_references());
        self::assertEquals($this->sender, $message->sender());
        self::assertEquals([$this->recipient], $message->recipients(message::ROLE_TO));
        self::assertEquals([], $message->recipients(message::ROLE_CC));
        self::assertEquals([], $message->recipients(message::ROLE_BCC));
        self::assertFalse($message->unread($this->sender));
        self::assertTrue($message->unread($this->recipient));
        self::assertFalse($message->starred($this->sender));
        self::assertFalse($message->starred($this->recipient));
        self::assertEquals(message::NOT_DELETED, $message->deleted($this->sender));
        self::assertEquals(message::NOT_DELETED, $message->deleted($this->recipient));
        self::assertEquals([], $message->get_labels($this->sender));
        self::assertEquals([], $message->get_labels($this->recipient));
    }

    public function test_send_message_delivers_a_message_the_recipient_can_read(): void {
        $eventdata = $this->eventdata([
            'courseid' => $this->course->id,
            'subject' => 'Subject',
            'fullmessagehtml' => '<p>Full message</p>',
        ]);

        self::assertTrue($this->processor->send_message($eventdata));

        // Delivering a message the recipient cannot open is indistinguishable from
        // not delivering it, so assert visibility rather than mere existence.
        $message = $this->delivered_message();
        self::assertTrue($this->recipient->can_view_message($message));
        self::assertTrue($this->recipient->can_use_mail($this->course));
    }

    public function test_send_message_falls_back_to_the_localised_subject(): void {
        global $CFG;

        foreach (['ca', 'en', 'es', 'eu', 'gl', 'pt_br'] as $lang) {
            // Simulate language pack is installed.
            $langfolder = "$CFG->dataroot/lang/$lang";
            check_dir_exists($langfolder);
            file_put_contents("$langfolder/langconfig.php", '<?php');
            $stringmanager = get_string_manager();
            $stringmanager->reset_caches(true);

            $userto = \core_user::get_user($this->recipient->id);
            $userto->lang = $lang;
            $eventdata = $this->eventdata([
                'courseid' => $this->course->id,
                'userto' => $userto,
                'subject' => '',
            ]);

            message::delete_course_data($this->course->get_context());
            self::assertTrue($this->processor->send_message($eventdata));

            $expected = $stringmanager->get_string('notification', 'message_localmail', null, $lang);
            self::assertEquals($expected, $this->delivered_message()->subject, "Subject for language $lang");
        }
    }

    /**
     * Every input format has to survive conversion and be stored as HTML.
     *
     * The unset case is the one that matters: the processor's own default is
     * FORMAT_MOODLE, and no test used to reach it because every fixture set the
     * format explicitly.
     *
     * Note the FORMAT_* constants are strings, not integers, so this parameter is
     * deliberately untyped.
     *
     * @dataProvider fullmessageformat_provider
     * @param ?string $format The value of $eventdata->fullmessageformat, or null to leave it unset.
     * @return void
     */
    public function test_send_message_renders_every_message_format($format): void {
        $properties = [
            'courseid' => $this->course->id,
            'subject' => 'Subject',
            'fullmessage' => 'Full message &',
        ];
        if ($format !== null) {
            $properties['fullmessageformat'] = $format;
        }

        self::assertTrue($this->processor->send_message($this->eventdata($properties)));

        $message = $this->delivered_message();
        // Local Mail stores every message as HTML whatever the notification carried.
        self::assertEquals(FORMAT_HTML, $message->format);
        self::assertStringContainsString('Full message', $message->content);
        // The ampersand must never reach storage as a bare, unescaped character.
        self::assertDoesNotMatchRegularExpression('/&(?!amp;|#)/', $message->content);
    }

    /**
     * Data provider for test_send_message_renders_every_message_format.
     *
     * @return array Format values, including the unset case.
     */
    public static function fullmessageformat_provider(): array {
        return [
            'plain' => [FORMAT_PLAIN],
            'html' => [FORMAT_HTML],
            'markdown' => [FORMAT_MARKDOWN],
            'moodle' => [FORMAT_MOODLE],
            'unset, processor default applies' => [null],
        ];
    }

    public function test_send_message_falls_back_to_the_small_message(): void {
        $eventdata = $this->eventdata([
            'courseid' => $this->course->id,
            'subject' => 'Subject',
            'fullmessage' => '',
            'fullmessageformat' => FORMAT_PLAIN,
            'smallmessage' => 'Small message &',
        ]);

        self::assertTrue($this->processor->send_message($eventdata));
        self::assertEquals('Small message &amp;', $this->delivered_message()->content);
    }

    public function test_send_message_copies_the_attachment_and_cleans_the_draft_area(): void {
        global $USER;

        $fs = get_file_storage();
        $filerecord = [
            'contextid' => \context_user::instance($this->sender->id)->id,
            'component' => 'user',
            'filearea' => 'private',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'file.txt',
        ];
        $eventdata = $this->eventdata([
            'courseid' => $this->course->id,
            'timecreated' => make_timestamp(2021, 10, 11, 12, 0),
            'attachment' => $fs->create_file_from_string($filerecord, 'file content'),
            'attachname' => 'attachment.txt',
        ]);

        self::assertTrue($this->processor->send_message($eventdata));

        $message = $this->delivered_message();
        self::assertEquals(1, $message->attachments);
        $files = $fs->get_area_files($this->course->get_context()->id, 'local_mail', 'message', $message->id, 'id', false);
        self::assertCount(1, $files);
        $file = current($files);
        self::assertEquals('attachment.txt', $file->get_filename());
        self::assertEquals('file content', $file->get_content());
        $draft = $fs->get_area_files(\context_user::instance($USER->id)->id, 'user', 'draft', false, 'id', false);
        self::assertEquals([], $draft);
    }

    /**
     * An unset course id reaches a processor as null, not as zero.
     *
     * \core\message\message declares $courseid with no default and core never fills
     * it, so this is the shape core hands over for the async backup notice, the
     * failed-task callbacks and the activity due-date helpers. Before the guard was
     * normalised this raised a TypeError inside \local_mail\course::get(), which is
     * not a moodle_exception and therefore escaped message_send() entirely.
     *
     * @return void
     */
    public function test_send_message_skips_a_notification_with_no_course(): void {
        // Control: the same event data with a course id is delivered.
        self::assertTrue($this->processor->send_message($this->eventdata(['courseid' => $this->course->id])));
        self::assertEquals(1, $this->count_messages());

        self::assertTrue($this->processor->send_message($this->eventdata([])));

        $this->assertDebuggingCalled();
        self::assertEquals(1, $this->count_messages());
    }

    public function test_send_message_skips_the_site_course(): void {
        self::assertTrue($this->processor->send_message($this->eventdata(['courseid' => $this->course->id])));
        self::assertEquals(1, $this->count_messages());

        self::assertTrue($this->processor->send_message($this->eventdata(['courseid' => SITEID])));

        $this->assertDebuggingCalled();
        self::assertEquals(1, $this->count_messages());
    }

    public function test_send_message_skips_a_deleted_course(): void {
        global $DB;

        $eventdata = $this->eventdata(['courseid' => $this->course->id]);
        self::assertTrue($this->processor->send_message($eventdata));
        self::assertEquals(1, $this->count_messages());

        // A course can be removed between queueing a notification and delivering it.
        $missing = $this->eventdata(['courseid' => $this->course->id + 1000]);
        self::assertFalse($DB->record_exists('course', ['id' => $missing->courseid]));

        self::assertTrue($this->processor->send_message($missing));

        // The skip is diagnosable rather than silent, and asserting it here also stops
        // the unasserted message failing this test wherever DEBUG_DEVELOPER is on.
        $this->assertDebuggingCalled();
        self::assertEquals(1, $this->count_messages());
    }

    public function test_send_message_skips_internal_and_deleted_users(): void {
        global $CFG, $DB;

        $noreply = \core_user::get_user(\core_user::NOREPLY_USER);
        $guest = \core_user::get_user($CFG->siteguest);

        // Set the flag directly: delete_user() anonymises the record, and the point
        // here is a user with a real, positive id that local_mail flags as deleted.
        $deleted = \core_user::get_user($this->getDataGenerator()->create_user()->id);
        $DB->set_field('user', 'deleted', 1, ['id' => $deleted->id]);
        $deleted->deleted = 1;
        self::assertGreaterThan(0, $deleted->id, 'A deleted user keeps its positive id');

        foreach (['noreply sender' => $noreply, 'guest sender' => $guest, 'deleted sender' => $deleted] as $case => $user) {
            self::assertTrue(
                $this->processor->send_message($this->eventdata([
                    'courseid' => $this->course->id,
                    'userfrom' => $user,
                ])),
                $case
            );
            // No substitute sender is configured, so the placeholder is reported and skipped.
            $this->assertDebuggingCalled();
        }
        foreach (['noreply recipient' => $noreply, 'guest recipient' => $guest] as $case => $user) {
            self::assertTrue(
                $this->processor->send_message($this->eventdata([
                    'courseid' => $this->course->id,
                    'userto' => $user,
                ])),
                $case
            );
        }

        self::assertEquals(0, $this->count_messages());

        // Control: the same call with two real users does deliver.
        self::assertTrue($this->processor->send_message($this->eventdata(['courseid' => $this->course->id])));
        self::assertEquals(1, $this->count_messages());
    }

    public function test_send_message_skips_a_self_notification(): void {
        $self = \core_user::get_user($this->recipient->id);

        self::assertTrue($this->processor->send_message($this->eventdata([
            'courseid' => $this->course->id,
            'userfrom' => $self,
        ])));

        self::assertEquals(0, $this->count_messages());

        // Control: a different sender does deliver.
        self::assertTrue($this->processor->send_message($this->eventdata(['courseid' => $this->course->id])));
        self::assertEquals(1, $this->count_messages());
    }

    public function test_send_message_records_the_originating_component(): void {
        self::assertTrue($this->processor->send_message($this->eventdata([
            'courseid' => $this->course->id,
            'component' => 'mod_forum',
        ])));

        $message = $this->delivered_message();

        /*
         * The originating component is stored, not this plugin's own name: the mailbox
         * needs to know which part of Moodle produced the mail, and naming the transport
         * would say nothing the day a second one exists.
         */
        self::assertEquals('mod_forum', $message->component);
        self::assertEquals(message::CATEGORY_UPDATES, $message->category());

        /*
         * Control: a message a person composes in the same course through Local Mail's
         * own API carries no component. Without it, every assertion above would still
         * hold if Local Mail stamped everything it ever created.
         */
        $data = \local_mail\message_data::new($this->course, $this->sender);
        $data->to = [$this->recipient];
        $data->subject = 'Written by a person';
        $data->content = 'Content';
        $data->format = FORMAT_PLAIN;
        $human = \local_mail\message::create($data);

        self::assertNull($human->component);
        self::assertEquals(message::CATEGORY_PRIMARY, $human->category());
    }

    public function test_send_message_ignores_local_mail_notifications(): void {
        self::assertTrue($this->processor->send_message($this->eventdata([
            'courseid' => $this->course->id,
            'component' => 'local_mail',
        ])));

        self::assertEquals(0, $this->count_messages());

        // Control: the same notification from another component does deliver.
        self::assertTrue($this->processor->send_message($this->eventdata(['courseid' => $this->course->id])));
        self::assertEquals(1, $this->count_messages());
    }

    /**
     * Core sends many genuinely course-scoped notifications from a placeholder user.
     *
     * Course completion, quiz submission confirmations and analytics insights all set a real
     * course id and a noreply or support sender, so without a substitute they are dropped —
     * which is the single largest category of course mail this processor loses.
     *
     * @return void
     */
    public function test_send_message_substitutes_a_placeholder_sender(): void {
        $service = $this->getDataGenerator()->create_user(['username' => 'localmailbot']);
        $eventdata = $this->eventdata([
            'courseid' => $this->course->id,
            'userfrom' => \core_user::get_user(\core_user::NOREPLY_USER),
            'subject' => 'Course completed',
        ]);

        // Control: with nothing configured the notification is skipped, as before the setting.
        self::assertTrue($this->processor->send_message($eventdata));
        $this->assertDebuggingCalled();
        self::assertEquals(0, $this->count_messages());

        set_config('systemsender', 'localmailbot', 'message_localmail');

        self::assertTrue($this->processor->send_message($eventdata));

        self::assertEquals(1, $this->count_messages());
        $message = $this->delivered_message();
        self::assertEquals('Course completed', $message->subject);
        self::assertEquals((int) $service->id, $message->sender()->id);
        // The substitute needs no enrolment, so it cannot read its own Sent copy.
        self::assertFalse((new user($service))->can_use_mail($this->course));
    }

    public function test_send_message_ignores_an_unusable_system_sender(): void {
        $eventdata = $this->eventdata([
            'courseid' => $this->course->id,
            'userfrom' => \core_user::get_user(\core_user::NOREPLY_USER),
        ]);

        foreach (['nosuchaccount', ''] as $username) {
            set_config('systemsender', $username, 'message_localmail');
            self::assertTrue($this->processor->send_message($eventdata), "username '$username'");
            $this->assertDebuggingCalled();
        }

        self::assertEquals(0, $this->count_messages());

        // Control: a real account does deliver, so the assertions above are not vacuous.
        $this->getDataGenerator()->create_user(['username' => 'localmailbot']);
        set_config('systemsender', 'localmailbot', 'message_localmail');
        self::assertTrue($this->processor->send_message($eventdata));
        self::assertEquals(1, $this->count_messages());
    }

    /**
     * A recipient who cannot use mail in the course has no mailbox the message could reach.
     *
     * message_search scopes every unscoped listing to course::get_by_user(), so a message
     * stored against a course the recipient is not actively enrolled in is written and shown
     * to nobody. Both halves of that predicate are exercised: no enrolment at all, and an
     * enrolment that exists but is suspended.
     *
     * @return void
     */
    public function test_send_message_skips_a_recipient_who_cannot_use_mail(): void {
        $unenrolled = $this->getDataGenerator()->create_user();
        $suspended = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user(
            $suspended->id,
            $this->course->id,
            'student',
            'manual',
            0,
            0,
            ENROL_USER_SUSPENDED
        );

        foreach (['unenrolled' => $unenrolled, 'suspended enrolment' => $suspended] as $case => $user) {
            self::assertFalse((new user($user))->can_use_mail($this->course), $case);
            self::assertTrue(
                $this->processor->send_message($this->eventdata([
                    'courseid' => $this->course->id,
                    'userto' => \core_user::get_user($user->id),
                ])),
                $case
            );
            $this->assertDebuggingCalled();
        }

        self::assertEquals(0, $this->count_messages());

        // Control: the actively enrolled recipient from the fixture still receives.
        self::assertTrue($this->processor->send_message($this->eventdata(['courseid' => $this->course->id])));
        self::assertEquals(1, $this->count_messages());
    }

    /**
     * Delivery needs a real $USER because the message is assembled in a draft area.
     *
     * The transaction assertion is the load-bearing one: a failure that left the
     * delegated transaction open would poison every later write in the process.
     *
     * @return void
     */
    public function test_send_message_skips_an_unauthenticated_session(): void {
        global $CFG, $DB;

        /*
         * advanced_testcase normally runs each test inside a transaction it rolls back
         * afterwards, which would make is_transaction_started() true regardless of what
         * the processor did and the assertion below vacuous. Opting out of that reset
         * strategy is what makes it measure the plugin.
         */
        $this->preventResetByRollback();
        self::assertFalse($DB->is_transaction_started(), 'The test itself must not run inside a transaction');

        $eventdata = $this->eventdata(['courseid' => $this->course->id]);

        // Control: delivery works while an authenticated user is set.
        self::assertTrue($this->processor->send_message($eventdata));
        self::assertEquals(1, $this->count_messages());

        foreach ([null, \core_user::get_user($CFG->siteguest)] as $user) {
            $this->setUser($user);
            self::assertTrue($this->processor->send_message($eventdata));
            $this->assertDebuggingCalled();
            self::assertEquals(1, $this->count_messages());
            self::assertFalse($DB->is_transaction_started(), 'A skipped notification must not leave a transaction open');
        }
    }
}
