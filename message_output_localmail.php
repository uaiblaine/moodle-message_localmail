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
 * Local Mail message processor.
 *
 * @package    message_localmail
 * @copyright  2024 Proyecto UNIMOODLE
 * @copyright  2025 Albert Gasset <albertgasset@fsfe.org>
 * @copyright  2026 Anderson Blaine
 * @author     UNIMOODLE Group (Coordinator) <direccion.area.estrategia.digital@uva.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/message/output/lib.php');

/**
 * Local Mail message processor
 */
class message_output_localmail extends message_output {
    /**
     * Processes a notification and delivers it to the recipient's Local Mail mailbox.
     *
     * Notifications that Local Mail cannot represent are skipped rather than reported
     * as failures, because returning false makes core log a delivery error for a
     * condition that is expected and permanent.
     *
     * @see message_send()
     * @param \stdClass $eventdata Event data submitted by the message provider to
     *                             message_send(), plus $eventdata->savedmessageid.
     * @return bool True when the notification was delivered or deliberately skipped,
     *              false when delivery was attempted and failed.
     */
    public function send_message($eventdata) {
        global $CFG, $DB, $USER;

        if ($eventdata->component == 'local_mail') {
            // Ignore notifications from Local Mail to avoid infinite loops and duplicate messages.
            return true;
        }

        /*
         * Every guard below runs before any lookup, because the lookups throw. In
         * particular \core\message\message declares $courseid with no default and core
         * never fills it, so an unset course id arrives here as null rather than 0:
         * `null == SITEID` is false, and \local_mail\course::get() takes a non-nullable
         * int, so an unnormalised null reaches it as a TypeError. TypeError is not a
         * moodle_exception, so message_send() does not catch it and the sending task
         * dies. Core senders that set no course id include the async backup notice and
         * the activity due-date helpers.
         */
        $courseid = (int) ($eventdata->courseid ?? 0);
        if ($courseid <= 0 || $courseid == SITEID) {
            // Ignore notifications with no course and notifications in the site course.
            return true;
        }

        $senderid = (int) ($eventdata->userfrom->id ?? 0);
        $recipientid = (int) ($eventdata->userto->id ?? 0);
        if ($senderid <= 0 || $recipientid <= 0 || $senderid == $recipientid) {
            // Ignore notifications from internal users such as noreply, and self-notifications.
            return true;
        }

        /*
         * \local_mail\message_data::new() calls file_get_unused_draft_itemid(), which
         * throws 'noguest' for a guest or unauthenticated session. Delivery genuinely
         * cannot proceed without a real $USER, so skip rather than fail: the draft area
         * a message is assembled in belongs to whoever is running the request.
         */
        if (!isloggedin() || isguestuser()) {
            debugging('Local Mail skipped a notification: no authenticated user to build the draft in', DEBUG_DEVELOPER);
            return true;
        }

        try {
            $course = \local_mail\course::get($courseid);
            $sender = \local_mail\user::get($senderid);
            $recipient = \local_mail\user::get($recipientid);
        } catch (\local_mail\exception $e) {
            // The course was deleted between queueing and delivery. Nothing to deliver to.
            debugging('Local Mail skipped a notification: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return true;
        }

        /*
         * local_mail returns deleted and guest users with their original, positive id and
         * a deleted flag set, so testing the id alone cannot see them.
         */
        if ($sender->deleted || $recipient->deleted) {
            // Ignore notifications involving deleted or guest users.
            return true;
        }

        $fs = get_file_storage();
        $stringmanager = get_string_manager();
        $lang = !empty($eventdata->userto->lang) ? $eventdata->userto->lang : $CFG->lang;
        $subject = trim($eventdata->subject ?? '');
        $fullmessage = trim($eventdata->fullmessage ?? '');
        $fullmessageformat = $eventdata->fullmessageformat ?? FORMAT_MOODLE;
        $fullmessagehtml = trim($eventdata->fullmessagehtml ?? '');
        $smallmessage = trim($eventdata->smallmessage ?? '');
        $attachment = $eventdata->attachment ?? null;
        $attachname = clean_filename($eventdata->attachname ?? '');
        $timecreated = !empty($eventdata->timecreated) ? $eventdata->timecreated : time();
        $hasattachment = $attachname !== '' && $attachment instanceof stored_file;
        $usercontext = $hasattachment ? \context_user::instance($USER->id) : null;
        $draftitemid = null;

        $transaction = $DB->start_delegated_transaction();

        try {
            // Create message data.
            $data = \local_mail\message_data::new($course, $sender);
            $draftitemid = $data->draftitemid;
            $data->to = [$recipient];
            if ($subject !== '') {
                $data->subject = $subject;
            } else {
                $data->subject = $stringmanager->get_string('notification', 'message_localmail', null, $lang);
            }
            $options = ['filter' => false, 'para' => false, 'context' => $course->get_context()];
            if ($fullmessagehtml !== '') {
                $data->content = $fullmessagehtml;
            } else if ($fullmessage !== '') {
                $data->content = format_text($fullmessage, $fullmessageformat, $options);
            } else {
                $data->content = format_text($smallmessage, FORMAT_PLAIN, $options);
            }

            // Copy attachment to draft area.
            if ($hasattachment) {
                $filerecord = [
                    'contextid' => $usercontext->id,
                    'component' => 'user',
                    'filearea' => 'draft',
                    'itemid' => $draftitemid,
                    'filepath' => '/',
                    'filename' => $attachname,
                ];
                $fs->create_file_from_storedfile($filerecord, $attachment);
            }

            // Create and send message.
            $message = \local_mail\message::create($data);
            $message->send($timecreated);

            if ($hasattachment && $message->attachments < 1) {
                // Local Mail dropped the file, most likely over its configured maxbytes.
                debugging('Local Mail delivered a notification without its attachment: ' . $attachname, DEBUG_NORMAL);
            }

            $transaction->allow_commit();
        } catch (\Throwable $e) {
            /*
             * Without this the transaction stays on $DB->transactions for the rest of the
             * process: message_send() catches moodle_exception and returns false, so no
             * exception handler ever calls abort_all_db_transactions(), every later
             * notification and event is buffered instead of delivered, and the shutdown
             * rollback discards every write made after the failure.
             */
            try {
                $transaction->rollback($e);
            } catch (\Throwable $rethrown) {
                // The DML rollback closes the transaction and then always rethrows, by design.
                debugging('Local Mail could not deliver a notification: ' . $rethrown->getMessage(), DEBUG_NORMAL);
            }
            return false;
        } finally {
            // Delete draft area to avoid cluttering the database, delivered or not.
            if ($hasattachment && $draftitemid !== null) {
                $fs->delete_area_files($usercontext->id, 'user', 'draft', $draftitemid);
            }
        }

        return true;
    }

    /**
     * Loads config data from the database for the messaging preferences page.
     *
     * This processor exposes no user preferences, so nothing is loaded.
     *
     * @param array $preferences Array of user preferences.
     * @param int $userid The user ID.
     * @return void
     */
    public function load_data(&$preferences, $userid) {
        // No preferences.
    }

    /**
     * Creates the processor's fields on the messaging preferences page.
     *
     * This processor exposes no user preferences, so it contributes no fields.
     *
     * @param array $preferences An array of user preferences.
     * @return ?string Always null.
     */
    public function config_form($preferences) {
        // No preferences.
        return null;
    }

    /**
     * Parses the submitted preferences form and saves the processor's own data.
     *
     * This processor exposes no user preferences, so nothing is saved.
     *
     * @param \stdClass $form Preferences form class.
     * @param array $preferences Preferences array.
     * @return void
     */
    public function process_form($form, &$preferences) {
        // No preferences.
    }

    /**
     * Returns the processor's default messaging settings.
     *
     * Delivery is disallowed by default: an administrator has to permit Local Mail
     * per notification provider at the site messaging defaults.
     *
     * @return int The default message output settings expressed as a bit mask.
     */
    public function get_default_messaging_settings() {
        return MESSAGE_DISALLOWED;
    }

    /**
     * Returns whether this processor has configurable message preferences.
     *
     * @return bool Always false.
     */
    public function has_message_preferences() {
        return false;
    }
}
