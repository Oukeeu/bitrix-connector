var time_switcher = 1440;
var time_list = [1, 15, 30, 60, 90, 120, 150, 180, 210, 240, 270, 300, 330, 360, 390, 420, 450, 480, 510, 540, 570, 600, 630, 660, 690, 720, 750,
    780, 810, 840, 870, 900, 930, 960, 990, 1020, 1050, 1080, 1110, 1140, 1170, 1200, 1230, 1260, 1290, 1320, 1350, 1380, 1410, 1440];
var time_dict = {};
var options_time_select_dict = {
    "groups" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "list" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "deal_category" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "office" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "users" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "contacts" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "tasks" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "company" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "deals" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "leads" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "calls" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "invoice" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "target" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "plans" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "strategic_plan" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "smart_process_type" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "smart_process_category" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "smart_process" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "offers" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "activity" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "catalog" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "catalog_type" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "product" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "comments" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "work_time" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
    },
    "absolut_options" : {
        "from": "1970-01-01",
        "to": "2099-01-01",
        "update_interval": 1440,
    },
};

for (var i = 0; i < time_list.length; i++) {
    var hours = Math.floor(time_list[i] / 60);
    var minutes = time_list[i] % 60;
    var description = "";

    if (hours > 0) {description += hours + " ч ";}
    if (minutes > 0) {description += minutes + " мин";}

    time_dict[time_list[i]] = description;
}
function confirm_local_options_upload(id, path_from_index){
    const loaderContainer = document.querySelector(".loader-container");
    const errorContainer = document.querySelector(".error-container");
    const successContainer = document.querySelector(".success-container");

    if (confirm("Вы уверены, что хотите выполнить обновление таблицы прямо сейчас?")) {
        loaderContainer.style.display = "block";

        $.ajax({
            url: 'handler.php',
            type: 'POST',
            data: {
                type: 'sql local update table',
                path: path_from_index,
                table_name: id,
            },
            success: function (response){
                setTimeout(() => {
                    loaderContainer.style.display = "none";

                    if (response === "success") {
                        successContainer.style.display = "block";

                        setTimeout(() => {
                            successContainer.style.display = "none";
                        }, 2000);
                    } else {
                        errorContainer.style.display = "block";

                        setTimeout(() => {
                            errorContainer.style.display = "none";
                        }, 2000);
                    }
                }, 1000);
            }
        });
    }
}
function confirm_local_options_remove(id){
    if (confirm("Вы уверены, что хотите выполнить скрипт?")) {
        //выполнить удаление таблицы базы данных
    }
}
function AddEventListenerOnSqlButtons(path_from_index){
    var button_min = document.querySelector(".sql-option-button-left");
    var button_max = document.querySelector(".sql-option-button-right");

    var button_drop = document.querySelector(".local-conn");

    button_min.addEventListener('click', function (){
        if (time_switcher >=1)
        {
            time_switcher--;
            UpdateValueForSqlTime(time_switcher);
        }
    });
    button_max.addEventListener('click', function (){
        if (time_switcher <= 48)
        {
            time_switcher++;
            UpdateValueForSqlTime(time_switcher);
        }
    });

    button_drop.addEventListener('click', function (){
        var db_host = document.querySelector(".db-ip input");
        var db_port = document.querySelector(".db-port input");
        var db_name = document.querySelector(".db-name input");
        var db_user = document.querySelector(".db-user input");
        var db_pass = document.querySelector(".db-pass input");

        $.ajax({
            url: "handler.php",
            type: "POST",
            data: {
                type: "get data from default sql conn cfg",
                path: path_from_index,
            },
            success: function (response){
                response = JSON.parse(response);

                db_host.value = response[path_from_index]['host'];
                db_port.value = response[path_from_index]['port'];
                db_name.value = response[path_from_index]['db_name'];
                db_user.value = response[path_from_index]['user'];
                db_pass.value = response[path_from_index]['password'];
            }
        });
    });
}
function UpdateValueForSqlTime(time_switcher){
    var time_panel = document.querySelector(".sql-option-param");
    time_panel.textContent = time_dict[time_list[time_switcher]];
}
function saveValidator(){
    var options_time_from = document.querySelectorAll(".from");
    var options_time_to = document.querySelectorAll(".to");

    var db_host = document.querySelector(".db-ip input");
    var db_port = document.querySelector(".db-port input");
    var db_name = document.querySelector(".db-name input");
    var db_user = document.querySelector(".db-user input");
    var db_pass = document.querySelector(".db-pass input");

    //выделяем заданные значения из каждого inpute date и дополняем dict для выгрузки в application.json

    options_time_from.forEach(option => {
        options_time_select_dict[option.id]["from"] = option.value;
    });

    options_time_to.forEach(option => {
        options_time_select_dict[option.id]["to"] = option.value;
    });


    if (db_host.value === ""){db_host.style.borderColor = "#f44336";}
    else{db_host.style.borderColor = "rgba(244,67,54,0)";}
    if (db_port.value === ""){db_port.style.borderColor = "#f44336";}
    else{db_port.style.borderColor = "rgba(244,67,54,0)";}
    if (db_name.value === ""){db_name.style.borderColor = "#f44336";}
    else{db_name.style.borderColor = "rgba(244,67,54,0)";}
    if (db_user.value === ""){db_user.style.borderColor = "#f44336";}
    else{db_user.style.borderColor = "rgba(244,67,54,0)";}
    if (db_pass.value === ""){db_pass.style.borderColor = "#f44336";}
    else{db_pass.style.borderColor = "rgba(244,67,54,0)";}

    if (db_host.value === "" || db_port.value === "" || db_name.value === "" || db_user.value === "" || db_pass.value === ""){return false;}
    else {return true;}
}
function saveInFile(path_from_index){
    $.ajax({
        url: "handler.php",
        type: "POST",
        data: {
            type: "application settings save in file",
            options: JSON.stringify(options_time_select_dict),
            path: path_from_index
        },
        success: function (response){
            if (response === "success"){
                alert('ok');
            }
        }
    })
}
function LoadAppSettings(path_from_index){

    var options_time_from = document.querySelectorAll(".from");
    var options_time_to = document.querySelectorAll(".to");
    var selector = document.querySelector(".user-permission-select");
    var interval = document.querySelector(".sql-option-param");

    var db_host = document.querySelector(".db-ip input");
    var db_port = document.querySelector(".db-port input");
    var db_name = document.querySelector(".db-name input");
    var db_user = document.querySelector(".db-user input");
    var db_pass = document.querySelector(".db-pass input");

    $.ajax({
        url: "handler.php",
        type: "POST",
        data: {
            type: "load data from app settings",
            path: path_from_index,
            default_app_settings: JSON.stringify(options_time_select_dict),
        },
        success: function (response){
            response = JSON.parse(response);

            options_time_from.forEach(option => {
                option.value = response[option.id]["from"];
            });
            options_time_to.forEach(option => {
                option.value = response[option.id]["to"];
            });
            options_time_select_dict = response;
            interval.textContent = time_dict[options_time_select_dict["absolut_options"]["update_interval"]];
            time_switcher = time_list.indexOf(options_time_select_dict["absolut_options"]["update_interval"]);
        }
    });
    $.ajax({
        url: "handler.php",
        type: "POST",
        data: {
            type: "load data from sql settings",
            path: path_from_index
        },
        success: function (response){
            response = JSON.parse(response);

            db_host.value = response[path_from_index]["host"];
            db_port.value = response[path_from_index]["port"];
            db_name.value = response[path_from_index]["db_name"];
            db_user.value = response[path_from_index]["user"];
            db_pass.value = response[path_from_index]["password"];
        }
    });

}
function AddEventListenerOnPanelButtons(path_from_index){
    const button = document.querySelector(".button-save");

    const loaderContainer = document.querySelector(".loader-container");
    const errorContainer = document.querySelector(".error-container");
    const successContainer = document.querySelector(".success-container");

    //привязка события по клику на кнопку сохранить
    button.addEventListener("click", () => {
        var interval = time_list[time_switcher];
        options_time_select_dict["absolut_options"]["update_interval"] = interval;

        //проверка на корректность и полноту введенных данных
        //сохранить настройки в cfg json

        if (saveValidator())
        {
            loaderContainer.style.display = "block";

            //ajax запрос для обновления или создания задачи крон
            $.ajax({
                url: "handler.php",
                type: "POST",
                data: {
                    interval: interval,
                    type: "crontab task update time",
                    path: path_from_index,
                },
                success: function (response) {
                    setTimeout(() => {
                        loaderContainer.style.display = "none";

                        if (response === "success") {
                            successContainer.style.display = "block";

                            setTimeout(() => {
                                successContainer.style.display = "none";
                            }, 2000);
                        } else {
                            errorContainer.style.display = "block";

                            setTimeout(() => {
                                errorContainer.style.display = "none";
                            }, 2000);
                        }
                    }, 1000);
                }
            });
            saveInFile(path_from_index);
        }
    });
}
function AddEventListenerOnLocalButtons(path_from_index) {
    var buttons = document.querySelectorAll(".button-loc-update");

    buttons.forEach(button => {
        button.addEventListener("click", () => {confirm_local_options_upload(button.id, path_from_index)});
    });
}
function AddEventListenerForOptionsForChoose() {
    const expandables = document.querySelectorAll('.option-for-choose');
    const options = document.querySelectorAll('.option-for-choose-options');

    expandables.forEach((expandable) => {
        const header = expandable.querySelector('.option-for-choose-header');
        const content = expandable.querySelector('.option-for-choose-content');

        header.addEventListener('click', () => {
            if (expandable.classList.contains('active')) {
                content.style.maxHeight = '0';
                expandable.classList.remove('active');
            } else {
                content.style.maxHeight = content.scrollHeight + 'px';
                expandable.classList.add('active');
            }
        });
    });

    options.forEach(option => {
       option.addEventListener('click', function (){
           options.forEach(option_bck => {
               if (option_bck.classList.contains('choosen-option')){
                   option_bck.classList.remove('choosen-option');
               }
           });
           option.classList.add('choosen-option');
       });
    });
}
// function UpdateUserPermissionSelector(){
//     //обновить select
//     var selector = document.querySelector(".user-permission-select");
//
//     BX24.callMethod('user.get', function (result){
//         result.forEach(user => {
//             selector.value += (user['first_name'] . user['last_name']);
//         });
//     });
// }

$(document).ready(function (){
    var script = document.querySelector('script[src="script.js"]');
    var path_from_index = script.getAttribute('data-value');

    // UpdateUserPermissionSelector();
    LoadAppSettings(path_from_index);

    AddEventListenerOnSqlButtons(path_from_index);
    AddEventListenerOnPanelButtons(path_from_index);
    AddEventListenerOnLocalButtons(path_from_index);
    AddEventListenerForOptionsForChoose();
});
