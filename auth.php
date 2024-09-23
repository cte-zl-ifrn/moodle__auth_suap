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


class auth_plugin_suap extends auth_oauth2\auth
{
    public $authtype;
    public $roleauth;
    public $errorlogtag;
    public $config;
    public $usuario;

    public function __construct()
    {
        $this->authtype = 'suap';
        $this->roleauth = 'auth_suap';
        $this->errorlogtag = '[AUTH SUAP] ';
        $this->config = get_config('auth_suap');
        $this->usuario = null;
    }

    public function user_login($username, $password)
    {
        return false;
    }

    public function can_change_password()
    {
        return false;
    }

    public function is_internal()
    {
        return false;
    }

    function postlogout_hook($user)
    {
        global $CFG;
        if ($user->auth != 'suap') {
            return 0;
        }
        $config = get_config('auth/suap');
        redirect($CFG->wwwroot . '/auth/suap/logout.php');
    }

    public function login()
    {
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

    public function authenticate()
    {
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

            $response = \Httpful\Request::get("$conf->base_url/api/v1/userinfo/?scope=" . urlencode('read'))
                    ->addHeaders(["Authorization" => "Bearer {$auth->access_token}", 'x-api-key' => $conf->client_secret, 'Accept' => 'application/json'])
                    ->send()->raw_body;
            $userdata = json_decode($response);
            $this->create_or_update_user($userdata);
        } catch (Exception $e) {
            echo "<p>Erro ao tentar integrar com o SUAP. Aguarde alguns minutos e <a href='{$CFG->wwwroot}/auth/suap/login.php'>tente novamente</a>.";
            die();
        }
        echo "<p>Erro ao tentar integrar com o SUAP. Não foi possível obter seus dados da API.</a>.";
        die();
    }

    function create_or_update_user($userdata)
    {
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
                "foto":"https://cdn.suap.ifrn.edu.br/media/fotos/75x100/159574.4t54kAqLqyPB.jpg?X-Amz-Algorithm=...&X-Amz-Credential=...&X-Amz-Date=...&X-Amz-Expires=...&X-Amz-SignedHeaders=...&X-Amz-Signature=...",
                "tipo_usuario": "Servidor (Técnico-Administrativo)",
                "email_preferencial": "nome.sobrenome@ifrn.edu.br"
            }

            // Antes a foto era relativa ao baseurl do SUAP, agora é absoluta e temporária
        */
        global $DB, $USER, $SESSION, $CFG;
        
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
            ];
            $usuario->id = \user_create_user($usuario);
        }

        $usuario->firstname = $userdata->primeiro_nome;
        $usuario->lastname = $userdata->ultimo_nome;
        $usuario->email = $userdata->email_preferencial;
        $usuario->auth = 'suap';
        $usuario->suspended = 0;
        $usuario->profile_field_nome_apresentacao = $userdata->nome_usuaal;
        $usuario->profile_field_nome_completo = property_exists($userdata, 'nome_registro') ? $userdata->nome_registro : null;
        $usuario->profile_field_nome_social = property_exists($userdata, 'nome_social') ? $userdata->nome_social : null;
        $usuario->profile_field_email_secundario = property_exists($userdata, 'email_secundario') ? $userdata->email_secundario : null;
        $usuario->profile_field_email_google_classroom = property_exists($userdata, 'email_google_classroom') ? $userdata->email_google_classroom : null;
        $usuario->profile_field_email_academico = property_exists($userdata, 'email_academico') ? $userdata->email_academico : null;
        $usuario->profile_field_campus_sigla = property_exists($userdata, 'campus') ? $userdata->campus : null;
        $usuario->profile_field_last_login = \json_encode($userdata);
        $usuario->profile_field_tipo_usuario = property_exists($userdata, 'tipo_usuario') ? $userdata->tipo_usuario : null;
        $this->usuario = $usuario;
        $next = $SESSION->next_after_next;

        $this->update_user_record($this->usuario->username);
        if (property_exists($userdata, 'foto')) {
            $this->update_picture($usuario, $userdata->foto);
        }
        $usuario = $DB->get_record("user", ["username" => $userdata->identificacao]);
        complete_user_login($usuario);

        header("Location: $next", true, 302);
    }

    function update_picture($usuario, $foto)
    {
        global $CFG, $DB;
        require_once($CFG->libdir . '/gdlib.php');

        $conf = get_config('auth_suap');

        $tmp_filename = $CFG->tempdir . '/suapfoto' . $usuario->id;
        file_put_contents($tmp_filename, file_get_contents($foto));
        $usuario->imagefile = process_new_icon(context_user::instance($usuario->id, MUST_EXIST), 'user', 'icon', 0, $tmp_filename);
        if ($usuario->imagefile) {
            $DB->set_field('user', 'picture', $usuario->imagefile, ['id' => $usuario->id]);
        }
    }

    function get_userinfo($username)
    {
        return get_object_vars($this->usuario);
    }
}