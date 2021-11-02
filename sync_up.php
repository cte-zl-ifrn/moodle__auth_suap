<?php
require_once('../../config.php');
// require_once('../../lib/coursecatlib.php');
require_once('../../course/lib.php');
require_once('../../user/lib.php');
require_once('../../group/lib.php');
require_once("../../enrol/locallib.php");
require_once("../../enrol/externallib.php");
require_once("../../enrol/externallib.php");
require_once(__DIR__.'/locallib.php');


function suap_sync_authenticate() {
    $sync_up_auth_token = get_config('auth_suap', 'sync_up_auth_token');

    if (!array_key_exists('Authentication', getallheaders())) {
        header("HTTP/1.1 400 Bad Request - Authentication not informed");
        echo "400 Bad Request - Authentication not informed";
        exit;
    }

    if ("Token $sync_up_auth_token" != getallheaders()['Authentication']) {
        header("HTTP/1.1 401 Unauthorized");
        echo "401 Unauthorized.";
        exit;
    }
}


function suap_sync_get_or_create_category($idnumber, $name, $parent){
    global $DB;

    $course_category = $DB->get_record('course_categories', ['idnumber'=>$idnumber]);
    if (empty($course_category)) {
        $course_category = \core_course_category::create(['name'=>$name, 'idnumber'=>$idnumber, $parent]);
    }

    return $course_category->id;
}


function suap_sync_get_or_create_category_hierarchy($data) {
    $diarios = suap_sync_get_or_create_category(
        get_config('auth_suap', 'category_diarios_idnumber') ?: 'diarios', 
        get_config('auth_suap', 'category_diarios_name') ?: 'Diários', 
        get_config('auth_suap', 'category_diarios_parent') ?: 0
    );

    $curso = suap_sync_get_or_create_category(
        $data->curso->codigo, 
        $data->curso->nome, 
        $diarios->id
    );

    $ano_periodo = substr($data->turma->codigo, 0, 4) . "." . substr($data->turma->codigo, 4, 1);
    $semestre = suap_sync_get_or_create_category(
        "{$data->curso->codigo}.{$ano_periodo}",
        $ano_periodo, 
        $curso->id
    );
    return $semestre->id;
}


function suap_sync_course($categoryid, $json){
    global $DB;

    $diario_code = "{$json->turma->codigo}.{$json->componente->sigla}";
    $course = $DB->get_record('course', ['idnumber'=>$diario_code]);
    if (!$course) {
        $data = (object) [
            "category"=>$categoryid,
            "fullname"=>$json->componente->descricao,
            "shortname"=>$diario_code,
            "idnumber"=>$diario_code,
            "visible"=>1,
            "enablecompletion"=>1,
            // "startdate"=>time(),
            "showreports"=>1,
            "enablecompletion"=>1,
            "completionnotify"=>1,
            
            "customfield_campus_id"=> $json->campus->id,
            "customfield_campus_descricao"=> $json->campus->descricao,
            "customfield_campus_sigla"=> $json->campus->sigla,

            "customfield_curso_id"=> $json->curso->id,
            "customfield_curso_codigo"=> $json->curso->codigo,
            "customfield_curso_descricao"=> $json->curso->descricao,
            "customfield_curso_nome"=> $json->curso->nome,

            "customfield_turma_id"=> $json->turma->id,
            "customfield_turma_codigo"=> $json->turma->codigo,

            "customfield_diario_id"=> $json->diario->id,
            "customfield_diario_situacao"=> $json->diario->situacao,
            "customfield_diario_descricao"=> $json->diario->descricao,
            "customfield_diario_descricao_historico"=> $json->diario->descricao_historico,
            "customfield_diario_sigla"=> $json->diario->sigla,

            #"customfield_polo_id"=> $json->polo->id,
            #"customfield_polo_nome"=> $json->polo->nome,

            "customfield_disciplina_id"=> $json->componente->id,
            "customfield_disciplina_descricao_historico"=> $json->componente->descricao_historico,
            "customfield_disciplina_sigla"=> $json->componente->sigla,
            "customfield_disciplina_periodo"=> $json->componente->periodo,
            "customfield_disciplina_tipo"=> $json->componente->tipo,
            "customfield_disciplina_optativo"=> $json->componente->optativo,
            "customfield_disciplina_qtd_avaliacoes"=> $json->componente->qtd_avaliacoes,
        ];
        $course = create_course($data);
    }
    return $course->id;
}


function suap_sync_user($user, $issuerid){
    global $DB;
    $username = property_exists($user, 'matricula') ? $user->matricula : $user->login;
    $status = property_exists($user, 'situacao') ? $user->situacao : $user->status;
    if (property_exists($user, 'matricula')) {
        $auth = 'aluno_auth';
    } else {
        if ($user->tipo == 'Principal') {
            $auth = 'principal_auth';
        } else {
            $auth = 'moderador_auth';
        }
    }

    $usuario = $DB->get_record("user", ["username" => $username]);
    $nome_parts = explode(' ', $user->nome);
    $lastname = array_pop($nome_parts);
    $firstname = implode(' ', $nome_parts);
    $common = [
        'lastname'=>$lastname,
        'firstname'=>$firstname,
        'auth'=>get_config('auth_suap', $auth),
        'email'=> !empty($user->email) ? $user->email : $user->email_secundario,
        'suspended'=>($status == 'ativo' ? 0 : 1),
    ];
    $insert_only = [
        'username'=>$username,
        'password'=>'!aA1' . uniqid(),
        'timezone'=>'99',
        'lang'=>'pt_br',
        'confirmed'=>1,
        'mnethostid'=>1,
    ];

    if (!$usuario) {
        $userid = user_create_user(array_merge($common, $insert_only));
    } else {
        $userid = $usuario->id;
        user_update_user(array_merge(['id'=>$userid], $common));
    }

    $default_user_preferences = preg_split('/\r\n|\r|\n/', get_config('auth_suap', 'default_user_preferences'));
    foreach ($default_user_preferences as $preference) {
        $parts = explode("=", $preference);
        create_or_update('user_preferences', ['userid'=>$userid, 'name'=>$parts[0]], ['value'=>$parts[1]]);
    }
    create_or_update
    (
        'auth_oauth2_linked_login', 
        ['userid'=>$userid, 'issuerid'=>$issuerid],
        ['username'=>$username, 'email'=> !empty($user->email) ? $user->email : $user->email_secundario, 'timecreated'=>time(), 'usermodified'=>0, 'confirmtoken'=>'', 'confirmtokenexpires'=>0, 'timemodified'=>time()],
        ['timemodified'=>time()]
    );
    
    return $userid;
}


function sync_suap_issuer() {
    return create_or_update
    (
        'oauth2_issuer', 
        ['name'=>'suap'],
        ['image'=>'https://ead.ifrn.edu.br/portal/wp-content/uploads/2020/08/SUAP.png', 
        'loginscopes'=>'identificacao email',
        'loginscopesoffline'=>'identificacao email documentos_pessoais',
        'baseurl'=>'',
        'loginparams'=>'',
        'loginparamsoffline'=>'',
        'alloweddomains'=>'',
        'enabled'=>1,
        'showonloginpage'=>1,
        'basicauth'=>0,
        'sortorder'=>0,
        'timecreated'=>time(),
        'timemodified'=>time(),
        'usermodified'=>2],
        ['requireconfirmation'=>0],
        ['clientid'=>'changeme',
        'clientsecret'=>'changeme']
    )->id;
}


function suap_sync_enrol($contextid, $userid, $enrolid, $roleid){
    $n = time();
    $user_enrolments = get_or_create(
        'user_enrolments',
        ['userid'=>$userid, 'enrolid'=>$enrolid],
        ['timecreated'=>$n, 'timemodified'=>$n, 'timestart'=>$n, 'timeend'=>0, 'modifierid'=>$userid]
    );

    $role_assignments = get_or_create(
        'role_assignments',
        ['userid'=>$userid, 'contextid'=>$contextid, 'roleid'=>$roleid],
        ['timemodified'=>$n, 'modifierid'=>$userid]
    );
}


function suap_sync_group($courseid, $userid, $polo) {
    global $DB;
    if (empty($polo)) {
        return;
    }
    $data = ['courseid' => $courseid, 'name' => $polo->nome];
    $group = $DB->get_record('groups', $data);
    if (!$group) {
        groups_create_group((object)$data);
        $group = $DB->get_record('groups', $data);
    }
    if (!$DB->get_record('groups_members', ['groupid' => $group->id, 'userid' => $userid])) {
        groups_add_member($group->id, $userid);
    }
}


function get_enrolment_config($courseid, $type) {
    $role_id = get_config('auth_suap', "{$type}_role_id");
    $enrol_type = get_config('auth_suap', "{$type}_enrol_type");
    $enrol_id = get_or_create(
        'enrol', 
        ['enrol'=>$enrol_type, 'courseid'=>$courseid, 'roleid'=>$role_id],
        ['timecreated'=>time(), 'timemodified'=>time()]
    )->id;
    return (object)['roleid'=>$role_id, 'enrol_type'=>$enrol_type, 'enrolid'=>$enrol_id];
}


function suap_sync_up() {
    global $CFG;

    try { 
        suap_sync_authenticate();

        # $json = json_decode(file_get_contents('sample.json'));
        $json = json_decode(file_get_contents('php://input'));
    
        $categoryid = suap_sync_get_or_create_category_hierarchy($json);
        $courseid = suap_sync_course($categoryid, $json);
        $context = context_course::instance($courseid);
    
        $issuerid = sync_suap_issuer();
    
        $principal_config = get_enrolment_config($courseid, 'principal');
        $moderador_config = get_enrolment_config($courseid, 'moderador');
        foreach ($json->professores as $professor) {
            $userid = suap_sync_user($professor, $issuerid);
            $conf = $professor->tipo == 'Principal' || $professor->tipo == 'Formador' ? $principal_config : $moderador_config;
            suap_sync_enrol($context->id, $userid, $conf->enrolid, $conf->roleid);
        }
    
        $aluno_config = get_enrolment_config($courseid, 'aluno');
        foreach ($json->alunos as $aluno) {
            $userid = suap_sync_user($aluno, $issuerid);
            suap_sync_enrol($context->id, $userid, $aluno_config->enrolid, $aluno_config->roleid);
            suap_sync_group($courseid, $userid, $aluno->polo);
        }
        
        echo json_encode(["url" => $CFG->wwwroot . "/course/view.php?id=" . $courseid]);
    } catch (Exception $ex) {
        echo json_encode(["error" => ["message" => $ex->getMessage()]]);
    }
}

suap_sync_up();
