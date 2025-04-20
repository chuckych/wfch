<?php
/** Autor: Norberto CH: nchaquer@gmail.com */
// 15 minutos de tiempo maximo de ejecución
ini_set('max_execution_time', 900);
// json response
header("Content-Type: application/json; charset=utf-8");
// allow cross-domain requests
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=utf-8");

// Reportar todos Los errores
error_reporting(E_ALL);
ini_set('display_errors', '0'); // 0 - off 1 - on 

// Tiempo inicial
$tiempo_ini = microtime(true);

$_SERVER["argv"][1] = $_SERVER["argv"][1] ?? "";
$_GET['script'] = $_GET['script'] ?? "";
$_GET['html'] = $_GET['html'] ?? "";

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
$dataJson = $dataConfig;

// Ruta del archivo de log
$pathLog = __DIR__ . "/logs/novedades/";

// Dias a borrar
$dias = $dataJson['borrarLogs']['dias'];

// Extensión de los archivos de novedades
$ext = ".log";
borrarLogs($pathLog, $dias, $ext);

// Ruta del archivo de log de errores
$pathLog = __DIR__ . "/logs/errores/";
borrarLogs($pathLog, $dias, $ext);

// Ruta del archivo de log de la conexión
$pathLog = __DIR__ . "/logs/conn/";
borrarLogs($pathLog, $dias, $ext);

// Ruta del archivo de log de informacion
$pathLog = __DIR__ . "/logs/";
borrarLogs($pathLog, $dias, $ext);

// Ruta del archivo de log de informacion
$pathLog = __DIR__ . "/logs/info/";
borrarLogs($pathLog, $dias, $ext);
/**  */

/** Valores del archivo config.json */

// Usuario y contraseña de la API WF
$auth = [$dataJson['api']['user'], $dataJson['api']['pass']];
// Proxy datos
$proxy = [$dataJson['proxy']['ip'], $dataJson['proxy']['port'], $dataJson['proxy']['enabled']];
// Url del webservice
$urlWebService = $dataJson['webService']['url'];

/**  */

/** Realizar un Ping a la API WF */
// Ping de la API
$pingApi = json_decode(pingApi($dataJson['api']['url'] . "?status=Ping", $auth, $proxy), true);

// Si la respuesta de la pingApi de WF es NO
if ($pingApi['SUCCESS'] != 'YES') {
    $text = 'Error al conectar con API WF' . ' - ' . $pingApi['ERROR']; // Texto para el log
    fileLogs($text, __DIR__ . "/logs/errores/" . date('Ymd') . "_PingAPI_WF.log", ''); // Guardo el log
    sendEmail("WFCH -> Error API", "<pre>$text</pre>");
    exit; // Salgo deL SCRIPT
}
/** */

/** Realizar un Ping al WebService de Control Horario */

// ping al webservice
$ping = PingWebService($urlWebService . 'Ping?');

// Si no hay conexion con el webservice salimos del script
if (!$ping) {
    // Respuesta del script
    respuestaScript('Error al conectar con WebService', 'Error');
    // Enviar email de error
    sendEmail("WFCH -> Error al conectar con WebService", "<pre>$urlWebService - Error</pre>");
    // Cerrar conexión a la bd sql
    sqlsrv_close($link);
    // Salgo deL SCRIPT
    exit;
}
/**  */

$data = [];
$FechaHora = date('Ymd H:i:s');
$textLog = '';

/** Obtener Novedades de CH */
require __DIR__ . '/novedades.php';
/** Este devuelve un array con los datos de las novedades de control horario
 *  'CodNov'   => Codigo de Novedad,
 *  'NovDesc'  => Descripcion de la Novedad,
 *  'CodTipo'  => Codigo de Tipo de Novedad,
 *  'TipoDesc' => Descripcion del Tipo de Novedad,
 *   'NovID'    => ID de la Novedad,
 */
/**  */
/** Obtener Otras Novedades de CH */
require __DIR__ . '/novedades_otras.php';
/** Este devuelve un array con los datos de las otras novedades de control horario
 *  'CodNov'   => Codigo de Novedad,
 *  'NovDesc'  => Descripcion de la Novedad,
 *  'CodTipo'  => Codigo de Tipo de Novedad. Retorna 0 o 1
 */
/**  */

/** Obtener Personal de CH */
require __DIR__ . '/personal.php';
/** Este devuelve un array con los datos de los Legajo de control horario
 *  'legajo' => 99999999,
 *  'ApNo' => 'Nombre y Apellido',
 */
/** */

/** LLAMAMOS A LA API DE WF */
$dataApi = json_decode(apiData($dataJson['api']['url'] . "?status=P,A", $auth, $proxy, 10), true); // Obtengo los datos de la API WF
/** Ejemplo de respuesta API 
 * "SUCCESS": "YES",
 * "FAILURE": "NO",
 * "ERROR": "",
 * "count": 1,
 * "data": [
 *    {
 *    "id_out": "16",
 *    "legajo": "9999999",
 *    "novedad": "6",
 *    "fecha_desde": "2021-07-02",
 *    "fecha_hasta": "2021-07-02",
 *    "horas": "",
 *    "valor": "0.00",
 *    "justificacion": "0",
 *    "observaciones": "Es una prueba",
 *    "dias_laborales": "0",
 *    "categoria": "0",
 *    "status": "P",
 *    "registro": "2021-07-08 10:28:52",
 *    "export": null,
 *    "usuario_exec": null,
 *    "id_solicitud": "221",
 *    "usuario_modificacion": null,
 *    "fecha_modificacion": null
 *    }
 */
/** CHEQUEAMOS LA RESPUESTA DE LA API DE WF */

// Si la respuesta de la API de WF es NO
if ($dataApi['SUCCESS'] != 'YES') {
    $text = 'Error al obtener los datos de la API WF'; // Texto para el log
    fileLogs($dataApi['ERROR'], __DIR__ . "/logs/errores/" . date('Ymd') . "_API_WF.log", ''); // Guardo el log
    sendEmail("WFCH -> Error API WF", "<pre>$text</pre>");
    sqlsrv_close($link); // Cierro la conexión MSQL
    exit; // Salgo deL SCRIPT
}

// Verifico si la API devuelve datos, sino salgo del script
if ($dataApi['count'] <= 0) {
    fileLogs("No Hay Novedades Pendientes", __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", 'novOk'); // Guardo el log
    sqlsrv_close($link); // Cierro la conexión MSQL
    exit; // Salgo deL SCRIPT
}
/** */
$totalData = $dataApi['count']; // Cantidad de datos de la API

/** Recorrer los datos de la API WF  */
$textInicio = "INICIO DE PROCESO DE SOLICITUDES. TOTAL: ($totalData):";
fileLogs($textInicio, __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", ''); // Log de Inicio de proceso de Novedades

// Recorro los datos de la API para crear un objeto con los datos de la API
foreach ($dataApi['data'] as $key => $value) {
    $textLog = '';
    $fecha_desde = (validar_fecha(fechaFormat($value['fecha_desde'], 'd/m/Y'))) ? fechaFormat($value['fecha_desde'], 'd/m/Y') : '';
    $fecha_hasta = (validar_fecha(fechaFormat($value['fecha_hasta'], 'd/m/Y'))) ? fechaFormat($value['fecha_hasta'], 'd/m/Y') : '';
    $fecha_desdeStr = (fechaFormat($value['fecha_desde'], 'Ymd')) ? fechaFormat($value['fecha_desde'], 'Ymd') : '';
    $fecha_hastaStr = (fechaFormat($value['fecha_hasta'], 'Ymd')) ? fechaFormat($value['fecha_hasta'], 'Ymd') : '';

    // Creo un array con los datos de la API
    $dataApi = [
        'id_out' => $value['id_out'], // ID del registro de la API
        'fecha_desde' => $fecha_desde, // Fecha desde como d/m/Y
        'fecha_hasta' => $fecha_hasta, // Fecha hasta como d/m/Y
        'fecha_desdeStr' => $fecha_desdeStr, // Fecha desde como String Ymd
        'fecha_hastaStr' => $fecha_hastaStr, // Fecha hasta como String Ymd
        'horas' => empty($value['horas']) ? '00:00' : $value['horas'], // Horas
        'valor' => $value['valor'] ?? '0', // Valor
        'observaciones' => $value['observaciones'], // Observaciones
        'dias_laborales' => ($value['dias_laborales']) ? intval($value['dias_laborales']) : 0,  // Dias laborales 0 = Todos los días, 1 = Solo en días laborales
        'status' => $value['status'], // Status
        'id_solicitud' => $value['id_solicitud'], // id_solicitud
    ];

    // Si es una otra novedad le asigno 1, si no le asigno 0. Esto se usa para filtrar las otras novedades.
    $siEsOtrasNov = !in_array($dataApi['valor'], ['0.00', '0', ''], true);
    $dataApi['ONov'] = $siEsOtrasNov ? 1 : 0;

    // Obtengo el codigo de la novedad por separado y pasamos valor a entero con la funcion intval
    $novCodi = (intval($value['novedad'])) ?? '';
    $horasNov = horaFormat($dataApi['horas']); // Formateamos las horas en 00:00
    $horasNov = empty($value['horas']) ? '00:00' : $value['horas']; // Horas de la novedad devuelto por la API
    $horasNov = (!esHora($horasNov)) ? '00:00' : $horasNov; // Si no es correcto le asigno 00:00

    // Filtramos el codigo de la novedad que viene de la api de WF con el objetoNovedadesCH o objetoNovedadesOtrasCH para verificar que exista la novedad en CH y completar los datos faltantes.
    $filtroNovedad = filtrarObjeto($dataApi['ONov'] === 1 ? $objetoNovedadesOtrasCH : $objetoNovedadesCH, 'CodNov', $novCodi);

    // Filtramos el legajo que viene de la api de WF con el objetoLegajosCH para verificar que exista el legajo en CH y completar los datos faltantes.
    $filtrarLegajo = filtrarObjeto($objetoLegajosCH, 'legajo', intval($value['legajo']));

    if (
        $filtroNovedad // Si existe la novedad
        && $filtrarLegajo // Si existe el legajo
        && esHora($horasNov) // Si la hora no tiene un formato valido de 24 horas
        && !empty($dataApi['fecha_desde']) // Si la fecha desde no esta vacia
        && !empty($dataApi['fecha_hasta']) // Si la fecha hasta no esta vacia
        && !empty($value['legajo']) // Si el legajo no esta vacio
        && fechaFormat($value['fecha_desde'], 'Ymd') <= fechaFormat($value['fecha_hasta'], 'Ymd') // Si la fecha desde es menor o igual a la fecha hasta
    ) { // Si cumplen todas las condiciones. Creamos el objeto data.
        $data[] = array_merge($filtroNovedad, $dataApi, $filtrarLegajo); // Hacemos un merge con los datos del 'objetoNovedadesCH' al objeto 'dataApi' y 'objetoLegajosCH'.
        /** Ejemplo del objeto creado.
         *array (
         *  'CodNov'         => 301,
         *  'NovDesc'        => 'Ausente sin Aviso',
         *  'CodTipo'        => 3,
         *  'TipoDesc'       => 'Ausencia',
         *  'NovID'          => 'AUS',
         *  'id_out'         => '2',
         *  'fecha_desde'    => '06/09/2019',
         *  'fecha_hasta'    => '06/09/2019',
         *  'fecha_desdeStr' => '20190906',
         *  'fecha_hastaStr' => '20190906',
         *  'horas'          => '00:00',
         *  'observaciones'  => '',
         *  'dias_laborales' => '0',
         *  'legajo'         => 9999999,
         *  'ONov'           => 0,
         *  'ApNo'           => 'nombre y apellido',
         *  'id_solicitud'   => '215',
         *),
         */
    } else {
        $textLog .= 'Nov: ' . $novCodi . '. Leg: ' . $value['legajo'] . '. ' . fechaFormat($value['fecha_desde'], 'd/m/Y') . ' - ' . fechaFormat($value['fecha_hasta'], 'd/m/Y') . ' ID_OUT: ' . $value['id_out'] . '.'; // Texto de Error
        $textLog .= $filtrarLegajo ? '' : " Legajo no existe en CH. "; // Si el legajo no existe en CH
        $textLog .= $filtroNovedad ? '' : " Novedad no existe en CH. "; // Si la novedad no existe en CH
        $textLog .= (empty($value['fecha_desde'])) ? ", Fecha desde vacia. " : ''; // Si la fecha desde esta vacia
        $textLog .= (empty($value['fecha_hasta'])) ? ", Fecha hasta vacia. " : ''; // Si la fecha hasta esta vacia
        $textLog .= (empty($value['legajo'])) ? ", Legajo vacio. " : '';    // Si el legajo esta vacio
        $textLog .= (!esHora($horasNov)) ? ", Formato de horas erroneo. " : ''; // Si la hora no tiene un formato valido de 00:00
        $textLog .= (fechaFormat($value['fecha_desde'], 'Ymd') > fechaFormat($value['fecha_hasta'], 'Ymd')) ? ", La fecha desde: " . $dataApi['fecha_desde'] . " es mayor que la fecha hasta " . $dataApi['fecha_hasta'] : ""; // Si la fecha desde es mayor a la fecha hasta, Si no, no hacemos nada
        //$textLog .= "\n"; // Salto de linea
    }

    // Si el texto de error no esta vacio, guardamos el log de error.
    if (!empty($textLog)) {
        // Si hay texto de error lo guardamos en el archivo de log
        fileLogs("Error: {$textLog}", __DIR__ . "/logs/errores/" . date('Ymd') . "_error.log", '');
        // Log de Inicio de Ingreso de Novedades
        fileLogs("Error: {$textLog}", __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", '');
        //sendEmail("WFCH -> Error", "<pre>$textLog</pre>");
        $jsonData = json_encode(["legajo" => "$value[legajo]", "fecha" => "$dataApi[fecha_desdeStr]", "novedad" => "$value[novedad]", "status" => "N", "id_out" => "$value[id_out]", "motivo" => urlencode($textLog)]); // Creamos el array con los datos de la novedad para enviar a la API
        setErrorApi($jsonData, $value['id_solicitud'], $dataJson['api']['url'], $auth, $proxy);
        $jsonData = json_encode(["status" => "E", "id_out" => "$value[id_out]"]); // Creamos el objeto json para enviarlo al Api
        setExportadoApi($jsonData, $value['id_solicitud'], $dataJson['api']['url'], $auth, $proxy);
    }
}
/** */

$diasPresentes = []; // Array de dias presentes
$item = 0; // Contador de items
// Contador de items
/** Iniciamos el ingreso de novedades al Web Service */

// Recorremos el array con los datos del objeto creado de la API WF para ingresar las novedades en CH
foreach ($data as $key => $value) {
    $textNov = '';
    /** 
     * 
     * PROCESAMOS LOS REGISTROS CON STATUS p
     * 
     */

    // Si EL STATUS ES P (Pendiente)
    if ($value['status'] == 'P') {
        $error = true; // Marcamos el error como true

        // CHEQUEAMOS SI EL PERIODO ESTA CERRADO
        if ($error) {
            $arrayFechas = arrayFechas($value['fecha_desdeStr'], $value['fecha_hastaStr']); // Obtenemos el array de fechas

            foreach ($arrayFechas as $fech) { // Recorremos el array de fechas
                $perCierreFech = perCierreFech($fech, $value['legajo'], $link);

                if ($perCierreFech) {
                    // Si la fecha de cierre es mayor a la fecha de la novedad  marcamos el error
                    iniPendienteLog($value["id_solicitud"]); // Iniciamos el log de la novedad
                    $ini = microtime(true); // Iniciamos el contador de tiempo
                    $textError = "Error -> El legajo $value[legajo] tiene fecha de cierre en el periodo \"$value[fecha_desde] al $value[fecha_hasta]\"";
                    // sendEmail("WFCH -> Error", "<pre>$textError</pre>");

                    fileLogs($textError, __DIR__ . "/logs/errores/" . date('Ymd') . "_error.log", '');
                    fileLogs($textError, __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", '');

                    /** Marcamos Con Status (N) en API WF*/
                    $textError = "Error al ingresar la solicitud $value[id_solicitud]. Periodo cerrado.";
                    $jsonData = json_encode(array("legajo" => "$value[legajo]", "fecha" => "$dataApi[fecha_desdeStr]", "novedad" => "$value[CodNov]", "status" => "N", "id_out" => "$value[id_out]", "motivo" => urlencode($textError))); // Creamos el array con los datos de la novedad para enviar a la API
                    /**  */
                    setErrorApi($jsonData, $value['id_solicitud'], $dataJson['api']['url'], $auth, $proxy);
                    /** Marcamos Con Status (E) en API WF*/
                    $jsonData = json_encode(array("status" => "E", "id_out" => "$value[id_out]")); // Creamos el objeto json para enviarlo al Api
                    setExportadoApi($jsonData, $value['id_solicitud'], $dataJson['api']['url'], $auth, $proxy);
                    /**  */
                    finPendienteLog($ini, $value["id_solicitud"]); // Guardamos el texto de Fin ID Registro WF en el archivo de log
                    $error = true; // Marcamos el error como verdadero
                    break; // Salimos del ciclo
                } else {
                    $error = false; // Marcamos el error como falso
                }
            }
        }

        // SI NO HAY ERROR chequeamos si el legajo esta en el array de dias presentes
        if (!$error) {
            // Si el tipo de novedad es Ausencia y no es Otra Novedad retornamos true, si no retornamos false
            $TipoNovAusencia = ($value['CodTipo'] > 2 && $value['ONov'] !== 1) ? true : false;
            // Si el tipo de novedad es de tipo 'Ausencia'
            if ($TipoNovAusencia) {
                // Obtenemos un array (diasPresentes) con los dias que el legajo tiene fichadas. Esta información se busca en la tabla registro de control horario.
                $diasPresentes = validaDiaFichadas($value['legajo'], $value['fecha_desdeStr'], $value['fecha_hastaStr'], $link);

                // Si el legajo tiene dias presentes
                if ($diasPresentes) {
                    iniPendienteLog($value["id_solicitud"]); // Iniciamos el log de la novedad
                    $ini = microtime(true); // Iniciamos el contador de tiempo
                    $textError = "El legajo $value[legajo] tiene dias presentes en el periodo $value[fecha_desde] a $value[fecha_hasta]";
                    //sendEmail("WFCH -> Error", "<pre>$textError</pre>");

                    fileLogs($textError, __DIR__ . "/logs/errores/" . date('Ymd') . "_error.log", '');
                    fileLogs($textError, __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", '');

                    /** Marcamos Con Status (N) en API WF*/
                    $textError = "Error al ingresar la solicitud $value[id_solicitud]. Periodo con presencia.";
                    $jsonData = json_encode(array("legajo" => "$value[legajo]", "fecha" => "$dataApi[fecha_desdeStr]", "novedad" => "$value[CodNov]", "status" => "N", "id_out" => "$value[id_out]", "motivo" => urlencode($textError))); // Creamos el array con los datos de la novedad para enviar a la API
                    setErrorApi($jsonData, $value['id_solicitud'], $dataJson['api']['url'], $auth, $proxy);
                    /**  */
                    /** Marcamos Con Status (E) en API WF*/
                    $jsonData = json_encode(array("status" => "E", "id_out" => "$value[id_out]")); // Creamos el objeto json para enviarlo al Api
                    setExportadoApi($jsonData, $value['id_solicitud'], $dataJson['api']['url'], $auth, $proxy);
                    /**  */
                    finPendienteLog($ini, $value["id_solicitud"]); // Guardamos el texto de Fin ID Registro WF en el archivo de log
                    $error = true; // Marcamos el error como verdadero
                } else {
                    $error = false; // Marcamos el error como falso
                }
            } // Fin del if ($TipoNovAusencia)
        } // Fin del if (!$error)

        // SI NO HAY ERROR PROCESAMOS LA SOLICITUD
        if (!$error) {

            // Iniciamos el contador de tiempo
            $ini = microtime(true);
            // Iniciamos el log de la novedad
            iniPendienteLog($value["id_solicitud"]);

            // Obtenemos un array que divide rango de fechas en bloques de 31 días. Esto es porque el webservice solo carga informacion de 31 dias a la vez.
            $arrayFechas = fechaIniFinDias($value['fecha_desdeStr'], $value['fecha_hastaStr'], 31);

            // Recorremos el array de bloques de 31 días para ingresar las novedades en CH.
            foreach ($arrayFechas as $date) {
                $iniNov = microtime(true); // Tiempo de inicio ingreso Novedad
                // Si no es otra Novedad
                if ($value["ONov"] !== 1) {
                    $ingresarNovedad = ingresarNovedad($value['legajo'], $value['legajo'], $date['fecha_desde'], $date['fecha_hasta'], $value['dias_laborales'], $value['CodNov'], $value['observaciones'], $value['horas'], $urlWebService); // Ingresamos la novedad en CH por medio del webservice
                    $terminado = false; // Bandera para saber si la novedad se termino de ingresar
                    if ($ingresarNovedad == 'Terminado') { // Si la novedad se ingreso correctamente
                        $finNov = microtime(true); // Tiempo de finalizacion de ingreso Novedad
                        $durNov = round($finNov - $iniNov, 2); // Duracion de ingreso Novedad
                        $terminado = true; // Se cambia la bandera
                        audito_ch("A", 'Alta Novedad (' . $value['CodNov'] . ') Legajo ' . $value['legajo'] . '. Desde: ' . $date['fecha_desde'] . ' a ' . $date['fecha_hasta'], $link); // Audito la novedad en CH

                        $item = str_pad($item + 1, 2, '0', STR_PAD_LEFT); // Sumo 1 al contador de items

                        $textNov = 'Novedad "(' . $value['CodNov'] . ')" "' . trim($value['NovDesc']) . '" ingresada. Legajo "(' . $value['legajo'] . ') ' . trim($value['ApNo']) . '". Periodo: "' . $date['fecha_desde'] . '" al "' . $date['fecha_hasta'] . '". Dur: ' . $durNov;
                        $pathLog = __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log"; // Ruta del archivo de log
                        fileLogs($textNov, $pathLog, ''); // Guardamos el texto de la novedad en el archivo de log

                        /** 
                         * * * * *  FIN DEL PROCESAR   * * * * *
                         */
                    } else { // Si la novedad no se ingreso correctamente
                        $terminado = false; // Se cambia la bandera
                        $finNov = microtime(true);
                        $durNov = round($finNov - $iniNov, 2); // Duracion de ingreso Novedad
                        $textNov = 'Novedad "(' . $value['CodNov'] . ')" ' . trim($value['NovDesc']) . '. Ingresada: "NO". Legajo "(' . $value['legajo'] . ') ' . trim($value['ApNo']) . '". Fechas: "' . $date['fecha_desde'] . ' al ' . $date['fecha_hasta'] . '". Horas: "' . $value['horas'] . '" Dur:' . $durNov;
                        $pathLog = __DIR__ . "/logs/novedades/" . date('Ymd') . "_error.log";
                        fileLogs($textNov, $pathLog, ''); // Guardamos el texto de la novedad en el archivo de log
                    } // Fin del if que verifica si la novedad se ingreso correctamente
                } else {
                    $arrayFechas = arrayFechas($date['fecha_desdeStr'], $date['fecha_hastaStr']);
                    foreach ($arrayFechas as $fecha) {
                        // Ingresamos las otras novedades en CH.
                        $insertarOtrasNovedades = insertarOtrasNovedades(
                            $fecha,
                            $value['legajo'],
                            $value['CodNov'],
                            $value['valor'],
                            $value['observaciones'],
                            $link);
                    }
                }
            } // Fin del foreach que recorre las novedades

            /** 
             * * * * *  PROCESAMOS EL PERIODO SI LA FECHA DESDE ES MENOR O IGUAL A LA ACTUAL  * * * * *
             */
            if ($value['ONov'] !== 1) {
                if ($value['fecha_desdeStr'] <= date('Ymd')):
                    // Si la fecha hasta de la novedad es mayor al dia actual, definimos la fecha hasta como la fecha actual. Ya que no se puede procesar dia a futuro en CH.
                    $fechaHastaProc = ($date['fecha_hastaStr'] > date('Ymd')) ? date('d/m/Y') : $date['fecha_hasta'];
                    // Tiempo de inicio de proceso de la novedad
                    $iniProc = microtime(true);
                    $procesarNovedad = procesarNovedad($value['legajo'], $date['fecha_desde'], $fechaHastaProc, $urlWebService); // Procesamos la novedad en CH por medio del webservice
                    // Bandera para saber si la novedad se termino de procesar
                    $terminadoProc = false;
                    if ($procesarNovedad == 'Terminado'): // Si la novedad se ingreso correctamente
                        $terminadoProc = true; // Se cambia la bandera
                        $finProc = microtime(true); // Tiempo de finalizacion de proceso de la novedad
                        $durProc = round($finProc - $iniProc, 2); // Duracion de de proceso de la novedad
                        $textNov = 'Periodo "' . $date['fecha_desde'] . ' a ' . $fechaHastaProc . '" procesado. Legajo "(' . $value['legajo'] . ') ' . trim($value['ApNo']) . '" Dur: ' . $durProc; // Texto de la novedad ingresada para gardar en el log
                        $pathLog = __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log"; // Rsuta del archivo de log
                        fileLogs($textNov, $pathLog, ''); // Guardamos el texto de la novedad en el archivo de log
                        audito_ch("P", "Proceso de Datos Legajo $value[legajo]. Desde: $date[fecha_desde] a $fechaHastaProc", $link);
                    else:
                        $terminadoProc = false; // Se cambia la bandera
                        $textNov = 'Novedad "(' . $value['CodNov'] . ')" ' . trim($value['NovDesc']) . '. Procesada: "NO". Legajo "(' . $value['legajo'] . ') ' . trim($value['ApNo']) . '". Fechas: "' . $date['fecha_desde'] . ' al ' . $fechaHastaProc . '". Horas: "' . $value['horas'] . '" Dur: ' . $durProc; // Texto de la novedad no ingresada para gardar en el log
                        $pathLog = __DIR__ . "/logs/novedades/" . date('Ymd') . "_error.log"; // Ruta del archivo de log
                        fileLogs($textNov, $pathLog, ''); // Guardamos el texto de la novedad en el archivo de log
                    endif;
                endif;
            }
            /** 
             * * * * *  FIN DEL PROCESAR   * * * * *
             */

            $jsonData = json_encode(["status" => "E", "id_out" => "$value[id_out]"]); // Creamos el objeto json para enviarlo al Api
            setExportadoApi($jsonData, $value['id_solicitud'], $dataJson['api']['url'], $auth, $proxy);
            finPendienteLog($ini, $value["id_solicitud"]); // Guardamos el texto de Fin ID Registro WF en el archivo de log
        }
    } // FIN DE STATUS P
    /** 
     * 
     * FIN PROCESO DE REGISTROS CON STATUS P = PENDIENTE
     * 
     */
    /**--------------- */
    /** 
     * 
     * PROCESAMOS LOS REGISTROS CON STATUS A
     * 
     */
    if ($value['status'] == 'A') { // Si EL STATUS ES A (ANLUACION)

        $error = true; // Marcamos el error como falso
        $arrayFechas = arrayFechas($value['fecha_desdeStr'], $value['fecha_hastaStr']); // Obtenemos el array de fechas

        if ($error) { // Si hay error CHEQUEAMOS PERIODO CERRADO
            foreach ($arrayFechas as $fech) { // Recorremos el array de fechas
                $perCierreFech = perCierreFech($fech, $value['legajo'], $link);

                if ($perCierreFech) {
                    // Si la fecha de cierre es mayor a la fecha de la novedad  marcamos el error
                    iniPendienteLog($value["id_solicitud"], 'A  = Anulacion'); // Iniciamos el log de la novedad
                    $ini = microtime(true); // Iniciamos el contador de tiempo
                    $textError = "Error -> El legajo $value[legajo] tiene fecha de cierre en el periodo \"$value[fecha_desde] al $value[fecha_hasta]\"";

                    fileLogs($textError, __DIR__ . "/logs/errores/" . date('Ymd') . "_error.log", '');
                    fileLogs($textError, __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", '');

                    /** Marcamos Con Status (N) en API WF*/
                    $textError = "Error al anular la solicitud $value[id_solicitud]. Periodo cerrado.";
                    $jsonData = json_encode(array("legajo" => "$value[legajo]", "fecha" => "$dataApi[fecha_desdeStr]", "novedad" => "$value[CodNov]", "status" => "N", "id_out" => "$value[id_out]", "motivo" => urlencode($textError))); // Creamos el array con los datos de la novedad para enviar a la API
                    /**  */
                    setErrorApi($jsonData, $value['id_solicitud'], $dataJson['api']['url'], $auth, $proxy);
                    /** Marcamos Con Status (E) en API WF*/
                    $jsonData = json_encode(array("status" => "E", "id_out" => "$value[id_out]")); // Creamos el objeto json para enviarlo al Api
                    setExportadoApi($jsonData, $value['id_solicitud'], $dataJson['api']['url'], $auth, $proxy);
                    /**  */
                    finPendienteLog($ini, $value["id_solicitud"], 'A  = Anulacion'); // Guardamos el texto de Fin ID Registro WF en el archivo de log
                    $error = true; // Marcamos el error como verdadero
                    break; // Salimos del ciclo
                } else {
                    $error = false; // Marcamos el error como falso
                }
            }
        }
        if (!$error) {

            // Iniciamos el contador de tiempo
            $ini = microtime(true);
            // Iniciamos el log de la novedad
            iniPendienteLog($value["id_solicitud"], 'A  = Anulacion');

            // Eliminamos la novedad del periodo
            if ($value['ONov'] === 1) {
                // eliminamos de la tabla FICHAS2
                $eliminarNovedadPeriodo = eliminarOtraNovedadPeriodo($value['legajo'], $value['fecha_desdeStr'], $value['fecha_hastaStr'], $value['CodNov'], $link);
            } else {
                // eliminamos de la tabla FICHAS3
                $eliminarNovedadPeriodo = eliminarNovedadPeriodo($value['legajo'], $value['fecha_desdeStr'], $value['fecha_hastaStr'], $value['CodNov'], $link);
            }

            // Si se elimino la novedad
            if ($eliminarNovedadPeriodo) {

                $textNov = $value['ONov'] === 1 ? 'Otras Novedad' : 'Novedad'; // Definimos el tipo de novedad
                // Guardamos el texto de la novedad en el archivo de log
                fileLogs("$textNov \"($value[CodNov])\" $value[NovDesc]. Fecha: \"$value[fecha_desde] a $value[fecha_hasta]\" eliminada en CH", __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log", '');

                // Audito la baja de novedad en CH
                audito_ch("B", 'Baja ' . $textNov . ' (' . $value['CodNov'] . ') Legajo ' . $value['legajo'] . '. Fecha: ' . $value['fecha_desde'] . ' a ' . $value['fecha_hasta'], $link); // Audito la baja de novedad en CH

                /** 
                 * PROCESAMOS ELIMINACION DE FICHAS3 
                 */

                // Si la fecha desde de la novedad es menor o igual a la fecha actual
                if ($value['fecha_desdeStr'] <= date('Ymd')) {

                    // Si la fecha hasta de la novedad es mayor al dia actual, definimos la fecha hasta como la fecha actual. Ya que no se puede procesar dia a futuro en CH.
                    $fechaHastaProc = ($value['fecha_hastaStr'] > date('Ymd')) ? date('d/m/Y') : $value['fecha_hasta'];

                    // Iniciamos el contador de tiempo
                    $iniProc = microtime(true);

                    $procesarNovedad = ''; // Inicializamos la variable procesarNovedad
                    // Si la novedad se ingreso correctamente y no es otra novedad la procesamos
                    if ($value['ONov'] !== 1) {
                        $procesarNovedad = procesarNovedad($value['legajo'], $value['fecha_desde'], $fechaHastaProc, $urlWebService);
                    }
                    // Bandera para saber si la novedad se termino de procesar
                    $terminadoProc = false;

                    // Tiempo de finalizacion de proceso de la novedad
                    $finProc = microtime(true);
                    // Duracion de de proceso de la novedad
                    $durProc = round($finProc - $iniProc, 2);

                    if ($procesarNovedad == 'Terminado') {
                        // Se cambia la bandera
                        $terminadoProc = true;
                        // Texto de la novedad ingresada para gardar en el log
                        $textNov = 'Periodo "' . $value['fecha_desde'] . ' a ' . $fechaHastaProc . '" procesado. Legajo "(' . $value['legajo'] . ') ' . trim($value['ApNo']) . '" Dur: ' . $durProc;
                        // Ruta del archivo de log
                        $pathLog = __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log";
                        // Guardamos el texto de la novedad en el archivo de log
                        fileLogs($textNov, $pathLog, '');

                        // Audito la novedad en CH
                        audito_ch("P", "Proceso de Datos Legajo $value[legajo]. Desde: $value[fecha_desde] a $fechaHastaProc", $link);
                    } else {
                        $terminadoProc = false; // Se cambia la bandera
                        $textFechas = "$value[fecha_desde] a $value[fecha_hasta]"; // Definimos las fechas
                        $textNov = 'Fecha: "' . $textFechas . '". Procesada  "NO". Legajo "(' . $value['legajo'] . ') ' . trim($value['ApNo']) . '. " Dur: ' . $durProc; // Texto de la novedad ingresada para gardar en el log
                        $pathLog = __DIR__ . "/logs/novedades/" . date('Ymd') . "_error.log"; // Ruta del archivo de log
                        fileLogs($textNov, $pathLog, ''); // Guardamos el texto de la novedad en el archivo de log
                    }
                }
                /** 
                 * FIN DEL PROCESAR ELIMINACION DE NOVEDADES
                 * */
                // Creamos el objeto json para enviarlo al Api
                $jsonData = json_encode(["status" => "E", "id_out" => "$value[id_out]"]);
                // Marcamos con status E en la API WF
                setExportadoApi($jsonData, $value['id_solicitud'], $dataJson['api']['url'], $auth, $proxy);
                // Guardamos el texto de Fin ID Registro WF en el archivo de log
                finPendienteLog($ini, $value["id_solicitud"], 'A  = Anulacion');
            }
        }
    } // STATUS ES A (ANLUACION)
    /** 
     * 
     * FIN PROCESO DE REGISTROS CON STATUS A
     * 
     */
}

$tiempo_fin = microtime(true);
$duracion = round($tiempo_fin - $tiempo_ini, 2);
$textFin = "FIN DE PROCESO DE SOLICITUDES. TOTAL: ($totalData).  Dur: \"$duracion Segundos\"";
// Ruta del archivo de log
$pathLog = __DIR__ . "/logs/novedades/" . date('Ymd') . "_novedad.log";
// Guardamos el texto de Fin de Proceso de Novedades en el archivo de log
fileLogs($textFin, $pathLog, 'novOk');
sqlsrv_close($link); // Cierro la conexión MSQL

/**  */
exit;
