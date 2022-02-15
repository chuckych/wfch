<?php
header('Content-Type: application/json'); // Tipo de contenido JSON
date_default_timezone_set('America/Argentina/Buenos_Aires');
$_POST['tk'] = $_POST['tk'] ?? ''; // Token
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['tk'] === date('Ymd')) {
  $pathConfigData = __DIR__ . '/data.php'; // Path to data.php
  $dataConfig = parse_ini_file($pathConfigData, true); // Obtenemos los datos del data.php
  echo json_encode($dataConfig); // Enviamos los datos
  exit; 
}else {
  $error = array(
    'SUCCESS' => 'NO',
    'MESSAGE' => 'Error de Autenticacion'
  );
  echo json_encode($error, JSON_PRETTY_PRINT);
  exit; 
}