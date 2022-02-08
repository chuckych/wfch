<?php

/** Autor: Norberto CH: nchaquer@gmail.com */
ini_set('max_execution_time', 900); // 15 minutos de tiempo maximo de ejecución
header("Content-Type: application/json; charset=utf-8"); // json response
header("Access-Control-Allow-Origin: *"); // allow cross-domain requests
header("Content-Type: text/html; charset=utf-8");
error_reporting(E_ALL); // Reportar todos Los errores
ini_set('display_errors', '0'); // 0 - off 1 - on 
$tiempo_ini = microtime(true); // Tiempo inicial

$_SERVER["argv"][1] = $_SERVER["argv"][1] ?? "";
$_GET['script']     = $_GET['script'] ?? "";
$_GET['html']       = $_GET['html'] ?? "";

require __DIR__ . '/func.php'; // Funciones
require __DIR__ . '/conn.php'; // Conexion bd

if ($_SERVER["argv"][1] != 'tarea' && $_SERVER["argv"][1] != 'echo' && $_GET['script'] != true && $_GET['html'] != true) {
    $textArg = "Error al ejecutar el script. No se establecio un argumento o parametro valido";
    echo $textArg;
    fileLogs($textArg, __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log", '');
    fileLogs($textArg, __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", '');
    exit;
}
/** Borrar archivos de log */
$pathLog = __DIR__ . "/logs/novedades/"; // Ruta del archivo de log
$dias    = $dataJson['borrarLogs']['dias']; // Dias a borrar
$ext     = ".log"; // Extensión de los archivos de novedades
borrarLogs($pathLog, $dias, $ext);

$pathLog = __DIR__ . "/logs/errores/"; // Ruta del archivo de log de errores
borrarLogs($pathLog, $dias, $ext);

$pathLog = __DIR__ . "/logs/conn/"; // Ruta del archivo de log de la conexión
borrarLogs($pathLog, $dias, $ext);

$pathLog = __DIR__ . "/logs/"; // Ruta del archivo de log de la conexión
borrarLogs($pathLog, $dias, $ext);

$pathLog = __DIR__ . "/logs/info/"; // Ruta del archivo de log de informacion
borrarLogs($pathLog, $dias, $ext);
/**  */

/** Valores del archivo config.json */
$auth  = array($dataJson['api']['user'], $dataJson['api']['pass']); // Usuario y contraseña de la API WF
$proxy = array($dataJson['proxy']['ip'], $dataJson['proxy']['port'], $dataJson['proxy']['enabled']); // Proxy datos
$urlWebService = $dataJson['webService']['url']; // Url del webservice
/**  */

/** Realizar un Ping a la API WF */
$pingApi = (json_decode(pingApi($dataJson['api']['url'] . "?status=Ping", $auth, $proxy))); // Ping de la API

if ($pingApi->SUCCESS != 'YES') : // Si la respuesta de la pingApi de WF es NO
    $text = 'Error al conectar con API WF'; // Texto para el log
    fileLogs($text . ' - ' . $pingApi->ERROR, __DIR__ . "/logs/errores/" . date('Ymd') . "_PingAPI_WF.log", 'novErr'); // Guardo el log
    respuestaScript($text . ' - ' . $pingApi->ERROR, 'Error'); // Respuesta del script
    exit; // Salgo deL SCRIPT
endif;
/** */

/** Realizar un Ping al WebService */
$ping = PingWebService($urlWebService . 'Ping?'); // ping al webservice
(!$ping) ?  sqlsrv_close($link) . respuestaScript('Error al conectar con WebService', 'Error') . exit : ''; // Si no hay conexion con el webservice salimos del script
/**  */

$data      = array(); // Array para almacenar los datos
$FechaHora = date('Ymd H:i:s'); // Fecha y hora actual
$textLog   = ''; // Texto para el log
// $textLog  .= "\n";

/** Obtener Novedades de CH */
require __DIR__ . '/novedades.php';  // Novedades
/** Este objeto devuelve un array con los datos de las novedades de control horario
 *  'CodNov'   => Codigo de Novedad,
    'NovDesc'  => Descripcion de la Novedad,
    'CodTipo'  => Codigo de Tipo de Novedad,
    'TipoDesc' => Descripcion del Tipo de Novedad,
    'NovID'    => ID de la Novedad,
 * 
 */
/**  */

/** LLAMAMOS A LA API DE WF */
$dataApi = (json_decode(apiData($dataJson['api']['url'] . "?status=P,A", $auth, $proxy, 10))); // Obtengo los datos de la API WF
/** Ejemplo de respuesta API 
     "SUCCESS": "YES",
    "FAILURE": "NO",
    "ERROR": "",
    "count": 1,
    "data": [
        {
        "id_out": "16",
        "legajo": "9999999",
        "novedad": "6",
        "fecha_desde": "2021-07-02",
        "fecha_hasta": "2021-07-02",
        "horas": "",
        "justificacion": "0",
        "observaciones": "Es una prueba",
        "dias_laborales": "0",
        "categoria": "0",
        "status": "P",
        "registro": "2021-07-08 10:28:52",
        "export": null,
        "usuario_exec": null,
        "id_solicitud": "221",
        "usuario_modificacion": null,
        "fecha_modificacion": null
        }
 */
// fileLogs($dataApi->SUCCESS, __DIR__ . "/logs/errores/" . date('Ymd') . "_print.log", ''); // Guardo el log
// exit; 

/** CHEQUEAMOS LA RESPUESTA DE LA API DE WF */
if ($dataApi->SUCCESS != 'YES') : // Si la respuesta de la API de WF es NO
    $text = 'Error al obtener los datos de la API WF'; // Texto para el log
    fileLogs($dataApi->ERROR, __DIR__ . "/logs/errores/" . date('Ymd') . "_API_WF.log", 'novErr'); // Guardo el log
    sqlsrv_close($link); // Cierro la conexión MSQL
    respuestaScript($text, 'Error'); // Respuesta del script
    exit; // Salgo deL SCRIPT
endif;

($dataApi->count > 0) ? '' : fileLogs("No Hay Novedades Pendientes", __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'novOk') . respuestaScript('No Hay Novedades Pendientes', 'ok') . sqlsrv_close($link) . exit; // Verifico si la API devuelve datos, sino salgo del script
/** */

/** Obtener Personal de CH */
require __DIR__ . '/personal.php'; // Personal
/** Este objeto devuelve un array con los datos de los Legajo de control horario
    'legajo' => 99999999,
    'ApNo' => 'Nombre y Apellido',
 */
/** */

/** Recorrer los datos de la API WF  */
fileLogs("Inicio de Proceso de Novedades:", __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'novOk'); // Log de Inicio de proceso de Novedades
foreach ($dataApi->data as $key => $value) { // Recorro los datos de la API para crear un objeto con los datos de la API
    $textLog = '';
    $dataApi = array( // Creo un array con los datos de la API
        'id_out'         => $value->id_out, // ID del registro de la API
        'fecha_desde'    => (validar_fecha(fechaFormat($value->fecha_desde, 'd/m/Y'))) ? fechaFormat($value->fecha_desde, 'd/m/Y') : '', // Fecha desde como d/m/Y
        'fecha_hasta'    => (validar_fecha(fechaFormat($value->fecha_hasta, 'd/m/Y'))) ? fechaFormat($value->fecha_hasta, 'd/m/Y') : '', // Fecha hasta como d/m/Y
        'fecha_desdeStr' => ((fechaFormat($value->fecha_desde, 'Ymd'))) ? fechaFormat($value->fecha_desde, 'Ymd') : '', // Fecha desde como String Ymd
        'fecha_hastaStr' => ((fechaFormat($value->fecha_hasta, 'Ymd'))) ? fechaFormat($value->fecha_hasta, 'Ymd') : '', // Fecha hasta como String Ymd
        'horas'          => empty($value->horas) ? '00:00' : $value->horas, // Horas
        'observaciones'  => $value->observaciones, // Observaciones
        'dias_laborales' => ($value->dias_laborales) ? intval($value->dias_laborales) : 0,  // Dias laborales 0 = Todos los días, 1 = Solo en días laborales
        'status'         => $value->status, // Status
    );

    $novCodi  = (intval($value->novedad)) ?? ''; // Obtengo el codigo de la novedad por separado y pasamos valor a entero con la funcion intval
    $horasNov = horaFormat($dataApi['horas']); // Formateamos las horas en 00:00
    $horasNov = empty($value->horas) ? '00:00' : $value->horas; // Horas de la novedad devuelto por la API
    $horasNov = (!esHora($horasNov)) ? '00:00' : $horasNov; // Si no es correcto le asigno 00:00

    $filtroNovedad = (filtrarObjeto($objetoNovedadesCH, 'CodNov', $novCodi)); // Filtramos el codigo de la novedad que viene de la api de WF con el objetoNovedadesCH para verificar que exista la novedad en CH y completar los datos faltantes.
    $filtrarLegajo = (filtrarObjeto($objetoLegajosCH, 'legajo', intval($value->legajo))); // Filtramos el legajo que viene de la api de WF con el objetoLegajosCH para verificar que exista el legajo en CH y completar los datos faltantes.

    if (
        $filtroNovedad // Si existe la novedad
        && $filtrarLegajo // Si existe el legajo
        && esHora($horasNov) // Si la hora no tiene un formato valido de 24 horas
        && !empty($dataApi['fecha_desde']) // Si la fecha desde no esta vacia
        && !empty($dataApi['fecha_hasta']) // Si la fecha hasta no esta vacia
        && !empty($value->legajo) // Si el legajo no esta vacio
        && fechaFormat($value->fecha_desde, 'Ymd') <= fechaFormat($value->fecha_hasta, 'Ymd') // Si la fecha desde es menor o igual a la fecha hasta
    ) : // Si cumplen todas las condiciones. Creamos el objeto data.
        $data[] = (array_merge($filtroNovedad, $dataApi, $filtrarLegajo)); // Hacemnos un merge con los datos del 'objetoNovedadesCH' al objeto 'dataApi' y 'objetoLegajosCH'.
        /** Ejemplo del objeto creado.
          array (
            'CodNov'         => 301,
            'NovDesc'        => 'Ausente sin Aviso',
            'CodTipo'        => 3,
            'TipoDesc'       => 'Ausencia',
            'NovID'          => 'AUS',
            'id_out'         => '2',
            'fecha_desde'    => '06/09/2019',
            'fecha_hasta'    => '06/09/2019',
            'fecha_desdeStr' => '20190906',
            'fecha_hastaStr' => '20190906',
            'horas'          => '00:00',
            'observaciones'  => '',
            'dias_laborales'  => '0',
            'legajo'         => 9999999,
            'ApNo'           => 'nombre y apellido',
        ),
         */
    else : // Si no cumplen alguna condicion
        $textLog .= 'Nov: ' . $novCodi . '. Leg: ' . $value->legajo . '. ' . fechaFormat($value->fecha_desde, 'd/m/Y') . ' - ' . fechaFormat($value->fecha_hasta, 'd/m/Y') . ' ID_OUT: ' . $value->id_out . '.'; // Texto de Error
        $textLog .=  ($filtrarLegajo) ? '' : " Legajo no existe en CH."; // Si el legajo no existe en CH
        $textLog .=  ($filtroNovedad) ? '' : " Novedad no existe en CH."; // Si la novedad no existe en CH
        $textLog .= (empty($value->fecha_desde)) ? ", Fecha desde vacia." : ''; // Si la fecha desde esta vacia
        $textLog .= (empty($value->fecha_hasta)) ? ", Fecha hasta vacia." : ''; // Si la fecha hasta esta vacia
        $textLog .= (empty($value->legajo)) ? ", Legajo vacio." : '';    // Si el legajo esta vacio
        $textLog .= (!esHora($horasNov)) ? ", Formato de horas erroneo." : ''; // Si la hora no tiene un formato valido de 00:00
        $textLog .= (fechaFormat($value->fecha_desde, 'Ymd') > fechaFormat($value->fecha_hasta, 'Ymd')) ? ", La fecha desde: " . $dataApi['fecha_desde'] . " es mayor que la fecha hasta " . $dataApi['fecha_hasta'] : ""; // Si la fecha desde es mayor a la fecha hasta, Si no, no hacemos nada
    // $textLog .= "\n"; // Salto de linea
    endif;

    if (!empty($textLog)) {
        fileLogs("Error -> \n" . $textLog, __DIR__ . "/logs/errores/" . date('Ymd') . "_error.log", 'novErr'); // Si hay texto de error lo guardamos en el archivo de log
        fileLogs("Error -> \n" . $textLog, __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'novOk'); // Log de Inicio de Ingreso de Novedades
        $jsonData = array(
            "legajo" => "$value->legajo", "fecha" => "$dataApi[fecha_desdeStr]", "novedad" => "$value->novedad", "status" => "N", "id_out" => "$value->id_out", "motivo" => urlencode($textLog)
        ); // Creamos el array con los datos de la novedad para enviar a la API
        $jsonData      = json_encode($jsonData); // Convertimos el array en JSON
        $sendApiData   = sendApiData($dataJson['api']['url'] . '?TYPE=respuesta&data=[' . $jsonData . ']', $auth, $proxy, 10, ''); // Enviamos el objeto JSON a la API
        $respuesta     = (json_decode($sendApiData)); // Decodifico el JSON de la respuesta de la API WF
        $textRespuesta = ($respuesta->SUCCESS == 'YES') ? "Registro $value->id_out actualizado correctamente en WF con status N" :  $textRespuesta = $respuesta->MENSAJE; // Texto de la respuesta envio WF
        fileLogs($textRespuesta, __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'novOk'); // Guardamos el texto de la novedad en el archivo de log
        fileLogs($dataJson['api']['url'] . '?TYPE=respuesta&data=[' . $jsonData . ']', __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'novOk'); // Guardamos el texto de la novedad en el archivo de log

        $jsonData = json_encode(array("status" => "E", "id_out" => "$value->id_out")); // Creamos el objeto json para enviarlo al Api
        $sendApiData = sendApiData($dataJson['api']['url'] . "?TYPE=update&data=[$jsonData]", $auth, $proxy, 5, ''); // Enviamos el objeto JSON a la API notificando que se termino de procesar la novedad con status 'E' 
        $respuesta   = (json_decode($sendApiData)); // Decodifico el JSON de la respuesta de la API WF
        $textRespuesta = ($respuesta->SUCCESS == 'YES') ? "Registro actualizado correctamente en WF con status E" :  $textRespuesta = $respuesta->MENSAJE; // Texto de la respuesta envio WF
        fileLogs($textRespuesta, __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'novOk'); // Guardamos el texto de la novedad en el archivo de log
    };
}
/** */

$diasPresentes = array(); // Array de dias presentes
$item = 0; // Contador de items
// Contador de items
/** Iniciamos el ingreso de novedades al Web Service */
foreach ($data as $key => $value) { // Recorremos el array con los datos del objeto creado de la API WF para ingresar las novedades en CH
    $textNov = ''; // Texto de la novedad
    /** 
     * 
     * PROCESAMOS LOS REGISTROS CON STATUS p
     * 
     */
    if ($value['status'] == 'P') { // Si el status es P

        $arrayFechas = fechaIniFinDias($value['fecha_desdeStr'], $value['fecha_hastaStr'], 31); // Obtenemos un array que divide rango de fechas en bloques de 31 días. Esto es porque el webservice solo carga informacion de 31 dias a la vez.
        $iniRegWF_P = microtime(true);
        fileLogs("Inicio ID Registro WF Pendiente: " . $value["id_out"], __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'novOk');
        $TipoNovAusencia = ($value['CodTipo'] > 2) ? true : false; // Si el tipo de novedad es Ausencia retornamos true, si no retornamos false

        if ($TipoNovAusencia) : // Si el tipo de novedad es de tipo 'Ausencia'
            $diasPresentes = validaDiaFichadas($value['legajo'], $value['fecha_desdeStr'], $value['fecha_hastaStr'], $link); // Obtenemos un array con los dias que el legajo tiene fichadas.Esta información se busca en la tabla registro de control horario.
        endif;
        foreach ($arrayFechas as $date) { // Recorremos el array de bloques de 31 días para ingresar las novedades en CH.
            $iniNov = microtime(true); // Tiempo de inicio ingreso Novedad
            $ingresarNovedad = ingresarNovedad($value['legajo'], $value['legajo'], $date['fecha_desde'], $date['fecha_hasta'], $value['dias_laborales'],  $value['CodNov'], $value['observaciones'], $value['horas'], $urlWebService); // Ingresamos la novedad en CH por medio del webservice
            $terminado = false; // Bandera para saber si la novedad se termino de ingresar

            if (($ingresarNovedad) == 'Terminado') : // Si la novedad se ingreso correctamente
                $finNov    = microtime(true); // Tiempo de finalizacion de ingreso Novedad
                $durNov    = (round($finNov - $iniNov, 2)); // Duracion de ingreso Novedad
                $terminado = true; // Se cambia la bandera
                audito_ch("A", 'Alta Novedad (' . $value['CodNov'] . ') Legajo ' . $value['legajo']  . '. Desde: ' . $date['fecha_desde'] . ' a ' . $date['fecha_hasta'], $link); // Audito la novedad en CH

                $item = str_pad($item + 1, 2, '0', STR_PAD_LEFT); // Sumo 1 al contador de items

                $textNov = 'Novedad "(' . $value['CodNov'] . ')" "' . trim($value['NovDesc']) . '". Ingresada: "Si". Legajo "(' . $value['legajo'] . ') ' . trim($value['ApNo']) . '". Fechas: "' . $date['fecha_desde'] . '" al "' . $date['fecha_hasta'] . '". Horas: "' . $value['horas'] . '" Dur: ' . $durNov;
                $pathLog = __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log"; // Ruta del archivo de log
                fileLogs($textNov, $pathLog, 'novOk'); // Guardamos el texto de la novedad en el archivo de log

                /** 
                 * * * * * * * * * * * * * * * * * * * * * * 
                 * * * * * * * * * * * * * * * * * * * * * * 
                 * PROCESAMOS EL PERIODO SI LA FECHA DESDE ES MENOR O IGUAL A LA ACTUAL 
                 * * * * * * * * * * * * * * * * * * * * * * 
                 * * * * * * * * * * * * * * * * * * * * * * 
                 */
                if ($value['fecha_desdeStr'] <= date('Ymd')) :
                    $fechaHastaProc = ($date['fecha_hastaStr'] > date('Ymd')) ? date('d/m/Y') : $date['fecha_hasta']; // Si la fecha hasta de la novedad es mayor al dia actual, definimos la fecha hasta como la fecha actual. Ya que no se puede procesar dia a futuro en CH.
                    $iniProc = microtime(true); // Tiempo de inicio de proceso de la novedad
                    $procesarNovedad = procesarNovedad($value['legajo'], $date['fecha_desde'], $fechaHastaProc, $urlWebService); // Procesamos la novedad en CH por medio del webservice
                    $terminadoProc   = false; // Bandera para saber si la novedad se termino de procesar
                    if (($procesarNovedad) == 'Terminado') : // Si la novedad se ingreso correctamente
                        $terminadoProc = true; // Se cambia la bandera
                        $finProc    = microtime(true); // Tiempo de finalizacion de proceso de la novedad
                        $durProc    = (round($finProc - $iniProc, 2)); // Duracion de de proceso de la novedad
                        $textNov = 'Novedad "(' . $value['CodNov'] . ')" "' . trim($value['NovDesc']) . '". Procesada  "SI". Legajo "(' . $value['legajo'] . ') ' . trim($value['ApNo']) . '". Fechas: "' . $date['fecha_desde'] . '" al "' . $fechaHastaProc . '". Horas: "' . $value['horas'] . '" Dur: ' . $durProc; // Texto de la novedad ingresada para gardar en el log
                        $pathLog = __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log"; // Ruta del archivo de log
                        fileLogs($textNov, $pathLog, 'novOk'); // Guardamos el texto de la novedad en el archivo de log
                        audito_ch("P", "Proceso de Datos Legajo $value[legajo]. Desde: $date[fecha_desde] a $date[fecha_hasta]", $link);
                    else :
                        $terminadoProc = false; // Se cambia la bandera
                        $textNov = 'Novedad "(' . $value['CodNov'] . ')" ' . trim($value['NovDesc']) . '. Procesada: "NO". Legajo "(' . $value['legajo'] . ') ' . trim($value['ApNo']) . '". Fechas: "' . $date['fecha_desde'] . ' al ' . $fechaHastaProc . '". Horas: "' . $value['horas'] . '" Dur: ' . $durProc; // Texto de la novedad no ingresada para gardar en el log
                        $pathLog = __DIR__ . "/logs/novedades/" . date('Ymd') . "_error.log"; // Ruta del archivo de log
                        fileLogs($textNov, $pathLog, 'novErr'); // Guardamos el texto de la novedad en el archivo de log
                    endif;
                endif;
                /** 
                 * * * * * * * * * * * * * * * * * * * * * *  
                 * * * * * * * * * * * * * * * * * * * * * *  
                 * FIN DEL PROCESAR 
                 * * * * * * * * * * * * * * * * * * * * * *  
                 * * * * * * * * * * * * * * * * * * * * * *  
                 * */
            else : // Si la novedad no se ingreso correctamente
                $terminado = false; // Se cambia la bandera
                $finNov    = microtime(true);
                $durNov    = (round($finNov - $iniNov, 2)); // Duracion de ingreso Novedad
                $textNov = 'Novedad "(' . $value['CodNov'] . ')" ' . trim($value['NovDesc']) . '. Ingresada: "NO". Legajo "(' . $value['legajo'] . ') ' . trim($value['ApNo']) . '". Fechas: "' . $date['fecha_desde'] . ' al ' . $date['fecha_hasta'] . '". Horas: "' . $value['horas'] . '" Dur:' . $durNov;
                $pathLog = __DIR__ . "/logs/novedades/" . date('Ymd') . "_error.log";
                fileLogs($textNov, $pathLog, 'novErr'); // Guardamos el texto de la novedad en el archivo de log
            endif;
        }
        if ($terminado == true) : // Si la novedad se termino de ingresar
            if ($TipoNovAusencia) : // Si el tipo de novedad es de tipo 'Ausencia'
                foreach ($diasPresentes as $key => $dp) { // Recorremos el array con los dias presentes 
                    $motivo = "No se pudo cargar la Novedad $value[CodNov]. Existen ($dp[Count]) Fichadas";
                    $jsonData = array(
                        "legajo"  => "$dp[Legajo]",
                        "fecha"   => "$dp[FechaStr]",
                        "novedad" => "$value[CodNov]",
                        "status"  => "N",
                        "id_out"  => "$value[id_out]",
                        "motivo"  => urlencode($motivo)
                    ); // Creamos el array con los datos de la novedad para enviar a la API
                    $jsonData = json_encode($jsonData); // Convertimos el array en JSON
                    $iniSendApi = microtime(true); // Tiempo de inicio del envio al Api
                    $sendApiData = sendApiData($dataJson['api']['url'] . '?TYPE=respuesta&data=[' . $jsonData . ']', $auth, $proxy, 10, ''); // Enviamos el objeto JSON a la API
                    $respuesta = (json_decode($sendApiData)); // Decodifico el JSON de la respuesta de la API WF
                    $logDiasPresentes = "Novedad \"(" . $value['CodNov'] . ")\" " . trim($value['NovDesc']) . ". Ingresada: \"NO\". Legajo \"($dp[Legajo]) $dp[Nombre]\" Fecha: \"$dp[Fecha]\" tiene \"$dp[Count]\" Fichadas."; // Texto de Log de novedades no ingresadas
                    fileLogs($logDiasPresentes, __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'novOk'); // Guardamos el texto de la novedad en el archivo de log
                    $finSendApi    = microtime(true); // Tiempo de finalizacion del envio al Api
                    $durSendApi   = (round($finSendApi - $iniSendApi, 2)); // Duracion del envio al Api
                    $textRespuesta = ($respuesta->SUCCESS == 'YES') ? "Registros actualizados correctamente en WF. Dur $durSendApi" :  $textRespuesta = $respuesta->MENSAJE; // Texto de la respuesta envio WF
                    fileLogs($textRespuesta, __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'novOk'); // Guardamos el texto de la novedad en el archivo de log
                }
            endif;
        endif;

        $arrayFechas = arrayFechas($value['fecha_desdeStr'], $value['fecha_hastaStr']); // Obtenemos un array de fechas

        foreach ($arrayFechas as $fech) { // Recorremos el array de fechas
            $perCierreFech = perCierreFech($fech, $value['legajo'], $link); // Obtenemos el periodo de cierre de la fecha
            $fechaCarga  = fechaFormat($fech, 'd/m/Y'); 
            if ($perCierreFech) { // Si la fecha de cierre es mayor a la fecha de la novedad  y enviamos el error a la API WF con status N
                $fechaCierre = fechaFormat($perCierreFech, 'd/m/Y'); // Fecha de cierre 
                $motivo = "No se pudo ingresar la Novedad ($value[CodNov]) $value[NovDesc]. Período cerrado el $fechaCierre";
                $jsonData = array(
                    "legajo"  => "$value[legajo]",
                    "fecha"   => "$fech",
                    "novedad" => "$value[CodNov]",
                    "status"  => "N",
                    "id_out"  => "$value[id_out]",
                    "motivo"  => urlencode($motivo)
                ); // Creamos el array con los datos de la novedad para enviar a la API
                $jsonData = json_encode($jsonData); // Convertimos el array en JSON
                $iniSendApi = microtime(true); // Tiempo de inicio del envio al Api
                $sendApiData = sendApiData($dataJson['api']['url'] . '?TYPE=respuesta&data=[' . $jsonData . ']', $auth, $proxy, 10, ''); // Enviamos el objeto JSON a la API
                $respuesta = (json_decode($sendApiData)); // Decodifico el JSON de la respuesta de la API WF
                $fechaCerrada = "Novedad \"(" . $value['CodNov'] . ")\" " . trim($value['NovDesc']) . ". Ingresada: \"NO\". Legajo \"($value[legajo]) $value[ApNo]\" Fecha: \"$fechaCarga\". Período cerrado \"($fechaCierre)\""; // Texto de Log de novedades no ingresadas
                fileLogs($fechaCerrada, __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'novOk'); // Guardamos el texto de la novedad en el archivo de log
                $finSendApi    = microtime(true); // Tiempo de finalizacion del envio al Api
                $durSendApi   = (round($finSendApi - $iniSendApi, 2)); // Duracion del envio al Api
                $textRespuesta = ($respuesta->SUCCESS == 'YES') ? "Registros actualizados correctamente en WF. Dur $durSendApi" :  $textRespuesta = $respuesta->MENSAJE; // Texto de la respuesta envio WF
                fileLogs($textRespuesta, __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'novOk'); // Guardamos el texto de la novedad en el archivo de log
            }
        }

        $jsonData = json_encode(array("status" => "E", "id_out" => "$value[id_out]")); // Creamos el objeto json para enviarlo al Api
        $sendApiData = sendApiData($dataJson['api']['url'] . "?TYPE=update&data=[$jsonData]", $auth, $proxy, 5, ''); // Enviamos el objeto JSON a la API notificando que se termino de procesar la novedad con status 'E' 
        $respuesta   = (json_decode($sendApiData)); // Decodifico el JSON de la respuesta de la API WF
        $textRespuesta = ($respuesta->SUCCESS == 'YES') ? "Registro actualizado correctamente en WF con status E" :  $textRespuesta = $respuesta->MENSAJE; // Texto de la respuesta envio WF
        fileLogs($textRespuesta, __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'novOk'); // Guardamos el texto de la novedad en el archivo de log

        $finRegWF_P = microtime(true); // Tiempo de finalizacion del envio al Api
        $durRegWF_P = (round($finRegWF_P - $iniRegWF_P, 2)); // Duracion del envio al Api
        fileLogs("Fin ID Registro WF Pendiente: " . $value["id_out"] . ". Dur: $durRegWF_P", __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'novOk'); // Guardamos el texto de Fin ID Registro WF en el archivo de log
        // break;

    } // Fin status es P
    /** 
     * 
     * FIN PROCESO DE REGISTROS CON STATUS p
     * 
     */
    /**--------------- */
    /** 
     * 
     * PROCESAMOS LOS REGISTROS CON STATUS A
     * 
     */
    if ($value['status'] == 'A') { // Si el status es A

        // $arrayFechas = fechaIniFinDias($value['fecha_desdeStr'], $value['fecha_hastaStr'], 1);
        $arrayFechas = arrayFechas($value['fecha_desdeStr'], $value['fecha_hastaStr']);
        $iniRegWF_A = microtime(true);
        fileLogs("Inicio ID Registro WF Anulado: " . $value["id_out"], __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'novOk');

        foreach ($arrayFechas as $fech) {
            $perCierreFech = perCierreFech($fech, $value['legajo'], $link);
            $fechaCarga  = fechaFormat($fech, 'd/m/Y');
            if ($perCierreFech) { // Si la fecha de cierre es mayor a la fecha de la novedad  y enviamos el error a la API WF con status N
                $fechaCierre = fechaFormat($perCierreFech, 'd/m/Y'); // Fecha de cierre 
                $motivo = "No se pudo eliminar la Novedad ($value[CodNov]) $value[NovDesc]. Período cerrado el $fechaCierre";
                $jsonData = array(
                    "legajo"  => "$value[legajo]",
                    "fecha"   => "$fech",
                    "novedad" => "$value[CodNov]",
                    "status"  => "N",
                    "id_out"  => "$value[id_out]",
                    "motivo"  => urlencode($motivo)
                ); // Creamos el array con los datos de la novedad para enviar a la API
                $jsonData = json_encode($jsonData); // Convertimos el array en JSON
                $iniSendApi = microtime(true); // Tiempo de inicio del envio al Api
                $sendApiData = sendApiData($dataJson['api']['url'] . '?TYPE=respuesta&data=[' . $jsonData . ']', $auth, $proxy, 10, ''); // Enviamos el objeto JSON a la API
                $respuesta = (json_decode($sendApiData)); // Decodifico el JSON de la respuesta de la API WF
                $fechaCerrada = "Novedad \"(" . $value['CodNov'] . ")\" " . trim($value['NovDesc']) . ". Eliminada: \"NO\". Legajo \"($value[legajo]) $value[ApNo]\" Fecha: \"$fechaCarga\". Período cerrado \"($fechaCierre)\""; // Texto de Log de novedades no ingresadas
                fileLogs($fechaCerrada, __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'novOk'); // Guardamos el texto de la novedad en el archivo de log
                $finSendApi    = microtime(true); // Tiempo de finalizacion del envio al Api
                $durSendApi   = (round($finSendApi - $iniSendApi, 2)); // Duracion del envio al Api
                $textRespuesta = ($respuesta->SUCCESS == 'YES') ? "Registros actualizados correctamente en WF. Dur $durSendApi" :  $textRespuesta = $respuesta->MENSAJE; // Texto de la respuesta envio WF
                fileLogs($textRespuesta, __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'novOk'); // Guardamos el texto de la novedad en el archivo de log
            } else { // Si la fecha de cierre es menor a la fecha de la novedad  eliminamos la novedad de la tabla FICHAS3
                $eliminarNovedad = eliminarNovedad($value['legajo'], $fech, $value['CodNov'], $link); // Eliminamos la novedad de la tabla FICHAS3
                if ($eliminarNovedad) { // Si se elimino la novedad de la tabla FICHAS3
                    fileLogs("Novedad \"($value[CodNov])\" $value[NovDesc] eliminada en CH el \"$fechaCarga\"", __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'novOk'); // Guardamos el texto de la novedad en el archivo de log
                    audito_ch("B", 'Baja Novedad (' . $value['CodNov'] . ') Legajo ' . $value['legajo']  . '. Fecha: ' . $fechaCarga, $link); // Audito la baja de novedad en CH

                    /** 
                     * * * * * * * * * * * * * * * * * * * * * * 
                     * * * * * * * * * * * * * * * * * * * * * * 
                     * PROCESAMOS ELIMINACION DE FICHAS3 
                     * * * * * * * * * * * * * * * * * * * * * * 
                     * * * * * * * * * * * * * * * * * * * * * * 
                     */
                    if ($fech <= date('Ymd')) :
                        $iniProc = microtime(true); // Tiempo de inicio de proceso de la novedad
                        $procesarNovedad = procesarNovedad($value['legajo'], $fechaCarga, $fechaCarga, $urlWebService); // Procesamos la novedad en CH por medio del webservice
                        $terminadoProc   = false; // Bandera para saber si la novedad se termino de procesar
                        if (($procesarNovedad) == 'Terminado') : // Si la novedad se ingreso correctamente
                            $terminadoProc = true; // Se cambia la bandera
                            $finProc    = microtime(true); // Tiempo de finalizacion de proceso de la novedad
                            $durProc    = (round($finProc - $iniProc, 2)); // Duracion de de proceso de la novedad
                            $textNov = 'Fecha: "' . $fechaCarga . '". Procesada  "SI". Legajo "(' . $value['legajo'] . ') ' . trim($value['ApNo']) . '. " Dur: ' . $durProc; // Texto de la novedad ingresada para gardar en el log
                            $pathLog = __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log"; // Ruta del archivo de log
                            fileLogs($textNov, $pathLog, 'novOk'); // Guardamos el texto de la novedad en el archivo de log
                            audito_ch("P", "Proceso de Datos Legajo $value[legajo]. Desde: $fechaCarga a $fechaCarga", $link);
                        else :
                            $terminadoProc = false; // Se cambia la bandera
                            $textNov = 'Fecha: "' . $fechaCarga . '". Procesada  "NO". Legajo "(' . $value['legajo'] . ') ' . trim($value['ApNo']) . '. " Dur: ' . $durProc; // Texto de la novedad ingresada para gardar en el log
                            $pathLog = __DIR__ . "/logs/novedades/" . date('Ymd') . "_error.log"; // Ruta del archivo de log
                            fileLogs($textNov, $pathLog, 'novErr'); // Guardamos el texto de la novedad en el archivo de log
                        endif;
                    endif;
                    /** 
                     * * * * * * * * * * * * * * * * * * * * * *  
                     * * * * * * * * * * * * * * * * * * * * * *  
                     * FIN DEL PROCESAR ELIMINACION DE FICHAS3
                     * * * * * * * * * * * * * * * * * * * * * *  
                     * * * * * * * * * * * * * * * * * * * * * *  
                     * */
                }
            }
        }
        $jsonData = json_encode(array("status" => "E", "id_out" => "$value[id_out]")); // Creamos el objeto json para enviarlo al Api
        $sendApiData = sendApiData($dataJson['api']['url'] . "?TYPE=update&data=[$jsonData]", $auth, $proxy, 5, ''); // Enviamos el objeto JSON a la API notificando que se termino de procesar la novedad con status 'E' 
        $respuesta   = (json_decode($sendApiData)); // Decodifico el JSON de la respuesta de la API WF
        $textRespuesta = ($respuesta->SUCCESS == 'YES') ? "Registro actualizado correctamente en WF con status E" :  $textRespuesta = $respuesta->MENSAJE; // Texto de la respuesta envio WF
        fileLogs($textRespuesta, __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'novOk'); // Guardamos el texto de la novedad en el archivo de log

        $finRegWF_A = microtime(true); // Tiempo de finalizacion del envio al Api
        $durRegWF_A = (round($finRegWF_A - $iniRegWF_A, 2)); // Duracion del envio al Api
        fileLogs("Fin ID Registro WF Anulado: " . $value["id_out"] . ". Dur: $durRegWF_A", __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'novOk'); // Guardamos el texto de Fin ID Registro WF en el archivo de log

        // PerCierreFech($FechaStr, $Legajo, $link);

        fileLogs("leg.: $value[legajo]. Nov: $value[NovDesc]. Fecha: $value[fecha_desde] a $value[fecha_hasta]", __DIR__ . "/logs/errores/" . date('Ymd') . "_Status_A.log", ''); // Guardo el log

    } // Fin status es A
    /** 
     * 
     * FIN PROCESO DE REGISTROS CON STATUS A
     * 
     */
}
$tiempo_fin = microtime(true);
$duracion   = (round($tiempo_fin - $tiempo_ini, 2));
$textNov = "Fin de Proceso de Novedades. Dur: \"$duracion Segundos\"";
$pathLog = __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log"; // Ruta del archivo de log
fileLogs($textNov, $pathLog, 'novOk'); // Guardamos el texto de Fin de Proceso de Novedades en el archivo de log
sqlsrv_close($link);
respuestaScript($textNov, 'ok'); // Respuesta del script
/**  */
exit;
