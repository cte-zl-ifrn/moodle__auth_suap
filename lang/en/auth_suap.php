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
 * Plugin strings are defined here.
 *
 * @package     auth_suap
 * @category    string
 * @copyright   2020 Kelson Medeiros <kelsoncm@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'SUAP';
$string['auth_suap_description'] = 'SUAP';
$string['auth_description'] = '1';

$string['base_url'] = 'Base URL';
$string['base_url_desc'] = 'Type your SUAP URL';

$string['client_id'] = 'Client ID';
$string['client_id_desc'] = "Remember to first register this Moodle in your list of applications in SUAP (Tec. of Information / Services / Applications OAUTH2). Sign as 'Authorization code', 'Public' and as 'Redirect uris' put '{$CFG->wwwroot}/admin/oauth2callback.php'.";

$string['client_secret'] = 'Client secret';
$string['client_secret_desc'] = "Remember, if you do not immediately save 'Client Secret' when editing it will no longer be available, i.e. you will need to register new service.";
