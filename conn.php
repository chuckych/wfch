<?php
date_default_timezone_set('America/Argentina/Buenos_Aires'); // Establece la zona horaria
$datos = defaultConfigData(); // Obtiene los datos de configuración por defecto
// $rutaConfig = __DIR__ . '/config.json'; // Path to config.json
$pathConfigData = __DIR__ . '/data.php'; // Path to data.php
$rutaInfo   = __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log"; // Path to info log

// $datosConfig = getDataJson($rutaConfig); // Obtenemos los datos del config.json
// if ($datosConfig == false) : // Si no hay datos o no existe el archivo
//     $datosConfig = fileLogsJson(json_encode($datos, JSON_PRETTY_PRINT), $rutaConfig, 'json'); // Creamos el archivo config.json
//     fileLog("Se creo el archivo \"config.json\"", $rutaInfo, '');
// endif;

$dataConfig = getDataIni($pathConfigData); // Obtenemos los datos del data.php
if ($dataConfig == false) : // Si no hay datos o no existe el archivo
    $dataConfig = write_ini_file($datos,  $pathConfigData, true); // Creamos el archivo data.php
    fileLog("Se creo el archivo \"data.php\"", $rutaInfo, ''); // Creamos el archivo info.log
endif;


if (file_exists($pathConfigData)) : // Si existe archivo de configuraciones
    // $dataJson = file_get_contents($rutaConfig); // Leer archivo de configuraciones
    // $dataJson = json_decode($dataJson, true); // Decodificar JSON
    // json_validate($dataJson); // Validar archivo JSON

    $dataConfig = parse_ini_file($pathConfigData, true); // Obtenemos los datos del data.php

    if (!$dataConfig['api']['url'] || !$dataConfig['api']['user'] || !$dataConfig['api']['pass'] || !$dataConfig['mssql']['srv'] || !$dataConfig['mssql']['db'] || !$dataConfig['mssql']['user'] || !$dataConfig['mssql']['pass'] || !$dataConfig['webService']['url']) {  // validamos que los datos de configuracion esten completos
        $texto = "Error en la configuracion del archivo \"config.json\"";
        fileLogs($texto, $rutaInfo, '');
        // echo $texto . '<br><br>';
        function err($campo, $error, $rutaInfo)
        {
            if (!$campo) {
                fileLogs($error, $rutaInfo, '');
                // echo $error . '<br>';
            }
        }
        $error_api_url = "api url: ---- vacio ----";

        err($dataConfig['api']['url'], "api url: \"---- vacio ----\"", $rutaInfo);
        err($dataConfig['api']['user'], "api user: \"---- vacio ----\"", $rutaInfo);
        err($dataConfig['api']['pass'], "api pass: \"---- vacio ----\"", $rutaInfo);
        err($dataConfig['mssql']['srv'], "mssql srv: \"---- vacio ----\"", $rutaInfo);
        err($dataConfig['mssql']['db'], "mssql db: \"---- vacio ----\"", $rutaInfo);
        err($dataConfig['mssql']['user'], "mssql user: \"---- vacio ----\"", $rutaInfo);
        err($dataConfig['mssql']['pass'], "mssql pass: \"---- vacio ----\"", $rutaInfo);
        err($dataConfig['webService']['url'], "webService url: \"---- vacio ----\"", $rutaInfo);

        respuestaScript('Error en la configuracion del script', 'Error');
        header('Location: config/');
        exit(); // Terminamos la ejecucion del script
    } // fin validacion de datos de configuracion

    $connectionString = array( // Crear array con la conexion
        "Database"     => $dataConfig['mssql']['db'], // Nombre de la base de datos
        "UID"          => $dataConfig['mssql']['user'], // Usuario de la base de datos
        "PWD"          => $dataConfig['mssql']['pass'], // Contraseña de la base de datos
        "CharacterSet" => "utf-8" // Codificacion de caracteres
    );
    $iniConn = microtime(true); // Iniciamos el contador de tiempo de conexion
    $link = sqlsrv_connect($dataConfig['mssql']['srv'], $connectionString); // Conectar a la base de datos
    if ($link === false) : // Si no se pudo conectar a la base de datos
        if (($errors = sqlsrv_errors()) != null) : // Si existen errores
            foreach ($errors as $key => $error) { // Recorrer errores
                $SQLSTATE = $error['SQLSTATE']; // Obtener codigo de error
                $code = $error['code']; // Obtener codigo de error
                $message = $error['message']; // Obtener mensaje de error
                $text = "\nSQLSTATE: \"$SQLSTATE\"\ncode: \"$code\"\nMessage: \"$message\"";
                $text2 = "<br><br>SQLSTATE: $SQLSTATE<br>code: $code<br>Message: $message";
                $text .= ($key === 1) ? "\n----" : '';
                fileLogs($text, __DIR__ . "/logs/conn/" . date('Ymd') . "_ErrorConnexion.log", 'conErr'); // guarda el error en el log
                fileLogs($message, __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'conErr'); // Guardo el log

                if ($key === 1) {
                    respuestaScript($message, 'Error');
                    $finConn = microtime(true); // Terminamos el contador de tiempo de conexion
                    $durConn = (round($finConn - $iniConn, 2)); // Obtenemos la duracion de la conexion en segundos
                    fileLogs("Tiempo conexion MSSQL: $durConn segundos", __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'conErr'); // Guardo el log
                    fileLogs("Tiempo conexion MSSQL: $durConn segundos", __DIR__ . "/logs/conn/" . date('Ymd') . "_ErrorConnexion.log", 'conErr'); // Guardo el log
                    exit;
                }
            }
            exit;
        endif;
    else : // Si se pudo conectar a la base de datos
        $dataBase = $dataConfig['mssql']['db']; // Nombre de la base de datos
        $user = $dataConfig['mssql']['user']; // Usuario de la base de datos
        $pass = $dataConfig['mssql']['pass']; // Contraseña de la base de datos
        $conn = sqlsrv_query($link, "SELECT @@VERSION"); // Consulta de version de la base de datos
        $version = sqlsrv_fetch_array($conn); // Obtener version de la base de datos
        $version = $version[0]; // Obtener version de la base de datos
        $version = explode('.', $version); // Separar version en array
        $version = $version[0] . '.' . $version[1]; // Obtener version de la base de datos
        sqlsrv_free_stmt($conn); // Finalizar consulta
        $conn = sqlsrv_query($link, "SELECT @@SERVERNAME"); // Consulta de nombre de servidor
        $serverName = sqlsrv_fetch_array($conn); // Obtener nombre de servidor
        $serverName = $serverName[0]; // Obtener nombre de servidor
        sqlsrv_free_stmt($conn); // Finalizar consulta
        $ExtensionVer   = sqlsrv_client_info($link)['ExtensionVer'] ?? ''; // Version de la extensión
        $DriverVer      = sqlsrv_client_info($link)['DriverVer'] ?? ''; // Version del driver
        $DriverODBCVer  = sqlsrv_client_info($link)['DriverODBCVer'] ?? ''; // Version del driver ODBC
        $DriverDllName  = sqlsrv_client_info($link)['DriverDllName'] ?? ''; // Nombre del driver
        $text           = "\nConexión exitosa a la base de datos \"$dataBase\"\nVersion del motor SQL: \"$version\"\nNombre del servidor: \"$serverName\"\nVersion de la extensión: \"$ExtensionVer\"\nVersion del driver: \"$DriverVer\"\nVersion del driver ODBC: \"$DriverODBCVer\"\nNombre del driver: \"$DriverDllName";
        fileLogs($text, __DIR__ . "/logs/conn/" . date('Ymd') . "_successConexion.log", 'conOk'); // guarda el log de exito en el archivo
    endif;
else :
    fileLogs("El archivo $pathConfigData no existe", __DIR__ . "/logs/config.log", ''); // guarda el error en el log
    exit;
endif;
