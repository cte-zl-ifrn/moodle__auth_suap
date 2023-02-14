<?php
require_once('../../config.php');

header("Location: {$CFG->wwwroot}/auth/suap/login.php", true, 302);
