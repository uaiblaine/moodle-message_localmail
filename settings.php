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
 * Settings for the Local Mail message processor.
 *
 * @package    message_localmail
 * @copyright  2026 Anderson Blaine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * $settings is created by \core\plugininfo\message::load_settings() before this file is
 * included, under the section name it links to from Manage message outputs. Creating a new
 * admin_settingpage here would throw that one away and register a page core never links to.
 */
if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'message_localmail/systemsender',
        new lang_string('systemsender', 'message_localmail'),
        new lang_string('systemsender_desc', 'message_localmail'),
        '',
        PARAM_USERNAME
    ));
}
