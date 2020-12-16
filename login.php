<?php
if(file_exists('../../config.php')){
    require('../../config.php');
    global $CFG;
}
//Obtendo as configurasoes setadas para o plugin
$config_plugin =  get_config('auth/suap');
//Tratando as URL
$url_session = str_replace('https://desenvolvedores','http://api',$config_plugin->apiurl);
$url_login =str_replace('desenvolvedores','login',$config_plugin->apiurl);
?>
<html>
<head>
    <script type="text/javascript" src="./source/components/jquery.min.js"></script>
    <script>
        function createCookie(name,value,days) {
            if (days) {
                var date = new Date();
                date.setTime(date.getTime()+(days*24*60*60*1000));
                var expires = "; expires="+date.toGMTString();
            }
            else var expires = "";
            document.cookie = name+"="+value+expires+"; path=/";
        }
        function getCookie(cname) {
            var name = cname + "=";
            var ca = document.cookie.split(';');
            for(var i = 0; i <ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0)==' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length,c.length);
                }
            }
            return "";
        }
        function CompleteLogin(data){
            console.log(data);
            $.ajax({
                url: '<?php echo $CFG->wwwroot.'/auth/suap/callback.php'?>',
                data:data,
                dataType: "json",
                type:'POST',
                success: function(data) {
                    if(data.urltogo){
                        if(window.opener){
                            window.opener.location= data.urltogo;
                            window.close();
                        }
                        else{
                            window.location = data.urltogo;
                        }
                    }
                    else{
                        window.location = '<?php echo $CFG->wwwroot.'/auth/suap/redirect.php'?>'
                    }
                },
                error:function(data){
                    window.location = '<?php echo $CFG->wwwroot.'/auth/suap/redirect.php'?>'
                }
            })
        }
       function login_via_cookie(){
            $.getScript('<?php echo $config_plugin->apiurl; ?>/base/setcookies/',function(){
                var dados_cookie = getCookie('__suap');
                if(dados_cookie){
                    var dados_json = JSON.parse(dados_cookie);
                }

                if(!dados_json){
                    window.location = '<?php echo $CFG->wwwroot.'/auth/suap/redirect.php'?>'
                }
                var session_id = dados_json['SID'];

                CompleteLogin({'sessionid': session_id});
            });
        }
        login_via_cookie();
        </script>
    </head>
    <body>
    </body>
</html>
