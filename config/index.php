<?php
require __DIR__ . '../../func.php'; // Funciones
$datos = defaultConfigData(); // Datos por defecto
$rutaConfig = __DIR__ . '../../config.json'; // Path to config.json
$rutaInfo   = __DIR__ . "../../logs/info/" . date('Ymd') . "_informacion.log"; // Path to info log

$datosConfig = getDataJson($rutaConfig); // Obtenemos los datos del config.json
if ($datosConfig == false) : // Si no hay datos o no existe el archivo
    $datosConfig = fileLogsJson(json_encode($datos, JSON_PRETTY_PRINT), $rutaConfig, 'json'); // Creamos el archivo config.json
    fileLog("Se creo el archivo \"config.json\"", $rutaInfo, ''); // Creamos el archivo info.log
endif;

if ($_SERVER["REQUEST_METHOD"] == "POST") { // Check if the form has been submitted
    header('Content-Type: application/json'); // Set the header to return JSON
    $_POST['api_url']            = ($_POST['api_url']) ?? ''; // API URL
    $_POST['api_user']           = ($_POST['api_user']) ?? ''; // API USER
    $_POST['api_pass']           = ($_POST['api_pass']) ?? ''; // API PASS
    $_POST['mssql_srv']          = ($_POST['mssql_srv']) ?? ''; // MSSQL SERVER
    $_POST['mssql_db']           = ($_POST['mssql_db']) ?? ''; // MSSQL DATABASE
    $_POST['mssql_user']         = ($_POST['mssql_user']) ?? ''; // MSSQL USER
    $_POST['mssql_pass']         = ($_POST['mssql_pass']) ?? ''; // MSSQL PASS
    $_POST['ws_ip']              = ($_POST['ws_ip']) ?? ''; // WebService IP
    $_POST['proxy_ip']           = ($_POST['proxy_ip']) ?? ''; // PROXY IP
    $_POST['proxy_puerto']       = ($_POST['proxy_puerto']) ?? ''; // PROXY PUERTO
    $_POST['proxy_estado']       = ($_POST['proxy_estado']) ?? ''; // PROXY ESTADO
    $_POST['logs_conn_error']    = ($_POST['logs_conn_error']) ?? false; // LOGS CONNECTION ERROR
    $_POST['logs_conn_success']  = ($_POST['logs_conn_success']) ?? false; // LOGS CONNECTION SUCCESS
    $_POST['logs_nov_error']     = ($_POST['logs_nov_error']) ?? false; // LOGS NOVEDADES Error
    $_POST['logs_nov_success']   = ($_POST['logs_nov_success']) ?? false; // LOGS NOVEDADES Success
    $_POST['logs_borrar_estado'] = ($_POST['logs_borrar_estado']) ?? false; // LOGS BORRAR ESTADO
    $_POST['logs_borrar_dias']   = ($_POST['logs_borrar_dias']) ?? false; // LOGS BORRAR DIAS

    if (!$_POST['api_url'] || !$_POST['api_user'] || !$_POST['api_pass'] || !$_POST['mssql_srv'] || !$_POST['mssql_db'] || !$_POST['mssql_user'] || !$_POST['mssql_pass'] || !$_POST['ws_ip']) { // Validaciones
        $data = array('status' => 'Error', 'Mensaje' => 'Complete los campos requeridos'); // Mensaje
        echo json_encode($data); // Enviar datos
        exit; // Salir
    } else {
        $datos = array( // Datos a guardar
            'mssql' =>
            array(
                'srv'  => trim($_POST['mssql_srv']),
                'db'   => trim($_POST['mssql_db']),
                'user' => trim($_POST['mssql_user']),
                'pass' => trim($_POST['mssql_pass']),
            ),
            'logConexion' =>
            array(
                'success' => ($_POST['logs_conn_success']) ? true : false,
                'error'   => ($_POST['logs_conn_error']) ? true : false,
            ),
            'api' =>
            array(
                'url'  => trim($_POST['api_url']),
                'user' => trim($_POST['api_user']),
                'pass' => trim($_POST['api_pass']),
            ),
            'webService' =>
            array(
                'url' => trim($_POST['ws_ip']),
            ),
            'logNovedades' =>
            array(
                'success' => ($_POST['logs_nov_success'] == 'on') ? true : false,
                'error'   => ($_POST['logs_nov_error'] == 'on') ? true : false,
            ),
            'proxy' =>
            array(
                'ip'      => trim($_POST['proxy_ip']),
                'port'    => trim($_POST['proxy_puerto']),
                'enabled' => ($_POST['proxy_estado'] == 'on') ? true : false,
            ),
            'borrarLogs' =>
            array(
                'estado' => ($_POST['logs_borrar_estado'] == 'on') ? true : false,
                'dias'   => intval($_POST['logs_borrar_dias']),
            )
        ); // Datos a guardar
        $rutaConfig = __DIR__ . '../../config.json'; // Ruta del archivo
        $datosConfig = getDataJson($rutaConfig); // Obtenemos los datos del archivo
        if ($datosConfig == false) : // Si no hay datos o no existe el archivo
            $datosConfig = fileLogs(json_encode($datos, JSON_PRETTY_PRINT), $rutaConfig, 'json'); // Guardamos los datos
            fileLogs("Se creo el archivo \"config.json\"", __DIR__ . "../../logs/info/" . date('Ymd') . "_informacion.log", '');
        else : // Si ya existe el archivo
            $datosConfig = fileLogs(json_encode($datos, JSON_PRETTY_PRINT), $rutaConfig, 'json'); // Guardamos los datos
            fileLogs("Se actualizo el archivo \"config.json\"", __DIR__ . "../../logs/info/" . date('Ymd') . "_informacion.log", '');
        endif;
        $data = array('status' => 'ok', 'Mensaje' => 'Datos guardados corectamente.'); // Mensaje
        echo json_encode($data); // Enviar datos de respuesta del formulario
        exit; // Salir
    }
}
?>
<!doctype html> <!-- HTML5 -->
<html lang="es">
<!-- Lenguaje -->
<!-- Inicio HTML -->

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8"> <!-- Codificación -->
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- Escalable -->

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="..\asset\bootstrap.min.css"> <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="..\asset\style-min.css?v=<?= time() ?>"> <!-- Estilo CSS -->

    <title>Config WF-CH!</title> <!-- Título -->
</head>

<body class="p-3" style="background-color: #66615b;">
    <!-- Inicio body -->
    <div class="container p-1 shadow-lg" style="max-width: 900px; background: #66615b">
        <!-- Contenedor -->
        <form autocomplete=off class="p-4 bg-light" id="form" method="POST">
            <!-- Formulario -->
            <div class="row m-0">
                <!-- Fila -->
                <div class="col-12 px-0">
                    <!-- Columna -->
                    <p class="h5 p-0 m-0">Configuración de Script - Integración de Novedades Workflow / CH</p> <!-- Título -->
                </div> <!-- /Columna -->
            </div>
            <div class="row my-3">
                <!-- Fila -->
                <div class="col-12">
                    <!-- Columna -->
                    <p class="form-label">Datos API Workflow</p> <!-- Título -->
                </div> <!-- /Columna -->
                <div class="col-sm-6 col-12">
                    <!-- Columna -->
                    <div class="form-floating mb-1">
                        <!-- Grupo -->
                        <input type="text" class="form-control" id="api_url" name="api_url" placeholder="URL API" autocomplete=off> <!-- Campo -->
                        <label for="api_url">url (*)</label> <!-- Etiqueta -->
                    </div> <!-- /Grupo -->
                </div> <!-- /Columna -->
                <div class="col-sm-3 col-12">
                    <!-- Columna -->
                    <div class="form-floating mb-1">
                        <!-- Grupo -->
                        <input type="text" class="form-control req" id="api_user" name="api_user" placeholder="Usuario API" autocomplete=off> <!-- Campo -->
                        <label for="api_user">Usuario (*)</label> <!-- Etiqueta -->
                    </div> <!-- /Grupo -->
                </div> <!-- /Columna -->
                <div class="col-sm-3 col-12">
                    <!-- Columna -->
                    <div class="form-floating mb-1">
                        <!-- Grupo -->
                        <input type="password" class="form-control req" id="api_pass" name="api_pass" placeholder="Password API" autocomplete=off> <!-- Campo -->
                        <label for="api_pass">Password (*)</label> <!-- Etiqueta -->
                    </div> <!-- /Grupo -->
                </div> <!-- /Columna -->
            </div> <!-- /Fila -->
            <div class="row mb-3">
                <!-- Fila -->
                <div class="col-12">
                    <!-- Columna -->
                    <p class="form-label">Conexión MSSQL</p> <!-- Título -->
                </div> <!-- /Columna -->
                <div class="col-sm-3 col-12">
                    <!-- Columna -->
                    <div class="form-floating mb-1">
                        <!-- Grupo -->
                        <input type="text" class="form-control req" id="mssql_srv" placeholder="mssql_srvHelp" name="mssql_srv" autocomplete=off> <!-- Campo -->
                        <label for="mssql_srv">Servidor (*)</label> <!-- Etiqueta -->
                    </div> <!-- /Grupo -->
                </div> <!-- /Columna -->
                <div class="col-sm-3 col-12">
                    <!-- Columna -->
                    <div class="form-floating mb-1">
                        <!-- Grupo -->
                        <input type="text" class="form-control req" id="mssql_db" name="mssql_db" placeholder="mssql_dbHelp" autocomplete=off> <!-- Campo -->
                        <label for="mssql_db">Base de datos (*)</label> <!-- Etiqueta -->
                    </div> <!-- /Grupo -->
                </div> <!-- /Columna -->
                <div class="col-sm-3 col-12">
                    <!-- Columna -->
                    <div class="form-floating mb-1">
                        <!-- Grupo -->
                        <input type="text" class="form-control req" id="mssql_user" name="mssql_user" placeholder="mssql_userHelp" autocomplete=off> <!-- Campo -->
                        <label for="mssql_user">Usuario (*)</label> <!-- Etiqueta -->
                    </div> <!-- /Grupo -->
                </div> <!-- /Columna -->
                <div class="col-sm-3 col-12">
                    <!-- Columna -->
                    <div class="form-floating mb-1">
                        <!-- Grupo -->
                        <input type="password" class="form-control req" id="mssql_pass" name="mssql_pass" placeholder="mssql_passHelp" autocomplete=off> <!-- Campo -->
                        <label for="mssql_pass">Password (*)</label> <!-- Etiqueta -->
                    </div> <!-- /Grupo -->
                </div> <!-- /Columna -->
            </div> <!-- /Fila -->
            <div class="row mb-3">
                <!-- Fila -->
                <div class="col-12">
                    <!-- Columna -->
                    <p class="form-label">Conexión WebService CH</p> <!-- Título -->
                </div> <!-- /Columna -->
                <div class="col-sm-6 col-12">
                    <!-- Columna -->
                    <div class="form-floating mb-1">
                        <!-- Grupo -->
                        <input type="text" class="form-control req" id="ws_ip" placeholder="ws_ipHelp" name="ws_ip" autocomplete=off placeholder="http://localhost:6400/RRHHWebService/"> <!-- Campo -->
                        <label for="ws_ip">url (*)</label> <!-- Etiqueta -->
                    </div> <!-- /Grupo -->
                </div> <!-- /Columna -->
            </div> <!-- /Fila -->
            <div class="row mb-3">
                <!-- Fila -->
                <div class="col-12">
                    <!-- Columna -->
                    <p class="form-label">Internet Proxy</p> <!-- Título -->
                </div> <!-- /Columna -->
                <div class="col-sm-3 col-12">
                    <!-- Columna -->
                    <div class="form-floating mb-1">
                        <!-- Grupo -->
                        <input type="text" class="form-control" id="proxy_ip" placeholder="proxy_ipHelp" name="proxy_ip" autocomplete=off> <!-- Campo -->
                        <label for="proxy_ip">Ip</label> <!-- Etiqueta -->
                    </div> <!-- /Grupo -->
                </div> <!-- /Columna -->
                <div class="col-sm-3 col-12">
                    <!-- Columna -->
                    <div class="form-floating mb-1">
                        <!-- Grupo -->
                        <input type="text" class="form-control" id="proxy_puerto" placeholder="proxy_puertoHelp" name="proxy_puerto" autocomplete=off> <!-- Campo -->
                        <label for="proxy_puerto">Puerto</label> <!-- Etiqueta -->
                    </div> <!-- /Grupo -->
                </div> <!-- /Columna -->
                <div class="col-sm-3 col-12">
                    <!-- Columna -->
                    <div class="form-check form-switch mt-4">
                        <!-- Grupo -->
                        <input class="form-check-input" type="checkbox" name="proxy_estado" id="proxy_estado"> <!-- Campo -->
                        <label class="form-check-label" for="proxy_estado">Activo </label> <!-- Etiqueta -->
                    </div> <!-- /Grupo -->
                </div> <!-- /Columna -->
            </div> <!-- /Fila -->
            <div class="row mb-3">
                <!-- Fila -->
                <div class="col-sm-6 col-12">
                    <!-- Columna -->
                    <div class="row">
                        <!-- Fila -->
                        <div class="col-12">
                            <!-- Columna -->
                            <p class="form-label">Logs de Conexion MSSQL</p> <!-- Título -->
                        </div> <!-- /Columna -->
                        <div class="col-12">
                            <!-- Columna -->
                            <div class="form-check form-switch mt-2">
                                <!-- Grupo -->
                                <input class="form-check-input" type="checkbox" name="logs_conn_success" id="logs_conn_success"> <!-- Campo -->
                                <label class="form-check-label" for="logs_conn_success">Exitosa </label> <!-- Etiqueta -->
                            </div> <!-- /Grupo -->
                            <div class="form-check form-switch mt-2">
                                <!-- Grupo -->
                                <input class="form-check-input" type="checkbox" name="logs_conn_error" id="logs_conn_error" checked> <!-- Campo -->
                                <label class="form-check-label" for="logs_conn_error">Fallida</label> <!-- Etiqueta -->
                            </div> <!-- /Grupo -->
                        </div> <!-- /Columna -->
                    </div> <!-- /Fila -->
                </div> <!-- /Columna -->
                <div class="col-sm-6 col-12">
                    <!-- Columna -->
                    <div class="row">
                        <!-- Fila -->
                        <div class="col-12">
                            <!-- Columna -->
                            <p class="form-label">Logs de Novedades</p> <!-- Título -->
                        </div> <!-- /Columna -->
                        <div class="col-12">
                            <!-- Columna -->
                            <div class="form-check form-switch mt-2">
                                <!-- Grupo -->
                                <input class="form-check-input" type="checkbox" name="logs_nov_success" id="logs_nov_success" checked> <!-- Campo -->
                                <label class="form-check-label" for="logs_nov_success">Ingresadas </label> <!-- Etiqueta -->
                            </div> <!-- /Grupo -->
                            <div class="form-check form-switch mt-2">
                                <!-- Grupo -->
                                <input class="form-check-input" type="checkbox" name="logs_nov_error" id="logs_nov_error" checked>
                                <label class="form-check-label" for="logs_nov_error"> No Ingresadas</label> <!-- Etiqueta -->
                            </div> <!-- /Grupo -->
                        </div> <!-- /Columna -->
                    </div> <!-- /Fila -->
                </div> <!-- /Columna -->
            </div> <!-- /Fila -->
            <div class="row mb-3">
                <!-- Fila -->
                <div class="col-12">
                    <!-- Columna -->
                    <p class="form-label">Borrar Logs</p> <!-- Título -->
                </div> <!-- /Columna -->
                <div class="col-12">
                    <!-- Columna -->
                    <div class="d-inline-flex">
                        <!-- Grupo -->
                        <div class="form-check form-switch mt-2">
                            <!-- Grupo -->
                            <input class="form-check-input" type="checkbox" name="logs_borrar_estado" id="logs_borrar_estado" checked> <!-- Campo -->
                            <label class="form-check-label" for="logs_borrar_estado"></label> <!-- Etiqueta -->
                        </div> <!-- /Grupo -->
                        <div class="form-text mt-2">Cada</div> <!-- Etiqueta -->
                        <input type="number" class="form-control ms-2" id="logs_borrar_dias" aria-describedby="logs_borrar_diasHelp" name="logs_borrar_dias" autocomplete=off min="1" max="365" style="width: 70px;"> <!-- Campo -->
                        <div id="logs_borrar_diasHelp" class="form-text ms-2 mt-2">Días</div> <!-- Etiqueta -->
                    </div> <!-- /Grupo -->
                </div> <!-- /Columna -->
            </div> <!-- /Fila -->
            <button type="submit" class="btn btn-primary" name="submit" id="submit">Guardar</button> <!-- Botón -->
            <button type="button" class="btn btn-success" name="script" id="script">Ejecutar Script</button> <!-- Botón -->
            <button class="btn btn-secondary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasLogs" aria-controls="offcanvasLogs" id="verLogs" hidden>
                Log
            </button>
            <span class="float-end mt-4 pb-2" style="font-size:12px">(*) Requeridos</span> <!-- Requeridos -->
            <span id="spanRespuesta" class="ms-2"> </span> <!-- Mensaje -->
        </form> <!-- Fin Formulario -->
        <div class="offcanvas offcanvas-top h-100" tabindex="-1" id="offcanvasLogs" aria-labelledby="offcanvasLogsLabel" style="background-color: #2D333B;color: #ADB6BA">
            <!-- Inicio Offcanvas -->
            <div class="offcanvas-header">
                <!-- Inicio Offcanvas Header -->
                <h5 class="offcanvas-title" id="offcanvasLogsLabel">Log</h5> <!-- Título -->
                <button type="button" class="bg-white btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button> <!-- Botón Cerrar -->
            </div> <!-- Fin Offcanvas Header -->
            <div class="offcanvas-body">
                <!-- Inicio Offcanvas Body -->
                <pre id="contentCanva"></pre> <!-- Contenido -->
            </div>
        </div>
    </div> <!-- Fin Contenedor -->
    <script src="..\asset\jquery-3.6.0.min.js"></script> <!-- Jquery -->
    <script src="..\asset\bootstrap.min.js"></script> <!-- Bootstrap -->
    <script src="..\asset\form-min.js?v=<?= time() ?>"></script> <!-- Form -->
</body> <!-- Fin Body -->

</html> <!-- Fin Html -->