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
 * Authentication class for suap is defined here.
 *
 * @package     auth_suap
 * @copyright   2020 Kelson Medeiros <kelsoncm@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');


/**
 * Authentication class for suap.
 */
class auth_plugin_suap extends auth_plugin_base {

    /**
     * Set the properties of the instance.
     */
    public function __construct() {
        $this->authtype = 'suap';
        $this->roleauth = 'auth_suap';
        $this->errorlogtag = '[AUTH suap] ';
        $this->config = get_config('auth/suap');
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username.
     * @param string $password The password.
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password) {
        global $DB, $CFG;

        // Retrieve the user matching username.
        $user = $DB->get_record('user', array('username' => $username));
        // Username must exist and have the right authentication method.
        if (!empty($user) && ($user->auth == 'suap')) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if this authentication plugin can change the user's password.
     *
     * @return bool
     */
    public function can_change_password() {
        return false;
    }

    /**
     * Returns true if this authentication plugin is "internal".
     *
     * Internal plugins use password hashes from Moodle user table for authentication.
     *
     * @return bool
     */
    public function is_internal() {
        return false;
    }

    /**
     * Indicates if password hashes should be stored in local moodle database.
     *
     * @return bool True means password hash stored in user table, false means flag 'not_cached' stored there instead.
     */
    public function prevent_local_passwords() {
        return true;
    }

    /**
     * Authentication hook - is called every time user hit the login page
     * The code is run only if the param code is mentionned.
     */
    function loginpage_hook() {
        global $SESSION, $CFG, $DB, $USER;
		$CFG->nolastloggedin = true;
        
    }

    function get_userinfo($username) {
        global $SESSION, $CFG;

        return $result;
    }

    function get_attributes() {
        $moodleattributes = array();
        $customfields = $this->get_custom_user_profile_fields();
        if (!empty($customfields) && !empty($this->userfields)) {
            $userfields = array_merge($this->userfields, $customfields);
        } else {
            $userfields = $this->userfields;
        }

        foreach ($userfields as $field) {
            if (!empty($this->config->{"field_map_$field"})) {
                $moodleattributes[$field] = core_text::strtolower(trim($this->config->{"field_map_$field"}));
            }
        }
        $moodleattributes['username'] = core_text::strtolower(trim($this->config->username));
        return $moodleattributes;
    }

    /**
     * Called when the user record is updated.
     *
     * We check there is no hack-attempt by a user to change his/her email address
     *
     * @param mixed $olduser     Userobject before modifications    (without system magic quotes)
     * @param mixed $newuser     Userobject new modified userobject (without system magic quotes)
     * @return boolean result
     *
     */
    function user_update($olduser, $newuser) {
        if ($olduser->email != $newuser->email) {
            return false;
        } else {
            return true;
        }
    }
    
    function postlogout_hook($user){
        global $CFG;
        if($user->auth != 'suap'){
            return 0;
        }
        $config = get_config('auth/suap');

        $url=$config->apiurl.'/logout/';
        $config = get_config('auth/suap');

        redirect($CFG->wwwroot.'/auth/suap/logout.php');
    }

    /**
     * Prints a form for configuring this authentication plugin.
     *
     * This function is called from admin/auth.php, and outputs a full page with
     * a form for configuring this plugin.
     *
     * @param object $config
     * @param object $err
     * @param array $user_fields
     */
    public function config_form($config, $err, $user_fields) {

        // A html file for the form can be included here:
        include 'config.html';

    }

    /**
     * Processes and stores configuration data for the plugin.
     *
     * @param stdClass $config Object with submitted configuration settings (without system magic quotes).
     * @return bool True if the configuration was processed successfully.
     */
    function process_config($config) {
        // Set to defaults if undefined.
        if (!isset ($config->consumer_key)) {
            $config->consumer_key = '';
        }
        if (!isset ($config->consumer_secret)) {
            $config->consumer_secret = '';
        }
        if (!isset ($config->baseurl)) {
            $config->baseurl = '';
        }        
        if (!isset ($config->apiurl)) {
            $config->apiurl = '';
        }

        // Save settings.
        set_config('consumer_key', $config->consumer_key, 'auth/suap');
        set_config('consumer_secret', $config->consumer_secret, 'auth/suap');
        set_config('baseurl', $config->baseurl, 'auth/suap');
        set_config('apiurl', $config->apiurl, 'auth/suap');

        return true;
    }
}
