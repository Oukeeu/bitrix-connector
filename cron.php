<?php
require_once (__DIR__."/settings.php");
const BATCH_COUNT = 50;//count batch 1 query
const TYPE_TRANSPORT = 'json';// json or xmlaa
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
function call($method, $params = [], $domain)
{
    $arPost = [
        'method' => $method,
        'params' => $params
    ];

    $result = callCurl($arPost, $domain);
    return $result;
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

if (isset($argv[1]) && isset($argv[2])) {
    $domain = $argv[1];
    $access_token = $argv[2];

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
    if (!$conn) {
        file_put_contents("/var/www/btx24conn/tmp/$domain/cron.txt", "connection failed" . "\n", FILE_APPEND);
        die();
    }

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
    //    $batch = array(
//        "db_groups" => array(
//            'method' => 'sonet_group.get',
//            'params' => array(),
//            ),
//        "db_list" => array(
//            'method' => 'crm.enum.fields',
//        'params' => array(
//                'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//            ),
//        ),
//        "db_deal_category" => array(
//            'method' => 'crm.dealcategory.list',
//        'params' => array(
//                'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//            ),
//        ),
//        "db_office" => array(
//            'method' => 'department.get',
//        'params' => array(
//                'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//            ),
//        ),
//        "db_users" => array(
//            'method' => 'user.get',
//        'params' => array(
//                'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//            ),
//        ),
//        "db_contacts" => array(
//            'method' => 'crm.contact.list',
//        'params' => array(
//                'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//        ),
//    ),
//        "db_tasks" => array(
//            'method' => 'tasks.task.list',
//        'params' => array(
//                'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//        ),
//    ),
//        "db_company" => array(
//            'method' => 'crm.company.list',
//        'params' => array(
//                'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//        ),
//    ),
//        "db_deals" => array(
//            'method' => 'crm.deal.list',
//    'params' => array(
//            'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//    ),
//    ),
//        "db_leads" => array(
//            'method' => 'crm.lead.list',
//            'params' => array(
//                    'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//            ),
//    ),
//        "db_calls" => array(
//            'method' => 'telephony.call.list',
//            'params' => array(
//                    'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//            ),
//    ),
//        "db_invoice" => array(
//            'method' => 'crm.invoice.all',
//        'params' => array(
//                'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//        ),
//    ),
//        "db_target" => array(
//            'method' => 'crm.adsrtg.list',
//        'params' => array(
//                'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//        ),
//    ),
//        "db_plans" => array(
//            'method' => 'crm.plan.list',
//        'params' => array(
//                'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//        ),
//    ),
//        "db_strategic_plan" => array(
//            'method' => 'crm.plan.category.list',
//        'params' => array(
//                'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//        ),
//    ),
//        "db_smart-process_type" => array(
//            'method' => 'bizproc.automation.gettypes',
//            'params' => array(
//                    'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//            ),
//    ),
//        "db_smart-process_category" => array(
//            'method' => 'bizproc.automation.categories',
//            'params' => array(
//                    'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//            ),
//    ),
//        "db_smart-process" => array(
//            'method' => 'bizproc.worflow.list',
//            'params' => array(
//                    'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//            ),
//    ),
//        "db_offers" => array(
//            'method' => 'crm.quote.list',
//            'params' => array(
//                    'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//            ),
//    ),
//        "db_activity" => array(
//            'method' => 'crm.activity.list',
//            'params' => array(
//                    'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//            ),
//    ),
//        "db_catalog" => array(
//            'method' => 'catalog.section.list',
//            'params' => array(
//                    'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//            ),
//    ),
//        "db_catalog_type" => array(
//            'method' => 'catalog.section.list',
//        'params' => array(
//                'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//        ),
//    ),
//        "db_product" => array(
//            'method' => 'catalog.product.list',
//        'params' => array(
//                'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//        ),
//    ),
//        "db_comments" => array(
//            'method' => 'log.comment.list',
//            'params' => array(
//                    'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//            ),
//    ),
//        "db_work_time" => array(
//            'method' => 'timeman.settings.get',
//            'params' => array(
//                    'order' => array('ID' => 'ASC'),
//                'filter' => array('CATEGORY_ID' => 0),
//                'select' => array('ID', 'TITLE', 'STAGE_ID'),
//            ),
//        )
//    );

    $result_batch = CallBatch($batch, $domain);
    if (isset($result_batch['error'])) {
        if ($result_batch['error'] === 'expired_token') {
            installApp($domain);
            $result_batch = CallBatch($batch, $domain);
        }
    }
    file_put_contents("/var/www/btx24conn/tmp/$domain/batch.txt", var_export($result_batch['result']['result'], true) . "\n", true);

    // Очистка таблиц в базе данных
    try {
        foreach (TABLES_LIST as $table) {
            try {
                $table_name = TABLES_LIST_RUS[$table];
                $sql = "DROP TABLE IF EXISTS `$table_name`";
                $query = mysqli_query($conn, $sql);
            } catch (Exception $e) {
                continue;
            }
        }
        foreach (TABLES_LIST as $table) {
            $result = $result_batch["result"]["result"][$table];
            switch ($table){
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
        }
    } catch (Exception $e) {
        file_put_contents("/var/www/btx24conn/tmp/$domain/cron.txt", $e->getMessage() . "\n" . $sql . "\n", FILE_APPEND);
    }
    //file_put_contents("/var/www/btx24conn/tmp/$domain/cron.txt", "full ok!" . "\n", FILE_APPEND);
}
?>