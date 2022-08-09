<?php

/** LAST UPDATE DE PERSONAL */
$fechaHoraPersonal = getDataJson(__DIR__ . '\logs\data\fechaHoraPersonal.json'); // Obtenemos la max fecha y hora del personal

if ($fechaHoraPersonal == false) : // Si no hay fecha y hora o no existe el archivo    
    $log = fopen(__DIR__ . "/logs/data/fechaHoraPersonal.json", 'w'); // abrimos el archivo y sobreescribimos
    fwrite($log, json_encode(lastUpdateTabla($link, 'PERSONAL'), JSON_PRETTY_PRINT)); // escribimos en el archivo
    fileLogs("Se creo el archivo \"fechaHoraPersonal.json\"", __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log", '');
endif;
$fechaHoraPersonal = getDataJson(__DIR__ . '\logs\data\fechaHoraPersonal.json'); // Obtenemos la max fecha y hora del personal
/** FIN LAST UPDATE DE PERSONAL */
/** OBTENEMOS EL OBJETO DE PERSONAL */
$objetoLegajosCH = getDataJson(__DIR__ . '\logs\data\personal.json'); // Obtenemos el objeto del personal

if ($objetoLegajosCH == false) : // Si no existe el archivo
    $objetoLegajosCH = (dataLegajos($link)); // Creamos el objeto del personal
    $log = fopen(__DIR__ . "/logs/data/personal.json", 'w'); // abrimos el archivo y sobreescribimos
    fwrite($log, json_encode($objetoLegajosCH, JSON_PRETTY_PRINT)); // escribimos en el archivo
    fileLogs("Se creo el archivo \"personal.json\"", __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log", '');
endif;
/** FIN OBJETO DE PERSONAL */
/** VERIFICAMOS LAST UPDATE DE PERSONAL */
$FechaPerDB = (lastUpdateTabla($link, 'PERSONAL')['FechaHora']); // Fecha y hora del personal en la base de datos
$FechaPerLoc = ($fechaHoraPersonal['FechaHora']); // Fecha y hora del personal en el archivo local
$objetoLegajosCH = (dataLegajos($link));

if ($FechaPerDB > $FechaPerLoc) : // Si la fecha y hora del personal en la base de datos es mayor que la del archivo local
    /** CREAMOS OBJETO DE LA TABLA PERSONAL DE CH */
    $objetoLegajosCH = (dataLegajos($link)); // Obtengo los legajos de la tabla control horario para crear un objeto con el Legajo y ApNo
    fileLogs(json_encode(lastUpdateTabla($link, 'PERSONAL'), JSON_PRETTY_PRINT), __DIR__ . "/logs/data/fechaHoraPersonal.json", ''); // Actualizamos el archivo local con la fecha y hora del personal
    fileLogs("Se actualizo el archivo \"fechaHoraPersonal.json\"", __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log", '');
    $log = fopen(__DIR__ . "/logs/data/personal.json", 'w'); // abrimos el archivo y sobreescribimos
    fwrite($log, json_encode($objetoLegajosCH, JSON_PRETTY_PRINT)); // escribimos en el archivo    
    fileLogs("Se actualizo el archivo \"personal.json\"", __DIR__ . "/logs/info/" . date('Ymd') . "_informacion.log", '');
endif;
/** FIN VERIFICAMOS LAST UPDATE DE PERSONAL */
$objetoLegajosCH = getDataJson(__DIR__ . '\logs\data\personal.json'); // Obtenemos el objeto del personal del archivo local