﻿<?php
function version()
{
    return "1.1.20";
}
function defaultConfigData() // default config data
{
    $datos = array(
        'mssql' => array('srv' => '', 'db' => '', 'user' => '', 'pass' => ''),
        'logConexion' => array('success' => false, 'error' => true),
        'api' => array('url' => "https://hr-process.com/hrctest/api/novedades/", 'user' => 'admin', 'pass' => 'admin'),
        'webService' => array('url' => "http://localhost:6400/RRHHWebService/"),
        'logNovedades' => array('success' => true, 'error' => true),
        'proxy' => array('ip' => '', 'port' => '', 'enabled' => false),
        'borrarLogs' => array('estado' => true, 'dias' => 31), // 'interrumpirSolicitud'=>array
    );
    return $datos;
}
function test_input($data) // Función para limpiar los datos de entrada
{
    $data = trim($data);
    $data = htmlspecialchars(stripslashes($data), ENT_QUOTES);
    return ($data);
}
function dataNovedades($link) // Obtiene los datos de las novedades de la base de datos CH
{
    $params = [];
    $options = ["Scrollable" => SQLSRV_CURSOR_KEYSET];
    $query = "SELECT NOVEDAD.NovTipo, NOVEDAD.NovCodi, NOVEDAD.NovDesc, NOVEDAD.NovID FROM NOVEDAD WHERE NOVEDAD.NovCodi > 0";
    $stmt = sqlsrv_query($link, $query, $params, $options);
    while ($row = sqlsrv_fetch_array($stmt)) {
        $data[] = [
            'CodNov' => $row['NovCodi'], // Codigo de la novedad
            'NovDesc' => $row['NovDesc'], // Descripcion de la novedad
            'CodTipo' => $row['NovTipo'], // Codigo del tipo de novedad
            'TipoDesc' => descTipoNov($row['NovTipo']), // Descripcion del tipo de novedad
            'NovID' => $row['NovID'] // ID de la novedad
        ];
    }
    sqlsrv_free_stmt($stmt);
    return $data;
    // sqlsrv_close($link);
}
function dataNovedadesOtras($link) // Obtiene los datos de las novedades de la base de datos CH
{
    $params = [];
    $options = ["Scrollable" => SQLSRV_CURSOR_KEYSET];
    $query = "SELECT * FROM OTRASNOV WHERE OTRASNOV.ONovCodi > 0";
    $stmt = sqlsrv_query($link, $query, $params, $options);
    while ($row = sqlsrv_fetch_array($stmt)) {
        $data[] = [
            'CodNov' => $row['ONovCodi'], // Codigo de la novedad
            'NovDesc' => $row['ONovDesc'], // Descripcion de la novedad
            'CodTipo' => $row['ONovTipo'], // Codigo del tipo de novedad. Retorna 0 o 1
        ];
    }
    sqlsrv_free_stmt($stmt);
    return $data;
    // sqlsrv_close($link);
}
function perCierreFech($FechaStr, $Legajo, $link)
{
    $params = [];
    $options = ["Scrollable" => SQLSRV_CURSOR_KEYSET];
    $query = "SELECT TOP 1 CierreFech FROM PERCIERRE WHERE PERCIERRE.CierreLega = '$Legajo'";

    $stmt = sqlsrv_query($link, $query, $params, $options);
    while ($row = sqlsrv_fetch_array($stmt)) {
        $perCierre = $row['CierreFech']->format('Ymd');
    }
    $perCierre = !empty($perCierre) ? $perCierre : '17530101';
    sqlsrv_free_stmt($stmt);

    if ($FechaStr <= $perCierre) {
        return $perCierre;
    } else {
        $query = "SELECT ParCierr FROM PARACONT WHERE ParCodi = 0 ORDER BY ParCodi";
        $stmt = sqlsrv_query($link, $query, $params, $options);
        while ($row = sqlsrv_fetch_array($stmt)) {
            $ParCierr = $row['ParCierr']->format('Ymd');
        }
        $ParCierr = !empty($ParCierr) ? $ParCierr : '17530101';
        sqlsrv_free_stmt($stmt);
        if ($FechaStr <= $ParCierr) {
            return $ParCierr;
        } else {
            return false;
        }
    }
}
function lastUpdateTabla($link, $tabla) // Obtiene la ultima fecha de actualización de una tabla
{
    $stmt = sqlsrv_query($link, "SELECT MAX($tabla.FechaHora) as 'maxFecha' FROM $tabla"); // Ejecutamos la consulta
    $row = sqlsrv_fetch_array($stmt); // Obtenemos el resultado
    $data = array('FechaHora' => $row['maxFecha']->format('d-m-Y H:i:s')); // Formateamos la fecha
    sqlsrv_free_stmt($stmt); // Liberamos el statement
    return $data; // Retornamos la fecha de actualización
}
function dataLegajos($link) // Obtiene los legajos de la base de datos CH
{
    $params = array();
    $options = array("Scrollable" => SQLSRV_CURSOR_KEYSET);
    // require __DIR__ . '/conn.php';
    $query = "SELECT PERSONAL.LegNume, PERSONAL.LegApNo FROM PERSONAL WHERE PERSONAL.LegNume > 0";
    $stmt = sqlsrv_query($link, $query, $params, $options);
    while ($row = sqlsrv_fetch_array($stmt)) {
        $data[] = array(
            'legajo' => $row['LegNume'],
            'ApNo' => $row['LegApNo'],
        );
    }
    sqlsrv_free_stmt($stmt);
    return $data;
    // sqlsrv_close($link);
}
function eliminarNovedad($FicLega, $FicFech, $FicNove, $link) // Obtiene los legajos de la base de datos CH
{
    $params = array();
    $options = array("Scrollable" => SQLSRV_CURSOR_KEYSET);
    // require __DIR__ . '/conn.php';
    $query = "DELETE FROM FICHAS3 WHERE FICHAS3.FicLega = '$FicLega' AND FICHAS3.FicFech = '$FicFech' AND FICHAS3.FicNove = '$FicNove'";
    $stmt = sqlsrv_query($link, $query, $params, $options);
    if (($stmt)) {
        return true;
    } else {
        if (($errors = sqlsrv_errors()) != null) {
            foreach ($errors as $error) {
                $mensaje = explode(']', $error['message']);
                $data[] = $mensaje[3];
            }
        }
        fileLogs('Error al Eliminar Novedad ' . $data[0], __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", '');
        fileLogs('Error al Eliminar Novedad ' . $data[0], __DIR__ . "/logs/errores/" . date('Ymd') . "_error.log", '');
        exit;
    }
}
function eliminarNovedadPeriodo($FicLega, $Ini, $Fin, $FicNove, $link) // Obtiene los legajos de la base de datos CH
{
    $params = array();
    $options = array("Scrollable" => SQLSRV_CURSOR_KEYSET);
    // require __DIR__ . '/conn.php';
    $query = "DELETE FROM FICHAS3 WHERE FICHAS3.FicLega = '$FicLega' AND FICHAS3.FicFech BETWEEN '$Ini' AND '$Fin' AND FICHAS3.FicNove = '$FicNove'";
    $stmt = sqlsrv_query($link, $query, $params, $options);
    if (($stmt)) {
        return true;
    } else {
        if (($errors = sqlsrv_errors()) != null) {
            foreach ($errors as $error) {
                $mensaje = explode(']', $error['message']);
                $data[] = $mensaje[3];
            }
        }
        fileLogs("Error al eliminar Novedad desde $Ini a $Fin. $data[0]", __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", '');
        fileLogs("Error al eliminar Novedad desde $Ini a $Fin. $data[0]", __DIR__ . "/logs/errores/" . date('Ymd') . "_error.log", '');
    }
}
function eliminarOtraNovedadPeriodo($FicLega, $Ini, $Fin, $FicNove, $link) // Obtiene los legajos de la base de datos CH
{
    $params = [];
    $options = ["Scrollable" => SQLSRV_CURSOR_KEYSET];

    $query = "DELETE FROM FICHAS2 WHERE FICHAS2.FicLega = '$FicLega' AND FICHAS2.FicFech BETWEEN '$Ini' AND '$Fin' AND FICHAS2.FicONov = '$FicNove'";
    $stmt = sqlsrv_query($link, $query, $params, $options);
    if ($stmt) {
        return true;
    } else {
        if (($errors = sqlsrv_errors()) != null) {
            foreach ($errors as $error) {
                $mensaje = explode(']', $error['message']);
                $data[] = $mensaje[3];
            }
        }
        fileLogs("Error al eliminar Otra Novedad desde $Ini a $Fin. $data[0]", __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", '');
        fileLogs("Error al eliminar Otra Novedad desde $Ini a $Fin. $data[0]", __DIR__ . "/logs/errores/" . date('Ymd') . "_error.log", '');
    }
}
function insertarOtrasNovedades($FicFech, $FicLega, $FicONov, $FicValor, $FicObsN, $link)
{
    $query = "INSERT INTO FICHAS2 (FicLega, FicFech, FicTurn, FicONov, FicValor, FicObsN, FechaHora, FicUsua) VALUES (?, ?, ?, ?, ?, ?, getdate(), ?)";
    $textWF = ($FicObsN == '') ? 'WF Novedades' : '. WF Novedades'; // Concateno texto. WF Novedades a la Observación
    // Preparar los parámetros
    $params = [$FicLega, $FicFech, 1, $FicONov, $FicValor, "{$FicObsN}{$textWF}", 'Script WF'];

    // Opciones (considera si SQLSRV_CURSOR_KEYSET es necesario para un INSERT)
    $options = ["Scrollable" => SQLSRV_CURSOR_KEYSET];

    // Ejecutar la consulta una sola vez con los parámetros correctos
    $stmt = sqlsrv_query($link, $query, $params, $options);

    if ($stmt === false) { // Comprobar si la ejecución falló
        $errors = sqlsrv_errors();
        $errorMessages = [];
        if ($errors != null) {
            foreach ($errors as $error) {
                // Loguear el error completo para depuración
                // fileLogs("Error SQL Server: " . print_r($error, true), __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", '');
                $mensajeParts = explode(']', $error['message']);
                // Usar el mensaje completo si no se puede parsear
                $errorMessages[] = isset($mensajeParts[3]) ? trim($mensajeParts[3]) : $error['message'];
            }
        }
        $logMessage = "Error al insertar Otras Novedades. Leg: $FicLega, Fecha: $FicFech. Error: " . implode('; ', $errorMessages);

        fileLogs($logMessage, __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", '');
        fileLogs($logMessage, __DIR__ . "/logs/errores/" . date('Ymd') . "_error.log", '');
        return false; // Indicar fallo
    } else {
        audito_ch("A", "Otras Novedad ({$FicONov}). $FicLega fecha $FicFech, valor $FicValor.", $link);
        fileLogs("Otras Novedad ({$FicONov}) insertada de $FicLega fecha $FicFech, valor $FicValor.", __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", ''); // Loguear el éxito
        sqlsrv_free_stmt($stmt); // Liberar recursos
        return true; // Indicar éxito
    }
}
function filtrarObjeto($array, $key, $valor) // Función para filtrar un objeto
{
    $r = array_filter($array, function ($e) use ($key, $valor) {
        return $e[$key] === $valor;
    });
    foreach ($r as $key => $value) {
        return ($value);
    }
}
function descTipoNov($var) // Función para obtener la descripcion del tipo de novedad
{
    switch (intval($var)) { // Switch para obtener la descripcion del tipo de novedad
        case 0:
            $tipo = 'Llegada tarde';
            break;
        case 1:
            $tipo = 'Incumplimiento';
            break;
        case 2:
            $tipo = 'Salida anticipada';
            break;
        case 3:
            $tipo = 'Ausencia';
            break;
        case 4:
            $tipo = 'Licencia';
            break;
        case 5:
            $tipo = 'Accidente';
            break;
        case 6:
            $tipo = 'Vacaciones';
            break;
        case 7:
            $tipo = 'Suspensión';
            break;
        case 8:
            $tipo = 'ART';
            break;
        default:
            $tipo = $var;
            break;
    }
    return $tipo;
}
function validaDiaFichadas($legajo, $fecha_desde, $fecha_hasta, $link) // Valida si el legajo tiene fichadas en el rango de fechas
{
    //require __DIR__ . '/conn.php'; // Conexión a la base de datos
    $query = "SELECT COUNT(R.RegHoRe) AS 'Fichada', R.RegFeAs AS 'Fecha', R.RegLega AS 'Legajo', P.LegApNo AS 'Nombre' FROM REGISTRO R INNER JOIN PERSONAL P ON R.RegLega=P.LegNume WHERE R.RegLega = $legajo AND R.RegFeAs BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY R.RegFeAs, R.RegLega, P.LegApNo";
    $rows = array(); // Almacenará los datos de la consulta
    $params = array(); // Parámetros de la consulta     
    $options = array("Scrollable" => SQLSRV_CURSOR_KEYSET); // Opciones para la consulta
    $stmt = sqlsrv_query($link, $query, $params, $options); // Ejecutar la consulta
    while ($row = sqlsrv_fetch_array($stmt))
        $rows[] = array( // Almacenar los datos de la consulta en un array multidimensional
            'Legajo' => $row['Legajo'], // Legajo
            'Nombre' => $row['Nombre'], // Nombre
            'Fecha' => $row['Fecha']->format('d/m/Y'), // Fecha
            'FechaStr' => $row['Fecha']->format('Ymd'), // Fecha en formato Ymd
            'Count' => $row['Fichada'], // Cantidad de fichadas
        );
    sqlsrv_free_stmt($stmt); // Liberar el statement
    return $rows; // Retornar los datos de la consulta
    //sqlsrv_close($link); // Cerrar la conexión
}
function apiData($url, $auth, $proxy, $timeout = 10) // Función para obtener datos de la API
{
    $proxyIP = $proxy[0]; // IP del proxy
    $proxyPort = $proxy[1]; // Puerto del proxy
    $proxyEnable = $proxy[2]; // Habilitado o no
    $username = $auth[0];
    $password = $auth[1];
    $ch = curl_init(); // initialize curl handle
    curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); // The number of seconds to wait while trying to connect
    curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    if ($proxyEnable) { // si hay proxy
        curl_setopt($ch, CURLOPT_PROXY, $proxyIP); // use this proxy
        curl_setopt($ch, CURLOPT_PROXYPORT, $proxyPort); // set this proxy's port
    }
    $headers = array(
        'Content-Type:application/json', // Le enviamos JSON al servidor con los datos
        'Authorization: Basic ' . base64_encode($username . ":" . $password) // Basic Authentication
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // Add headers
    $data_content = curl_exec($ch); // extract information from response
    $curl_errno = curl_errno($ch); // get error code
    $curl_error = curl_error($ch); // get error information
    if ($curl_errno > 0) { // si hay error
        $text = "cURL Error ($curl_errno): $curl_error"; // set error message
        fileLog($text, __DIR__ . "/logs/errores/" . date('Ymd') . "_errorAPI_WF.log"); // escribir en el log
        fileLog($text, __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log');
        exit; // salimos del script
    }
    curl_close($ch); // close curl handle
    return ($data_content) ? $data_content : fileLog("No datos en API WF", __DIR__ . "/logs/errores/" . date('Ymd') . "_API_WF.log") . fileLog("No datos en API WF", __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log'); // si no hay datos, escribir en el log
}
function pingApi($url, $auth, $proxy, $timeout = 10) // Función para verificar la conexión a la API
{
    $iniPingApiWF = microtime(true); // Iniciamos el contador de tiempo de conexion
    $proxyIP = $proxy[0]; // IP del proxy
    $proxyPort = $proxy[1]; // Puerto del proxy
    $proxyEnable = $proxy[2]; // Habilitado o no
    $username = $auth[0];
    $password = $auth[1];
    $ch = curl_init(); // initialize curl handle
    curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); // The number of seconds to wait while trying to connect
    curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
    if ($proxyEnable) { // si hay proxy
        curl_setopt($ch, CURLOPT_PROXY, $proxyIP); // use this proxy
        curl_setopt($ch, CURLOPT_PROXYPORT, $proxyPort); // set this proxy's port
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $headers = array(
        'Content-Type:application/json', // Le enviamos JSON al servidor con los datos
        'Authorization: Basic ' . base64_encode($username . ":" . $password) // Basic Authentication
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // Add headers
    $data_content = curl_exec($ch); // extract information from response
    $curl_errno = curl_errno($ch); // get error code
    $curl_error = curl_error($ch); // get error information
    if ($curl_errno > 0) { // si hay error

        $finPingApiWF = microtime(true); // Terminamos el contador de tiempo de conexion
        $durPingApiWF = (round($finPingApiWF - $iniPingApiWF, 2)); // Obtenemos la duracion de la conexion en segundos

        $text = "cURL Error ($curl_errno): $curl_error"; // set error message
        sendEmail("WFCH -> Error API WF", "<pre>$text</pre>");

        fileLog($text, __DIR__ . "/logs/errores/" . date('Ymd') . "_errorPingAPI_WF.log"); // escribir en el log
        fileLog("Duracion Ping ApiWF $durPingApiWF segundos.", __DIR__ . "/logs/errores/" . date('Ymd') . "_errorPingAPI_WF.log"); // escribir en el log
        fileLog($text, __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log'); // escribir en el log
        fileLog("Duracion Ping ApiWF $durPingApiWF segundos.", __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log'); // escribir en el log

        respuestaScript($text, 'Error');
        exit; // salimos del script
    }
    curl_close($ch); // close curl handle
    return ($data_content) ? $data_content : fileLog("Error Ping API WF", __DIR__ . "/logs/errores/" . date('Ymd') . "_PingAPI_WF.log") . respuestaScript('Error Ping API WF', 'Error') . fileLog('Error Ping API WF', __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log'); // escribir en el log; // si no hay datos, escribir en el log
}
function sendApiData($url, $auth, $proxy, $timeout, $data) // Enviar datos a la API
{
    $timeout = $timeout ?? 10;
    $proxyIP = $proxy[0]; // IP del proxy
    $proxyPort = $proxy[1]; // Puerto del proxy
    $proxyEnable = $proxy[2]; // Habilitado o no
    $username = $auth[0];
    $password = $auth[1];
    $ch = curl_init(); // initialize curl handle
    curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); // The number of seconds to wait while trying to connect
    curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
    curl_setopt($ch, CURLOPT_POSTFIELDS, ($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    if ($proxyEnable) { // si hay proxy
        curl_setopt($ch, CURLOPT_PROXY, $proxyIP); // use this proxy
        curl_setopt($ch, CURLOPT_PROXYPORT, $proxyPort); // set this proxy's port
    }
    $headers = array(
        'Content-Type:application/json', // Le enviamos JSON al servidor con los datos
        'Authorization: Basic ' . base64_encode($username . ":" . $password) // Basic Authentication
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // Add headers
    $data_content = curl_exec($ch); // extract information from response
    $curl_errno = curl_errno($ch); // get error code
    $curl_error = curl_error($ch); // get error information
    if ($curl_errno > 0) { // si hay error
        $text = "cURL Error ($curl_errno): $curl_error"; // set error message
        fileLog($text, __DIR__ . "/logs/errores/" . date('Ymd') . "_errorAPI_WF.log"); // escribir en el log
        fileLog($text, __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log'); // escribir en el log
        respuestaScript($text, 'Error');
        exit; // salimos del script
    }
    curl_close($ch); // close curl handle
    return ($data_content) ? $data_content : fileLog("No hay datos en API WF", __DIR__ . "/logs/errores/" . date('Ymd') . "_API_WF.log") . fileLog("No hay datos en API WF", __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log'); // escribir en el log; // si no hay datos, escribir en el log
}
function tipoEjecucion()  // Función para saber desde donde se ejecta el script
{
    /** para que se cumplan estas condiciones hay que indicar el parámetro get para cada ejecución, siempre que se ejecute desde un servidor web.
     * los argumentos que se pueden pasar son:
     * - "script=true" para ejecutar el script desde el servidor web
     * - "html=true" para ejecutar el script desde el navegador
     * - Si se ejecuta el script desde un cron solo poner el parámetro "index.php tarea"  en los argumentos de la acción
     */
    $_GET['script'] = $_GET['script'] ?? false;  // Si no se indica el parámetro script, se asigna false
    $_GET['html'] = $_GET['html'] ?? false; // Si no se indica el parámetro html, se asigna false

    $_SERVER["REQUEST_METHOD"] = $_SERVER["REQUEST_METHOD"] ?? 'GET'; // Si no se indica el parámetro REQUEST_METHOD, se asigna GET

    $tipoArgv = $_SERVER["argv"][1] ?? ''; // Si no se indica el parámetro argv, se asigna ''
    $tipoEjecucion = ''; // Variable para almacenar el tipo de ejecución
    switch ($tipoArgv) { // Si se indica el parámetro argv
        case 'tarea': // Si se indica el parámetro tarea
            $tipoEjecucion = ' (T)'; // Se asigna el tipo de ejecución
            break;
        case 'echo': // Si se indica el parámetro echo
            $tipoEjecucion = ' (C)'; // Se asigna el tipo de ejecución
            break;
    }

    switch ($_SERVER["REQUEST_METHOD"]) { // obtener el método de la petición
        case ($_GET['script'] == true): // Si se indica el parámetro script
            $tipo = ' (M)'; // Manual
            break;
        case ($_GET['html'] == true): // Si se indica el parámetro html
            $tipo = ' (H)'; // HTML
            break;
        default:
            $tipo = '';
            break;
    }
    return $tipo . $tipoEjecucion;
}
function fileLog($text, $ruta_archivo) // escribir en el log
{
    $log = fopen($ruta_archivo, 'a');
    $date = date('d-m-Y H:i:s');
    $text = $date . ' ' . $text . tipoEjecucion() . "\n";
    fwrite($log, $text);
    fclose($log);
}
function json_validate($string) // Función para validar la estructura JSON
{
    $result = ($string); // si es un string, retornarlo
    switch (json_last_error()) { // evaluar el error

        case JSON_ERROR_NONE:
            // $error ='Sin errores';
            break;
        case JSON_ERROR_DEPTH:
            $error = 'Excedido tamaño máximo de la pila';
            fileLog($error, __DIR__ . "/logs/fichero.log");
            break;
        case JSON_ERROR_STATE_MISMATCH:
            $error = 'Desbordamiento de buffer o los modos no coinciden';
            fileLog($error, __DIR__ . "/logs/fichero.log");
            break;
        case JSON_ERROR_CTRL_CHAR:
            $error = 'Encontrado carácter de control no esperado';
            fileLog($error, __DIR__ . "/logs/fichero.log");
            break;
        case JSON_ERROR_SYNTAX:
            $error = 'Error de sintaxis, JSON mal formado';
            fileLog($error, __DIR__ . "/logs/fichero.log");
            break;
        case JSON_ERROR_UTF8:
            $error = 'Caracteres UTF-8 mal-formados, posiblemente codificados de forma incorrecta';
            fileLog($error, __DIR__ . "/logs/fichero.log");
            break;
        default:
            $error = 'Error desconocido';
            fileLog($error, __DIR__ . "/logs/fichero.log");
            break;
    }
    respuestaScript($error, 'Error');
}
function fechaFormat($fecha, $format) // Función para formatear fechas
{
    $fecha = (empty($fecha)) ? '0000-00-00' : $fecha; // si no hay fecha, se asigna a 00/00/00
    $date = date_create($fecha); // crear objeto de fecha
    $FormatFecha = date_format($date, $format); // formatear fecha
    return $FormatFecha; // retornar fecha formateada
}
function esHora($hora) // Función para validar el formato de las horas
{
    if (preg_match('/^([0-1][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/', $hora)) { // Formato de 24 horas
        // Devuelve la hora correcta
    } else {
        $hora = "00:00"; // Devuelve la hora correcta
    }
    $hora = explode(':', $hora); // Separo las horas
    $hora = $hora[0] * 60 + $hora[1]; // Calculo las horas en minutos
    if ($hora >= 0 && $hora <= 1439) { // Valido que la hora sea correcta
        return true;  // Retorna verdadero
    } else {  // Si no es correcta
        return false; // Retorna falso
    }
}
function horaFormat($value) // Función para formatear horas
{
    $value = empty($value) ? '00:00' : $value; // si no hay hora, se asigna a 00:00
    $value = explode(':', $value); // Separo las horas
    $hora = str_pad(intval($value[0]), 2, '0', STR_PAD_LEFT); // Pongo a 2 dígitos la hora auto-completando con cero a la izquierda
    $min = ($value[1] < 10) ? str_pad(intval($value[1]), 2, '0', STR_PAD_LEFT) : $value[1]; // Si los minutos son menores a 10 Pongo a 2 dígitos el minuto auto-completando con cero a la izquierda
    $min = ($min > 59) ? 59 : $min; // Si los minutos son mayores a 59 Pongo a 59 los minutos
    return $hora . ":" . $min; // Retorna la hora formateada
}
function esFecha($fecha) // Función para validar el formato de las fechas
{
    if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $fecha)) { // Formato de fecha
        // $fecha = $fecha; // Devuelve la fecha correcta
    } else { // Si no es correcta
        $fecha = "0000-00-00"; // Devuelve la fecha correcta
    }
    return $fecha; // Retorna la fecha formateada
}
function validar_fecha($fecha) // Función para validar la fecha
{
    $valores = explode('/', $fecha); // Separo los valores de la fecha
    $checkdate = checkdate(intval($valores[1]), intval($valores[0]), intval($valores[2])); // Chequeo la fecha
    if (count($valores) === 3 && $checkdate) { // Si los bloques de valores son 3 y la fecha es correcta
        return true; // Retorna verdadero
    }
    return false; // Si no es correcta
}
function pingWebService($url) // Función para validar que el Webservice de Control Horario esta disponible
{
    $iniPingWebService = microtime(true); // Iniciamos el contador de tiempo de conexion
    $ch = curl_init(); // Inicializar el objeto curl
    curl_setopt($ch, CURLOPT_URL, $url); // Establecer la URL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Establecer que retorne el contenido del servidor
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // The number of seconds to wait while trying to connect
    curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch); // extract information from response
    $curl_errno = curl_errno($ch); // get error code
    $curl_error = curl_error($ch); // get error information
    if ($curl_errno > 0) { // si hay error
        $text = "Error Ping WebService. \"Cod: $curl_errno: $curl_error\""; // set error message
        sendEmail("WFCH -> Error Ping WebService", "<pre>$text</pre>");
        fileLog($text, __DIR__ . '/logs/errores/' . date('Ymd') . '_errorWebService.log'); // escribir en el log
        fileLog($text, __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log'); // escribir en el log
        $finPingWebService = microtime(true); // Terminamos el contador de tiempo de conexion
        $durPingWebService = (round($finPingWebService - $iniPingWebService, 2)); // Obtenemos la duracion de la conexion en segundos
        fileLog("Duracion Ping WebService $durPingWebService segundos.", __DIR__ . '/logs/errores/' . date('Ymd') . '_errorWebService.log'); // escribir en el log
        fileLog("Duracion Ping WebService $durPingWebService segundos.", __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log'); // escribir en el log
        respuestaScript($text, 'Error'); // retornar error
        exit; // salimos del script
    }
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // get http response code
    curl_close($ch); // close curl handle
    //return curl_getinfo($ch, CURLINFO_HTTP_CODE); // retornar el codigo de respuesta
    $textoErr = "Error -> No hay conexion con WebService: " . $http_code;
    return ($http_code == 201) ? true : fileLog($textoErr, __DIR__ . "/logs/errores/" . date('Ymd') . "_errorWebService.log") . respuestaScript($textoErr, 'Error') . fileLog($textoErr, __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log');
    ; // escribir en el log
}
function respuestaWebService($respuesta) // Función para formatear la respuesta del Webservice de Control Horario
{
    $respuesta = substr($respuesta, 1, -1);
    $respuesta = explode("=", $respuesta);
    return ($respuesta[0]);
}
function respuestaProcesoWebService($url) // Función para procesar la respuesta del Webservice de Control Horario
{
    do { // bucle para verificar el estado del proceso
        $ch = curl_init(); // inicializar curl
        curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return the transfer as a string
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $respuesta = curl_exec($ch); // ejecutar curl
        curl_close($ch); // cerrar curl
        return respuestaWebService($respuesta); // retornar el estado del proceso
    } while (respuestaWebService($respuesta) == 'Pendiente'); // bucle hasta que el estado sea diferente de pendiente
}
function ingresarNovedad($LegajoDesde, $LegajoHasta, $FechaDesde, $FechaHasta, $Laboral, $Novedad, $Observaciones, $Horas, $url) // Función para ingresar la novedad con el WebService de Control Horario
{
    $textWF = ($Observaciones == '') ? 'WF Novedades' : '. WF Novedades'; // Concateno texto. WF Novedades a la Observación
    $data = "{Usuario=Script WF, TipoDePersonal=0, LegajoDesde=$LegajoDesde, LegajoHasta=$LegajoHasta, FechaDesde=$FechaDesde, FechaHasta=$FechaHasta, Empresa=0, Planta=0, Sucursal=0, Grupo=0, Sector=0, Seccion=0, Laboral=$Laboral, Novedad=$Novedad,Justifica=0, Observacion=$Observaciones$textWF, Horas=$Horas, Causa=0, Categoria=0}"; // string de datos para enviar a WebService
    $ch = curl_init(); // inicializar curl
    curl_setopt($ch, CURLOPT_URL, $url . "Novedades"); // agregar el parámetro Novedades a la URL del WebService para identificar el tipo de comando a enviar
    curl_setopt($ch, CURLOPT_POST, TRUE); // establecer el método de envío de datos a post
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  // establecer los datos a enviar
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // establecer que retorne el contenido del servidor
    $respuesta = curl_exec($ch); // ejecutar curl
    $curl_errno = curl_errno($ch); // get error code
    $curl_error = curl_error($ch); // get error information
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // get http response code
    if ($curl_errno > 0) { // si hay error
        $text = "cURL Error ($curl_errno): $curl_error"; // set error message
        fileLog($text, __DIR__ . "/logs/errores/" . date('Ymd') . "_errorWebService.log"); // escribir en el log
        fileLog($text, __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log'); // escribir en el log
        respuestaScript($text, 'Error'); // retornar error
        exit; // salimos del script
    }
    $hashtag = stringAleatorio(4); // generar un hashtag aleatorio
    if ($http_code == 404) { // si el codigo de respuesta es 404 Not Found
        fileLog("#" . $hashtag . " Error al conectar con Novedades WebService: " . $http_code . ' Not Found ' . $respuesta, __DIR__ . "/logs/errores/" . date('Ymd') . "_errorWebService.log"); // escribir en el log
    }
    ;
    curl_close($ch);  // cerrar curl

    $processID = respuestaWebService($respuesta); // obtengo el id del proceso
    $estado = $url . "Estado?ProcesoId=" . $processID; // obtengo el estado del proceso

    if ($http_code == 201) { // si el codigo de respuesta es 201
        if (respuestaProcesoWebService($estado) == 'Terminado') { // si el proceso termino correctamente
            return true; // retornar true
        } else {
            fileLog("#" . $hashtag . " Error al ingresar Novedades WebService. Estado: " . $estado, __DIR__ . "/logs/errores/" . date('Ymd') . "_errorWebService.log"); // escribir en el log
            fileLog("#" . $hashtag . " Error al ingresar Novedades WebService. Estado: " . $estado, __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log'); // escribir en el log
        }
        exit; // salimos del script
    } else { // si el codigo de respuesta no es 201
        $textErr = "#" . $hashtag . " Error al ingresar Novedad (" . $Novedad . ") Legajo :" . $LegajoDesde . " Fechas: " . $FechaDesde . " al " . $FechaDesde . " - " . $respuesta; // set error message
        fileLog($textErr, __DIR__ . "/logs/errores/" . date('Ymd') . "_errorWebService.log"); // escribir en el log
        fileLog($textErr, __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log');
        //respuestaScript($textErr, 'Error'); // retornar error
    }
}
function procesarNovedad($legajo, $FechaDesde, $FechaHasta, $url) // Función para ingresar la novedad con el WebService de Control Horario
{
    $data = "{Usuario=Script WF,TipoDePersonal=0,LegajoDesde='$legajo',LegajoHasta='$legajo',FechaDesde='$FechaDesde',FechaHasta='$FechaHasta',Empresa=0,Planta=0,Sucursal=0,Grupo=0,Sector=0,Seccion=0}";
    $ch = curl_init(); // inicializar curl
    curl_setopt($ch, CURLOPT_URL, $url . "Procesar"); // agregar el parámetro Procesar a la URL del WebService para identificar el tipo de comando a enviar
    curl_setopt($ch, CURLOPT_POST, TRUE); // establecer el método de envío de datos a post
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  // establecer los datos a enviar
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // establecer que retorne el contenido del servidor
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $respuesta = curl_exec($ch); // ejecutar curl
    $curl_errno = curl_errno($ch); // get error code
    $curl_error = curl_error($ch); // get error information
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // get http response code
    if ($curl_errno > 0) { // si hay error
        $text = "cURL Error ($curl_errno): $curl_error"; // set error message
        fileLog($text, __DIR__ . "/logs/errores/" . date('Ymd') . "_errorWebService.log"); // escribir en el log
        respuestaScript($text, 'Error'); // retornar error
        exit; // salimos del script
    }
    $hashtag = stringAleatorio(4); // generar un hashtag aleatorio
    if ($http_code == 404) { // si el codigo de respuesta es 404 Not Found
        $textErr = "#" . $hashtag . " Error al conectar con Procesar WebService: " . $http_code . ' Not Found ';
        fileLog($textErr . $respuesta, __DIR__ . "/logs/errores/" . date('Ymd') . "_errorWebService.log"); // escribir en el log
        fileLog($textErr . $respuesta, __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log');
        respuestaScript($textErr . $respuesta, 'Error'); // retornar error
    }
    ;
    curl_close($ch);  // cerrar curl

    $processID = respuestaWebService($respuesta); // obtengo el id del proceso
    $estado = $url . "Estado?ProcesoId=" . $processID; // obtengo el estado del proceso

    if ($http_code == 201) { // si el codigo de respuesta es 201
        if (respuestaProcesoWebService($estado) == 'Terminado') { // si el proceso termino correctamente
            return true; // retornar true
        } else {
            fileLog("#" . $hashtag . " Error al procesar Novedad con WebService. Estado: " . $estado, __DIR__ . "/logs/errores/" . date('Ymd') . "_errorWebService.log"); // escribir en el log
            fileLog("#" . $hashtag . " Error al procesar Novedad con WebService. Estado: " . $estado, __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log');
        }
        exit;
        // salimos del script
    } else { // si el codigo de respuesta no es 201
        $textErr = "#" . $hashtag . " Error al procesar Novedad Legajo :" . $legajo . " Fechas: " . $FechaDesde . " al " . $FechaDesde . " - " . $respuesta;
        fileLog($textErr, __DIR__ . "/logs/errores/" . date('Ymd') . "_errorWebService.log"); // escribir en el log
        fileLog($textErr, __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log');
        respuestaScript($textErr . $respuesta, 'Error'); // retornar error
    }
}
function stringAleatorio($longitud) // Función para generar un string aleatorio
{
    $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; // caracteres a utilizar
    $string = ''; // variable para almacenar la cadena generada
    for ($i = 0; $i < $longitud; $i++) { // bucle para generar la cadena
        $string .= $caracteres[rand(0, strlen($caracteres) - 1)]; // concatenar caracter aleatorio
    }
    return $string; // retornar la cadena generada
}
function getClientIP()
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }

    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
}
function audito_ch_old($AudTipo, $AudDato, $link) // Función para auditar el control horario
{
    $ipCliente = getClientIP() ?? '127.0.0.1'; // obtengo la ip del cliente

    $ipCliente = substr($ipCliente, 0, 20);
    $AudUser = 'Script WF'; // usuario que realiza la acción
    $AudTerm = $ipCliente;
    $AudModu = 21; // modulo en el que se realiza la acción
    $FechaHora = "getdate()";
    $AudFech = fechaHora();
    $AudHora = date('H:i:s'); // obtengo la hora actual

    $procedure_params = [ // parámetros del procedimiento almacenado
        [&$AudFech],
        [&$AudHora],
        [&$AudUser],
        [&$AudTerm],
        [&$AudModu],
        [&$AudTipo],
        [&$AudDato],
        [&$FechaHora],
        ["(UTC-03:00) Ciudad de Buenos Aires"], // zona horaria
    ];
    $sql = "exec DATA_AUDITORInsert @AudFech=?,@AudHora=?,@AudUser=?,@AudTerm=?,@AudModu=?,@AudTipo=?,@AudDato=?,@FechaHora=?, @AudZonaHoraria=?"; // procedimiento almacenado
    $stmt = sqlsrv_prepare($link, $sql, $procedure_params); // ejecuto el procedimiento almacenado

    if (sqlsrv_execute($stmt)) { // ejecuto el procedimiento almacenado

    } else {
        if (($errors = sqlsrv_errors()) != null) {
            foreach ($errors as $error) {
                $mensaje = explode(']', $error['message']);
                $dataAud = ["auditor" => "error", "dato" => $mensaje[3]];
            }
            fileLog('Error Audito: ' . $error['message'], __DIR__ . "/logs/errores/" . date('Ymd') . "_errorAuditor.log"); // escribir en el log
            fileLog('Error Audito: ' . $error['message'], __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log');
            respuestaScript('Error Audito: ' . $error['message'], 'Error'); // retornar error
        }
    }
}
function audito_ch($AudTipo, $AudDato, $link) // Función para auditar el control horario
{
    try {
        $ipCliente = getClientIP() ?? '127.0.0.1'; // obtengo la ip del cliente
        $ipCliente = substr($ipCliente, 0, 20);
        $AudUser = 'Script WF'; // usuario que realiza la acción
        $AudTerm = $ipCliente;
        $AudModu = 21; // modulo en el que se realiza la acción
        $FechaHora = fechaHora();
        $AudFech = fechaHora();
        $AudHora = date('H:i:s'); // obtengo la hora actual
        $AudZonaHoraria = "(UTC-03:00) Ciudad de Buenos Aires"; // zona horaria

        $procedure_params = [ // parámetros del procedimiento almacenado
            [&$AudFech],
            [&$AudHora],
            [&$AudUser],
            [&$AudTerm],
            [&$AudModu],
            [&$AudTipo],
            [&$AudDato],
            [&$FechaHora],
            [&$AudZonaHoraria]
        ];
        $sql = "exec DATA_AUDITORInsert @AudFech=?,@AudHora=?,@AudUser=?,@AudTerm=?,@AudModu=?,@AudTipo=?,@AudDato=?,@FechaHora=?, @AudZonaHoraria=?"; // procedimiento almacenado
        $stmt = sqlsrv_prepare($link, $sql, $procedure_params); // preparo el procedimiento almacenado

        if (!sqlsrv_execute($stmt)) { // ejecuto el procedimiento almacenado
            $errors = sqlsrv_errors();
            error_log(print_r($errors, true)); // escribir en el log
            $errorMsg = '';
            if ($errors != null) {
                foreach ($errors as $error) {
                    $mensaje = explode(']', $error['message']);
                    $errorMsg = isset($mensaje[3]) ? $mensaje[3] : $error['message'];
                }
            }
            fileLog('Error Audito: ' . $errorMsg, __DIR__ . "/logs/errores/" . date('Ymd') . "_errorAuditor.log");
            fileLog('Error Audito: ' . $errorMsg, __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log');
            respuestaScript('Error Audito: ' . $errorMsg, 'Error');
        }
    } catch (Throwable $e) {
        $msg = 'Excepción Audito: ' . $e->getMessage();
        fileLog($msg, __DIR__ . "/logs/errores/" . date('Ymd') . "_errorAuditor.log");
        fileLog($msg, __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log');
        respuestaScript($msg, 'Error');
    }
}
function totalDiasFechas($fecha_inicial, $fecha_final) // obtengo los días entre dos fechas
{
    $days = (strtotime($fecha_inicial) - strtotime($fecha_final)) / 86400; // resto las dos fechas
    $days = abs($days); // obtengo el valor absoluto
    $days = floor($days); // redondeo hacia abajo
    return $days; // retorno el resultado
}
function fechaIniFinDias($fecha_inicial, $fecha_final, $days) // obtengo objeto de rango de fecha separado en rangos de $days
{
    $TotalDias = totalDiasFechas($fecha_inicial, $fecha_final); // obtengo la cantidad de días entre las fechas
    $arrayTotalMeses[] = array(); // creo un array para almacenar los meses
    for ($i = 0; $i < intval($TotalDias / $days); $i++) { // bucle para obtener los meses
        $arrayTotalMeses[] = array($i); // almaceno los meses en el array
    }
    foreach ($arrayTotalMeses as $value) { // bucle para obtener los meses
        $fecha1 = $fecha_inicial; // fecha inicial
        // $days = ($days-1);
        $fecha2 = date("Ymd", strtotime($fecha1 . "+ " . $days . " days")); // fecha final
        $fecha2 = ($fecha2 > $fecha_final) ? $fecha_final : $fecha2; // si la fecha2 es mayor a la fecha final
        if (($fecha1 > $fecha2)) { // si la fecha inicial es mayor a la fecha final
            break; // salgo del bucle
        }
        $arrayFechas[] = array( // almaceno las fechas en el array
            'fecha_desde' => fechaFormat($fecha1, 'd/m/Y'), // fecha inicial
            'fecha_hasta' => fechaFormat($fecha2, 'd/m/Y'), // fecha final
            'fecha_desdeStr' => fechaFormat($fecha1, 'Ymd'),
            'fecha_hastaStr' => fechaFormat($fecha2, 'Ymd'),
        );
        $fecha_inicial = date("Ymd", strtotime($fecha2 . "+ 1 days")); // fecha inicial
    }
    return $arrayFechas; // retorno el array con las fechas
}
function arrayFechas($start, $end)
{
    $range = array();

    if (is_string($start) === true)
        $start = strtotime($start);
    if (is_string($end) === true)
        $end = strtotime($end);

    // if ($start > $end) return createDateRangeArray($end, $start);

    do {
        $range[] = date('Ymd', $start);
        $start = strtotime("+ 1 day", $start);
    } while ($start <= $end);

    return $range;
}
function dateDifference($date_1, $date_2, $differenceFormat = '%a') // diferencia en días entre dos fechas
{
    $datetime1 = date_create($date_1); // creo la fecha 1
    $datetime2 = date_create($date_2); // creo la fecha 2

    $interval = date_diff($datetime1, $datetime2); // obtengo la diferencia de fechas
    return $interval->format($differenceFormat); // devuelvo el número de días

}
function borrarLogs($path, $days, $ext) // borra los logs a partir de una cantidad de días
{
    $pathConfig = __DIR__ . '\data.php'; // ruta del archivo data.php
    $dataConfig = parse_ini_file($pathConfig, true); // Obtenemos los datos del data.php

    if ($dataConfig['borrarLogs']['estado'] == true) { // si está activado el borrado de logs
        $files = glob($path . '*' . $ext); //obtenemos el nombre de todos los ficheros
        foreach ($files as $file) { // recorremos todos los ficheros
            $lastModifiedTime = filemtime($file); // obtenemos la fecha de modificación del fichero
            $currentTime = time(); // obtenemos la fecha actual
            $dateDiff = dateDifference(date('Ymd', $lastModifiedTime), date('Ymd', $currentTime)); // obtenemos la diferencia de fechas
            if ($dateDiff >= $days):
                unlink($file); // borramos el fichero
                fileLogs("Se elimino log \"$file\"", __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log", '');
                fileLog("Se elimino log \"$file\"", __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log');
            endif; //elimino el fichero
        }
    }
}
function fileLogs($text, $ruta_archivo, $tipo) // escribe  el log de novedades
{
    $pathConfig = __DIR__ . '\data.php'; // ruta del archivo data.php
    $dataConfig = parse_ini_file($pathConfig, true); // Obtenemos los datos del data.php
    $logNovedadesOk = $dataConfig['logNovedades']['success'];
    $logNovedadesEr = $dataConfig['logNovedades']['error'];
    $logConnOk = $dataConfig['logConexion']['success'];
    $logConnErr = $dataConfig['logConexion']['error'];
    $date = date('d-m-Y H:i:s'); // obtenemos la fecha actual
    $textJson = $text; // obtenemos el texto a escribir
    $text2 = $text . "\n"; // armamos el texto a escribir
    $text = $date . ' ' . $text . tipoEjecucion() . "\n"; // armamos el texto a escribir
    switch ($tipo): // según el tipo de log
        case 'novOk': // si es un log de novedades exitosa
            if ($logNovedadesOk): // si está activado el log de novedades exitosa
                $log = fopen($ruta_archivo, 'a'); // abrimos el archivo
                fwrite($log, $text); // escribimos en el archivo
                respuestaScript($text2, 'ok');
                // respuestaScript($text, 'ok'); // Respuesta del script
            endif;
            break;
        case 'novErr': // si es un log de novedades fallida
            if ($logNovedadesEr): // si está activado el log de novedades fallida
                $log = fopen($ruta_archivo, 'a'); // abrimos el archivo
                fwrite($log, $text); // escribimos en el archivo
                respuestaScript($text2, 'ok');
            endif;
            break;
        case 'conOk': // si es un log de conexión exitosa
            if ($logConnOk): // si está activado el log de conexión exitosa
                $log = fopen($ruta_archivo, 'a'); // abrimos el archivo
                fwrite($log, $text); // escribimos en el archivo
                respuestaScript($text2, 'ok');
            endif;
            break;
        case 'conErr': // si es un log de conexión fallida
            if ($logConnErr): // si está activado el log de conexión fallida
                $log = fopen($ruta_archivo, 'a'); // abrimos el archivo
                fwrite($log, $text); // escribimos en el archivo
                respuestaScript($text2, 'ok');
            endif;
            break;
        case 'json': // si es un log de json
            //header("Content-Type: application/json; charset=utf-8"); // json response
            $log = fopen($ruta_archivo, 'w'); // abrimos el archivo y sobre-escribimos
            fwrite($log, $textJson); // escribimos en el archivo
            respuestaScript($text2, 'ok');
            break;
        default:
            $log = fopen($ruta_archivo, 'a'); // abrimos el archivo
            fwrite($log, $text); // escribimos en el archivo
            fclose($log); // cerramos el archivo
            respuestaScript($text2, 'ok');
            break;
    endswitch; // fin del switch
}
;
function fileLogsJson($text, $ruta_archivo) // escribe  el log de novedades
{
    $log = fopen($ruta_archivo, 'a'); // abrimos el archivo
    $textJson = $text; // obtenemos el texto
    $log = fopen($ruta_archivo, 'w'); // abrimos el archivo
    fwrite($log, $textJson); // escribimos en el archivo
}
;
function getDataJson($url) // obtiene el json de la url
{
    if (file_exists($url)) { // si existe el archivo
        $data = file_get_contents($url); // obtenemos el contenido del archivo
        if ($data) { // si el contenido no está vacío
            $data = json_decode($data, true); // decodificamos el json
            return $data; // devolvemos el json
        } else { // si el contenido está vacío
            fileLog("No hay informacion en el archivo \"$url\"", __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log"); // escribimos en el log
        }
    } else { // si no existe el archivo
        fileLog("No existe archivo \"$url\"", __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log"); // escribimos en el log
        return false; // devolvemos false
    }
}
function getDataIni($url) // obtiene el json de la url
{
    if (file_exists($url)) { // si existe el archivo
        $data = file_get_contents($url); // obtenemos el contenido del archivo
        if ($data) { // si el contenido no está vacío
            $data = parse_ini_file($url, true); // Obtenemos los datos del data.php
            return $data; // devolvemos el json
        } else { // si el contenido está vacío
            fileLog("No hay informacion en el archivo \"$url\"", __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log"); // escribimos en el log
        }
    } else { // si no existe el archivo
        fileLog("No existe archivo \"$url\"", __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log"); // escribimos en el log
        return false; // devolvemos false
    }
}
function respuestaScript($mensaje, $status) // genera la respuesta del script cuando parámetro get script=true
{

    $tipo = tipoEjecucion(); // obtenemos el tipo de ejecución

    echo (($_SERVER["argv"][1] ?? '') == 'echo') ? $mensaje : '';  // si el argumento es echo, escribimos en pantalla

    $_GET['script'] = $_GET['script'] ?? false; // si no existe el parámetro script, lo setea a false
    if ($_GET['script']) { // si es una petición con el parámetro get script=true
        header("Content-Type: application/json; charset=utf-8"); // json response
        $data = array('status' => $status, 'Mensaje' => "<br><br>$mensaje.$tipo"); // Mensaje
        echo json_encode($data, JSON_PRETTY_PRINT); // devuelve el json con la respuesta
        //exit(); // Salir
    }
    $_GET['echo'] = $_GET['echo'] ?? false; // si no existe el parámetro echo, lo setea a false
    if ($_GET['echo']) { // si es una petición con el parámetro get echo=true
        echo $mensaje; // devuelve la respuesta
        //exit(); // Salir
    }
    $_GET['html'] = $_GET['html'] ?? false; // si no existe el parámetro html, lo setea a false
    if ($_GET['html']) { // si es una petición con el parámetro get html=true
        echo "<html style='width:100%; height:100%; background-color: #ddd'><h3><div style='padding:20px;'>$mensaje$tipo</div></h3></html>"; // devuelve la respuesta
        //exit(); // Salir
    }
}
function fechaHora()
{
    timeZone();
    $t = explode(" ", microtime());
    $t = date("Ymd H:i:s", $t[1]) . substr((string) $t[0], 1, 4);
    return $t;
}
function fechaHora2()
{
    timeZone();
    $t = date("Y-m-d H:i:s");
    return $t;
}
function timeZone()
{
    return date_default_timezone_set('America/Argentina/Buenos_Aires');
}
function timeZone_lang()
{
    return setlocale(LC_TIME, "es_ES");
}
function write_ini_file($assoc_arr, $path, $has_sections = false)
{
    $content = "; <?php exit; ?> <-- ¡No eliminar esta línea! --> \n";
    $content .= "; \n";
    $content .= "; --> ARCHIVO DE CONFIGURACIÓN DEL SCRIPT WF CH <--\n";
    $content .= "; \n";
    $content .= "; \n";
    if ($has_sections) {
        foreach ($assoc_arr as $key => $elem) {
            $content .= "[" . $key . "]\n";
            foreach ($elem as $key2 => $elem2) {
                if (is_array($elem2)) {
                    for ($i = 0; $i < count($elem2); $i++) {
                        $content .= $key2 . "[] =\"" . $elem2[$i] . "\"\n";
                    }
                } else if ($elem2 == "")
                    $content .= $key2 . " =\n";
                else
                    $content .= $key2 . " = \"" . $elem2 . "\"\n";
            }
        }
    } else {
        foreach ($assoc_arr as $key => $elem) {
            if (is_array($elem)) {
                for ($i = 0; $i < count($elem); $i++) {
                    $content .= $key . "[] = \"" . $elem[$i] . "\"\n";
                }
            } else if ($elem == "")
                $content .= $key . " = \n";
            else
                $content .= $key . " = \"" . $elem . "\"\n";
        }
    }

    if (!$handle = fopen($path, 'w')) {
        return false;
    }

    $content .= "; \n";
    $content .= "; \n";
    $content .= "; ## CONFIGURACIÓN DE CONEXION A MS SQLSERVER ##\n";
    $content .= "; [mssql] \n";
    $content .= ";  srv  = string servidor mssql o ip. Si es Local puede ir un punto\n";
    $content .= ";  db   = string con el nombre de la base de datos\n";
    $content .= ";  user = string con el usuario de la base de datos\n";
    $content .= ";  pass = string con el password de la base de datos\n";
    $content .= "; < --- >\n";
    $content .= "; \n";
    $content .= "; ## ACTIVAR LOGS DE CONEXION A LA DB MSSQL ##\n";
    $content .= "; [logConexion]\n";
    $content .= ";  success  = ingresar un 1 = activo. Dejar vacío si esta inactivo\n";
    $content .= ";  error    = ingresar un 1 = activo. Dejar vacío si esta inactivo.\n";
    $content .= "; < --- >\n";
    $content .= "; \n";
    $content .= "; ## CONFIGURACIÓN DE CONEXION A LA API DE WORKFLOW DE NOVEDADES (WF) ##\n";
    $content .= "; [api]\n";
    $content .= ";  url  = string con la ruta de conexion a la API workflow. Ejemplo \"https://hr-process.com/hrctest/api/novedades/\" \n";
    $content .= ";  user = string con el usuario de autenticación a la API. Ejemplo \"Admin\"\n";
    $content .= ";  pass = string con el password de autenticación a la API. Ejemplo \"Admin\"\n";
    $content .= "; < --- >\n";
    $content .= "; \n";
    $content .= "; ## CONFIGURACIÓN DE CONEXION AL WEBSERVICE DE CONTROL HORARIO (CH) ##\n";
    $content .= "; [webService]\n";
    $content .= ";  url = string con la ruta de conexion al webservice de Control Horario. Ejemplo \"http://192.168.1.202:6400/RRHHWebService/\" \n";
    $content .= "; < --- >\n";
    $content .= "; \n";
    $content .= "; ## ACTIVAR LOGS DE NOVEDADES INGRESADAS CORRECTAMENTE(success) E INCORRECTAMENTE(error) ##\n";
    $content .= "; [logNovedades]\n";
    $content .= ";  success = ingresar un 1 = activo. Dejar vacÍo si esta inactivo\n";
    $content .= ";  error   = ingresar un 1 = activo. Dejar vacÍo si esta inactivo\n";
    $content .= "; < --- >\n";
    $content .= "; \n";
    $content .= "; ## CONFIGURACIÓN DE PROXY. SI LA CONEXION A INTERNET PASAS POR UN PROXY ##\n";
    $content .= "; [proxy]\n";
    $content .= ";  ip      = dirección de ip del proxy\n";
    $content .= ";  puerto  = numero de puerto del proxy\n";
    $content .= ";  enabled = ingresar un 1 = activo. Dejar vacÍo si esta inactivo\n";
    $content .= "; < --- >\n";
    $content .= "; \n";
    $content .= "; ## ACTIVAR EL BORRADO DE LOGS ##\n";
    $content .= "; [borrarLogs]\n";
    $content .= ";  estado = ingresar un 1 = activo. Dejar vacÍo si esta inactivo. \"Al estar inactivo nunca se eliminarán los logs que genera el script\"\n";
    $content .= ";  días   = numero con cantidad de días a borrar los logs que genera el script. \"Valor mínimo 1\".\n";
    $content .= "; < --- >\n";
    $content .= "; \n";
    $content .= "; \n";
    $content .= "; --> autor  : Norberto CH \n";
    $content .= "; --> para   : HR Consulting \n";
    $content .= "; --> e-mail : nch@outlook.com.ar \n";
    $success = fwrite($handle, $content);
    fclose($handle);

    return $success;
}
function setErrorApi($jsonData, $solicitud, $apiUrl, $auth, $proxy)
{
    $sendApiData = sendApiData($apiUrl . '?TYPE=respuesta&data=[' . $jsonData . ']', $auth, $proxy, 10, ''); // Enviamos el objeto JSON a la API
    $respuesta = (json_decode($sendApiData)); // Decodifico el JSON de la respuesta de la API WF
    if ($respuesta->SUCCESS == 'YES') {
        $textRespuesta = "Solicitud ($solicitud) actualizada correctamente en WF con status \"N\""; // Texto de la respuesta envío WF
    } else {
        $textRespuesta = $respuesta->MENSAJE; // Texto de la respuesta envío WF
    }
    fileLogs("$textRespuesta", __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", ''); // Guardamos el texto de la novedad en el archivo de log
}
function setExportadoApi($jsonData, $solicitud, $apiUrl, $auth, $proxy)
{
    $sendApiData = sendApiData($apiUrl . "?TYPE=update&data=[$jsonData]", $auth, $proxy, 10, ''); // Enviamos el objeto JSON a la API notificando que se termino de procesar la novedad con status 'E' 
    $respuesta = json_decode($sendApiData); // Decodifico el JSON de la respuesta de la API WF

    if ($respuesta->SUCCESS == 'YES') {
        $textRespuesta = "Solicitud ($solicitud) actualizada correctamente en WF con status \"E\""; // Texto de la respuesta envío WF
    } else {
        $textRespuesta = $respuesta->MENSAJE; // Texto de la respuesta envío WF
    }

    fileLogs("$textRespuesta", __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", ''); // Guardamos el texto de la novedad en el archivo de log
}
function finPendienteLog($ini, $solicitud, $tipo = 'P = Pendiente')
{
    $fin = microtime(true); // Tiempo de finalización del envío al Api
    $dur = (round($fin - $ini, 2)); // Duracion del envío al Api
    fileLogs("Fin Registro WF ($tipo). Solicitud: " . $solicitud . ". Dur: $dur", __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", '');
}
function iniPendienteLog($solicitud, $tipo = 'P = Pendiente')
{
    fileLogs("Inicio Registro WF ($tipo). Solicitud: " . $solicitud, __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", '');
}
function sendEmail($subject, $body) // Enviar datos a la API
{

    $pathConfigData = __DIR__ . '/data.php'; // Path to data.php
    $dataConfig = getDataIni($pathConfigData); // Obtenemos los datos del data.php
    $dataConfig = parse_ini_file($pathConfigData, true); // Obtenemos los datos del data.php
    $proxy = array($dataConfig['proxy']['ip'], $dataConfig['proxy']['port'], $dataConfig['proxy']['enabled']); // Proxy datos
    $url = 'https://ht-api.helpticket.com.ar/sendMail/';

    $data = array(
        "subjet" => "$subject | " . $dataConfig['mssql']['db'],
        "to" => "wf-ch",
        "replyTo" => "wf-ch",
        "body" => $body
    );

    $timeout = $timeout ?? 10;
    $proxyIP = $proxy[0]; // IP del proxy
    $proxyPort = $proxy[1]; // Puerto del proxy
    $proxyEnable = $proxy[2]; // Habilitado o no
    $ch = curl_init(); // initialize curl handle
    curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); // The number of seconds to wait while trying to connect
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    if ($proxyEnable) { // si hay proxy
        curl_setopt($ch, CURLOPT_PROXY, $proxyIP); // use this proxy
        curl_setopt($ch, CURLOPT_PROXYPORT, $proxyPort); // set this proxy's port
    }
    $headers = array(
        'Content-Type:application/json', // Le enviamos JSON al servidor con los datos
        'Token:38c913f0cf4d1a9e588c687c7f7e9871a52245ac' // token
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // Add headers
    $data_content = curl_exec($ch); // extract information from response
    $curl_errno = curl_errno($ch); // get error code
    $curl_error = curl_error($ch); // get error information
    if ($curl_errno > 0) { // si hay error
        $text = "cURL Error ($curl_errno): $curl_error"; // set error message
        fileLog($text, __DIR__ . "/logs/errores/" . date('Ymd') . "_errorAPI_WF.log"); // escribir en el log
        fileLog($text, __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log'); // escribir en el log
        respuestaScript($text, 'Error');
        exit; // salimos del script
    }
    curl_close($ch); // close curl handle
    return ($data_content) ? $data_content : fileLog("No hay datos en API WF", __DIR__ . "/logs/errores/" . date('Ymd') . "_API_WF.log") . fileLog("No hay datos en API WF", __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log'); // escribir en el log; // si no hay datos, escribir en el log
}
$optionEncode = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT;
