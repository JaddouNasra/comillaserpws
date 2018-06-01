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
 * COMPONENT External functions unit tests
 *
 * @package    local_comillaserpws
 * @copyright  2018 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/local/comillaserpws/externallib.php');

/**
 * Events tests class.
 *
 * @copyright  2018 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_comillaserpws_external_testcase extends externallib_advanced_testcase {

    /**
     * Tests for phpunit.
     */
    public function test_comillaserpws_get_enrolled_users() {
        global $DB;
        $this->resetAfterTest(true);

        // Creating a few users.
        $user1 = $this->getDataGenerator()->create_user(array('username' => 'user1', 'email' => 'user1@test.com'));
        $user2 = $this->getDataGenerator()->create_user(array('username' => 'user2', 'email' => 'user2@test.com'));
        $user3 = $this->getDataGenerator()->create_user(array('username' => 'user3', 'email' => 'user3@test.com'));

        // Creating three courses to test.
        $course1 = $this->getDataGenerator()->create_course(array('shortname' => 'Course 1'));
        $course2 = $this->getDataGenerator()->create_course(array('shortname' => 'Course 2'));
        $course3 = $this->getDataGenerator()->create_course(array('shortname' => 'Course 3'));

        // Getting the id of the roles.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));

        // Course 1 enrols: user1, user2 and user3 as students.
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id, $studentrole->id, 'manual');

        // Course 2 enrols: user1 as teacher, user2 and user3 as students.
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id, $teacherrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user2->id, $course2->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user3->id, $course2->id, $studentrole->id, 'manual');

        // Set the user as admin to use the web service.
        $this->setAdminUser();

        // Call the external function with the course1.
        $enrolleduserscourse1 = local_comillaserpws_external::comillaserpws_get_enrolled_users ($course1->id);
        // Clean the returning values to simulate the web service server.
        $enrolleduserscourse1 = external_api::clean_returnvalue(
            local_comillaserpws_external::comillaserpws_get_enrolled_users_returns(), $enrolleduserscourse1);
        // Validate the return data with different asserts comparing the expected results with the response values.
        $this->assertEquals(3, count($enrolleduserscourse1));
        $this->assertArrayHasKey('id', $enrolleduserscourse1[0]);
        $this->assertArrayHasKey('username', $enrolleduserscourse1[0]);
        $this->assertArrayHasKey('email', $enrolleduserscourse1[1]);
        $this->assertArrayHasKey('roles', $enrolleduserscourse1[1]);
        $this->assertArrayHasKey('enrolments', $enrolleduserscourse1[2]);
        $this->assertEquals('user1', $enrolleduserscourse1[0]['username']);
        $this->assertEquals('user2@test.com', $enrolleduserscourse1[1]['email']);
        $this->assertEquals('manual', $enrolleduserscourse1[2]['enrolments'][0]['enroltype']);
        $this->assertEquals('student', $enrolleduserscourse1[0]['roles'][0]['shortname']);

        // Call the external function with the course1.
        $enrolleduserscourse2 = local_comillaserpws_external::comillaserpws_get_enrolled_users ($course2->id);
        // Clean the returning values to simulate the web service server.
        $enrolleduserscourse2 = external_api::clean_returnvalue(
            local_comillaserpws_external::comillaserpws_get_enrolled_users_returns(), $enrolleduserscourse2);
        // Validate the return data with different asserts comparing the expected results with the response values.
        $this->assertEquals(3, count($enrolleduserscourse2));
        $this->assertArrayHasKey('id', $enrolleduserscourse2[1]);
        $this->assertArrayHasKey('username', $enrolleduserscourse2[1]);
        $this->assertArrayHasKey('email', $enrolleduserscourse2[2]);
        $this->assertArrayHasKey('roles', $enrolleduserscourse2[2]);
        $this->assertArrayHasKey('enrolments', $enrolleduserscourse2[0]);
        $this->assertEquals('user2', $enrolleduserscourse2[1]['username']);
        $this->assertEquals('user3@test.com', $enrolleduserscourse2[2]['email']);
        $this->assertEquals('manual', $enrolleduserscourse2[0]['enrolments'][0]['enroltype']);
        $this->assertEquals('teacher', $enrolleduserscourse2[0]['roles'][0]['shortname']);

        // Call the external function with the course1.
        $enrolleduserscourse3 = local_comillaserpws_external::comillaserpws_get_enrolled_users ($course3->id);
        // Clean the returning values to simulate the web service server.
        $enrolleduserscourse3 = external_api::clean_returnvalue(
            local_comillaserpws_external::comillaserpws_get_enrolled_users_returns(), $enrolleduserscourse3);
        // Validate the return data with different asserts comparing the expected results with the response values.
        $this->assertEquals(0, count($enrolleduserscourse3));
    }

    /**
     * Tests for phpunit.
     */
    public function test_comillaserpws_get_turnitintool_user() {
        global $DB;
        $this->resetAfterTest(true);

        // Creating a few users.
        $user1 = $this->getDataGenerator()->create_user(array('username' => 'user1', 'email' => 'user1@test.com'));
        $user2 = $this->getDataGenerator()->create_user(array('username' => 'user2', 'email' => 'user2@test.com'));

        // Generate entries in turnitintwo table for user1 and user2.
        $entry1 = new stdClass();
        $entry1->userid       = $user1->id;
        $entry1->turnitin_uid = 1234;
        $entry1->turnitin_utp = 1234;
        $entry2 = new stdClass();
        $entry2->userid       = $user2->id;
        $entry2->turnitin_uid = 1235;
        $entry2->turnitin_utp = 1235;

        $entry1id = $DB->insert_record('turnitintooltwo_users', $entry1);
        $entry2id = $DB->insert_record('turnitintooltwo_users', $entry2);

        // Check if the entries exist.
        $this->assertNotNull($entry1id);
        $this->assertNotNull($entry2id);
        // Set the user as admin to use the web service.
        $this->setAdminUser();

        // Call the external function with the user1.
        $turnitinuser1 = local_comillaserpws_external::comillaserpws_get_turnitintool_user ($user1->username);
        // Clean the returning values to simulate the web service server.
        $turnitinuser1 = external_api::clean_returnvalue(
            local_comillaserpws_external::comillaserpws_get_turnitintool_user_returns(), $turnitinuser1);
        // Validate the return data.
        $this->assertArrayHasKey('username', $turnitinuser1);
        $this->assertArrayHasKey('email', $turnitinuser1);
        $this->assertArrayHasKey('idnumber', $turnitinuser1);
        $this->assertArrayHasKey('turnitin_uid', $turnitinuser1);
        $this->assertEquals('user1', $turnitinuser1['username']);
        $this->assertEquals('user1@test.com', $turnitinuser1['email']);
        $this->assertEquals('1234', $turnitinuser1['turnitin_uid']);

        // Call the external function with the user2.
        $turnitinuser2 = local_comillaserpws_external::comillaserpws_get_turnitintool_user ($user2->username);
        // Clean the returning values to simulate the web service server.
        $turnitinuser2 = external_api::clean_returnvalue(
            local_comillaserpws_external::comillaserpws_get_turnitintool_user_returns(), $turnitinuser2);
        // Validate the return data.
        $this->assertArrayHasKey('username', $turnitinuser2);
        $this->assertArrayHasKey('email', $turnitinuser2);
        $this->assertArrayHasKey('idnumber', $turnitinuser2);
        $this->assertArrayHasKey('turnitin_uid', $turnitinuser2);
        $this->assertEquals('user2', $turnitinuser2['username']);
        $this->assertEquals('user2@test.com', $turnitinuser2['email']);
        $this->assertEquals('1235', $turnitinuser2['turnitin_uid']);
    }

    /**
     * Tests for phpunit.
     */
    public function test_comillaserpws_manage_database_enrolments() {
        global $DB;
        $this->resetAfterTest(true);

        // Creating a few users.
        $user1 = $this->getDataGenerator()->create_user(array('username' => 'user1', 'email' => 'user1@test.com'));
        $user2 = $this->getDataGenerator()->create_user(array('username' => 'user2', 'email' => 'user2@test.com'));

        // Creating a course to test.
        $course1 = $this->getDataGenerator()->create_course(array('shortname' => 'Course 1'));
        $context1 = context_course::instance($course1->id);
        // Getting the id of the role student.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        // Set the user as admin to use the web service.
        $this->setAdminUser();

        // Params required in the ws.
        $enrol1 = array ();
        $enrol1['roleid'] = $studentrole->id;
        $enrol1['userid'] = $user1->id;
        $enrol1['courseid'] = $course1->id;
        $enrol1['timestart'] = 0;
        $enrol1['timeend'] = 0;
        $enrol1['suspend'] = 0;

        $enrol2 = array ();
        $enrol2['roleid'] = $studentrole->id;
        $enrol2['userid'] = $user2->id;
        $enrol2['courseid'] = $course1->id;
        $enrol2['timestart'] = 0;
        $enrol2['timeend'] = 0;
        $enrol2['suspend'] = 0;

        $enrols = array($enrol1, $enrol2);

        $action = 'enrol';

        // Call the external function with the params to test enrolments.
        $response = local_comillaserpws_external::comillaserpws_manage_database_enrolments ($enrols, $action);

        // Validate the return data with different asserts comparing the expected results with the response values.
        $this->assertEquals(null, $response);
        $this->assertTrue(is_enrolled($context1, $user1));
        $this->assertTrue(is_enrolled($context1, $user2));

        $action = 'unenrol';

        // Call the external function with the params to test unenrolments.
        $response = local_comillaserpws_external::comillaserpws_manage_database_enrolments ($enrols, $action);

        // Validate the return data with different asserts comparing the expected results with the response values.
        $this->assertEquals(null, $response);
        $this->assertFalse(is_enrolled($context1, $user1));
        $this->assertFalse(is_enrolled($context1, $user2));
    }
}