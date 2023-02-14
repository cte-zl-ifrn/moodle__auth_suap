<?php
/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     auth_suap
 * @copyright   2020 Kelson Medeiros <kelsoncm@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'auth_suap';
$plugin->release = '0.2.024';
$plugin->version = 20230210000 + substr($plugin->release, 4);
$plugin->maturity = MATURITY_ALPHA;
$plugin->requires = 2019052000;
