<?php
function defaultConfigData() // default config data
{
    $datos = array(
        'mssql' => array('srv' => '', 'db' => '', 'user' => '', 'pass' => ''), 'logConexion' => array('success' => false, 'error' => true), 'api' => array('url' => "http://52.38.68.90/hrc/api/novedades/", 'user' => 'admin', 'pass' => 'admin'), 'webService' => array('url' => "http:/localhost:6400/RRHHWebService/"), 'logNovedades' => array('success' => true, 'error' => true), 'proxy' => array('ip' => '', 'port' => '', 'enabled' => false), 'borrarLogs' => array('estado' => true, 'dias' => 31)
    );
    return $datos;
}
function test_input($data) // Funcion para limpiar los datos de entrada
{
    $data = trim($data);
    $data = htmlspecialchars(stripslashes($data), ENT_QUOTES);
    return ($data);
}
function dataNovedades($link) // Obtiene los datos de las novedades de la base de datos CH
{
    $params  = array();
    $options = array("Scrollable" => SQLSRV_CURSOR_KEYSET);
    $query = "SELECT NOVEDAD.NovTipo, NOVEDAD.NovCodi, NOVEDAD.NovDesc, NOVEDAD.NovID FROM NOVEDAD WHERE NOVEDAD.NovCodi > 0";
    $stmt  = sqlsrv_query($link, $query, $params, $options);
    while ($row = sqlsrv_fetch_array($stmt)) {
        $data[] = array(
            'CodNov'    => $row['NovCodi'], // Codigo de la novedad
            'NovDesc'   => $row['NovDesc'], // Descripcion de la novedad
            'CodTipo'   => $row['NovTipo'], // Codigo del tipo de novedad
            'TipoDesc'  => descTipoNov($row['NovTipo']), // Descripcion del tipo de novedad
            'NovID'     => $row['NovID'] // ID de la novedad
        );
    }
    return $data;
    sqlsrv_free_stmt($stmt);
    // sqlsrv_close($link);
}
function lastUpdateTabla($link, $tabla) // Obtiene la ultima fecha de actualizacion de una tabla
{
    $stmt  = sqlsrv_query($link, "SELECT MAX($tabla.FechaHora) as 'maxFecha' FROM $tabla"); // Ejecutamos la consulta
    $row   = sqlsrv_fetch_array($stmt); // Obtenemos el resultado
    $data  = array('FechaHora' => $row['maxFecha']->format('d-m-Y H:i:s')); // Formateamos la fecha
    return $data; // Retornamos la fecha de actualizacion
    sqlsrv_free_stmt($stmt); // Liberamos el statement
}
function dataLegajos($link) // Obtiene los legajos de la base de datos CH
{
    $params  = array();
    $options = array("Scrollable" => SQLSRV_CURSOR_KEYSET);
    // require __DIR__ . '/conn.php';
    $query = "SELECT PERSONAL.LegNume, PERSONAL.LegApNo FROM PERSONAL WHERE PERSONAL.LegNume > 0";
    $stmt  = sqlsrv_query($link, $query, $params, $options);
    while ($row = sqlsrv_fetch_array($stmt)) {
        $data[] = array(
            'legajo' => $row['LegNume'],
            'ApNo'   => $row['LegApNo'],
        );
    }
    return $data;
    sqlsrv_free_stmt($stmt);
    // sqlsrv_close($link);
}
function filtrarObjeto($array, $key, $valor) // Funcion para filtrar un objeto
{
    $r = array_filter($array, function ($e) use ($key, $valor) {
        return $e[$key] === $valor;
    });
    foreach ($r as $key => $value) {
        return ($value);
    }
}
function descTipoNov($var) // Funcion para obtener la descripcion del tipo de novedad
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
    $rows    = array(); // Almacenará los datos de la consulta
    $params  = array(); // Parámetros de la consulta     
    $options = array("Scrollable" => SQLSRV_CURSOR_KEYSET); // Opciones para la consulta
    $stmt  = sqlsrv_query($link, $query, $params, $options); // Ejecutar la consulta
    while ($row = sqlsrv_fetch_array($stmt))
        $rows[] = array( // Almacenar los datos de la consulta en un array multidimensional
            'Legajo'   => $row['Legajo'], // Legajo
            'Nombre'   => $row['Nombre'], // Nombre
            'Fecha'    => $row['Fecha']->format('d/m/Y'), // Fecha
            'FechaStr' => $row['Fecha']->format('Ymd'), // Fecha en formato Ymd
            'Count'    => $row['Fichada'], // Cantidad de fichadas
        );
    return $rows; // Retornar los datos de la consulta
    sqlsrv_free_stmt($stmt); // Liberar el statement
    //sqlsrv_close($link); // Cerrar la conexión
}
function apiData($url, $auth, $proxy, $timeout = 10) // Función para obtener datos de la API
{
    $proxyIP     = $proxy[0]; // IP del proxy
    $proxyPort   = $proxy[1]; // Puerto del proxy
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
    return ($data_content) ? $data_content : fileLog("No datos en API WF", __DIR__ . "/logs/errores/" . date('Ymd') . "_API_WF.log").fileLog("No datos en API WF", __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log'); // si no hay datos, escribir en el log
    exit;
}
function pingApi($url, $auth, $proxy, $timeout = 5) // Función para verificar la conexión a la API
{
    $proxyIP     = $proxy[0]; // IP del proxy
    $proxyPort   = $proxy[1]; // Puerto del proxy
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
        fileLog($text, __DIR__ . "/logs/errores/" . date('Ymd') . "_errorPingAPI_WF.log"); // escribir en el log
        fileLog($text, __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log'); // escribir en el log
        respuestaScript($text, 'Error');
        exit; // salimos del script
    }
    curl_close($ch); // close curl handle
    return ($data_content) ? $data_content : fileLog("Error Ping API WF", __DIR__ . "/logs/errores/" . date('Ymd') . "_PingAPI_WF.log") . respuestaScript('Error Ping API WF', 'Error').fileLog('Error Ping API WF', __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log'); // escribir en el log; // si no hay datos, escribir en el log
    exit;
}
function sendApiData($url, $auth, $proxy, $timeout = 10, $data) // Enviar datos a la API
{
    $proxyIP     = $proxy[0]; // IP del proxy
    $proxyPort   = $proxy[1]; // Puerto del proxy
    $proxyEnable = $proxy[2]; // Habilitado o no
    $username = $auth[0];
    $password = $auth[1];
    $ch = curl_init(); // initialize curl handle
    curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); // The number of seconds to wait while trying to connect
    curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
    curl_setopt($ch, CURLOPT_POSTFIELDS, ($data));
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
    return ($data_content) ? $data_content : fileLog("No hay datos en API WF", __DIR__ . "/logs/errores/" . date('Ymd') . "_API_WF.log").fileLog("No hay datos en API WF", __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log'); // escribir en el log; // si no hay datos, escribir en el log
    exit;
}
function tipoEjecucion()  // Función para saber desde donde se ejecta el script
{
    /** para que se cumplan estas condiciones hay que indicar el parametro get para cada ejecución, siempre que se ejecute desde un servidor web.
     * los argumentos que se pueden pasar son:
     * - "script=true" para ejecutar el script desde el servidor web
     * - "html=true" para ejecutar el script desde el navegador
     * - Si se ejecuta el script desde un cron solo poner el parametro "index.php tarea"  en los argumentos de la accion
     */
    $_GET['script'] = $_GET['script'] ?? false;  // Si no se indica el parametro script, se asigna false
    $_GET['html']   = $_GET['html'] ?? false; // Si no se indica el parametro html, se asigna false

    $_SERVER["REQUEST_METHOD"] = $_SERVER["REQUEST_METHOD"] ?? 'GET'; // Si no se indica el parametro REQUEST_METHOD, se asigna GET

    $tipoArgv = $_SERVER["argv"][1] ?? ''; // Si no se indica el parametro argv, se asigna ''
    $tipoEjecucion = ''; // Variable para almacenar el tipo de ejecucion
    switch ($tipoArgv) { // Si se indica el parametro argv
        case 'tarea': // Si se indica el parametro tarea
            $tipoEjecucion = ' (T)'; // Se asigna el tipo de ejecucion
            break; 
        case 'echo': // Si se indica el parametro echo
            $tipoEjecucion = ' (C)'; // Se asigna el tipo de ejecucion
            break; 
    }   

    switch ($_SERVER["REQUEST_METHOD"]) { // obtener el metodo de la peticion
        case ($_GET['script'] == true): // Si se indica el parametro script
            $tipo = ' (M)'; // Manual
            break;
        case ($_GET['html'] == true): // Si se indica el parametro html
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
    $log    = fopen($ruta_archivo, 'a');
    $date   = date('d-m-Y H:i:s');
    $text   = $date . ' ' . $text . tipoEjecucion() . "\n";
    fwrite($log, $text);
    fclose($log);
}
function json_validate($string) // Funcion para validar la estructura JSON
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
            $error = 'Caracteres UTF-8 malformados, posiblemente codificados de forma incorrecta';
            fileLog($error, __DIR__ . "/logs/fichero.log");
            break;
        default:
            $error = 'Error desconocido';
            fileLog($error, __DIR__ . "/logs/fichero.log");
            break;
            respuestaScript($error, 'Error');
    }
}
function fechaFormat($fecha, $format) // Funcion para formatear fechas
{
    $fecha = (empty($fecha)) ? '0000-00-00' : $fecha; // si no hay fecha, se asigna a 00/00/00
    $date        = date_create($fecha); // crear objeto de fecha
    $FormatFecha = date_format($date, $format); // formatear fecha
    return $FormatFecha; // retornar fecha formateada
}
function esHora($hora) // Funcion para validar el formato de las horas
{
    if (preg_match('/^([0-1][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/', $hora)) { // Formato de 24 horas
        $hora = $hora; // Devuelve la hora correcta
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
function horaFormat($value) // Funcion para formatear horas
{
    $value = empty($value) ? '00:00' : $value; // si no hay hora, se asigna a 00:00
    $value = explode(':', $value); // Separo las horas
    $hora  = str_pad(intval($value[0]), 2, '0', STR_PAD_LEFT); // Pongo a 2 digitos la hora autocompletando con cero a la izquierda
    $min   = ($value[1] < 10) ? str_pad(intval($value[1]), 2, '0', STR_PAD_LEFT) : $value[1]; // Si los minutos son menores a 10 Pongo a 2 digitos el minuto autocompletando con cero a la izquierda
    $min = ($min > 59) ? 59 : $min; // Si los minutos son mayores a 59 Pongo a 59 los minutos
    return $hora . ":" . $min; // Retorna la hora formateada
}
function esFecha($fecha) // Funcion para validar el formato de las fechas
{
    if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $fecha)) { // Formato de fecha
        $fecha = $fecha; // Devuelve la fecha correcta
    } else { // Si no es correcta
        $fecha = "0000-00-00"; // Devuelve la fecha correcta
    }
    return $fecha; // Retorna la fecha formateada
}
function validar_fecha($fecha) // Funcion para validar la fecha
{
    $valores = explode('/', $fecha); // Separo los valores de la fecha
    $checkdate = checkdate(intval($valores[1]), intval($valores[0]), intval($valores[2])); // Chequeo la fecha
    if (count($valores) === 3 && $checkdate) { // Si los bloques de valores son 3 y la fecha es correcta
        return true; // Retorna verdadero
    }
    return false; // Si no es correcta
}
function pingWebService($url) // Funcion para validar que el Webservice de Control Horario esta disponible
{
    $ch = curl_init(); // Inicializar el objeto curl
    curl_setopt($ch, CURLOPT_URL, $url); // Establecer la URL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Establecer que retorne el contenido del servidor
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // The number of seconds to wait while trying to connect
    curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
    $response   = curl_exec($ch); // extract information from response
    $curl_errno = curl_errno($ch); // get error code
    $curl_error = curl_error($ch); // get error information
    if ($curl_errno > 0) { // si hay error
        $text = "Error Ping WebService. \"Cod: $curl_errno: $curl_error\""; // set error message
        fileLog($text, __DIR__ . '/logs/errores/' . date('Ymd') . '_errorWebService.log'); // escribir en el log
        fileLog($text, __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log'); // escribir en el log
        respuestaScript($text, 'Error'); // retornar error
        exit; // salimos del script
    }
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // get http response code
    curl_close($ch); // close curl handle
    //return curl_getinfo($ch, CURLINFO_HTTP_CODE); // retornar el codigo de respuesta
    $textoErr = "Error -> No hay conexion con WebService: " . $http_code;
    return ($http_code == 201) ? true : fileLog($textoErr, __DIR__ . "/logs/errores/" . date('Ymd') . "_errorWebService.log") . respuestaScript($textoErr, 'Error').fileLog($textoErr, __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log');; // escribir en el log
}
function respuestaWebService($respuesta) // Funcion para formatear la respuesta del Webservice de Control Horario
{
    $respuesta = substr($respuesta, 1, -1);
    $respuesta = explode("=", $respuesta);
    return ($respuesta[0]);
}
function respuestaProcesoWebService($url) // Funcion para procesar la respuesta del Webservice de Control Horario
{
    do { // bucle para verificar el estado del proceso
        $ch = curl_init(); // inicializar curl
        curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return the transfer as a string
        $respuesta = curl_exec($ch); // ejecutar curl
    } while (respuestaWebService($respuesta) == 'Pendiente'); // bucle hasta que el estado sea diferente de pendiente
    curl_close($ch); // cerrar curl
    return respuestaWebService($respuesta); // retornar el estado del proceso
}
function ingresarNovedad($LegajoDesde, $LegajoHasta, $FechaDesde, $FechaHasta, $Laboral, $Novedad, $Observacion, $Horas, $url) // Funcion para ingresar la novedad con el WebService de Control Horario
{
    $textWF = ($Observacion == '') ? 'WF Novedades' : '. WF Novedades'; // Concateno texto. WF Novedades a la Observacion
    $data = "{Usuario=Script WF, TipoDePersonal=0, LegajoDesde=$LegajoDesde, LegajoHasta=$LegajoHasta, FechaDesde=$FechaDesde, FechaHasta=$FechaHasta, Empresa=0, Planta=0, Sucursal=0, Grupo=0, Sector=0, Seccion=0, Laboral=$Laboral, Novedad=$Novedad,Justifica=0, Observacion=$Observacion$textWF, Horas=$Horas, Causa=0, Categoria=0}"; // string de datos para enviar a WebService
    $ch = curl_init(); // inicializar curl
    curl_setopt($ch, CURLOPT_URL, $url . "Novedades"); // agregar el parametro Novedades a la URL del WebService para identificar el tipo de comando a enviar
    curl_setopt($ch, CURLOPT_POST, TRUE); // establecer el metodo de envio de datos a post
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  // establecer los datos a enviar
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // establecer que retorne el contenido del servidor
    $respuesta  = curl_exec($ch); // ejecutar curl
    $curl_errno = curl_errno($ch); // get error code
    $curl_error = curl_error($ch); // get error information
    $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE); // get http response code
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
    };
    curl_close($ch);  // cerrar curl

    $processID = respuestaWebService($respuesta); // obtengo el id del proceso
    $estado    = $url . "Estado?ProcesoId=" . $processID; // obtengo el estado del proceso

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
function procesarNovedad($legajo, $FechaDesde, $FechaHasta, $url) // Funcion para ingresar la novedad con el WebService de Control Horario
{
    $data = "{Usuario=Script WF,TipoDePersonal=0,LegajoDesde='$legajo',LegajoHasta='$legajo',FechaDesde='$FechaDesde',FechaHasta='$FechaHasta',Empresa=0,Planta=0,Sucursal=0,Grupo=0,Sector=0,Seccion=0}";
    $ch = curl_init(); // inicializar curl
    curl_setopt($ch, CURLOPT_URL, $url . "Procesar"); // agregar el parametro Procesar a la URL del WebService para identificar el tipo de comando a enviar
    curl_setopt($ch, CURLOPT_POST, TRUE); // establecer el metodo de envio de datos a post
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  // establecer los datos a enviar
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // establecer que retorne el contenido del servidor
    $respuesta  = curl_exec($ch); // ejecutar curl
    $curl_errno = curl_errno($ch); // get error code
    $curl_error = curl_error($ch); // get error information
    $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE); // get http response code
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
    };
    curl_close($ch);  // cerrar curl

    $processID = respuestaWebService($respuesta); // obtengo el id del proceso
    $estado    = $url . "Estado?ProcesoId=" . $processID; // obtengo el estado del proceso

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
function stringAleatorio($longitud) // Funcion para generar un string aleatorio
{
    $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; // caracteres a utilizar
    $string = ''; // variable para almacenar la cadena generada
    for ($i = 0; $i < $longitud; $i++) { // bucle para generar la cadena
        $string .= $caracteres[rand(0, strlen($caracteres) - 1)]; // concatenar caracter aleatorio
    }
    return $string; // retornar la cadena generada
}
function audito_ch($AudTipo, $AudDato, $link) // Funcion para auditar el control horario
{
    $ipCliente = $_SERVER['REMOTE_ADDR']; // obtengo la ip del cliente
    switch ($ipCliente) { // verifico la ip del cliente
        case '::1': // si es localhost
            $ipCliente = ('127.0.0.1'); // seteo la ip localhost
            break;
        default:
            $ipCliente = ($_SERVER['REMOTE_ADDR']); // seteo la ip del cliente
            break;
    }
    // require __DIR__ . '/conn.php'; // conexion a la base de datos
    $AudUser    = 'Script WF'; // usuario que realiza la accion
    $AudTerm   = $ipCliente . '-' . stringAleatorio(4); // terminal donde se realiza la accion
    $AudModu   = 21; // modulo en el que se realiza la accion
    $FechaHora = date('Ymd H:i:s'); // obtengo la fecha y hora actual
    $AudFech   = date('Ymd'); // obtengo la fecha actual como string
    $AudHora   = date('H:i:s'); // obtengo la hora actual

    $procedure_params = array( // parametros del procedimiento almacenado
        array(&$AudFech),
        array(&$AudHora),
        array(&$AudUser),
        array(&$AudTerm),
        array(&$AudModu),
        array(&$AudTipo),
        array(&$AudDato),
        array(&$FechaHora),
    );

    $sql = "exec DATA_AUDITORInsert @AudFech=?,@AudHora=?,@AudUser=?,@AudTerm=?,@AudModu=?,@AudTipo=?,@AudDato=?,@FechaHora=?"; // procedimiento almacenado
    $stmt = sqlsrv_prepare($link, $sql, $procedure_params); // ejecuto el procedimiento almacenado

    if (sqlsrv_execute($stmt)) { // ejecuto el procedimiento almacenado

    } else {
        if (($errors = sqlsrv_errors()) != null) {
            foreach ($errors as $error) {
                $mensaje = explode(']', $error['message']);
                $dataAud = array("auditor" => "error", "dato" => $mensaje[3]);
            }
            fileLog('Error Audito: ' . $error['message'], __DIR__ . "/logs/errores/" . date('Ymd') . "_errorAuditor.log"); // escribir en el log
            fileLog('Error Audito: ' . $error['message'], __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log');
            respuestaScript('Error Audito: ' . $error['message'], 'Error'); // retornar error
        }
    }
}
function totalDiasFechas($fecha_inicial, $fecha_final) // obtengo los dias entre dos fechas
{
    $dias = (strtotime($fecha_inicial) - strtotime($fecha_final)) / 86400; // resto las dos fechas
    $dias = abs($dias); // obtengo el valor absoluto
    $dias = floor($dias); // redondeo hacia abajo
    return $dias; // retorno el resultado
}
function fechaIniFinDias($fecha_inicial, $fecha_final, $dias) // obtengo objeto de rango de fecha separado en rangos de $dias
{
    $TotalDias = totalDiasFechas($fecha_inicial, $fecha_final); // obtengo la cantidad de dias entre las fechas
    $arrayTotalMeses[] = array(); // creo un array para almacenar los meses
    for ($i = 0; $i < intval($TotalDias / $dias); $i++) { // bucle para obtener los meses
        $arrayTotalMeses[] = array($i); // almaceno los meses en el array
    }
    foreach ($arrayTotalMeses as $value) { // bucle para obtener los meses
        $fecha1 = $fecha_inicial; // fecha inicial
        // $dias = ($dias-1);
        $fecha2 = date("Ymd", strtotime($fecha1 . "+ " . $dias . " days")); // fecha final
        $fecha2 = ($fecha2 > $fecha_final) ?  $fecha_final : $fecha2; // si la fecha2 es mayor a la fecha final
        if (($fecha1 > $fecha2)) { // si la fecha inicial es mayor a la fecha final
            break; // salgo del bucle
        }
        $arrayFechas[] = array( // almaceno las fechas en el array
            'fecha_desde' => fechaFormat($fecha1, 'd/m/Y'), // fecha inicial
            'fecha_hasta' => fechaFormat($fecha2, 'd/m/Y'), // fecha final
            'fecha_hastaStr' => fechaFormat($fecha1, 'Ymd'),
            'fecha_hastaStr' => fechaFormat($fecha2, 'Ymd'),
        );
        $fecha_inicial = date("Ymd", strtotime($fecha2 . "+ 1 days")); // fecha inicial
    }
    return $arrayFechas; // retorno el array con las fechas
}
function dateDifference($date_1, $date_2, $differenceFormat = '%a') // diferencia en días entre dos fechas
{
    $datetime1 = date_create($date_1); // creo la fecha 1
    $datetime2 = date_create($date_2); // creo la fecha 2

    $interval = date_diff($datetime1, $datetime2); // obtengo la diferencia de fechas
    return $interval->format($differenceFormat); // devuelvo el número de días

}
function borrarLogs($path, $dias, $ext) // borra los logs a partir de una cantidad de días
{
    $urlJson = __DIR__ . '\config.json'; // obtengo la ruta del archivo json
    $dataJson = file_get_contents($urlJson); // obtengo el contenido del archivo json
    $dataJson = json_decode($dataJson, true); // decodifico el json
    if ($dataJson['borrarLogs']['estado'] == true) { // si está activado el borrado de logs
        $files = glob($path . '*' . $ext); //obtenemos el nombre de todos los ficheros
        foreach ($files as $file) { // recorremos todos los ficheros
            $lastModifiedTime = filemtime($file); // obtenemos la fecha de modificación del fichero
            $currentTime = time(); // obtenemos la fecha actual
            $dateDiff = dateDifference(date('Ymd', $lastModifiedTime), date('Ymd', $currentTime)); // obtenemos la diferencia de fechas
            if ($dateDiff >= $dias) :
                unlink($file); // borramos el fichero
                fileLogs("Se elimino log \"$file\"", __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log", '');
                fileLog("Se elimino log \"$file\"", __DIR__ . '/logs/novedades/' . date('Ymd') . '_novedad.log');
            endif; //elimino el fichero
        }
    }
}
function fileLogs($text, $ruta_archivo, $tipo) // escribe  el log de novedades
{
    $urlJson  = __DIR__ . '\config.json'; // ruta del archivo json
    $dataJson = file_get_contents($urlJson); // obtenemos el contenido del archivo json
    $dataJson = json_decode($dataJson, true); // decodificamos el json
    $logNovedadesOk = $dataJson['logNovedades']['success'];
    $logNovedadesEr = $dataJson['logNovedades']['error'];
    $logConnOk      = $dataJson['logConexion']['success'];
    $logConnErr     = $dataJson['logConexion']['error'];
    $date     = date('d-m-Y H:i:s'); // obtenemos la fecha actual
    $textJson = $text; // obtenemos el texto a escribir
    $text     = $date . ' ' . $text . tipoEjecucion() . "\n"; // armamos el texto a escribir
    switch ($tipo): // según el tipo de log
        case 'novOk': // si es un log de novedades exitosa
            if ($logNovedadesOk) : // si está activado el log de novedades exitosa
                $log      = fopen($ruta_archivo, 'a'); // abrimos el archivo
                fwrite($log, $text); // escribimos en el archivo
            endif;
            break;
        case 'novErr': // si es un log de novedades fallida
            if ($logNovedadesEr) : // si está activado el log de novedades fallida
                $log      = fopen($ruta_archivo, 'a'); // abrimos el archivo
                fwrite($log, $text); // escribimos en el archivo
            endif;
            break;
        case 'conOk': // si es un log de conexión exitosa
            if ($logConnOk) : // si está activado el log de conexión exitosa
                $log      = fopen($ruta_archivo, 'a'); // abrimos el archivo
                fwrite($log, $text); // escribimos en el archivo
            endif;
            break;
        case 'conErr': // si es un log de conexión fallida
            if ($logConnErr) : // si está activado el log de conexión fallida
                $log      = fopen($ruta_archivo, 'a'); // abrimos el archivo
                fwrite($log, $text); // escribimos en el archivo
            endif;
            break;
        case 'json': // si es un log de json
            header("Content-Type: application/json; charset=utf-8"); // json response
            $log = fopen($ruta_archivo, 'w'); // abrimos el archivo y sobreescribimos
            fwrite($log, $textJson); // escribimos en el archivo
            break;
        default:
            $log      = fopen($ruta_archivo, 'a'); // abrimos el archivo
            fwrite($log, $text); // escribimos en el archivo
            break;
            fclose($log); // cerramos el archivo
    endswitch; // fin del switch
};
function fileLogsJson($text, $ruta_archivo) // escribe  el log de novedades
{
    $log      = fopen($ruta_archivo, 'a'); // abrimos el archivo
    $textJson = $text; // obtenemos el texto
    $log = fopen($ruta_archivo, 'w'); // abrimos el archivo
    fwrite($log, $textJson); // escribimos en el archivo
};
function getDataJson($url) // obtiene el json de la url
{
    if (file_exists($url)) { // si existe el archivo
        $data = file_get_contents($url); // obtenemos el contenido del archivo
        if ($data) { // si el contenido no está vacío
            $data = json_decode($data, true); // decodificamos el json
            return $data; // devolvemos el json
        } else { // si el contenido está vacío
            fileLog("No hay informacion en el archivo \"$url\"", __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log", ''); // escribimos en el log
        }
    } else { // si no existe el archivo
        fileLog("No existe archivo \"$url\"", __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log", ''); // escribimos en el log
        return false; // devolvemos false
    }
}
function respuestaScript($mensaje, $status) // genera la respuesta del script cuando parametro get script=true
{

    $tipo = tipoEjecucion(); // obtenemos el tipo de ejecución

    echo (($_SERVER["argv"][1] ?? '') == 'echo') ? $mensaje . $tipo : '';  // si el argumento es echo, escribimos en pantalla

    $_GET['script'] = $_GET['script'] ?? false; // si no existe el parametro script, lo setea a false
    if ($_GET['script']) { // si es una petición con el parametro get script=true
        header("Content-Type: application/json; charset=utf-8"); // json response
        $data = array('status' => $status, 'Mensaje' => "<br><br>$mensaje.$tipo"); // Mensaje
        echo json_encode($data); // devuelve el json con la respuesta
        exit(); // Salir
    }
    $_GET['echo'] = $_GET['echo'] ?? false; // si no existe el parametro echo, lo setea a false
    if ($_GET['echo']) { // si es una petición con el parametro get echo=true
        echo $mensaje . $tipo; // devuelve la respuesta
        exit(); // Salir
    }
    $_GET['html'] = $_GET['html'] ?? false; // si no existe el parametro html, lo setea a false
    if ($_GET['html']) { // si es una petición con el parametro get html=true
        echo "<html style='width:100%; height:100%; background-color: #ddd'><h3><div style='padding:20px;'>$mensaje$tipo</div></h3></html>"; // devuelve la respuesta
        exit(); // Salir
    }
}
