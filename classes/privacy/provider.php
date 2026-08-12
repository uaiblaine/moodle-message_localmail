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
 * Privacy provider for the Local Mail message processor.
 *
 * @package    message_localmail
 * @copyright  2026 Anderson Blaine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace message_localmail\privacy;

/**
 * Privacy provider for the Local Mail message processor.
 *
 * The processor stores no personal data of its own: it ships no database tables,
 * exposes no user preferences (has_message_preferences() is false and load_data()
 * is a no-op) and keeps no plugin configuration. Everything it produces is written
 * through the Local Mail API into local_mail's own tables and file areas, which
 * local_mail\privacy\provider already declares, exports and deletes.
 */
class provider implements \core_privacy\local\metadata\null_provider {
    /**
     * Returns the reason why this plugin stores no personal data of its own.
     *
     * @return string The identifier of a string in lang/en/message_localmail.php.
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
