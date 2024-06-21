<?php

define('AJAX_SCRIPT', true);
define('REQUIRE_CORRECT_ACCESS', true);
define('NO_MOODLE_COOKIES', true);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../lib/externallib.php');
require_once("$CFG->dirroot/auth/suap/classes/Httpful/Bootstrap.php");
\Httpful\Bootstrap::init();

// Permições de CORS para requisições PREFLIGHT (ionic)
if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authentication");
    exit;
}

// Allow CORS requests.
header('Access-Control-Allow-Origin: *');

function validate_enabled_web_services() {
    global $DB, $CFG;

    if (!$CFG->enablewebservices) {
        throw new moodle_exception('enablewsdescription', 'webservice');
    }

    // Não pode se o serviço não existir e não estiver habilitado
    $service = $DB->get_record('external_services', array('shortname' => $_GET['service'], 'enabled' => 1));
    if (empty($service)) {
        throw new moodle_exception('servicenotavailable', 'webservice');
    }

    // This script is used by the mobile app to check that the site is available and web services
    // are allowed. In this mode, no further action is needed.
    if (optional_param('appsitecheck', 0, PARAM_INT)) {
        echo json_encode((object)['appsitecheck' => 'ok']);
        exit;
    }

    return $service;
}

function authenticate_service_caller() {
    $headers = getallheaders();

    // Verifica se o token de autenticação está no header
    $authentication_key = array_key_exists('Authentication', $headers) ? "Authentication": "authentication";
    if (!array_key_exists($authentication_key, $headers)) {
        throw new \Exception("Bad Request - Authentication not informed", 400);
    }

    // Recorta o token do header "Token ..."
    $token = substr($headers[$authentication_key], 6);

    $response = json_decode(
        \Httpful\Request::post(
            "http://login/api/v1/validate/",
            json_encode(["token" => $token]),
            \Httpful\Mime::JSON
        )->send()->raw_body
    );

    return $response->username;
}

function authenticate_user($username) {
    global $USER, $DB;

    // Verifica se o usuário necessita trocar a senha
    $username = trim(core_text::strtolower($username));
    if (is_restored_user($username)) {
        throw new moodle_exception('restoredaccountresetpassword', 'webservice');
    }

    // Não pode se o usuário não existir
    $USER = $DB->get_record("user", ["username" => $username]);
    if (empty($USER)) {
        throw new moodle_exception('invalidlogin');
    }
}

function authorize_user() {
    global $USER;

    // Não pode guest user
    if (isguestuser($USER)) {
        throw new moodle_exception('noguest');
    }

    // Não pode usuário que ainda não confirmaram a senha
    if (empty($USER->confirmed)) {
        throw new moodle_exception('usernotconfirmed', 'moodle', '', $USER->username);
    }

    // Para controlar: autorização
    $systemcontext = context_system::instance();

    // Não pode em mode de manutenção, exceto administradores
    $hasmaintenanceaccess = has_capability('moodle/site:maintenanceaccess', $systemcontext, $USER);
    if (!empty($CFG->maintenance_enabled) and !$hasmaintenanceaccess) {
        throw new moodle_exception('sitemaintenance', 'admin');
    }

    // let enrol plugins deal with new enrolments if necessary
    enrol_check_plugins($USER);

    // setup user session to check capability
    \core\session\manager::set_user($USER);

    $USER->site_admin = has_capability('moodle/site:config', $systemcontext, $USER->id);
}

function response_token($service) {
    $token = external_generate_token_for_current_user($service);

    // prod
    // echo json_encode(
    //     [
    //         "token" => $token->token,
    //         "privatetoken" => is_https() && !$USER->site_admin ? $token->privatetoken : null,
    //     ]
    // );

    // dev
    echo json_encode(
        [
            "token" => $token->token,
            "privatetoken" => !$USER->site_admin ? $token->privatetoken : null,
        ]
    );
    external_log_token_request($token);
}

$service = validate_enabled_web_services();
$username = authenticate_service_caller();
authenticate_user($username);
authorize_user();
response_token($service);
