<?php
require_once(__DIR__ . '/settings.php');
require_once(__DIR__ . '/crest.php');

$dir = __DIR__ . '/tmp/' . SERVER_PATH;
if(!file_exists($dir)){mkdir($dir, 0777, true);}
if (file_exists(($dir . "/settings.json"))){
    $result = array();
    file_put_contents($dir . "/settings.json", json_encode($result), true);
    unlink($dir . "/appsettings.json");
    file_put_contents($dir . "/database_conn.json", json_encode($result), true);
    file_put_contents($dir . "/database_conn_default.json", json_encode($result), true);
    //Или
    //unlink($dir . "/settings.json", 'a');
}

$result = CRest::installApp();

if($result['rest_only'] === false):?>
    <head>
        <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
        <script src="//api.bitrix24.com/api/v1/"></script>
        <?php if($result['install'] === true):?>
            <script>
                //ajax запрос на сервер дя настроек подклбчения базы данных, создания полььзователя и базы данных + настроек доступа
                $.ajax({
                    url: 'handler.php',
                    type: 'POST',
                    data: {
                        type: 'sql data base create',
                        path: '<?= SERVER_PATH; ?>'
                    },
                    success: function (response){
                        console.log(response);
                        response = JSON.parse(response);

                        //ajax запрос на сервер для сохранения настроек подключения к базе данных
                        $.ajax({
                            url: 'handler.php',
                            type: 'POST',
                            data: {
                                type: "save cfg for connect to sql",
                                password: response["password"],
                                user: response["user"],
                                db_name: response["db_name"],
                                host: response["host"],
                                port: response["port"],
                                path: '<?= SERVER_PATH;?>'
                            },
                            success: function () {
                                $.ajax({
                                    url: 'handler.php',
                                    type: 'POST',
                                    data: {
                                        type: 'crontab task update time',
                                        interval: 1440,
                                        path: '<?=SERVER_PATH;?>'
                                    },
                                    success: function () {
                                        BX24.init(function () {
                                            var auth_data = BX24.getAuth();
                                            console.log("Auth: ", auth_data);
                                            BX24.installFinish();
                                        });
                                    }
                                });
                            }
                        });

                        //сделать создание json cfg для настроек приложения application.json
                    }
                });
            </script>
        <?php endif;?>
    </head>
    <body>
    <?php if($result['install'] === true):?>
        installation has been finished

        Если окно не переключается - то просто обновите страницу
    <?php else:?>
        installation error
    <?php endif;?>
    </body>
<?php endif;
