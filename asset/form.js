$(function () {
    'use strict'
    let urlConfig = '../config.json?v=' + Date.now();
    function getConfig(url) {
        fetch(url)
            .then(response => response.json())
            .then(data => {
                // console.log(data);
                $('#api_url').val(data['api']['url']);
                $('#api_pass').val(data['api']['pass']);
                $('#api_user').val(data['api']['user']);
                $('#mssql_srv').val(data['mssql']['srv']);
                $('#mssql_db').val(data['mssql']['db']);
                $('#mssql_user').val(data['mssql']['user']);
                $('#mssql_pass').val(data['mssql']['pass']);
                $('#logs_borrar_dias').val(data['borrarLogs']['dias']);
                $('#ws_ip').val(data['webService']['url']);
                $('#proxy_puerto').val(data['proxy']['port']);
                $('#proxy_ip').val(data['proxy']['ip']);
                function activeCheckbox(selector, value) {
                    (value) ? $(selector).prop('checked', true) : $(selector).prop('checked', false);
                }
                activeCheckbox('#logs_conn_success', data['logConexion']['success']);
                activeCheckbox('#logs_conn_error', data['logConexion']['error']);
                activeCheckbox('#logs_nov_error', data['logNovedades']['error']);
                activeCheckbox('#logs_nov_success', data['logNovedades']['success']);
                activeCheckbox('#logs_borrar_estado', data['borrarLogs']['estado']);
                activeCheckbox('#proxy_estado', data['proxy']['enabled']);
            });
    }
    $("#form").bind("submit", function (e) {
        e.preventDefault();
        $.ajax({
            type: $(this).attr("method"),
            url: $(this).attr("action"),
            data: $(this).serialize(),
            // dataType: "json",
            beforeSend: function (data) {
                $("#submit").prop("disabled", true);
                $("#submit").html("Guardar..");
                $('#spanRespuesta').html('')
            },
            success: function (data) {
                if (data.status == "ok") {
                    $("#submit").prop("disabled", false);
                    $("#submit").html("Guardar");
                    $('#spanRespuesta').html('<b>' + data.Mensaje + '</b>')
                    setTimeout(function () {
                        $('#spanRespuesta').html('')
                    }, 6000);
                } else {
                    $("#submit").prop("disabled", false);
                    $("#submit").html("Guardar");

                    $('#spanRespuesta').html('<span class="text-danger"><b>' + data.Mensaje + '</b></span>')
                    setTimeout(function () {
                        $('#spanRespuesta').html('')
                    }, 6000);
                }
            },
            error: function () {
                $("#submit").prop("disabled", false);
                $("#submit").html("Guardar");

                $('#spanRespuesta').html('<span class="text-danger"><b>Error</b></span>')
                setTimeout(function () {
                    $('#spanRespuesta').html('')
                }, 6000);
            }
        });
    });

    getConfig(urlConfig);

    $('#script').click(function (e) {
        e.preventDefault();
        $.ajax({
            type: 'GET',
            url: "../",
            data: "script=true",
            dataType: "json",
            beforeSend: function (data) {
                $("#script").prop("disabled", true);
                $("#script").html("Ejecutando ......");
                $('#spanRespuesta').html('Ejecutando script...')
            },
            success: function (data) {
                if (data.status == "ok") {
                    $("#script").prop("disabled", false);
                    $("#script").html("Ejecutar Script");
                    $('#spanRespuesta').html('')
                    $('#spanRespuesta').html('<b>' + data.Mensaje + '</b>')
                } else {
                    $("#script").prop("disabled", false);
                    $("#script").html("Ejecutar Script");

                    $('#spanRespuesta').html('')
                    $('#spanRespuesta').html('<span class="text-danger"><b>' + data.Mensaje + '</b></span>')
                }
            },
            error: function () {
                $("#script").prop("disabled", false);
                $("#script").html("Ejecutar Script");

                $('#spanRespuesta').html('')
                $('#spanRespuesta').html('<span class="text-danger"><b>Error</b></span>')
            }
        });
    });
});