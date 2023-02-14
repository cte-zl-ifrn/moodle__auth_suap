<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/auth/suap/auth.php');

(new auth_plugin_suap())->login();
