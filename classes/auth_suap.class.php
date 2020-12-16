<?php
require_once($CFG->dirroot . '/auth/suap/source/httpful.phar');
class auth_suap
{
    function __construct() {
        $config_plugin = get_config('auth/suap');
        $this->authHost = rtrim($config_plugin->apiurl, "/");
        $this->clientID = $config_plugin->consumer_key;
        $this->secret = $config_plugin->consumer_secret;
        $this->redirectURI = $config_plugin->baseurl;
        $this->responseType = "code";
        $this->resourceHost = $this->authHost . "/api/user";
        $this->authorizationURL = $this->authHost . "/oauth/authorize/";
        $this->logoutURL = $this->authHost . "/oauth/revoke_token/";
        $this->dataJSON = null;
        $this->getTokenSes();

    }

    function getTokenSes(){
        global $SESSION;
        if(isset($SESSION->oauth_token)){
            $this->token = $SESSION->oauth_token;
        }
    }

    function setTokenSes($token){
        global $SESSION;
        $this->token = $token;
        $SESSION->oauth_token = $this->token;
    }

    function makeTokenSessid($session){
        $params = array('sessionid'=>$session
        ,'client_id'=>$this->clientID,
            'client_secret'=>$this->secret,
            'redirect_uri'=>$this->redirectURI
        );


        $url = $this->authHost. '/sessao/';
        $request = \Httpful\Request::post($url,http_build_query($params));
        $request->strictSSL(false);
        $request->addHeader("Accept", "application/json");
        $request->addHeader("Authorization", 'Token '.$this->clientID);
        $request->addHeader("Content-Type", "application/x-www-form-urlencoded");
        $request->strictSSL(false);
        $response = $request->send();
        $this->setTokenSes($response->body->access_token);

        return $response->body;
    }

    function makeTokenRefresh($token){
        $params = array('grant_type'=>'refresh_token',
            'refresh_token'=>$token,'client_id'=>$this->clientID,
            'client_secret'=>$this->secret
        );
        $request = \Httpful\Request::post($this->authHost,
            http_build_query($params));
        $request->addHeader("Accept", "application/json");
        $request->addHeader("Content-Type", "application/x-www-form-urlencoded");

        $response = $request->send();
        $this->setTokenSes($response->body->access_token);
        return $response->body;

    }

    function makeToken($code){


        $params = array('response_type'=>'acces_token',
            'grant_type'=>'authorization_code',
            'code'=>$code,'client_id'=>$this->clientID,
            'client_secret'=>$this->secret,
            'redirect_uri'=>$this->redirectURI
        );

        $request = \Httpful\Request::post($this->authHost . '/oauth/token/',
            http_build_query($params));
        $request->addHeader("Accept", "request");

        $request->addHeader("Content-Type", "application/x-www-form-urlencoded");

        $response = $request->send();
        $this->token = $response->body->access_token;
        return $response->body;

        $ch = curl_init();
        $url = $this->authHost . '/oauth/token/';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: request' ,'application/x-www-form-urlencoded' ));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 255);
        curl_setopt($ch, CURLOPT_TIMEOUT, 255);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($result);
        $this->token = $data->access_token;
        return $result;
    }
    static function extractToken() {
        $querystring = parse_str($_SERVER["QUERY_STRING"]);
        if ($querystring != null and array_key_exists("token", $querystring)) {
            return $querystring["token"];
        } else {
            return null;
        }
    }

    static function extractScope() {
        $querystring = parse_str($_SERVER["QUERY_STRING"]);
        if ($querystring != null and array_key_exists("scope", $querystring)) {
            return $querystring["scope"];
        } else {
            return null;
        }
    }

    static function extractDuration() {
        $querystring = parse_str($_SERVER["QUERY_STRING"]);
        if ($querystring != null and array_key_exists("duration", $querystring)) {
            return $querystring["duration"];
        } else {
            return 0;
        }
    }


    function getDataJSON() {
        return $this->dataJSON;
    }


    function getLoginURL() {
        $loginUrl = $this->authorizationURL .
            "?response_type=" . $this->responseType .
            "&client_id="     . $this->clientID .
            "&redirect_uri="  . $this->redirectURI;
        return $loginUrl;
    }

    function doRequest($scope) {
        $params = array(
            'scope'=>$scope
        );

        $urlSessao = str_replace('https://login', 'https://api', $this->authHost);
        $request = \Httpful\Request::post($urlSessao . '/perfil/dados/',
            http_build_query($params));
        $request->addHeader("Authorization", "Bearer " . $this->token);
        $request->addHeader("Accept", "application/json");
        $request->addHeader("Content-Type", "application/x-www-form-urlencoded");
        $request->strictSSL(false);
        $response = $request->send();

        if($response->code==400){
            $scope=str_replace(" ", "%20", $scope);
            $request = \Httpful\Request::get($this->resourceHost . '/?scope=' . $scope);
            $request->addHeader("Authorization", "Bearer " . $this->token);
            $request->addHeader("Accept", "application/json")->addHeader('Content-Type', 'application/json');

            $response = $request->send();
        }

        $this->dataJSON = $response->body;

        return $this->dataJSON;
    }

    function login() {
        header('Location: ' . $this->getLoginURL());
        exit();
    }

    function logout($session) {
        $params = array('sessionid'=>$session
        ,'client_id'=>$this->clientID,
            'client_secret'=>$this->secret
        );
    }

    function create_or_replace_user($resultado){
        // Adiciona usario se necessario e obtem o id de usuario do moodle
        global $DB, $CFG;

        if (strpos($resultado->email,'@')===FALSE) {
            $this->login();
        }

        if (!$resultado->cpf && !$resultado->receita_federal->cpf) {
            throw new Exception("Usuário sem CPF", 1);            
        }



        if (is_numeric($resultado->cpf)) {
            $user = $DB->get_record('user', array('username' => $resultado->cpf, 'suspended' => 0, 'deleted' => 0));
        }
        if (!$user) {
            $user = $DB->get_record('user', array('email' => $resultado->email, 'suspended' => 0, 'deleted' => 0));        
        }

        if (!$user) {
            $user = new stdClass();
        }

        // Adiciona o CPF como username e adiciona os campos de city, email e auth(provedor)
        if (isset($resultado->receita_federal->cpf)) {
            $user->username = $resultado->receita_federal->cpf;
            $user->city = $resultado->receita_federal->municipio . '/' . $resultado->receita_federal->uf;
        } elseif (is_numeric($resultado->cpf) || !$user->username) {
            $user->username = $resultado->cpf;
        }
        $user->email = $resultado->email;
        $user->auth = 'suap';


        if (isset($resultado->receita_federal->nome)) {
            $user->firstname = strtok($resultado->receita_federal->nome, " ");
            $user->lastname = strtok(NULL);
        } elseif ($resultado->name) {
            $user->firstname = strtok($resultado->name, " ");
            $lastname = strtok(NULL);
            if ($lastname) {
                $user->lastname = $lastname;
            }
        }
        if (!$user->lastname) {
            $user->lastname = " ";
        }


        if (!isset($user->id)) {
            $user->confirmed = 1;
            $user->mnethostid = 1;
            $user->country = 'BR';
            $user->lang = 'pt_br';
            $user->timemodified = time();
            $user->timecreated = time();
            $user->id = $DB->insert_record('user', $user, $returnid=true);
        } else {
            $DB->update_record("user", $user);
        }

        $suap_user = $DB->get_record('auth_suap_data', ['userid'=>$user->id]);
        if (!$suap_user) {
            $suap_user = new stdClass();
            $suap_user->userid = $user->id;
            $suap_user->id = $DB->insert_record('auth_suap_data', $suap_user, $returnid=true);
        }
        if (isset($resultado->receita_federal->nome)) {
            $suap_user->nome_civil_validado_rfb = true;
            $suap_user->nome_civil = $resultado->receita_federal->nome;
        } else {
            $suap_user->nome_civil_validado_rfb = false;
            $suap_user->nome_civil = $resultado->name;
        }
        $suap_user->cpf = $resultado->cpf;
        $suap_user->sexo = $resultado->receita_federal->sexo;
        $suap_user->uf = strtoupper($resultado->receita_federal->uf);
        $suap_user->municipio = strtoupper($resultado->receita_federal->municipio);
        $suap_user->data_nascimento = strtotime($resultado->receita_federal->dtNascimento);
        $suap_user->nome_mae = $resultado->receita_federal->mae;
        $suap_user->avatar = $this->save_user_icon($user->id, $resultado->avatar);

        return $suap_user;
    }

    function validate_token() {
        $retorno = null;
        //Verifica se a variavel 'code' foi passada
        
        if ($_GET['code']) {
            // (variavel passada quando o provedor retorna a autorizacao de acesso para obter o token)
            $retorno = $this->makeToken($_GET['code']);
        } elseif ($_POST['token']) {
            //Verifica se a variavel 'token' ja foi passada (O token ja foi criado)
            $retorno = $this->makeTokenRefresh($_POST['token']);
        } elseif ($_POST['sessionid']) {
            //Verifica se a variavel de id de sessao foi passada
            $retorno = $this->makeTokenSessid($_POST['sessionid']);
            if ($retorno->refresh_token) {
                setcookie('token', $retorno->refresh_token);
            }
        } elseif ($_POST['scope'] && $_POST['access_token']) {
            //Verifica se foi passado os escopos de acesso(informacoes a serem consumidas) e o token de acesso
            global $SESSION;
            $retorno = new stdClass();
            $retorno->access_token = $_POST['access_token'];
            $retorno->scope = $_POST['scope'];
            $SESSION->oauth_token = $_POST['access_token'];
            $suap->setTokenSes($retorno->access_token);
        } elseif ($_COOKIE['token']) {
            // Verifica se o cookie 'token' foi criado
            $retorno = $suap->makeTokenRefresh($_COOKIE['token']);
        }

        // Verifica se obteve o token de acesso e os escopos
        if ($retorno && isset($retorno->access_token) && isset($retorno->scope)) {
            //gravar dados na secao
            //requisicao para achar usuarios
            //Obtem informacoes do usuario (scopes)
            
            $resultado = $this->doRequest($retorno->scope);
            if ($resultado) {
                return $resultado;
            }
            $scope = str_replace(' cnes', '', $retorno->scope);
            $resultado = $this->doRequest($scope);
            if ($resultado) {
                return $resultado;
            }
        }
        throw new Exception("Erro de protocolo", 1);
    }


    function save_user_icon($userid, $url) {
        global $DB;
        return NULL;
        if (empty($url)) {
            return NULL;
        }
    
        // Se o arquivo não existe no SUAP não salvar
        $file_headers = @get_headers($url);
        if ($file_headers[0] == 'HTTP/1.1 404 Not Found') {
            return NULL;
        }
    
        // Adiciona o campo para imagem do suap
        $context = context_user::instance($userid, MUST_EXIST);
    
        // Apaga os icones antigos deste usuário
        $image = $DB->delete_records('files', ['contextid' => $context->id,
                                                'component' => 'user',
                                                'filearea' => 'icon',
                                                'itemid' => 0,
                                                'filepath' => '/']);
    
        // Criar o gerenciar de arquivos
        $fs = get_file_storage();
    
        // Baixa o ícone e salva em um arquivo
        $idTeste = $fs->create_file_from_url(['contextid' => $context->id,
                                                'component' => 'user',
                                                'filearea' => 'newicon',
                                                'itemid' => 0,
                                                'filepath' => '/'], 
                                                $url, 
                                                array('skipcertverify' => true));
    
        $iconfiles = $fs->get_area_files($context->id, 'user', 'newicon');
        if ( ($iconfiles) && count($iconfiles) == 2) {
            require_once("$CFG->libdir/gdlib.php");
            // Get file which was uploaded in draft area.
            foreach ($iconfiles as $file) {
                if (!$file->is_directory()) {
                    // Copy file to temporary location and the send it for processing icon.
                    if ($iconfile = $file->copy_content_to_temp()) {
                        // There is a new image that has been uploaded.
                        // Process the new image and set the user to make use of it.
                        // NOTE: Uploaded images always take over Gravatar.
                        $newpicture = (int) process_new_icon($context, 'user', 'icon', 0, $iconfile);
                        // Delete temporary file.
                        @unlink($iconfile);
                        // Remove uploaded file.
                        $fs->delete_area_files($context->id, 'user', 'newicon');
                        //atualize name
                        $DB->set_field('files', 'filename', $url, ['id' => $newpicture]);
                        $DB->set_field('user', 'picture', $newpicture, ['id' => $userid]);
                        return $url;
                    } else {
                        // Remove uploaded file.
                        $fs->delete_area_files($context->id, 'user', 'newicon');
                    }
                    break;
                }
            }
        }
        return NULL;
    }
    
    function save_extra_fields($suap_user) {
        AdicionaCampoInfo($suap_user->userid, "nome_civil_validado_rfb", $suap_user->nome_civil_validado_rfb);
        AdicionaCampoInfo($suap_user->userid, "nome_civil", $suap_user->nome_civil);
        AdicionaCampoInfo($suap_user->userid, "cpf", $suap_user->cpf);
        AdicionaCampoInfo($suap_user->userid, "sexo", $suap_user->sexo);
        AdicionaCampoInfo($suap_user->userid, "uf", $suap_user->uf);
        AdicionaCampoInfo($suap_user->userid, "municipio", $suap_user->municipio);
        AdicionaCampoInfo($suap_user->userid, "data_nascimento", $suap_user->data_nascimento);
        AdicionaCampoInfo($suap_user->userid, "nome_mae", $suap_user->nome_mae);
        AdicionaCampoInfo($suap_user->userid, "imagem_suap", $suap_user->avatar);
    }    
}