<?php
/** LAST UPDATE DE NOVEDADES */
$fechaHoraNovedades = getDataJson(__DIR__ . '\logs\data\fechaHoraNovedades.json'); // Obtenemos la max fecha y hora de las novedades
if ($fechaHoraNovedades == false): // Si no hay fecha y hora o no existe el archivo
    $log = fopen(__DIR__ . "/logs/data/fechaHoraNovedades.json", 'w'); // abrimos el archivo y sobreescribimos
    fwrite($log, json_encode(lastUpdateTabla($link, 'NOVEDAD'), $optionEncode)); // escribimos en el archivo
    fileLogs("Se creo el archivo \"fechaHoraNovedades.json\"", __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log", '');
endif;
$fechaHoraNovedades = getDataJson(__DIR__ . '\logs\data\fechaHoraNovedades.json'); // Obtenemos la max fecha y hora de las novedades
/** FIN LAST UPDATE DE NOVEDADES */

/** OBTENEMOS EL OBJETO DE NOVEDADES */
$objetoNovedadesCH = getDataJson(__DIR__ . '\logs\data\novedades.json'); // Obtenemos el objeto de las novedades
if ($objetoNovedadesCH == false): // Si no existe el archivo
    $log = fopen(__DIR__ . "/logs/data/novedades.json", 'w'); // abrimos el archivo y sobreescribimos
    fwrite($log, json_encode(dataNovedades($link), $optionEncode)); // escribimos en el archivo
    fileLogs("Se creo el archivo \"novedades.json\"", __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log", '');
endif;
/** FIN OBJETO DE NOVEDADES */

/** VERIFICAMOS LAST UPDATE DE NOVEDADES */
$FechaNovDB = (lastUpdateTabla($link, 'NOVEDAD')['FechaHora']); // Fecha y hora de las novedades en la base de datos
$FechaNovLoc = ($fechaHoraNovedades['FechaHora']); // Fecha y hora de las novedades en el archivo local

$Date_DB_nov = new DateTime($FechaNovDB);
$Date_Local_nov = new DateTime($FechaNovLoc);

if ($Date_DB_nov->format('Ymdhis') > $Date_Local_nov->format('Ymdhis')):  // Si la fecha y hora de las novedades en la base de datos es mayor que la del archivo local
    /** CREAMOS OBJETO DE LA TABLA NOVEDADES DE CH */
    $objetoNovedadesCH = dataNovedades($link); // obtenemos el Objeto de la tabla novedades de CH

    file_put_contents(__DIR__ . "/logs/data/fechaHoraNovedades.json", json_encode(lastUpdateTabla($link, 'NOVEDAD'), $optionEncode), LOCK_EX);  // actualizamos fecha hora de novedades
    fileLogs("Se actualizo el archivo \"fechaHoraNovedades.json\"", __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log", '');

    file_put_contents(__DIR__ . "/logs/data/novedades.json", json_encode($objetoNovedadesCH, $optionEncode), LOCK_EX); // Actualizamos el archivo local con el objeto de las novedades
    fileLogs("Se actualizo el archivo \"novedades.json\"", __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log", '');

endif;
/** FIN VERIFICAMOS LAST UPDATE DE NOVEDADES */
$objetoNovedadesCH = getDataJson(__DIR__ . '\logs\data\novedades.json'); // Obtenemos el objeto de las novedades del archivo local