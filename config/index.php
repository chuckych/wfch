<?php
require __DIR__ . '../../func.php'; // Funciones
$datos = defaultConfigData();
$rutaConfig = __DIR__ . '../../config.json'; // Path to config.json
$rutaInfo   = __DIR__ . "../../logs/info/" . date('Ymd') . "_informacion.log"; // Path to info log

$datosConfig = getDataJson($rutaConfig); // Obtenemos los datos del config.json
if ($datosConfig == false) : // Si no hay datos o no existe el archivo
    $datosConfig = fileLogsJson(json_encode($datos, JSON_PRETTY_PRINT), $rutaConfig, 'json'); // Creamos el archivo config.json
    fileLog("Se creo el archivo \"config.json\"", $rutaInfo, '');
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
    <link rel="stylesheet" href="..\asset\style.css?v=<?= time() ?>"> <!-- Estilo CSS -->

    <title>Config WF-CH!</title> <!-- Título -->
</head>

<body class="p-3" style="background-color: #66615b;">
    <!-- Inicio body -->
    <div class="container p-1 shadow-lg" style="max-width: 900px; background: #66615b">
        <!-- Contenedor -->
        <form autocomplete=off class="p-4 bg-light" id="form" method="POST">
            <!-- Formulario -->
            <div class="row m-0">
                <div class="col-12 px-0">
                    <p class="h5 p-0 m-0">Configuración de Script - Integración de Novedades Workflow / CH</p>
                </div>
            </div>
            <div class="row my-3">
                <div class="col-12">
                    <p class="form-label">Datos API Workflow</p>
                </div>
                <div class="col-sm-6 col-12">
                    <div class="form-floating mb-1">
                        <input type="text" class="form-control" id="api_url" name="api_url" placeholder="URL API" autocomplete=off>
                        <label for="api_url">url (*)</label>
                    </div>
                </div>
                <div class="col-sm-3 col-12">
                    <div class="form-floating mb-1">
                        <input type="text" class="form-control req" id="api_user" name="api_user" placeholder="Usuario API" autocomplete=off>
                        <label for="api_user">Usuario (*)</label>
                    </div>
                </div>
                <div class="col-sm-3 col-12">
                    <div class="form-floating mb-1">
                    <input type="password" class="form-control req" id="api_pass" name="api_pass" placeholder="Password API" autocomplete=off>
                        <label for="api_pass">Password (*)</label>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-12">
                    <p class="form-label">Conexión MSSQL</p>
                </div>
                <div class="col-sm-3 col-12">
                    <div class="form-floating mb-1">
                        <input type="text" class="form-control req" id="mssql_srv" placeholder="mssql_srvHelp" name="mssql_srv" autocomplete=off>
                        <label for="mssql_srv">Servidor (*)</label>
                    </div>
                </div>
                <div class="col-sm-3 col-12">
                    <div class="form-floating mb-1">
                        <input type="text" class="form-control req" id="mssql_db" name="mssql_db" placeholder="mssql_dbHelp" autocomplete=off>
                        <label for="mssql_db">Base de datos (*)</label>
                    </div>
                </div>
                <div class="col-sm-3 col-12">
                    <div class="form-floating mb-1">
                        <input type="text" class="form-control req" id="mssql_user" name="mssql_user" placeholder="mssql_userHelp" autocomplete=off>
                        <label for="mssql_user">Usuario (*)</label>
                    </div>
                </div>
                <div class="col-sm-3 col-12">
                    <div class="form-floating mb-1">
                        <input type="password" class="form-control req" id="mssql_pass" name="mssql_pass" placeholder="mssql_passHelp" autocomplete=off>
                        <label for="mssql_pass">Password (*)</label>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-12">
                    <p class="form-label">Conexión WebService CH</p>
                </div>
                <div class="col-sm-6 col-12">
                    <div class="form-floating mb-1">
                        <input type="text" class="form-control req" id="ws_ip" placeholder="ws_ipHelp" name="ws_ip" autocomplete=off placeholder="http://localhost:6400/RRHHWebService/">
                        <label for="ws_ip">url (*)</label>
                    </div>

                </div>
            </div>
            <div class="row mb-3">
                <div class="col-12">
                    <p class="form-label">Internet Proxy</p>
                </div>
                <div class="col-sm-3 col-12">
                    <div class="form-floating mb-1">
                        <input type="text" class="form-control" id="proxy_ip" placeholder="proxy_ipHelp" name="proxy_ip" autocomplete=off>
                        <label for="proxy_ip">Ip</label>
                    </div>

                </div>
                <div class="col-sm-3 col-12">
                    <div class="form-floating mb-1">
                        <input type="text" class="form-control" id="proxy_puerto" placeholder="proxy_puertoHelp" name="proxy_puerto" autocomplete=off>
                        <label for="proxy_puerto">Puerto</label>
                    </div>

                </div>
                <div class="col-sm-3 col-12">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="proxy_estado" id="proxy_estado">
                        <label class="form-check-label" for="proxy_estado">
                            Activo
                        </label>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-sm-6 col-12">
                    <div class="row">
                        <div class="col-12">
                            <p class="form-label">Logs de Conexion MSSQL</p>
                        </div>
                        <div class="col-sm-3 col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="logs_conn_success" id="logs_conn_success">
                                <label class="form-check-label" for="logs_conn_success">
                                    Exitosa
                                </label>
                            </div>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="logs_conn_error" id="logs_conn_error" checked>
                                <label class="form-check-label" for="logs_conn_error">
                                    Fallida
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-12">
                    <div class="row">
                        <div class="col-12">
                            <p class="form-label">Logs de Novedades</p>
                        </div>
                        <div class="col-sm-3 col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="logs_nov_success" id="logs_nov_success" checked>
                                <label class="form-check-label" for="logs_nov_success">
                                    Ingresadas
                                </label>
                            </div>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="logs_nov_error" id="logs_nov_error" checked>
                                <label class="form-check-label" for="logs_nov_error">
                                    Fallidas
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-12">
                    <p class="form-label">Borrar Logs</p>
                </div>
                <div class="col-12">
                    <div class="d-inline-flex">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="logs_borrar_estado" id="logs_borrar_estado" checked>
                            <label class="form-check-label" for="logs_borrar_estado">
                                Si
                            </label>
                        </div>
                        <div class="form-text ms-3 mt-2">Cada</div>
                        <input type="number" class="form-control ms-2" id="logs_borrar_dias" aria-describedby="logs_borrar_diasHelp" name="logs_borrar_dias" autocomplete=off min="1" max="365">
                        <div id="logs_borrar_diasHelp" class="form-text ms-2 mt-2">Días</div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" name="submit" id="submit">Guardar</button>
            <button type="button" class="btn btn-success" name="script" id="script">Ejecutar Script</button>
            <span class="float-end mt-4 pb-2" style="font-size:12px">(*) Requeridos</span>
            <span id="spanRespuesta" class="ms-2">
        </form> <!-- Fin Formulario -->
    </div> <!-- Fin Contenedor -->
    <script src="..\asset\jquery-3.6.0.min.js"></script>
    <script src="..\asset\bootstrap.min.js"></script>
    <script src="..\asset\form.js?v=<?= time() ?>"></script>
</body> <!-- Fin Body -->

</html> <!-- Fin Html -->