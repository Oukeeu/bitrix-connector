<?php
require_once (__DIR__."/settings.php");
require_once (__DIR__."/crest.php");
const BATCH_COUNT = 50;//count batch 1 query
const TYPE_TRANSPORT = 'json';// json or xmlaa
function sanitize_db_name($name) {
    // Удаляем все символы, кроме букв, цифр и знака подчеркивания
    $name = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
    // Убеждаемся, что название не начинается с цифры
    if (preg_match('/^[0-9]/', $name)) {
        $name = '_' . $name;
    }
    return $name;
}
function generateUsername($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $username = '';
    for ($i = 0; $i < $length; $i++) {
        $username .= $chars[rand(0, strlen($chars) - 1)];
    }
    // Проверяем, что имя пользователя не начинается с цифры
    while (preg_match('/^[0-9]/', $username)) {
        $username = substr_replace($username, $chars[rand(0, strlen($chars) - 1)], 0, 1);
    }
    return $username;
}
function generateMediumPassword() {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+-=';
    $password = '';
    for ($i = 0; $i < 10; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    // Добавляем случайный символ, отличный от букв
    $password .= $characters[rand(10, strlen($characters) - 1)];
    $password .= $characters[rand(10, strlen($characters) - 1)];

    return $password;
}

function callBatch($arData, $domain, $halt = 0)
{
    $arResult = [];
    if(is_array($arData))
    {
        $arDataRest = [];
        $i = 0;
        foreach($arData as $key => $data)
        {
            if(!empty($data[ 'method' ]))
            {
                $i++;
                if(BATCH_COUNT >= $i)
                {
                    $arDataRest[ 'cmd' ][ $key ] = $data[ 'method' ];
                    if(!empty($data[ 'params' ]))
                    {
                        $arDataRest[ 'cmd' ][ $key ] .= '?' . http_build_query($data[ 'params' ]);
                    }
                }
            }
        }
        if(!empty($arDataRest))
        {
            $arDataRest[ 'halt' ] = $halt;
            $arPost = [
                'method' => 'batch',
                'params' => $arDataRest
            ];
            $arResult = callCurl($arPost, $domain);
        }
    }
    return $arResult;
}
function callCurl($arParams, $domain)
{
    if(!function_exists('curl_init'))
    {
        return [
            'error'             => 'error_php_lib_curl',
            'error_information' => 'need install curl lib'
        ];
    }
    $arSettings = getAppSettings($domain);
    if($arSettings !== false)
    {
        if(isset($arParams[ 'this_auth' ]) && $arParams[ 'this_auth' ] == 'Y')
        {
            $url = 'https://oauth.bitrix.info/oauth/token/';
        }
        else
        {
            $url = $arSettings[ "client_endpoint" ] . $arParams[ 'method' ] . '.' . TYPE_TRANSPORT;
            if(empty($arSettings[ 'is_web_hook' ]) || $arSettings[ 'is_web_hook' ] != 'Y')
            {
                $arParams[ 'params' ][ 'auth' ] = $arSettings[ 'access_token' ];
            }
        }

        $sPostFields = http_build_query($arParams[ 'params' ]);

        try
        {
            $obCurl = curl_init();
            curl_setopt($obCurl, CURLOPT_URL, $url);
            curl_setopt($obCurl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($obCurl, CURLOPT_POSTREDIR, 10);
            curl_setopt($obCurl, CURLOPT_USERAGENT, 'Bitrix24 CRest PHP 1.36');
            if($sPostFields)
            {
                curl_setopt($obCurl, CURLOPT_POST, true);
                curl_setopt($obCurl, CURLOPT_POSTFIELDS, $sPostFields);
            }
            curl_setopt(
                $obCurl, CURLOPT_FOLLOWLOCATION, (isset($arParams[ 'followlocation' ]))
                ? $arParams[ 'followlocation' ] : 1
            );
            if(defined("C_REST_IGNORE_SSL") && C_REST_IGNORE_SSL === true)
            {
                curl_setopt($obCurl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($obCurl, CURLOPT_SSL_VERIFYHOST, false);
            }
            $out = curl_exec($obCurl);
            $info = curl_getinfo($obCurl);
            if(curl_errno($obCurl))
            {
                $info[ 'curl_error' ] = curl_error($obCurl);
            }
            if(TYPE_TRANSPORT == 'xml' && (!isset($arParams[ 'this_auth' ]) || $arParams[ 'this_auth' ] != 'Y'))//auth only json support
            {
                $result = $out;
            }
            else
            {
                $result = expandData($out);
            }
            curl_close($obCurl);

            if(!empty($result[ 'error' ]))
            {
                if($result[ 'error' ] == 'expired_token' && empty($arParams[ 'this_auth' ]))
                {
                    $result = GetNewAuth($arParams, $domain);
                }
                else
                {
                    $arErrorInform = [
                        'expired_token'          => 'expired token, cant get new auth? Check access oauth server.',
                        'invalid_token'          => 'invalid token, need reinstall application',
                        'invalid_grant'          => 'invalid grant, check out define C_REST_CLIENT_SECRET or C_REST_CLIENT_ID',
                        'invalid_client'         => 'invalid client, check out define C_REST_CLIENT_SECRET or C_REST_CLIENT_ID',
                        'QUERY_LIMIT_EXCEEDED'   => 'Too many requests, maximum 2 query by second',
                        'ERROR_METHOD_NOT_FOUND' => 'Method not found! You can see the permissions of the application: CRest::call(\'scope\')',
                        'NO_AUTH_FOUND'          => 'Some setup error b24, check in table "b_module_to_module" event "OnRestCheckAuth"',
                        'INTERNAL_SERVER_ERROR'  => 'Server down, try later'
                    ];
                    if(!empty($arErrorInform[ $result[ 'error' ] ]))
                    {
                        $result[ 'error_information' ] = $arErrorInform[ $result[ 'error' ] ];
                    }
                }
            }
            if(!empty($info[ 'curl_error' ]))
            {
                $result[ 'error' ] = 'curl_error';
                $result[ 'error_information' ] = $info[ 'curl_error' ];
            }

            return $result;
        }
        catch(Exception $e)
        {
            return [
                'error' => 'exception',
                'error_exception_code' => $e->getCode(),
                'error_information' => $e->getMessage(),
            ];
        }
    }

    return [
        'error'             => 'no_install_app',
        'error_information' => 'error install app, pls install local application '
    ];
}
function getAppSettings($domain)
{
    if(defined("C_REST_WEB_HOOK_URL") && !empty(C_REST_WEB_HOOK_URL))
    {
        $arData = [
            'client_endpoint' => C_REST_WEB_HOOK_URL,
            'is_web_hook'     => 'Y'
        ];
        $isCurrData = true;
    }
    else
    {
        $arData = getSettingData($domain);
        $isCurrData = false;
        if(
            !empty($arData[ 'access_token' ]) &&
            !empty($arData[ 'domain' ]) &&
            !empty($arData[ 'refresh_token' ]) &&
            !empty($arData[ 'application_token' ]) &&
            !empty($arData[ 'client_endpoint' ])
        )
        {
            $isCurrData = true;
        }
    }

    return ($isCurrData) ? $arData : false;
}
function GetNewAuth($arParams, $domain)
{
    $result = [];
    $arSettings = getAppSettings($domain);
    if($arSettings !== false)
    {
        $arParamsAuth = [
            'this_auth' => 'Y',
            'params'    =>
                [
                    'client_id'     => $arSettings[ 'C_REST_CLIENT_ID' ],
                    'grant_type'    => 'refresh_token',
                    'client_secret' => $arSettings[ 'C_REST_CLIENT_SECRET' ],
                    'refresh_token' => $arSettings[ "refresh_token" ],
                ]
        ];
        $newData = callCurl($arParamsAuth, $domain);
        if(isset($newData[ 'C_REST_CLIENT_ID' ]))
        {
            unset($newData[ 'C_REST_CLIENT_ID' ]);
        }
        if(isset($newData[ 'C_REST_CLIENT_SECRET' ]))
        {
            unset($newData[ 'C_REST_CLIENT_SECRET' ]);
        }
        if(isset($newData[ 'error' ]))
        {
            unset($newData[ 'error' ]);
        }
        if(setAppSettings($newData, $domain))
        {
            $arParams[ 'this_auth' ] = 'N';
            $result = callCurl($arParams, $domain);
        }
    }
    return $result;
}
function getSettingData($domain)
{
    $return = [];
    if(file_exists(__DIR__ . '/tmp/' . $domain . '/settings.json'))
    {
        $return = expandData(file_get_contents(__DIR__ . '/tmp/' . $domain . '/settings.json'));

        if(defined("C_REST_CLIENT_ID") && !empty(C_REST_CLIENT_ID))
        {
            $return['C_REST_CLIENT_ID'] = C_REST_CLIENT_ID;
        }
        if(defined("C_REST_CLIENT_SECRET") && !empty(C_REST_CLIENT_SECRET))
        {
            $return['C_REST_CLIENT_SECRET'] = C_REST_CLIENT_SECRET;
        }
    }
    return $return;
}
function expandData($data)
{
    $return = json_decode($data, true);
    return $return;
}
function setAppSettings($arSettings, $domain, $isInstall = false)
{
    $return = false;
    if(is_array($arSettings))
    {
        $oldData = getAppSettings($domain);
        if($isInstall != true && !empty($oldData) && is_array($oldData))
        {
            $arSettings = array_merge($oldData, $arSettings);
        }
        $return = setSettingData($arSettings, $domain);
    }
    return $return;
}
function setSettingData($arSettings, $domain)
{
    return  (boolean)file_put_contents(__DIR__ . '/tmp/' . $domain . '/settings.json', wrapData($arSettings));
}
function wrapData($data, $debag = false)
{
    $return = json_encode($data, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);

    if($debag)
    {
        $e = json_last_error();
        if ($e != JSON_ERROR_NONE)
        {
            if ($e == JSON_ERROR_UTF8)
            {
                return 'Failed encoding! Recommended \'UTF - 8\' or set define C_REST_CURRENT_ENCODING = current site encoding for function iconv()';
            }
        }
    }

    return $return;
}

function installApp($domain)
{
    $result = [
        'rest_only' => true,
        'install' => false
    ];

    if($_REQUEST['event'] == 'ONAPPINSTALL' && !empty($_REQUEST[ 'auth' ]))
    {
        $result['install'] = setAppSettings($_REQUEST[ 'auth' ], $domain, true);
    }
    elseif($_REQUEST['PLACEMENT'] == 'DEFAULT')
    {
        $result['rest_only'] = false;
        $result['install'] = setAppSettings(
            [
                'access_token' => htmlspecialchars($_REQUEST['AUTH_ID']),
                'expires_in' => htmlspecialchars($_REQUEST['AUTH_EXPIRES']),
                'application_token' => htmlspecialchars($_REQUEST['APP_SID']),
                'refresh_token' => htmlspecialchars($_REQUEST['REFRESH_ID']),
                'domain' => htmlspecialchars($_REQUEST['DOMAIN']),
                'client_endpoint' => 'https://' . htmlspecialchars($_REQUEST['DOMAIN']) . '/rest/',
            ],
            $domain,
            true
        );
    }

    setLog(
        [
            'request' => $_REQUEST,
            'result' => $result
        ],
        $domain,
        'installApp'
    );
    return $result;
}
function setLog($arData, $domain, $type = '')
{
    $return = false;
    if(!defined("C_REST_BLOCK_LOG") || C_REST_BLOCK_LOG !== true)
    {
        if(defined("C_REST_LOGS_DIR"))
        {
            $path = C_REST_LOGS_DIR;
        }
        else
        {
            $path = __DIR__ . '/tmp/' . $domain . '/logs/';
        }
        $path .= date("Y-m-d/H") . 'crest.php/';

        if (!file_exists($path))
        {
            @mkdir($path, 0775, true);
        }

        $path .= time() . '_' . $type . '_' . rand(1, 9999999) . 'log';
        if(!defined("C_REST_LOG_TYPE_DUMP") || C_REST_LOG_TYPE_DUMP !== true)
        {
            $jsonLog = wrapData($arData);
            if ($jsonLog === false)
            {
                $return = file_put_contents($path . '_backup.txt', var_export($arData, true));
            }
            else
            {
                $return = file_put_contents($path . '.json', $jsonLog);
            }
        }
        else
        {
            $return = file_put_contents($path . '.txt', var_export($arData, true));
        }
    }
    return $return;
}

switch ($_POST["type"]) {
    case "get data from default sql conn cfg":
        $result = file_get_contents(__DIR__ . "/tmp/" . $_POST["path"] . "/database_conn_default.json");

        echo $result;
        break;
    case "load data from app settings":
        $default_app_settings = $_POST['default_app_settings'];

        if (file_exists("/var/www/btx24conn/tmp/" . $_POST['path'] . "/appsettings.json")) {
            $result = file_get_contents("/var/www/btx24conn/tmp/" . $_POST['path'] . "/appsettings.json");
        } else {
            $result = $default_app_settings;
        }
        if (empty($result)) {
            $result = $default_app_settings;
        }

        echo $result;
        break;
    case "load data from sql settings":
        $result = file_get_contents("/var/www/btx24conn/tmp/" . $_POST['path'] . "/database_conn.json");

        echo $result;
        break;
    case "application settings save in file":
        $data_to_write = json_decode($_POST['options']);

        file_put_contents("/var/www/btx24conn/tmp/" . $_POST['path'] . "/appsettings.json", json_encode($data_to_write, JSON_FORCE_OBJECT));
        echo "success";

        break;
    case "crontab task update time":
        $interval = $_POST['interval'];
        $path = escapeshellarg($_POST['path']);

        $result = file_get_contents("/var/www/btx24conn/tmp/" . $_POST["path"] . "/settings.json");
        $result = json_decode($result, true);

        $access_token = escapeshellarg($result['access_token']);

        $command = "/usr/bin/php /var/www/btx24conn/cron.php $path $access_token";

        // получаем текущий список заданий
        $jobs = shell_exec('crontab -l');

        // проверяем, есть ли среди заданий строка с командой, которую нужно удалить
        if (strpos($jobs, $command) !== false) {

            // удаляем только эту строку
            $new_jobs = preg_replace('/.*' . preg_quote("/usr/bin/php /var/www/btx24conn/cron.php $path", '/') . '.*\n?/', '', $jobs);

            // устанавливаем новый список заданий
            shell_exec('echo "' . trim($new_jobs) . '" | crontab -');
        }

        // добавляем новую задачу
        shell_exec('(crontab -l; echo "*/' . $interval . ' * * * * ' . $command . '") | crontab -');

        $jobs = shell_exec('crontab -l');
        if (strpos($jobs, $command) !== false) {
            echo 'success';
        } else {
            echo 'error';
        }

        break;
    case "sql data base create":

        $SERVER_PATH = $_POST['path'];
        $host = SERVER_HOST;

        // Подключаемся к базе данных MySQL
        $db_host = 'localhost';
        $db_user_root = 'webuser';
        $db_pass_root = 'Nuspff122!@';
        $db_port = '3306';

        $db_user_web = generateUsername();
        $db_pass_web = generateMediumPassword();
        $db_name = sanitize_db_name($SERVER_PATH);

        $conn = mysqli_connect($db_host, $db_user_root, $db_pass_root);

        // Проверяем соединение
        if (!$conn) {
            echo("Connection failed: " . mysqli_connect_error());
            die();
        }

        // Проверяем существование базы данных
        try {
            mysqli_select_db($conn, $db_name);

            // Проверяем наличие пользователя в базе данных
            $sql = "SELECT COUNT(*) FROM mysql.user WHERE User = '$db_user_web'";
            $result = mysqli_query($conn, $sql);
            $count = mysqli_fetch_array($result)[0];

            // Если пользователь уже существует, изменяем его пароль на password из BX24.getAuth();
            if ($count > 0) {
                $sql = "ALTER USER '$db_user_web'@'%' IDENTIFIED BY '$db_pass_web'";
                if (!mysqli_query($conn, $sql)) {
                    echo("Error change password: " . mysqli_error($conn) . " \n");
                    die();
                }
            } else {
                // Создаем нового пользователя
                $sql = "CREATE USER '$db_user_web'@'%' IDENTIFIED BY '$db_pass_web'";
                if (!mysqli_query($conn, $sql)) {
                    echo("Error creating user: " . mysqli_error($conn) . " \n");
                    die();
                }
            }

            // Назначаем пользователю все права на базу данных
            $sql = "GRANT ALL PRIVILEGES ON $db_name.* TO '$db_user_web'@'%'";
            try{
                $query = mysqli_query($conn, $sql);
            }catch (Exception $e){
                file_put_contents(__DIR__."/tmp/".$SERVER_PATH."/error_grant.txt", $e->getMessage(), true);
            }

            // Выбираем созданную базу данных
            mysqli_select_db($conn, $db_name);
        } catch (Exception $e) {

            // Создаем новую базу данных
            $sql = "CREATE DATABASE " . $db_name;
            if (!mysqli_query($conn, $sql)) {
                echo("Error creating database: " . mysqli_error($conn) . " \n");
                die();
            }

            // Проверяем наличие пользователя в базе данных
            $sql = "SELECT COUNT(*) FROM mysql.user WHERE User = '$db_user_web'";
            $result = mysqli_query($conn, $sql);
            $count = mysqli_fetch_array($result)[0];

            // Если пользователь уже существует, изменяем его пароль на password из BX24.getAuth();
            if ($count > 0) {
                $sql = "ALTER USER '$db_user_web'@'%' IDENTIFIED BY '$db_pass_web'";
                if (!mysqli_query($conn, $sql)) {
                    echo("Error change password: " . mysqli_error($conn) . " \n");
                    die();
                }
            } else {
                // Создаем нового пользователя
                $sql = "CREATE USER '$db_user_web'@'%' IDENTIFIED BY '$db_pass_web'";
                if (!mysqli_query($conn, $sql)) {
                    echo("Error creating user: " . mysqli_error($conn) . " \n");
                    die();
                }
            }

            // Назначаем пользователю все права на базу данных
            $sql = "GRANT ALL PRIVILEGES ON $db_name.* TO '$db_user_web'@'%'";
            try{
                $query = mysqli_query($conn, $sql);
            }catch (Exception $e){
                file_put_contents(__DIR__."/tmp/".$SERVER_PATH."/error_grant.txt", $e->getMessage(), true);
            }
            // Выбираем созданную базу данных
            mysqli_select_db($conn, $db_name);
        }

        // Подключение к базе данных
        mysqli_select_db($conn, $db_name);

        //Таблицы
//        {
//            foreach (TABLES_LIST as $table_name)
//            {
//                switch ($table_name)
//                {
//                    case "groups":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "list":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "deal_category":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "office":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "users":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "contacts":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "tasks":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "company":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "deals":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "leads":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "calls":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "invoice":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "target":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "plans":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "strategic_plan":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "smart-process_type":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "smart-process_category":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "smart-process":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "offers":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "activity":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "catalog":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "catalog_type":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "product":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "comments":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                    case "work_time":
//                        $sql_fields = "(
//                            `id` INT(11) NOT NULL AUTO_INCREMENT,
//
//                            PRIMARY KEY (`id`)
//                        )";
//                        break;
//                }
//                $sql = "CREATE TABLE IF NOT EXISTS `$table_name` " . $sql_fields;
//
//                if (!mysqli_query($conn, $sql)) {
//                    file_put_contents(__DIR__ . '/tmp/' . $SERVER_PATH . '/error.txt', mysqli_error($conn), true);
//                    echo("Error creating table " . $table_name . ": " . mysqli_error($conn) . " \n");
//                    die();
//                }
//            }
//        }
        // Закрываем соединение с сервером MySQL
        mysqli_close($conn);

        $result = array(
            'password' => $db_pass_web,
            'user' => $db_user_web,
            'db_name' => $db_name,
            'host' => $_SERVER["SERVER_ADDR"],
            'port' => $db_port,
        );

        echo json_encode($result);

        break;
    case "save cfg for connect to sql":
        $db_pass_web = $_POST["password"];
        $db_user_web = $_POST["user"];
        $db_name = $_POST["db_name"];
        $db_host = $_POST["host"];
        $db_port = $_POST["port"];
        $path = $_POST["path"];

        $result = array(
            $path => array(
                'password' => $db_pass_web,
                'user' => $db_user_web,
                'db_name' => $db_name,
                'host' => $db_host,
                'port' => $db_port,
            ),
        );

        file_put_contents(__DIR__ . '/tmp/' . $path . '/database_conn.json', json_encode($result, JSON_FORCE_OBJECT));
        file_put_contents(__DIR__ . '/tmp/' . $path . '/database_conn_default.json', json_encode($result, JSON_FORCE_OBJECT));

        echo "success";
        break;
    case "sql local update table":
        $domain = $_POST['path'];
        $table = $_POST['table_name'];

        $sql_connect_data = file_get_contents("/var/www/btx24conn/tmp/$domain/database_conn.json");
        $dates = file_get_contents("/var/www/btx24conn/tmp/$domain/appsettings.json");
        $settings = file_get_contents("/var/www/btx24conn/tmp/$domain/settings.json");
        $sql_connect_data = json_decode($sql_connect_data, true);
        $dates = json_decode($dates, true);
        $ip_server = file_get_contents('http://ifconfig.me/ip');

        $db_host = $sql_connect_data[$domain]['host'];
        if ($db_host === $ip_server) {
            $db_host = "localhost";
        }
        $db_name = $sql_connect_data[$domain]['db_name'];
        $db_user = $sql_connect_data[$domain]['user'];
        $db_password = $sql_connect_data[$domain]['password'];


        $conn = mysqli_connect($db_host, $db_user, $db_password, $db_name);

        try {
            $batch = array(
                "db_groups" => array(
                    'method' => 'sonet_group.get',
                    'ORDER' => array(
                        'NAME' => 'ASC',
                    ),
                    'params' => array(),
                ),
                "db_list" => array(
                    'method' => 'crm.status.list',
                    'params' => array(),
                ),
                "db_deal_category" => array(
                    'method' => 'crm.category.list',
                    'params' => array(
                        'entityTypeId' => 2,
                    ),
                ),
                "db_office" => array(
                    'method' => 'department.get',
                    'params' => array(),
                ),
                "db_users" => array(
                    'method' => 'user.get',
                    'params' => array(),
                ),
                "db_contacts" => array(
                    'method' => 'crm.contact.list',
                    'params' => array(
                        'filter' => array(
                            '>DATE_CREATE' => $dates["contacts"]["from"], // дата начала периода
                            '<DATE_CREATE' => $dates["contacts"]["to"], // дата конца периода
                        ),
                    ),
                ),
                "db_tasks" => array(
                    'method' => 'tasks.task.list',
                    'params' => array(
                        'filter' => array(
                            '>createdDate' => $dates["tasks"]["from"], // дата начала периода
                            '<createdDate' => $dates["tasks"]["to"], // дата конца периода
                        ),
                    ),
                ),
                "db_company" => array(
                    'method' => 'crm.company.list',
                    'params' => array(
                        'filter' => array(
                            '>DATE_CREATE' => $dates["company"]["from"], // дата начала периода
                            '<DATE_CREATE' => $dates["company"]["to"], // дата конца периода
                        ),
                    ),
                ),
                "db_deals" => array(
                    'method' => 'crm.deal.list',
                    'params' => array(
                        'filter' => array(
                            '>DATE_CREATE' => $dates["deals"]["from"], // дата начала периода
                            '<DATE_CREATE' => $dates["deals"]["to"], // дата конца периода
                        ),
                        'order' => array(
                            'DATE_CREATE' => 'ASC',
                        ),
                    ),
                ),
                "db_leads" => array(
                    'method' => 'crm.lead.list',
                    'params' => array(
                        'filter' => array(
                            '>DATE_CREATE' => $dates["leads"]["from"], // дата начала периода
                            '<DATE_CREATE' => $dates["leads"]["to"], // дата конца периода
                        ),
                        'order' => array(
                            'DATE_CREATE' => 'ASC',
                        ),
                    ),
                ),
                "db_calls" => array(
                    'method' => 'voximplant.statistic.get',
                    'params' => array(
                        'filter' => array(
                            '>CALL_START_DATE' => $dates["calls"]["from"], // дата начала периода
                            '<CALL_START_DATE' => $dates["calls"]["to"], // дата конца периода
                        ),
                    ),
                ),
                "db_invoice" => array(
                    'method' => 'crm.item.list',
                    'params' => array(
                        'entityTypeId' => 31,
                        'filter' => array(
                            '>DATE_CREATE' => $dates["invoice"]["from"], // дата начала периода
                            '<DATE_CREATE' => $dates["invoice"]["to"], // дата конца периода
                        ),
                    ),
                ),
                "db_smart_process_type" => array(
                    'method' => 'crm.type.list',
                    'params' => array(
                        'filter' => array(),
                    ),
                ),
                "db_smart_process_category" => array(
                    'method' => 'crm.category.list',
                    'params' => array(
                        'entityTypeId' => 4,
                        'filter' => array(
                            '>DATE_CREATE' => $dates["smart_process_category"]["from"], // дата начала периода
                            '<DATE_CREATE' => $dates["smart_process_category"]["to"], // дата конца периода
                        ),
                    ),
                ),
                "db_smart_process" => array(
                    'method' => 'crm.item.list',
                    'params' => array(
                        'entityTypeId' => 128,
                        'filter' => array(
                            '>DATE_CREATE' => $dates["smart_process"]["from"], // дата начала периода
                            '<DATE_CREATE' => $dates["smart_process"]["to"], // дата конца периода
                        ),
                    ),
                ),
                "db_offers" => array(
                    'method' => 'crm.product.list',
                    'params' => array(
                        'filter' => array(
                            '>DATE_CREATE' => $dates["offers"]["from"], // дата начала периода
                            '<DATE_CREATE' => $dates["offers"]["to"], // дата конца периода
                        ),
                    ),
                ),
                "db_activity" => array(
                    'method' => 'crm.activity.list',
                    'params' => array(
                        'filter' => array(
                            '>CREATED_DATE' => $dates["activity"]["from"], // дата начала периода
                            '<CREATED_DATE' => $dates["activity"]["to"], // дата конца периода
                        ),
                    ),
                ),
                "db_catalog" => array(
                    'method' => 'crm.catalog.list',
                    'params' => array(
                        'filter' => array(
                            '>DATE_CREATE' => $dates["catalog"]["from"], // дата начала периода
                            '<DATE_CREATE' => $dates["catalog"]["to"], // дата конца периода
                        ),
                    ),
                ),
                "db_catalog_type" => array(
                    'method' => 'crm.item.productrow.list',
                    'params' => array(
                        'filter' => array(
                            '>DATE_CREATE' => $dates["catalog_type"]["from"], // дата начала периода
                            '<DATE_CREATE' => $dates["catalog_type"]["to"], // дата конца периода
                        ),
                    ),
                ),
                "db_product" => array(
                    'method' => 'crm.product.list',
                    'params' => array(
                        'filter' => array(
                            '>DATE_CREATE' => $dates["product"]["from"], // дата начала периода
                            '<DATE_CREATE' => $dates["product"]["to"], // дата конца периода
                        ),
                    ),
                ),
                "db_comments" => array(
                    'method' => 'crm.timeline.comment.list',
                    'params' => array(),
                ),
                "db_work_time" => array(
                    'method' => 'timeman.timecontrol.reports.get',
                    'params' => array(
                        'filter' => array(
                            '>DATE_START' => $dates["work_time"]["from"], // дата начала периода
                            '<DATE_START' => $dates["work_time"]["to"], // дата конца периода
                        ),
                    ),
                ),
            );
        }catch (Exception $e){
            file_put_contents("/var/www/btx24conn/tmp/$domain/batch.txt", $e->getMessage() . "\n", true);
        }

        $result_batch = CallBatch($batch, $domain);
        if (isset($result_batch['error'])) {
            if ($result_batch['error'] === 'expired_token') {
                installApp($domain);
                $result_batch = CallBatch($batch, $domain);
            }
        }

        file_put_contents("/var/www/btx24conn/tmp/$domain/batch.txt", var_export($result_batch, true) . "\n", true);

        // Очистка таблиц в базе данных
        try {
            try {
                $table_name = TABLES_LIST_RUS[$table];
                $sql = "DROP TABLE IF EXISTS `$table_name`";
                $query = mysqli_query($conn, $sql);
            } catch (Exception $e) {
                continue;
            }

            $result = $result_batch["result"]["result"][$table];
            switch ($table) {
                case "db_tasks":
                    $result = $result_batch["result"]["result"][$table]['tasks'];
                    break;
                case "db_smart_process_type":
                    $result = $result_batch["result"]["result"][$table]['types'];
                    break;
                case "db_activity":
                    $result = $result_batch["result"]["result"][$table]['tasks'];
                    break;
                case "db_deal_category":
                    $result = $result_batch["result"]["result"][$table]['categories'];
                    break;
            }

            if (!empty($result)) {
                $table_name = TABLES_LIST_RUS[$table];
                $sql = "CREATE TABLE `$table_name` (";
                $temp_array = [];
                switch ($table) {
                    case "db_deal_category":
                    case "db_offers":
                    case "db_activity":
                        $sql .= "`S-номер сделки` VARCHAR(255),";
                        $sql .= "`S-наименование сделки` VARCHAR(255),";
                        $sql .= "`S-ответственный за сделку` VARCHAR(255),";
                        $sql .= "`S-дата создания сделки` VARCHAR(255),";

                        break;
                    case "db_contacts":
                    case "db_tasks":
                    case "db_company":
                        $sql .= "`S-номер лида` VARCHAR(255),";
                        $sql .= "`S-наименование лида` VARCHAR(255),";
                        $sql .= "`S-ответственный за лид` VARCHAR(255),";
                        $sql .= "`S-дата создания лида` VARCHAR(255),";

                        break;
                }
                foreach ($result as $key0 => $field0) {
                    if (is_array($field0)) {
                        foreach ($field0 as $key1 => $field1) {
                            if (is_array($field1)) {
                                foreach ($field1 as $key2 => $field2) {
                                    if (!in_array($key2, $temp_array)) {
                                        $temp_array[] = $key2;
                                        $column_name = $key2;

                                        if (!empty(COLUMNS_NAMES_DICT[$table_name][$key2])){
                                            $column_name = COLUMNS_NAMES_DICT[$table_name][$key2];
                                        }

                                        if (in_array($key2, RESERVED_WORDS_SQL)) {$column_name .= "_db";}
                                        if ($key2 === "description"){$sql .= "`$column_name` TEXT,";}
                                        else{$sql .= "`$column_name` VARCHAR(255),";}
                                    }
                                }
                            } else {
                                if (!in_array($key1, $temp_array)) {
                                    $temp_array[] = $key1;
                                    $column_name = $key1;

                                    if (!empty(COLUMNS_NAMES_DICT[$table_name][$key1])){
                                        $column_name = COLUMNS_NAMES_DICT[$table_name][$key1];
                                    }

                                    if (in_array($key1, RESERVED_WORDS_SQL)) {$column_name .= "_db";}
                                    if ($key1 === "description"){$sql .= "`$column_name` TEXT,";}
                                    else{$sql .= "`$column_name` VARCHAR(255),";}
                                }
                            }
                        }
                    } else {
                        if (!in_array($key0, $temp_array)) {
                            $temp_array[] = $key0;
                            $column_name = $key0;
                            if (!empty(COLUMNS_NAMES_DICT[$table_name][$key0])){
                                $column_name = COLUMNS_NAMES_DICT[$table_name][$key0];
                            }

                            if (in_array($key0, RESERVED_WORDS_SQL)) {$column_name .= "_db";}
                            if ($key0 === "description"){$sql .= "`$column_name` TEXT,";}
                            else{$sql .= "`$column_name` VARCHAR(255),";}
                        }
                    }
                }
                $sql = rtrim($sql, ",");
                $sql .= ")";

                mysqli_query($conn, $sql);

                foreach ($result as $row) {
                    $table_name = TABLES_LIST_RUS[$table];
                    $sql = "INSERT INTO `$table_name` (";
                    $values = "";

                    $temp_array = [];
                    switch ($table) {
                        case "db_deal_category": //?
                            $deal_call_value = call('crm.deal.get', array('id' => $row['id']), $domain);
                        case "db_offers":
                            $deal_call_value = call('crm.deal.get', array('id' => $row['ID']), $domain);
                        case "db_activity":
                            $deal_call_value = call('crm.deal.get', array('id' => $row['id']), $domain); //?

                            $sql .= "`S-номер сделки`,";
                            $sql .= "`S-наименование сделки`,";
                            $sql .= "`S-ответственный за сделку`,";
                            $sql .= "`S-дата создания сделки`,";

                            $user_call_value = call('user.get', array('filter' => array('ID' => $deal_call_value['result']['ASSIGNED_BY_ID'])), $domain);

                            $deal_call_value_id = $deal_call_value["result"]["ID"];
                            $deal_call_value_title = $deal_call_value["result"]["TITLE"];
                            $deal_call_value_user = $user_call_value["result"]["ID"];
                            $deal_call_value_date = $deal_call_value["result"]["DATE_CREATE"];

                            $values .= "'$deal_call_value_id',";
                            $values .= "'$deal_call_value_title',";
                            $values .= "'$deal_call_value_user',";
                            $values .= "'$deal_call_value_date',";

                            break;
                        case "db_contacts":
                            $lead_call_value = call('crm.lead.get', array('id' => $row['ID']), $domain);
                        case "db_tasks":
                            $lead_call_value = call('crm.lead.get', array('id' => $row['id']), $domain);
                        case "db_company":
                            $lead_call_value = call('crm.lead.get', array('id' => $row['ID']), $domain);

                            $sql .= "`S-номер лида`,";
                            $sql .= "`S-наименование лида`,";
                            $sql .= "`S-ответственный за лид`,";
                            $sql .= "`S-дата создания лида`,";

                            $user_call_value = call('user.get', array('ID' => $lead_call_value['result']['ASSIGNED_BY_ID']), $domain);

                            $lead_call_value_id = $lead_call_value["result"]["ID"];
                            $lead_call_value_title = $lead_call_value["result"]["TITLE"];
                            $lead_call_value_user = $lead_call_value["result"]["ID"];
                            $lead_call_value_date = $lead_call_value["result"]["DATE_CREATE"];

                            $values .= "'$lead_call_value_id',";
                            $values .= "'$lead_call_value_title',";
                            $values .= "'$lead_call_value_user',";
                            $values .= "'$lead_call_value_date',";

                            break;
                    }
                    foreach ($row as $key0 => $value0) {
                        if (is_array($value0)) {
                            foreach ($value0 as $key1 => $value1) {
                                if (is_array($value1)) {
                                    foreach ($value1 as $key2 => $value2) {
                                        if (!in_array($key2, $temp_array)) {
                                            $temp_array[] = $key2;
                                            $column_name = $key2;

                                            if (!empty(COLUMNS_NAMES_DICT[$table_name][$key2])) {
                                                $column_name = COLUMNS_NAMES_DICT[$table_name][$key2];
                                            }

                                            if (in_array($key2, RESERVED_WORDS_SQL)) {
                                                $column_name .= "_db";
                                            }
                                            $sql .= "`$column_name`,";
                                            $values .= "'$value2',";
                                        }
                                    }
                                } else {
                                    if (!in_array($key1, $temp_array)) {
                                        $temp_array[] = $key1;
                                        $column_name = $key1;

                                        if (!empty(COLUMNS_NAMES_DICT[$table_name][$key1])) {
                                            $column_name = COLUMNS_NAMES_DICT[$table_name][$key1];
                                        }

                                        if (in_array($key1, RESERVED_WORDS_SQL)) {
                                            $column_name .= "_db";
                                        }
                                        $sql .= "`$column_name`,";
                                        $values .= "'$value1',";
                                    }
                                }
                            }
                        } else {
                            if (!in_array($key0, $temp_array)) {
                                $temp_array[] = $key0;
                                $column_name = $key0;
                                if (!empty(COLUMNS_NAMES_DICT[$table_name][$key0])) {
                                    $column_name = COLUMNS_NAMES_DICT[$table_name][$key0];
                                }

                                if (in_array($key0, RESERVED_WORDS_SQL)) {
                                    $column_name .= "_db";
                                }
                                $sql .= "`$column_name`,";
                                $values .= "'$value0',";
                            }
                        }
                    }

                    $sql = rtrim($sql, ",");
                    $values = rtrim($values, ",");
                    $sql .= ") VALUES ($values)";

                    $query = mysqli_query($conn, $sql);
                }
            }
        } catch (Exception $e) {
            file_put_contents("/var/www/btx24conn/tmp/$domain/cron.txt", $e->getMessage() . "\n" . $sql . "\n", FILE_APPEND);

            echo "error";
            die();
        }
        //file_put_contents("/var/www/btx24conn/tmp/$domain/cron.txt", "full ok!" . "\n", FILE_APPEND);

        echo "success";

        break;
}
?>