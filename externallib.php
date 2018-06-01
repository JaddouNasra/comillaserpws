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
 * Webservice implementation.
 *
 * @package    local_comillaserpws
 * @copyright  2018 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Class that retrieve the usage data of users
 *
 * @package   local_comillaserpws
 * @copyright 2017 Planificación Entornos Tecnológicos {@link http://www.pentec.es/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_comillaserpws_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function comillaserpws_get_enrolled_users_parameters() {
        return new external_function_parameters(
                array(
            'courseid' => new external_value(PARAM_INT, 'course id'),
            'options' => new external_multiple_structure(
                    new external_single_structure(
                    array(
                'name' => new external_value(PARAM_ALPHANUMEXT, 'option name'),
                'value' => new external_value(PARAM_RAW, 'option value')
                    )
                    ), 'Option names:
                        * withcapability (string) return only users with this capability. Requires \'moodle/role:review\' on
                            the course context.
                        * groupid (integer) return only users in this group id. If the course has groups enabled and this param
                            isn\'t defined, returns all the viewable users. This option requires \'moodle/site:accessallgroups\' on
                            the course context if the user doesn\'t belong to the group.
                        * onlyactive (integer) return only users with active enrolments and matching time restrictions. This option
                            requires \'moodle/course:enrolreview\' on the course context.
                        * userfields (\'string, string, ...\') return only the values of these user fields.
                        * limitfrom (integer) sql limit from.
                        * limitnumber (integer) maximum number of returned users.
                        * sortby (string) sort by id, firstname or lastname. For ordering like the site does, use siteorder.
                        * sortdirection (string) ASC or DESC', VALUE_DEFAULT, array()),
                )
        );
    }

    /**
     * Get course participants details also with enrolments information
     *
     * @param int $courseid  course id
     * @param array $options options {
     *                                'name' => option name
     *                                'value' => option value
     *                               }
     * @return array An array of users
     */
    public static function comillaserpws_get_enrolled_users($courseid, $options = array()) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/user/lib.php");
        require_once($CFG->dirroot . "/enrol/externallib.php");

        // Parameter validation.
        self::validate_parameters(
                self::comillaserpws_get_enrolled_users_parameters(), array(
            'courseid' => $courseid,
            'options' => $options
                )
        );

        // Now we recover the users enrolled in the given course.
        $users = core_enrol_external::get_enrolled_users($courseid, $options);

        // For each user recovered we search its enrolments in the given course and save the data.
        foreach ($users as $key => $value) {
            $sql = "SELECT ue.id, e.enrol as enroltype, e.courseid, ue.status, ue.timestart, ue.timeend
                      FROM {user_enrolments} ue
                      JOIN {enrol} e ON ue.enrolid = e.id
                     WHERE e.courseid = :courseid
                       AND ue.userid = :userid";
            $enroltypes = $DB->get_records_sql($sql, array('courseid' => $courseid, 'userid' => $value['id']));
            $users[$key]['enrolments'] = $enroltypes;
        }

        return $users;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function comillaserpws_get_enrolled_users_returns() {
        return new external_multiple_structure(
                new external_single_structure(
                array(
            'id' => new external_value(PARAM_INT, 'ID of the user'),
            'username' => new external_value(PARAM_TEXT, 'Username of the user', VALUE_OPTIONAL),
            'email' => new external_value(PARAM_TEXT, 'User email address', VALUE_OPTIONAL),
            'roles' => new external_multiple_structure(
                    new external_single_structure(
                    array(
                'roleid' => new external_value(PARAM_INT, 'role id'),
                'name' => new external_value(PARAM_TEXT, 'role name'),
                'shortname' => new external_value(PARAM_ALPHANUMEXT, 'role shortname'),
                'sortorder' => new external_value(PARAM_INT, 'role sortorder')
                    )
                    ), 'user roles', VALUE_OPTIONAL),
            'enrolments' => new external_multiple_structure(
                    new external_single_structure(
                    array(
                'id' => new external_value(PARAM_INT, 'Id of the enrolment'),
                'enroltype' => new external_value(PARAM_TEXT, 'Type of enrolment'),
                'courseid' => new external_value(PARAM_INT, 'Id of the course'),
                'status' => new external_value(PARAM_INT, 'Status of the enrolment'),
                'timestart' => new external_value(PARAM_INT, 'Timestart of the enrolment'),
                'timeend' => new external_value(PARAM_INT, 'Timeend of the enrolment')
                    )
                    ), 'Information of the enrolments of the user in the course', VALUE_OPTIONAL)
                )
                )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function comillaserpws_get_turnitintool_user_parameters() {
        return new external_function_parameters(
                array(
            'username' => new external_value(PARAM_TEXT, 'username of the user')
                )
        );
    }

    /**
     * Get turnitin_uid and some user data for a single user
     *
     * @param string $username  username of an user
     * @return array An array of stdClass object with data from the user and turnitin plugin
     */
    public static function comillaserpws_get_turnitintool_user($username) {
        global $DB;

        // Parameter validation.
        self::validate_parameters(
                self::comillaserpws_get_turnitintool_user_parameters(), array(
            'username' => $username
                )
        );

        // Context validation.
        $context = context_system::instance();
        self::validate_context($context);

        // Capability checking.
        if (!has_capability('moodle/user:viewhiddendetails', $context)) {
            throw new required_capability_exception($context, 'moodle/user:viewhiddendetails', '', '');
        }

        // We recover the user data and turnitin_uid, ( only 1 record on turnitintooltwo_users table for each user).
        $sql = "SELECT tu.turnitin_uid, u.username, u.email, u.idnumber
                  FROM {turnitintooltwo_users} tu
                  JOIN {user} u on tu.userid = u.id
                 WHERE u.username = :username;";
        $record = $DB->get_record_sql($sql, array('username' => $username));
        // If we dont have any record either the username is not valid or the user doesn't have a turnitit account.
        if (!$record) {
            throw new invalid_parameter_exception(get_string('user_not_found', 'local_comillaserpws'), 'debug', '', null);
        }
        $userdata = array();
        $userdata['username'] = $record->username;
        $userdata['email'] = $record->email;
        $userdata['idnumber'] = $record->idnumber;
        $userdata['turnitin_uid'] = $record->turnitin_uid;

        return $userdata;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function comillaserpws_get_turnitintool_user_returns() {
        return new external_single_structure(
                array(
            'username' => new external_value(PARAM_TEXT, 'Username policy is defined in Moodle security config'),
            'email' => new external_value(PARAM_TEXT, 'User email address', VALUE_OPTIONAL),
            'idnumber' => new external_value(PARAM_TEXT, 'An ID code number from the institution', VALUE_OPTIONAL),
            'turnitin_uid' => new external_value(PARAM_TEXT, 'External uid of turnitin',
                                                 'Information of the enrolments of the user in the course', VALUE_OPTIONAL)
                )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function comillaserpws_manage_database_enrolments_parameters() {
        return new external_function_parameters(
                array(
                    'enrolments' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'roleid' => new external_value(PARAM_INT, 'Role to assign to the user'),
                                'userid' => new external_value(PARAM_INT, 'The user that is going to be enrolled'),
                                'courseid' => new external_value(PARAM_INT, 'The course to enrol the user role in'),
                                'timestart' => new external_value(PARAM_INT, 'Timestamp when the enrolment start', VALUE_OPTIONAL),
                                'timeend' => new external_value(PARAM_INT, 'Timestamp when the enrolment end', VALUE_OPTIONAL),
                                'suspend' => new external_value(PARAM_INT, 'set to 1 to suspend the enrolment', VALUE_OPTIONAL)
                            )
                        )
                    ),
                    'action' => new external_value(PARAM_TEXT, 'set to enrol or unenrol')
                )
        );
    }

    /**
     * Get turnitin_uid and some user data for a single user
     *
     * @param array $enrolments  An array of user enrolment/unenrolment data
     * @param string $action  String with the action to execute (posibble values -> 'enrol' or 'unenrol'))
     * @return void
     */
    public static function comillaserpws_manage_database_enrolments($enrolments, $action) {
        global $DB, $CFG;

        require_once($CFG->libdir . '/enrollib.php');

        // Parameter validation.
        self::validate_parameters(
                self::comillaserpws_manage_database_enrolments_parameters(),
                    array(
                        'enrolments' => $enrolments,
                        'action' => $action
                )
        );

        // Rollback all enrolment if an error occurs.
        $transaction = $DB->start_delegated_transaction();

        // Retrieve the database enrolment plugin.
        $enrol = enrol_get_plugin('database');
        if (empty($enrol)) {
            echo "vacio";
            throw new moodle_exception('databasepluginnotinstalled', 'local_comillaserpws');
        }

        foreach ($enrolments as $enrolment) {

            if ($action == 'enrol') {
                // Ensure the current user is allowed to run this function in the enrolment context.
                $context = context_course::instance($enrolment['courseid'], IGNORE_MISSING);
                self::validate_context($context);

                // Check that the user has the permission to configure enrolments.
                require_capability('moodle/course:enrolconfig', $context);

                // Throw an exception if user is not able to assign the role.
                $roles = get_assignable_roles($context);

                if (!array_key_exists($enrolment['roleid'], $roles)) {
                    $errorparams = new stdClass();
                    $errorparams->roleid = $enrolment['roleid'];
                    $errorparams->courseid = $enrolment['courseid'];
                    $errorparams->userid = $enrolment['userid'];
                    throw new moodle_exception('wsusercannotassign', 'enrol_manual', '', $errorparams);
                }

                // Check database enrolment plugin instance is enabled/exist.
                $instance = $DB->get_record('enrol', array('courseid' => $enrolment['courseid'], 'enrol' => 'database'));
                // Add an enrol db instance if there isn't one linked to the course.
                if (!$instance) {
                    $course = $DB->get_record('course', array('id' => $enrolment['courseid']));
                    $instanceid = $enrol->add_instance($course);
                    $instance = $DB->get_record('enrol', array('id' => $instanceid));
                }

                // Finally proceed the enrolment.
                $enrolment['timestart'] = isset($enrolment['timestart']) ? $enrolment['timestart'] : 0;
                $enrolment['timeend'] = isset($enrolment['timeend']) ? $enrolment['timeend'] : 0;
                $enrolment['status'] = (isset($enrolment['suspend'])
                                        && !empty($enrolment['suspend'])) ? ENROL_USER_SUSPENDED : ENROL_USER_ACTIVE;

                $enrol->enrol_user($instance, $enrolment['userid'], $enrolment['roleid'], $enrolment['timestart'],
                                   $enrolment['timeend'], $enrolment['status']);
            } else if ($action == 'unenrol') {

                // Ensure the current user is allowed to run this function in the enrolment context.
                $context = context_course::instance($enrolment['courseid'], IGNORE_MISSING);
                self::validate_context($context);

                // Check that the user has the permission to configure enrolments.
                require_capability('moodle/course:enrolconfig', $context);

                $instance = $DB->get_record('enrol', array('courseid' => $enrolment['courseid'], 'enrol' => 'database'));
                if (!$instance) {
                    throw new moodle_exception('wsnoinstance', 'local_comillaserpws', $enrolment);
                }
                $user = $DB->get_record('user', array('id' => $enrolment['userid']));
                if (!$user) {
                    throw new invalid_parameter_exception('User id not exist: ' . $enrolment['userid']);
                }

                $enrol->unenrol_user($instance, $enrolment['userid']);
            }
        }
        $transaction->allow_commit();
    }

    /**
     * Returns description of method result value
     *
     * @return null
     */
    public static function comillaserpws_manage_database_enrolments_returns() {
        return null;
    }

}
