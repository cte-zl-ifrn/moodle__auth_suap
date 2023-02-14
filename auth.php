<?php
/**
 * Authentication class for suap is defined here.
 *
 * @package     auth_suap
 * @copyright   2020 Kelson Medeiros <kelsoncm@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/user/lib.php");
require_once("$CFG->dirroot/user/profile/lib.php");
require_once("$CFG->dirroot/lib/authlib.php");
require_once("$CFG->dirroot/auth/suap/classes/Httpful/Bootstrap.php");
\Httpful\Bootstrap::init();


class auth_plugin_suap extends auth_plugin_base {

    public function __construct() {
        $this->authtype = 'suap';
        $this->roleauth = 'auth_suap';
        $this->errorlogtag = '[AUTH SUAP] ';
        $this->config = get_config('auth_suap');
        $this->usuario = null;
    }

    public function user_login($username, $password) {
        return false;
    }

    public function can_change_password() {
        return false;
    }

    public function is_internal() {
        return false;
    }

    function postlogout_hook($user){
        global $CFG;
        if($user->auth != 'suap'){
            return 0;
        }
        $config = get_config('auth/suap');
        redirect($CFG->wwwroot.'/auth/suap/logout.php');
    }

    public function login() {
        global $CFG, $USER, $SESSION;

        $next = isset($_GET['next']) ? $_GET['next'] : $CFG->wwwroot;

        if ($USER->id) {
            header("Location: $next", true, 302);
        } else {
            $SESSION->next_after_next = $next;
            $redirect_uri = "$CFG->wwwroot/auth/suap/authenticate.php";
            header("Location: {$this->config->base_url}/o/authorize/?response_type=code&client_id={$this->config->client_id}&redirect_uri=$redirect_uri", true, 302);
        }        
    }

    public function authenticate() {
        global $CFG, $USER;

        if ($USER->id) {
            header("Location: $next", true, 302);
            die();
        }

        $conf = get_config('auth_suap');
        
        if (!isset($_GET['code'])) {
            throw new Exception("O código de autenticação não foi informado.");
        }

        try {
            $auth = json_decode(
                \Httpful\Request::post(
                    "$conf->base_url/o/token/",
                    [
                        'grant_type' => 'authorization_code',
                        'code' => $_GET['code'],
                        'redirect_uri' => "{$CFG->wwwroot}/auth/suap/authenticate.php",
                        'client_id' => $conf->client_id,
                        'client_secret' => $conf->client_secret
                    ],
                    \Httpful\Mime::FORM
                )->send()->raw_body
            );

            $userdata = json_decode(
                \Httpful\Request::get("$conf->base_url/api/eu/?scope=" . urlencode('identificacao documentos_pessoais'))
                    ->addHeaders(["Authorization" => "Bearer {$auth->access_token}", 'x-api-key' => $conf->client_secret, 'Accept' => 'application/json'])
                    ->send()->raw_body
            );
        } catch (Exception $e) {
            echo "<p>Erro ao tentar integrar com o SUAP. Aguarde alguns minutos e <a href='{$CFG->wwwroot}/auth/suap/login.php'>tente novamente</a>.";
            die();
        }

        $this->create_or_update_user($userdata);
    }

    function create_or_update_user($userdata){
        global $DB, $USER, $SESSION;
        /*
        "identificacao": user.username,
        "nome": user.get_full_name(),
        "primeiro_nome": user.first_name,
        "ultimo_nome": user.last_name,
        "email": user.email,
        "email_secundario": relacionamento.pessoa_fisica.email_secundario,
        "email_google_classroom": getattr(relacionamento, "email_google_classroom", None),
        "email_academico": getattr(relacionamento, "email_academico", None),
        "email_preferencial": data['email'] or data['email_secundario'] or data['email_academico'] or data['email_google_classroom']
        "campus": campus and str(campus) or None,
        
        # allow_scopes == "documentos_pessoais"
        "cpf": relacionamento.pessoa_fisica.cpf
        "data_de_nascimento": relacionamento.pessoa_fisica.nascimento_data
        "sexo": relacionamento.pessoa_fisica.sexo
        */
        $usuario = $DB->get_record("user", ["username" => $userdata->identificacao]);
        if (!$usuario) {
            $usuario = (object)[
                'username' => $userdata->identificacao,
                'firstname' => $userdata->primeiro_nome,
                'lastname' => $userdata->ultimo_nome,
                'email' => $userdata->email_preferencial,
                'auth' => 'suap',
                'suspended' => 0,
                'password' => '!aA1' . uniqid(),
                'timezone' => '99',
                // 'lang'=>'pt_br',
                'confirmed' => 1,
                'mnethostid' => 1,
                'policyagreed' => 0,
                'deleted' => 0,
                'firstaccess' => time(),
                'currentlogin' => time(),
                'lastip' => $_SERVER['REMOTE_ADDR'],
                'firstnamephonetic' => null,
                'lastnamephonetic' => null,
                'middlename' => null,
                'alternatename' => null,
                // 'picture' => '',
            ];
            $usuario->id = \user_create_user($usuario);
        }

        $usuario->firstname = $userdata->primeiro_nome;
        $usuario->lastname = $userdata->ultimo_nome;
        $usuario->email = $userdata->email_preferencial;
        $usuario->auth = 'suap';
        $usuario->suspended = 0;
        $usuario->profile_field_nome_apresentacao = $userdata->nome;
        $usuario->profile_field_nome_completo = property_exists($userdata, 'nome_completo') ? $userdata->nome_completo : null;
        $usuario->profile_field_nome_social = property_exists($userdata, 'nome_social') ? $userdata->nome_social : null;
        $usuario->profile_field_email_secundario = property_exists($userdata, 'email_secundario') ? $userdata->email_secundario : null;
        $usuario->profile_field_email_google_classroom = property_exists($userdata, 'email_google_classroom') ? $userdata->email_google_classroom : null;
        $usuario->profile_field_email_academico = property_exists($userdata, 'email_academico') ? $userdata->email_academico : null;
        $usuario->profile_field_campus_sigla = property_exists($userdata, 'campus') ? $userdata->campus : null;
        $this->usuario = $usuario;
        $this->update_user_record($this->usuario->username);
        $next = $SESSION->next_after_next;

        complete_user_login($usuario);
        header("Location: $next", true, 302);
    }

    function get_userinfo($username) {
        return get_object_vars($this->usuario);
    }

}
