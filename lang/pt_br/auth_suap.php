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
$string['auth_apifunc'] = 'Function API';
$string['auth_apifunc_desc'] = 'API Chamada de função.';
$string['auth_apiurl'] = 'URL API';
$string['auth_apiurl_desc'] = 'Chamadas de API do endereço.';
$string['auth_baseurl'] = 'URL Provider';
$string['auth_baseurl_desc'] = 'Endereço de base do provedor..
<br>Callback URL: '.$CFG->wwwroot.'/auth/suap/callback.php';
$string['auth_consumer_key'] = 'Consumer Key';
$string['auth_consumer_key_desc'] = 'Chave de autorização consumer_key';
$string['auth_consumer_secret'] = 'Consumer Secret';
$string['auth_consumer_secret_desc'] = 'Chave de autorização consumer_secret';
$string['auth_fieldlocks_help'] = 'Esses campos são opcionais. E pode optar por preencher alguns campos de usuário Moodle com dados dos campos OAUTH.<br><br>
<b>Atualizar dados interno</b>: Se ativado, impedir que os usuários e administradores para alterar o campo diretamente para o Moodle.<br><br>
<b>Bloqueia Valor</b>: Se abilitato, impedirà agli utenti e agli amministratori di Moodle di modificare il campo direttamente.';
$string['auth_suapdescription'] = 'Permite que o usuário se conectar ao site através de um serviço externo (suap).
A primeira vez que você entra, você criar uma nova conta.
<br>Opção "<a href="'.$CFG->wwwroot.'/admin/search.php?query=authpreventaccountcreation">Evite criar contas no momento da autenticação</a>" <b>não deve</b> ser ativo.';
$string['auth_suapsettings'] = 'Configurações';


$string['error_incomplete_params'] = 'Parâmetros incompletos ($a).';
$string['configtitle'] = 'SUAP';
$string['generalsettings'] = 'General settings';
$string['sync_up_auth_token'] = 'Token de autenticação';
$string['sync_up_auth_token_desc'] = 'Token que será passado pelo SUAP Middleware para autenticar-se no Moodle. Será no header "Authentication" e o conteúdo será "Token ****", onde **** é o token em si.';
$string['default_user_preferences'] = 'Preferências de padrões do usuários';
$string['default_user_preferences_desc'] = 'Preferencias de padrões do usuários. Cada valor é preferência é separada por uma quebra de linha. A chave e o valor são separados pelo signal de igual =.';

$string['aluno_auth'] = 'Método de autenticação do aluno';
$string['aluno_auth_desc'] = 'Método de autenticação do aluno';
$string['aluno_role_id'] = 'RoleID do aluno';
$string['aluno_role_id_desc'] = 'RoleID do aluno';
$string['aluno_enrol_type'] = 'EnrolType do aluno';
$string['aluno_enrol_type_desc'] = 'EnrolType do aluno';

$string['principal_auth'] = 'Método de autenticação do professor';
$string['principal_auth_desc'] = 'Método de autenticação do professor';
$string['principal_role_id'] = 'RoleID do professor';
$string['principal_role_id_desc'] = 'RoleID do professor';
$string['principal_enrol_type'] = 'EnrolType do professor';
$string['principal_enrol_type_desc'] = 'EnrolType do professor';

$string['moderador_auth'] = 'Método de autenticação do moderador';
$string['moderador_auth_desc'] = 'Método de autenticação do moderador';
$string['moderador_role_id'] = 'RoleID do moderador';
$string['moderador_role_id_desc'] = 'RoleID do moderador';
$string['moderador_enrol_type'] = 'EnrolType do moderador';
$string['moderador_enrol_type_desc'] = 'EnrolType do moderador';
