<?php
/** LAST UPDATE DE OTRASNOV */
$fechaHoraNovedadesOtras = getDataJson(__DIR__ . '\logs\data\fechaHoraNovedadesOtras.json'); // Obtenemos la max fecha y hora de las otras novedades
if ($fechaHoraNovedadesOtras == false): // Si no hay fecha y hora o no existe el archivo
    $log = fopen(__DIR__ . "/logs/data/fechaHoraNovedadesOtras.json", 'w'); // abrimos el archivo y sobreescribimos
    fwrite($log, json_encode(lastUpdateTabla($link, 'OTRASNOV'), $optionEncode)); // escribimos en el archivo
    fileLogs("Se creo el archivo \"fechaHoraNovedadesOtras.json\"", __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log", '');
endif;
$fechaHoraNovedadesOtras = getDataJson(__DIR__ . '\logs\data\fechaHoraNovedadesOtras.json'); // Obtenemos la max fecha y hora de las otras novedades
/** FIN LAST UPDATE DE OTRASNOV */

/** OBTENEMOS EL OBJETO DE OTRASNOV */
$objetoNovedadesOtrasCH = getDataJson(__DIR__ . '\logs\data\otrasnov.json'); // Obtenemos el objeto de las otras novedades
if ($objetoNovedadesOtrasCH == false): // Si no existe el archivo
    $log = fopen(__DIR__ . "/logs/data/otrasnov.json", 'w'); // abrimos el archivo y sobreescribimos
    fwrite($log, json_encode(dataNovedadesOtras($link), $optionEncode)); // escribimos en el archivo
    fileLogs("Se creo el archivo \"otrasnov.json\"", __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log", '');
endif;
/** FIN OBJETO DE OTRASNOV */

/** VERIFICAMOS LAST UPDATE DE OTRASNOV */
$FechaNovDB = (lastUpdateTabla($link, 'OTRASNOV')['FechaHora']); // Fecha y hora de las otras novedades en la base de datos
$FechaNovLoc = ($fechaHoraNovedadesOtras['FechaHora']); // Fecha y hora de las otras novedades en el archivo local

$Date_DB_nov = new DateTime($FechaNovDB);
$Date_Local_nov = new DateTime($FechaNovLoc);

if ($Date_DB_nov->format('Ymdhis') > $Date_Local_nov->format('Ymdhis')):  // Si la fecha y hora de las otras novedades en la base de datos es mayor que la del archivo local
    /** CREAMOS OBJETO DE LA TABLA OTRASNOV DE CH */
    $objetoNovedadesOtrasCH = dataNovedadesOtras($link); // obtenemos el Objeto de la tabla otras novedades de CH

    file_put_contents(__DIR__ . "/logs/data/fechaHoraNovedadesOtras.json", json_encode(lastUpdateTabla($link, 'OTRASNOV'), $optionEncode), LOCK_EX);  // actualizamos fecha hora de otras novedades
    fileLogs("Se actualizo el archivo \"fechaHoraNovedadesOtras.json\"", __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log", '');

    file_put_contents(__DIR__ . "/logs/data/otrasnov.json", json_encode($objetoNovedadesOtrasCH, $optionEncode), LOCK_EX); // Actualizamos el archivo local con el objeto de las otras otras novedades
    fileLogs("Se actualizo el archivo \"otrasnov.json\"", __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log", '');

endif;
/** FIN VERIFICAMOS LAST UPDATE DE OTRASNOV */
$objetoNovedadesOtrasCH = getDataJson(__DIR__ . '\logs\data\otrasnov.json'); // Obtenemos el objeto de las otras novedades del archivo local