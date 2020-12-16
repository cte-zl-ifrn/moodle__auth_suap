<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/auth/suap/lib.php');
require_once($CFG->dirroot . '/auth/suap/classes/auth_suap.class.php');


// Cria a classe de autenticação com o SUAP
$suap = new auth_suap();

// Valida o token e obtém os dados do usuário no SUAP
$resultado = $suap->validate_token();

// Adiciona usario se necessario e obtem o id de usuario do moodle
$suap_user = $suap->create_or_replace_user($resultado);

// Salva os campos na tabela user_info_data
$suap->save_extra_fields($suap_user);

// Autentica usuario e redireciona para tela inicial
$user = authenticate_user_login($suap_user->cpf, NULL);
complete_user_login($user);

// O Moodle precisa redicionar este usuário para uma página específica?
$urlTogo = $CFG->wwwroot . '/my/';
if (isset($SESSION->wantsurl) && strpos($SESSION->wantsurl, '/auth/suap/') === FALSE) {
    $urlTogo = $SESSION->wantsurl;
}

if (count($_POST) == 0) {
    header("Location: {$urlTogo}", true, 302); // Se for GET
} else {    
    echo "{\"urltogo\":\"{$urlTogo}\"}"; // Se for POST
}
