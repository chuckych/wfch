<!DOCTYPE html>
<html lang="es">

<head>
    <title>Listar Archivos</title>
    <meta charset="UTF-8">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="..\asset\bootstrap.min.css"> <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="..\asset\style.css?v=<?= time() ?>"> <!-- Estilo CSS -->
    <style>
        .btn-link:hover {
            color: #fff !important;
            background-color: #333 !important;
        }
    </style>
</head>

<body class="bg-secondary">
    <div class="container bg-light my-3 shadow-lg p-3">
        <p class="h6">Listado de archivos y carpetas en Logs</p>
        <?php
        function fechaFormat($fecha, $format) // Funcion para formatear fechas
        {
            $fecha = (empty($fecha)) ? '0000-00-00' : $fecha; // si no hay fecha, se asigna a 00/00/00
            $date        = date_create($fecha); // crear objeto de fecha
            $FormatFecha = date_format($date, $format); // formatear fecha
            return $FormatFecha; // retornar fecha formateada
        }
        //Creamos Nuestra Función
        function lista_archivos($carpeta)
        { //La función recibira como parametro un carpeta
            if (is_dir($carpeta)) { //Comprovamos que sea un carpeta Valido
                if ($dir = opendir($carpeta)) { //Abrimos el carpeta
                    echo '<ul class="list-group my-2 py-2">';
                    while (($archivo = readdir($dir)) !== false) { //Comenzamos a leer archivo por archivo
                        if ($archivo != '.' && $archivo != '..' && $archivo != 'index.php') {
                            $nuevaRuta = $carpeta . $archivo . '/';
                            $href = $carpeta . $archivo;
                            echo '<li class="list-group-item">'; //Abrimos un elemento de lista 
                            if (is_dir($nuevaRuta)) { //Si la ruta que creamos es un carpeta entonces:
                                echo '<p class="bg-light h6 p-2 mt-2">' . $nuevaRuta . '</p>'; //Imprimimos la ruta completa resaltandola en negrita
                                lista_archivos($nuevaRuta); //Volvemos a llamar a este metodo para que explore ese carpeta.
                            } else { //si no es un carpeta:
                                if ($archivo != 'index.php') {
                                    echo '<a href="' . $href . '" class="btn btn-link text-decoration-none p-1 text-secondary" target="_blank">' . $archivo . '</a>'; //simplemente imprimimos el nombre del archivo actual
                                }
                            }
                            '</li>'; //Cerramos el item actual y se inicia la llamada al siguiente archivo
                        }
                    } //finaliza 
                    echo '</ul>'; //Se cierra la lista
                    closedir($dir); //Se cierra el archivo
                }
            } else { //Finaliza el If de la linea 12, si no es un carpeta valido, muestra el siguiente mensaje
                echo '<p class="alert alert-danger">No Existe la carpeta</p>'; //Imprimimos la ruta completa resaltandola en negrita
            }
        } //Fin de la Función	 
        //Llamamos a la función
        lista_archivos('./');
        ?>
    </div>
</body>

</html>