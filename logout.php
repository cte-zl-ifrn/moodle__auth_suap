<?php
require_once('../../config.php');
$config = get_config('auth_suap');
\core\session\manager::init_empty_session();
$logout_url = "{$config->base_url}/logout/";
?>
<html>

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
</head>

<body>
    <p style='text-align: center; margin-top: 2rem;'>Para sair completamente é necessário que você confirmar no botão
        abaixo.
        <span style='text-align: center; font-size: 90%; padding: 0 2rem; font-style: italic; display: block;'>Assim
            você sairá do SUAP e será reencaminhado para a página de acesso ao SUAP.</span>
    </p>
    <p style='text-align: center;'><a href="<?php echo $logout_url ?>" class='btn btn-primary'>Confirmar saída</a></p>
    <p style='text-align: center; margin-top: 2rem;'>Ou você pode <a href="<?php echo $CFG->wwwroot; ?>">continuar
            conectado</a>.
    </p>
</body>

</html>