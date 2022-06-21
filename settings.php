<?php
/**
 * SUAP Integration
 *
 * This module provides extensive analytics on a platform of choice
 * Currently support Google Analytics and Piwik
 *
 * @package     auth_suap
 * @category    upgrade
 * @copyright   2020 Kelson Medeiros <kelsoncm@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__.'/locallib.php');

if ($ADMIN->fulltree) {

    // Introductory explanation.
    $settings->add(new admin_setting_heading('auth_suap/pluginname', '', get_string('auth_suap_description', 'auth_suap')));

    if ($CFG->env == 'local') {
        create_setting_configtext($settings, "sync_up_auth_token", 'changeme');
    } else {
        create_setting_configtext($settings, "sync_up_auth_token", sha1(uniqid()));
    }

    create_setting_configtext($settings, "aluno_auth", 'oauth2');
    create_setting_configtext($settings, "aluno_role_id", 5);
    create_setting_configtext($settings, "aluno_enrol_type", 'manual');

    create_setting_configtext($settings, "principal_auth", 'oauth2');
    create_setting_configtext($settings, "principal_role_id", 3);
    create_setting_configtext($settings, "principal_enrol_type", 'manual');

    create_setting_configtext($settings, "moderador_auth", 'oauth2');
    create_setting_configtext($settings, "moderador_role_id", 4);
    create_setting_configtext($settings, "moderador_enrol_type", 'manual');

    create_setting_configtextarea($settings, "default_user_preferences", "auth_forcepasswordchange=0\nhtmleditor=0\nemail_bounce_count=1\nemail_send_count=1\nemail_bounce_count=0");

    $authplugin = get_auth_plugin('suap');
    display_auth_lock_options($settings, $authplugin->authtype, $authplugin->userfields, get_string('auth_fieldlocks_help', 'auth'), false, false);

}
