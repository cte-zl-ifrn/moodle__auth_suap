<?php
require_once('../../config.php');
require_once('./auth.php');

(new auth_plugin_suap())->authenticate();
