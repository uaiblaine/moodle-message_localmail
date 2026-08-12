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
 * Unit tests for the Local Mail message processor install script.
 *
 * @package    message_localmail
 * @copyright  2024 Proyecto UNIMOODLE
 * @copyright  2025 Albert Gasset <albertgasset@fsfe.org>
 * @copyright  2026 Anderson Blaine
 * @author     UNIMOODLE Group (Coordinator) <direccion.area.estrategia.digital@uva.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace message_localmail;

/**
 * Unit tests for the Local Mail message processor install script.
 *
 * @covers ::xmldb_message_localmail_install
 */
final class db_install_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    public function test_db_install(): void {
        global $CFG, $DB;

        $DB->delete_records('message_processors', ['name' => 'localmail']);
        require_once("$CFG->dirroot/message/output/localmail/db/install.php");

        self::assertTrue(xmldb_message_localmail_install());

        // Exactly one row: a second would make the processor appear twice in the
        // messaging settings and be dispatched to twice per notification.
        self::assertSame(1, $DB->count_records('message_processors', ['name' => 'localmail']));
    }
}
