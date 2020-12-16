<?php
global $USER,$CFG;
// $config_plugin = get_config('auth/suap');
// $logout_url =str_replace('desenvolvedores','login',$config_plugin->apiurl);
$logout_url = "https://suap.ifrn.edu.br/logout";
?>
<html>
    <body>
        <p>Para sair completamente é necessário que você também <a href="<?php echo $logout_url ?>">saia do SUAP</a>.</p>
    </body>
</html>
