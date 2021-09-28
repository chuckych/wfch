$(function () { // DOM ready
    'use strict' // Enable ECMAScript "strict"
    let urlConfig = '../config.json?v=' + Date.now(); // URL to config.json
    function getConfig(url) { // Get config.json
        fetch(url) // Fetch config.json
            .then(response => response.json()) // Parse JSON
            .then(data => { // When done
                $('#api_url').val(data['api']['url']); // Set API URL
                $('#api_pass').val(data['api']['pass']); // Set API Password
                $('#api_user').val(data['api']['user']); // Set API Username
                $('#mssql_srv').val(data['mssql']['srv']); // Set MSSQL Server
                $('#mssql_db').val(data['mssql']['db']); // Set MSSQL Database
                $('#mssql_user').val(data['mssql']['user']); // Set MSSQL Username
                $('#mssql_pass').val(data['mssql']['pass']); // Set MSSQL Password 
                $('#logs_borrar_dias').val(data['borrarLogs']['dias']); // Set Logs Delete Days
                $('#ws_ip').val(data['webService']['url']); // Set Web Service URL
                $('#proxy_puerto').val(data['proxy']['port']); // Set Proxy Port
                $('#proxy_ip').val(data['proxy']['ip']); // Set Proxy IP
                function activeCheckbox(selector, value) { // Checkbox active
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

    let fecha = new Date(); // Fecha actual
    let urlLogs = '../logs/novedades/' + formatDate(fecha) + '_novedad.log'; // URL

    $("#form").bind("submit", function (e) { // Submit form
        e.preventDefault(); // Prevent default
        $.ajax({ // Ajax
            type: $(this).attr("method"), // Method
            url: $(this).attr("action"), // URL
            data: $(this).serialize(), // Data
            dataType: "json", // Data type
            beforeSend: function (data) { // Before send
                $("#submit").prop("disabled", true); // Disable button
                $("#submit").html("Guardar.."); // Change button text
                $('#spanRespuesta').html('') // Clear response
            },
            success: function (data) { // Success
                if (data.status == "ok") { // If status ok
                    $("#submit").prop("disabled", false); // Enable button
                    $("#submit").html("Guardar"); // Change button text
                    $('#spanRespuesta').html('<b>' + data.Mensaje + '</b>') // Set response
                    setTimeout(function () { // Timeout
                        $('#spanRespuesta').html('') // Clear response
                    }, 6000); // 6 seconds
                } else { // If status error
                    $("#submit").prop("disabled", false); // Enable button
                    $("#submit").html("Guardars"); // Change button text
                    $('#spanRespuesta').html('<span class="text-danger"><b>' + data.Mensaje + '</b></span>') // Set response
                    setTimeout(function () { // Timeout
                        $('#spanRespuesta').html('') // Clear response
                    }, 6000); // 6 seconds
                }
            },
            error: function () { // Error
                $("#submit").prop("disabled", false); // Enable button
                $("#submit").html("Guardar"); // Change button text
                $('#spanRespuesta').html('<span class="text-danger"><b>Error</b></span>') // Set response
                setTimeout(function () { // Timeout
                    $('#spanRespuesta').html('') // Clear response
                }, 6000); // 6 seconds
                getLog(urlLogs+'?v=' + Date.now()); // Get logs
            } // End error
        }); // End ajax
    }); // End submit form

    getConfig(urlConfig); // Get config.json

    $('#script').click(function (e) { // Ejecutar Script
        let intervalLogs = null // Interval logs
        e.preventDefault(); // Prevent default
        $.ajax({  // Ajax 
            type: 'GET', // Method
            url: "../", // URL
            data: "script=true", // Data
            beforeSend: function (data) { // Before send
                $("#script").prop("disabled", true); // Disable button
                $("#script").html("Ejecutando ......"); // Change button text   
                $('#spanRespuesta').html('Ejecutando script...') // Set response
                intervalLogs = setInterval(function () { // Set interval logs
                    getLog(urlLogs+'?v=' + Date.now()); // Get logs 
                }, 1000); // 1 second 
            },
            success: function (data) { // Success 
                clearInterval(intervalLogs) // Clear interval logs
                if (data.status == "ok") {
                    $("#script").prop("disabled", false);
                    $("#script").html("Ejecutar Script");
                    getLog(urlLogs+'?v=' + Date.now()); // Get logs
                    $('#spanRespuesta').html('')
                    $('#spanRespuesta').html('<b>' + data.Mensaje + '</b>')
                } else {
                    $("#script").prop("disabled", false);
                    $("#script").html("Ejecutar Script");
                    getLog(urlLogs+'?v=' + Date.now()); // Get logs
                    $('#spanRespuesta').html('')
                    $('#spanRespuesta').html('<span class="text-danger"><b>' + data.Mensaje + '</b></span>')
                }
            },
            error: function () { // Error
                clearInterval(intervalLogs) // Clear interval logs
                $("#script").prop("disabled", false);
                $("#script").html("Ejecutar Script");
                getLog(urlLogs+'?v=' + Date.now()); // Get logs
                $('#spanRespuesta').html('')
                $('#spanRespuesta').html('<span class="text-danger"><b>Error</b></span>')
            } // End error
        }); // End ajax
    }); // End Ejecutar Script

    function getLog(url) { // Get Log
        fetch(url)
            .then(async (data) => {
                if (data.ok) {
                    data = await data.text()
                    document.getElementById("offcanvasLogsLabel").innerHTML = url + `<span class="refreshLog ms-2 border btn btn-sm" style="cursor:pointer; font-size:0.8rem; color: #ADB6BA">Actualizar</span>`; // Set label
                    document.getElementById("contentCanva").innerHTML = data; // Set response
                    document.getElementById("verLogs").hidden = false; // Show button
                    $('.refreshLog').click(function () { // Actualizar Log
                        document.getElementById("contentCanva").innerHTML = ''; // Clear response
                        setTimeout(function () { // Timeout
                            getLog(urlLogs+'?v=' + Date.now()); // Get logs
                        }, 100); // 1 second
                    }); // End refresh log
                } else {
                    throw new Error(`(` + data.status + `) ` + data.statusText)
                }
            }).catch(function (err) { // Error
                document.getElementById("offcanvasLogsLabel").innerHTML = url; // Set label
                document.getElementById("contentCanva").innerHTML = err; // Set content
                document.getElementById("verLogs").hidden = true; // Hide button
                $('.refreshLog').click(function () { // Actualizar Log
                    document.getElementById("contentCanva").innerHTML = ''; // Clear response
                    setTimeout(function () { // Timeout
                        getLog(urlLogs+'?v=' + Date.now()); // Get logs
                    }, 100); // 1 second
                }); // End refresh log
            }); // End fetch
    } // End getLog

    function formatDate(date) { // Format date to (yyyymmdd)
        var d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();

        if (month.length < 2)
            month = '0' + month;
        if (day.length < 2)
            day = '0' + day;

        return [year, month, day].join('');
    }

    getLog(urlLogs+'?v=' + Date.now()); // Get logs

    let offcanvasLogs = document.getElementById('offcanvasLogs') // Offcanvas
    offcanvasLogs.addEventListener('shown.bs.offcanvas', function () { // Offcanvas shown
        getLog(urlLogs+'?v=' + Date.now()); // Get logs
    }) // End offcanvas shown
}); // End ready