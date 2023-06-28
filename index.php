<?php
require_once("settings.php");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <title>My Page</title>
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="//api.bitrix24.com/api/v1/"></script>
    <script src="script.js" data-value="<?=SERVER_PATH;?>"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&display=swap">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="box" id="1card" style="width: 400px;">
    <div class="box-child option-entity">
        <div class="block-name">
            <h3>Раздел:</h3>
            <div class="tooltip">
                <span>?</span>
                <div class="tooltiptext">В данном блоке вы можете переключаться между разделами для открытия окна настройки опрделенного нужного элемента</div>
            </div>
        </div>
        <hr>
    </div>
    <div class="box-child-options-place card1-options">
        <div class="option-for-choose active">
            <div class="option-for-choose-header">Настройка таблиц для выгрузки</div>
            <div class="option-for-choose-content">
                <a href="#block1" ><div class="option-for-choose-options">Группы</div></a>
                <a href="#block2" ><div class="option-for-choose-options">Справочники</div></a>
                <a href="#block3" ><div class="option-for-choose-options">Категории сделок</div></a>
                <a href="#block4" ><div class="option-for-choose-options">Отделы</div></a>
                <a href="#block5" ><div class="option-for-choose-options">Пользователи</div></a>
                <a href="#block6" ><div class="option-for-choose-options">Контакты</div></a>
                <a href="#block7" ><div class="option-for-choose-options">Задачи</div></a>
                <a href="#block8" ><div class="option-for-choose-options">Компании</div></a>
                <a href="#block9" ><div class="option-for-choose-options">Сделки</div></a>
                <a href="#block10"><div class="option-for-choose-options">Лиды</div></a>
                <a href="#block11"><div class="option-for-choose-options">Звонки</div></a>
                <a href="#block12"><div class="option-for-choose-options">Счета</div></a>
                <a href="#block16"><div class="option-for-choose-options">Типы смарт-процессов</div></a>
                <a href="#block17"><div class="option-for-choose-options">Категории смарт-процессов</div></a>
                <a href="#block18"><div class="option-for-choose-options">Смарт-процессы</div></a>
                <a href="#block19"><div class="option-for-choose-options">Предложения</div></a>
                <a href="#block20"><div class="option-for-choose-options">Дела</div></a>
                <a href="#block21"><div class="option-for-choose-options">Каталоги</div></a>
                <a href="#block22"><div class="option-for-choose-options">Разделы каталога</div></a>
                <a href="#block23"><div class="option-for-choose-options">Товары</div></a>
                <a href="#block24"><div class="option-for-choose-options">Комментарии</div></a>
                <a href="#block25"><div class="option-for-choose-options">Рабочее время</div></a>
            </div>
        </div>
        <div class="option-for-choose active">
            <div class="option-for-choose-header">Прочее</div>
<!--            <div class="option-for-choose-content">-->
<!--                <a href="#block13"><div class="option-for-choose-options">Расходы на рекламу</div></a>-->
<!--                <a href="#block14"><div class="option-for-choose-options">Планы</div></a>-->
<!--                <a href="#block15"><div class="option-for-choose-options">Стратегический план</div></a>-->
<!---->
<!--                <a href="#block26"><div class="option-for-choose-options">Доступ</div></a>-->
<!--                <a href="#block27"><div class="option-for-choose-options">Статистика</div></a>-->
<!--                <a href="#block28"><div class="option-for-choose-options">Планы</div></a>-->
<!--                <a href="#block29"><div class="option-for-choose-options">База данных</div></a>-->
<!--                <a href="#block30"><div class="option-for-choose-options">Общие настройки</div></a>-->
<!--            </div>-->
        </div>
    </div>
</div>
<div class="box" id="2card">
    <div class="box-child block-local-options">
        <div class="block-name">
            <h3>Настройка:</h3>
            <div class="tooltip">
                <span>?</span>
                <div class="tooltiptext">В данном блоке отображается настройка выбранного элемента из раздела, вы можете настроить данные для выгрузки</div>
            </div>
        </div>
        <hr>
    </div>
    <div class="box-child-options-place card2-options">
        <div class="block-for-choosen-option" id="block1">
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="groups" type="date">
                    <input class="time-select to" id="groups" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_groups"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_groups" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block2" >
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="list" type="date">
                    <input class="time-select to" id="list" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_list"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_list" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block3" >
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="deal_category" type="date">
                    <input class="time-select to" id="deal_category" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_deal_category"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_deal_category" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block4" >
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="office" type="date">
                    <input class="time-select to" id="office" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_office"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_office" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block5" >
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="users" type="date">
                    <input class="time-select to" id="users" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_users"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_users" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block6" >
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="contacts" type="date">
                    <input class="time-select to" id="contacts" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_contacts"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_contacts" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block7" >
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="tasks" type="date">
                    <input class="time-select to" id="tasks" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_tasks"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_tasks" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block8" >
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="company" type="date">
                    <input class="time-select to" id="company" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_company"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_company" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block9" >
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="deals" type="date">
                    <input class="time-select to" id="deals" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_deals"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_deals" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block10">
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="leads" type="date">
                    <input class="time-select to" id="leads" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_leads"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_leads" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block11">
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="calls" type="date">
                    <input class="time-select to" id="calls" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_calls"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_calls" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block12">
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="invoice" type="date">
                    <input class="time-select to" id="invoice" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_invoice"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_invoice" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block13">
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="target"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="target" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block14">
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="plans"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="plans" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block15">
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="strategic_plan"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="strategic_plan" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block16">
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="smart_process_type" type="date">
                    <input class="time-select to" id="smart_process_type" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_smart_process_type"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_smart_process_type" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block17">
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="smart_process_category" type="date">
                    <input class="time-select to" id="smart_process_category" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_smart_process_category"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_smart_process_category" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block18">
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="smart_process" type="date">
                    <input class="time-select to" id="smart_process" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_smart_process"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_smart_process" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block19">
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="offers" type="date">
                    <input class="time-select to" id="offers" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_offers"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_offers" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block20">
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="activity" type="date">
                    <input class="time-select to" id="activity" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_activity"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_activity" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block21">
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="catalog" type="date">
                    <input class="time-select to" id="catalog" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_catalog"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_catalog" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block22">
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="catalog_type" type="date">
                    <input class="time-select to" id="catalog_type" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_catalog_type"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_catalog_type" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block23">
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="product" type="date">
                    <input class="time-select to" id="product" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_product"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_product" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block24">
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="comments" type="date">
                    <input class="time-select to" id="comments" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_comments"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_comments" >ОБНОВИТЬ</button>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block25">
            <div class="time-period">
                <p>Выберите за какой период нужно скачать данные</p>
                <div class="option-for-block">
                    <input class="time-select from" id="work_time" type="date">
                    <input class="time-select to" id="work_time" type="date">
                </div>
            </div>
            <div class="box-child-local-buttons-place">
                <button class="local-button button-loc-clear"  id="db_work_time"  onclick="confirm_local_options_remove(this.id)">ОЧИСТИТЬ</button>
                <button class="local-button button-loc-update" id="db_work_time" >ОБНОВИТЬ</button>
            </div>
        </div>

        <div class="block-for-choosen-option" id="block26">
            <div class="time-period">
                <p>Выберети пользователей, кому будет доступно приложение</p>
                <div class="option-for-block">
                    <select class="user-permission-select" multiple></select>
                </div>
            </div>
        </div>
        <div class="block-for-choosen-option" id="block27"></div>
        <div class="block-for-choosen-option" id="block28"></div>
        <div class="block-for-choosen-option" id="block29"></div>
        <div class="block-for-choosen-option" id="block30"></div>
    </div>
</div>
<div class="box" id="3card" style="width: 400px; margin-right: 0;">
    <div class="box-child block-local-img">
        <div class="block-name" id="img-logo">
            <a href="https://artsolution24.ru/" target="_blank"><img src="src/logo.png"></a>
        </div>
        <hr>
    </div>
    <div class="box-child-options-place card3-options">
        <div class="card3-options-name">
            <div class="local-conn">
                <img src="src/icons9.png">
            </div>
            <span class="span-name">База даных</span>
            <div class="tooltip-for-sql">
                <span>?</span>
                <div class="tooltiptext">Данные от вашей базы данных, укажите их в looker studio, копка обновить - сбрасывает настройки для подключения на стандартные от нашего сервера, так же вы можете в полях настроить данные для подключения к своей базе данных</div>
            </div>
        </div>
        <hr style="width: 150px; color: black">
        <div class="sql-info-row-block-top">
            <div class="sql-info-row">
                <div class="sql-info-content sql-info-name">IP адресс</div>
                <div class="sql-info-content sql-info-info db-ip"><input type="text" value=""></div>
            </div>
            <div class="sql-info-row">
                <div class="sql-info-content sql-info-name">Порт</div>
                <div class="sql-info-content sql-info-info db-port"><input type="text" value=""></div>
            </div>
            <div class="sql-info-row">
                <div class="sql-info-content sql-info-name">Имя</div>
                <div class="sql-info-content sql-info-info db-name"><input type="text" value=""></div>
            </div>
            <div class="sql-info-row">
                <div class="sql-info-content sql-info-name">Логин</div>
                <div class="sql-info-content sql-info-info db-user"><input type="text" value=""></div>
            </div>
            <div class="sql-info-row">
                <div class="sql-info-content sql-info-name">Пароль</div>
                <div class="sql-info-content sql-info-info db-pass"><input type="text" value=""></div>
            </div>
        </div>
        <div class="sql-info-row-block-bottom">
            <div class="sql-info-row">
                <div class="sql-info-content sql-option-update-name">Интервал</div>
                <div class="sql-option-update-block">
                    <div class="sql-option-update sql-option-button sql-option-button-left"><</div>
                    <div class="sql-option-update sql-option-param">24 ч</div>
                    <div class="sql-option-update sql-option-button sql-option-button-right">></div>
                </div>
            </div>
        </div>
    </div>
    <div class="box-child-buttons-place">
        <button class="button button-help">Помощь</button>
        <button class="button button-save">Сохранить</button>
    </div>
</div>

<div class="loader-container" style="display: none;">
    <div class="loader"></div>
</div>
<div class="error-container" style="display: none;">
    <div class="error"></div>
</div>
<div class="success-container" style="display: none;">
    <div class="success"></div>
</div>
<div class="help-container" style="display: none;">
    //сделать блок с QA
    //блок закрытия окна
    //блок с заданием своего вопроса и реализовать отправку его как сообщение в мессенджер/или предложить задать вопрос в мессенджере
</div>
</body>
</html>
