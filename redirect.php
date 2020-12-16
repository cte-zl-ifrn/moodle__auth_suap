<?php
require_once('../../config.php');
require_once('lib.php');
require_once($CFG->dirroot.'/auth/suap/classes/auth_suap.class.php');
global $SESSION,$CFG;
$config_plugin = get_config('auth/suap');
$suap = new auth_suap($config_plugin->apiurl, $config_plugin->consumer_key, $config_plugin->baseurl,$config_plugin->consumer_secret);
//Redireciona para a pagina de login do suap
$suap->login();