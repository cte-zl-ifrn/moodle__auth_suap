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

$string['base_url'] = 'URL base';
$string['base_url_desc'] = 'Informe o URL do seu SUAP';

$string['client_id'] = 'ID do cliente';
$string['client_id_desc'] = "Lembre-se de primeiro cadastrar este Moodle na sua lista de aplicações no SUAP (Tec. da Informação / Serviços / Aplicações OAUTH2). Cadastre como 'Authorization code', 'Public' e como 'Redirect uris' coloque '{$CFG->wwwroot}/admin/oauth2callback.php'.";

$string['client_secret'] = 'Secredo do cliente';
$string['client_secret_desc'] = "Lembre-se, se você não salvar logo 'Secredo do cliente' ao editar ele não estará mais disponível, ou seja, será necessário cadastrar novo serviço.";

$string['verify_token_url'] = "Verify token URL";
$string['verify_token_url_desc'] = "Verify token URL";