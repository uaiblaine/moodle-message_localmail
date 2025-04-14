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
 * @copyright  2025 Albert Gasset <albertgasset@fsfe.org>
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
     * {@inheritDoc}
     */
    public function send_message($eventdata) {
        global $CFG, $DB, $USER;

        if ($eventdata->component == 'local_mail') {
            // Ignore notifications from Local Mail to avoid infinite loops and duplicate messages.
            return true;
        }

        $fs = get_file_storage();
        $stringmanager = get_string_manager();

        $course = \local_mail\course::get($eventdata->courseid, IGNORE_MISSING);
        $sender = \local_mail\user::get($eventdata->userfrom->id);
        $recipient = \local_mail\user::get($eventdata->userto->id);
        $lang = !empty($eventdata->userto->lang) ? $eventdata->userto->lang : $CFG->lang;
        $subject = trim($eventdata->subject ?? '');
        $fullmessage = trim($eventdata->fullmessage ?? '');
        $fullmessageformat = $eventdata->fullmessageformat ?? FORMAT_MOODLE;
        $fullmessagehtml = trim($eventdata->fullmessagehtml ?? '');
        $smallmessage = trim($eventdata->smallmessage ?? '');
        $attachment = $eventdata->attachment ?? null;
        $attachname = clean_filename($eventdata->attachname ?? '');
        $timecreated = !empty($eventdata->timecreated) ? $eventdata->timecreated : time();
        $usercontext = \context_user::instance($USER->id);

        if (!$course) {
            // Ignore notifications in the site course.
            return true;
        }

        if ($sender->id <= 0 || $recipient->id <= 0 || $sender->id == $recipient->id) {
            // Ignore notifications with fake users or with same sender and recipient.
            return true;
        }

        $transaction = $DB->start_delegated_transaction();

        // Create message data.
        $data = \local_mail\message_data::new($course, $sender);
        $data->to = [$recipient];
        if ($subject !== '') {
            $data->subject = $subject;
        } else {
            $data->subject = $stringmanager->get_string('notification', 'message_localmail', null, $lang);
        }
        $options = ['filter' => false, 'para' => false];
        if ($fullmessagehtml !== '') {
            $data->content = $fullmessagehtml;
        } else if ($fullmessage !== '') {
            $data->content = format_text($fullmessage, $fullmessageformat, $options);
        } else {
            $data->content = format_text($smallmessage, FORMAT_PLAIN, $options);
        }

        // Copy attachment to draft area.
        if ($attachname !== '' && $attachment instanceof stored_file) {
            $filerecord = [
                'contextid' => $usercontext->id,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $data->draftitemid,
                'filepath' => '/',
                'filename' => $attachname,
            ];
            $fs->create_file_from_storedfile($filerecord, $attachment);
        }

        // Create and send message.
        $message = \local_mail\message::create($data);
        $message->send($timecreated);

        // Delete draft area to avoid cluttering the database.
        if ($attachname !== '' && $attachment instanceof stored_file) {
            $fs->delete_area_files($usercontext->id, 'user', 'draft', $data->draftitemid);
        }

        $transaction->allow_commit();

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function load_data(&$preferences, $userid) {
        // No preferences.
    }

    /**
     * {@inheritDoc}
     */
    public function config_form($preferences) {
        // No preferences.
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function process_form($form, &$preferences) {
        // No preferences.
    }
    /**
     * {@inheritDoc}
     */
    public function get_default_messaging_settings() {
        return MESSAGE_DISALLOWED;
    }

    /**
     * {@inheritDoc}
     */
    public function has_message_preferences() {
        return false;
    }
}
