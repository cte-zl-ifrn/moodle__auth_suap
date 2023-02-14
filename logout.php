<?php
require_once('../../config.php');
$config = get_config('auth_suap');
$logout_url = "{$config->base_url}/accounts/logout/";
?>
<html>
    <head>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    </head>
    <body>
        <p style='text-align: center; margin-top: 2rem;'>Para sair completamente é necessário que você confirmar no botão abaixo.</p>
        <p style='text-align: center;'><a href="<?php echo $logout_url ?>" class='btn btn-primary'>Sair completamente</a></p>
        <p style='text-align: center;'>Você será encaminhado para a página de acesso ao SUAP.</p>
    </body>
</html>
