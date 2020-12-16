<?php
/**
 * Plugin upgrade helper functions are defined here.
 *
 * @package     auth_suap
 * @category    upgrade
 * @copyright   2020 Kelson Medeiros <kelsoncm@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/auth/suap/locallib.php');


function suap_save_course_custom_field($categoryid, $shortname, $name, $type='text', $configdata='{"required":"0","uniquevalues":"0","displaysize":50,"maxlength":250,"ispassword":"0","link":"","locked":"0","visibility":"0"}') {
    return get_or_create(
        'customfield_field', 
        ['shortname'=>$shortname], 
        ['categoryid' => $categoryid, 'name' => $name, 'type' => $type, 'configdata' => $configdata, 'timecreated'=>time(), 'timemodified'=>time(), 'sortorder'=>get_last_sort_order('customfield_field')]
    );
}


function suap_save_user_custom_field($categoryid, $shortname, $name, $datatype='text', $visible=1, $p1=NULL, $p2=NULL) {
    return get_or_create(
        'user_info_field', 
        ['shortname'=>$shortname], 
        ['categoryid'=>$categoryid, 'name'=>$name, 'datatype'=>$datatype, 'visible'=>$visible, 'param1'=>$p1, 'param2'=>$p2]
    );
}


function suap_bulk_course_custom_field() {
    global $DB;
    $cid = get_or_create(
        'customfield_category', 
        ['name' => 'SUAP', 'component'=>'core_course', 'area'=>'course'], 
        ['sortorder'=>get_last_sort_order('customfield_category'), 'itemid'=>0, 'contextid'=>1, 'descriptionformat'=>0, 'timecreated'=>time(), 'timemodified'=>time()]
    )->id;
    suap_save_course_custom_field($cid, 'campus_id', 'ID do campus');
    suap_save_course_custom_field($cid, 'campus_descricao', 'Descrição do campus');
    suap_save_course_custom_field($cid, 'campus_sigla', 'Sigla do campus');

    suap_save_course_custom_field($cid, 'curso_id', 'ID do curso');
    suap_save_course_custom_field($cid, 'curso_codigo', 'Código do curso');
    suap_save_course_custom_field($cid, 'curso_descricao', 'Descrição do curso');
    suap_save_course_custom_field($cid, 'curso_nome', 'Nome do curso');

    suap_save_course_custom_field($cid, 'turma_id', 'ID da turma');
    suap_save_course_custom_field($cid, 'turma_codigo', 'Código da turma');
    
    suap_save_course_custom_field($cid, 'diario_id', 'ID do diario');
    suap_save_course_custom_field($cid, 'diario_situacao', 'Situação do diario');
    suap_save_course_custom_field($cid, 'diario_descricao', 'Descrição do diario');
    suap_save_course_custom_field($cid, 'diario_descricao_historico', 'Descrição no histórico do diario');
    suap_save_course_custom_field($cid, 'diario_sigla', 'Sigla do diario');

    suap_save_course_custom_field($cid, 'polo_id', 'ID da polo');
    suap_save_course_custom_field($cid, 'polo_nome', 'Nome da polo');
    
    suap_save_course_custom_field($cid, 'disciplina_id', 'ID do disciplina');
    suap_save_course_custom_field($cid, 'disciplina_descricao_historico', 'Descrição do disciplina');
    suap_save_course_custom_field($cid, 'disciplina_sigla', 'Sigla do diario');
    suap_save_course_custom_field($cid, 'disciplina_periodo', 'Período do disciplina');
    suap_save_course_custom_field($cid, 'disciplina_tipo', 'Tipo do disciplina');
    suap_save_course_custom_field($cid, 'disciplina_optativo', 'Optativo do disciplina');
    suap_save_course_custom_field($cid, 'disciplina_qtd_avaliacoes', 'Quantidade de avaliações do disciplina');
}


function suap_bulk_user_custom_field() {
    global $DB;

    $cid = get_or_create('user_info_category', ['name' => 'SUAP'], ['sortorder'=>get_last_sort_order('user_info_category')])->id;

    suap_save_user_custom_field($cid, 'email_google_classroom', 'E-mail @escolar (Google Classroom');
    suap_save_user_custom_field($cid, 'email_academico', 'E-mail @academico (Microsoft)');

    suap_save_user_custom_field($cid, 'campus_id', 'ID do campus');
    suap_save_user_custom_field($cid, 'campus_descricao', 'Descrição do campus');
    suap_save_user_custom_field($cid, 'campus_sigla', 'Sigla do campus');

    suap_save_user_custom_field($cid, 'curso_id', 'ID do curso');
    suap_save_user_custom_field($cid, 'curso_codigo', 'Código do curso');
    suap_save_user_custom_field($cid, 'curso_descricao', 'Descrição do curso');
    suap_save_user_custom_field($cid, 'curso_nome', 'Nome do curso');

    suap_save_user_custom_field($cid, 'turma_id', 'ID da turma');
    suap_save_user_custom_field($cid, 'turma_codigo', 'Código da turma');
    
    suap_save_user_custom_field($cid, 'polo_id', 'ID da polo');
    suap_save_user_custom_field($cid, 'polo_nome', 'Nome da polo');
}
