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
require_once("$CFG->dirroot/lib/classes/user.php");
require_once("$CFG->dirroot/auth/suap/classes/Httpful/Bootstrap.php");
\Httpful\Bootstrap::init();


class auth_plugin_suap extends auth_oauth2\auth {

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

        if (isset($_GET['next'])) {
            $next = $_GET['next'];
        } elseif (property_exists($SESSION, 'wantsurl')) {
            $next = $SESSION->wantsurl;
        } else {
            $next = $CFG->wwwroot;
        }

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
        $names = explode(' ', $userdata->nome_registro);
        $firstname = $names[0];
        $lastname = implode(' ', array_slice($names, 1));

        /*
        {
            "identificacao": "123456789",
            "nome_social": "",
            "nome_usual": "Nome Outros",
            "nome_registro": "Nome Outros Nomes Sobrenome",
            "nome": "Nome Sobrenome",
            "primeiro_nome": "Nome",
            "ultimo_nome": "Sobrenome",
            "email": "nome.sobrenome@ifrn.edu.br",
            "email_secundario": "nome.sobrenome@gmail.com",
            "email_google_classroom": "nome.sobrenome@escolar.ifrn.edu.br",
            "email_academico": "nome.sobrenome@academico.ifrn.edu.br",
            "campus": "RE",
            "foto": "/media/fotos/75x100/12asdf349.jpg",
            "tipo_usuario": "Servidor (Técnico-Administrativo)",
            "email_preferencial": "nome.sobrenome@ifrn.edu.br"
        }
        */
        $usuario = $DB->get_record("user", ["username" => $userdata->identificacao]);
        if (!$usuario) {
            $usuario = (object)[
                'username' => $userdata->identificacao,
                'firstname' => $firstname,
                'lastname' => $lastname,
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

        $usuario->firstname = $firstname;
        $usuario->lastname = $lastname;
        $usuario->email = $userdata->email_preferencial;
        $usuario->auth = 'suap';
        $usuario->suspended = 0;
        $usuario->profile_field_nome_apresentacao = $userdata->nome;
        $usuario->profile_field_nome_completo = property_exists($userdata, 'nome_registro') ? $userdata->nome_registro : null;
        $usuario->profile_field_nome_social = property_exists($userdata, 'nome_social') ? $userdata->nome_social : null;
        $usuario->profile_field_email_secundario = property_exists($userdata, 'email_secundario') ? $userdata->email_secundario : null;
        $usuario->profile_field_email_google_classroom = property_exists($userdata, 'email_google_classroom') ? $userdata->email_google_classroom : null;
        $usuario->profile_field_email_academico = property_exists($userdata, 'email_academico') ? $userdata->email_academico : null;
        $usuario->profile_field_campus_sigla = property_exists($userdata, 'campus') ? $userdata->campus : null;
        $this->usuario = $usuario;

        complete_user_login($usuario);

        if ( property_exists($userdata, 'foto') ) {
            require_once( $CFG->libdir . '/gdlib.php' );
            $tmp_file = sys_get_temp_dir() . '/' . basename($userdata->foto);
            $usericonid = process_new_icon( context_user::instance( $usuario->id, MUST_EXIST ), 'user', 'icon', 0, $tmp_file );
            if ( $usericonid ) {
                    $DB->set_field( 'user', 'picture', $usericonid, array( 'id' => $usuario->id ) );
            }

            $coursecontext = context_system::instance();
            $usuario->imagefile = $draftitemid;
            core_user::update_picture(
                $usuario,
                [
                    'maxbytes' => $CFG->maxbytes,
                    'subdirs' => 0,
                    'maxfiles' => 1,
                    'accepted_types' => 'optimised_image'
                ]
            );
        }
        
        $this->update_user_record($this->usuario->username);
        $next = $SESSION->next_after_next;

        header("Location: $next", true, 302);
    }

    function get_userinfo($username) {
        return get_object_vars($this->usuario);
    }

}
