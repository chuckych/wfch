# WF CH

INTEGRACION DE WORKFLOW DE NOVEDADES Y CONTROL HORARIO

### INSTALACION

Se puede clonar el repositorio desde $git clone https://github.com/chuckych/wfch/ $

### COMO SE USA

Ejecutar el index de la carpeta raiz en el navegador.

* Inicialmente el script verifica que exista el archivo config.json.
* Si no existe lo crea.
* Luego será redirigido a la carpeta config/
* Dentro de config/ le pedira los datos de configuración necesarios para poder funcionar mediante un formulario html.

Los datos a solicitar son los siguientes. 

#### Datos API Workflow

1. Url
2. Usuario
3. Clave

#### Conexión MSSQL

1. Servidor
2. Base de datos
3. Usuario
4. Clave

#### Conexión WebService CH

* Url de webservice: ej.: http://192.168.1.200:6400/RRHHWebService/

#### Internet Proxy

* Dirección IP
* Puerto
* Estado (switch de activo/inactivo)

#### Logs de Conexion MSSQL

* Exitosa (switch de activo/inactivo)
* Fallida (switch de activo/inactivo)

#### Logs de Novedades

* Ingresadas (switch de activo/inactivo)
* Fallidas (switch de activo/inactivo)

#### Borrar Logs

* Si (switch de activo/inactivo)
* Dias. (Cantidad de días mayor a borrar logs)
