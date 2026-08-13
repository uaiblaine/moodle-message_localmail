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
 * Language strings for the Local Mail message processor.
 *
 * @package    message_localmail
 * @copyright  2024 Proyecto UNIMOODLE
 * @copyright  2026 Anderson Blaine
 * @author     UNIMOODLE Group (Coordinator) <direccion.area.estrategia.digital@uva.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['notification'] = 'Notification';
$string['pluginname'] = 'Local Mail';
$string['privacy:metadata'] = 'The Local Mail message processor stores no personal data of its own. It delivers notifications into the Local Mail plugin, which stores, exports and deletes them under its own privacy provider. As with any message sent through Local Mail, a copy of each delivered notification also remains visible to the user it was sent from.';
$string['systemsender'] = 'System sender account';
$string['systemsender_desc'] = 'Username of the account to show as the sender when a course notification comes from a placeholder user such as noreply or support. Course completion, quiz submission confirmations and analytics insights are all sent this way, and are skipped entirely while this is empty. The account needs no enrolment and no capability; a copy of each notification is kept in its Sent folder, so a dedicated account is recommended over a real person.';
