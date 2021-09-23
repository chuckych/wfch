<?php
date_default_timezone_set('America/Argentina/Buenos_Aires'); // Establece la zona horaria
$datos = defaultConfigData(); // Obtiene los datos de configuración por defecto
$rutaConfig = __DIR__ . '/config.json'; // Path to config.json
$rutaInfo   = __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log"; // Path to info log

$datosConfig = getDataJson($rutaConfig); // Obtenemos los datos del config.json
if ($datosConfig == false) : // Si no hay datos o no existe el archivo
    $datosConfig = fileLogsJson(json_encode($datos, JSON_PRETTY_PRINT), $rutaConfig, 'json'); // Creamos el archivo config.json
    fileLog("Se creo el archivo \"config.json\"", $rutaInfo, '');
endif;

if (file_exists($rutaConfig)) : // Si existe archivo de configuraciones
    $dataJson = file_get_contents($rutaConfig); // Leer archivo de configuraciones
    $dataJson = json_decode($dataJson, true); // Decodificar JSON
    json_validate($dataJson); // Validar archivo JSON

    if (!$dataJson['api']['url'] || !$dataJson['api']['user'] || !$dataJson['api']['pass'] || !$dataJson['mssql']['srv'] || !$dataJson['mssql']['db'] || !$dataJson['mssql']['user'] || !$dataJson['mssql']['pass'] || !$dataJson['webService']['url']) {  // validamos que los datos de configuracion esten completos
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

        err($dataJson['api']['url'], "api url: \"---- vacio ----\"", $rutaInfo);
        err($dataJson['api']['user'], "api user: \"---- vacio ----\"", $rutaInfo);
        err($dataJson['api']['pass'], "api pass: \"---- vacio ----\"", $rutaInfo);
        err($dataJson['mssql']['srv'], "mssql srv: \"---- vacio ----\"", $rutaInfo);
        err($dataJson['mssql']['db'], "mssql db: \"---- vacio ----\"", $rutaInfo);
        err($dataJson['mssql']['user'], "mssql user: \"---- vacio ----\"", $rutaInfo);
        err($dataJson['mssql']['pass'], "mssql pass: \"---- vacio ----\"", $rutaInfo);
        err($dataJson['webService']['url'], "webService url: \"---- vacio ----\"", $rutaInfo);

        respuestaScript('Error en la configuracion del script', 'Error');
        header('Location: config/');
        exit(); // Terminamos la ejecucion del script
    } // fin validacion de datos de configuracion

    $connectionString = array( // Crear array con la conexion
        "Database"     => $dataJson['mssql']['db'], // Nombre de la base de datos
        "UID"          => $dataJson['mssql']['user'], // Usuario de la base de datos
        "PWD"          => $dataJson['mssql']['pass'], // Contraseña de la base de datos
        "CharacterSet" => "utf-8" // Codificacion de caracteres
    );

    $link = sqlsrv_connect($dataJson['mssql']['srv'], $connectionString); // Conectar a la base de datos
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
                ($key === 1) ? respuestaScript($text2, 'Error').exit : ''; // si es el primer error, termina el script
            }
            exit;
        endif;
    else : // Si se pudo conectar a la base de datos
        $dataBase = $dataJson['mssql']['db']; // Nombre de la base de datos
        $user = $dataJson['mssql']['user']; // Usuario de la base de datos
        $pass = $dataJson['mssql']['pass']; // Contraseña de la base de datos
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
        $text           = "\nConexión exitosa a la base de datos \"$dataBase\"\nVersion del motor SQL: \"$version\"\nNombre del servidor: \"$serverName\"\nVersion de la extensión: \"$ExtensionVer\"\nVersion del driver: \"$DriverVer\"\nVersion del driver ODBC: \"$DriverODBCVer\"\nNombre del driver: \"$DriverDllName\"\n";
        fileLogs($text, __DIR__ . "/logs/conn/" . date('Ymd') . "_successConexion.log", 'conOk'); // guarda el log de exito en el archivo
    endif;
else :
    fileLogs("El archivo $rutaConfig no existe", __DIR__ . "/logs/config.log", ''); // guarda el error en el log
    exit;
endif;
