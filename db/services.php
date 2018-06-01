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
 * Services declaration.
 *
 * @package    local_comillaserpws
 * @copyright  2018 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
        'local_comillaserpws_get_enrolled_users' => array(
                'classname'   => 'local_comillaserpws_external',
                'methodname'  => 'comillaserpws_get_enrolled_users',
                'classpath'   => 'local/comillaserpws/externallib.php',
                'description' => get_string('enrolled_users_desc', 'local_comillaserpws'),
                'type'        => 'read',
        ),
        'local_comillaserpws_get_turnitintool_user' => array(
                'classname'   => 'local_comillaserpws_external',
                'methodname'  => 'comillaserpws_get_turnitintool_user',
                'classpath'   => 'local/comillaserpws/externallib.php',
                'description' => get_string('turnitin_user_desc', 'local_comillaserpws'),
                'type'        => 'read',
        ),
        'local_comillaserpws_manage_database_enrolments' => array(
                'classname'   => 'local_comillaserpws_external',
                'methodname'  => 'comillaserpws_manage_database_enrolments',
                'classpath'   => 'local/comillaserpws/externallib.php',
                'description' => get_string('database_enrolments_desc', 'local_comillaserpws'),
                'type'        => 'write',
        )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        'COMILLAS ERP Webservice' => array(
                'functions' => array ('local_comillaserpws_get_enrolled_users',
                                      'local_comillaserpws_get_turnitintool_user',
                                      'local_comillaserpws_manage_database_enrolments'),
                'requiredcapability' => 'moodle/user:viewhiddendetails',
                'restrictedusers' => 0,
                'enabled' => 1
        )
);