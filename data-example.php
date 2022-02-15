; <?php exit; ?> <-- ¡No eliminar esta línea! --> 
; 
; ###########################################################
; ######## ARCHIVO DE CONFIGURACIÓN DEL SCRIPT WF CH ########
; ###########################################################
; 
;  - crear o renombrar este archivo a data.php
[mssql]
srv = "."
db = "Recursos_HRC60"
user = "sa"
pass = "sa2019"
[logConexion]
success =
error = "1"
[api]
url = "https://hr-process.com/hrctest/api/novedades/"
user = "admin"
pass = "admin"
[webService]
url = "http://localhost:6400/RRHHWebService/"
[logNovedades]
success = "1"
error = "1"
[proxy]
ip =
port =
enabled =
[borrarLogs]
estado = "1"
dias = "1"
; 
; 
;   _  _   ___      ___    ___    _  _   ___   _   _   _      _____   ___   _  _    ___  
;  | || | | _ \    / __|  / _ \  | \| | / __| | | | | | |    |_   _| |_ _| | \| |  / __| 
;  | __ | |   /   | (__  | (_) | | .` | \__ \ | |_| | | |__    | |    | |  | .` | | (_ | 
;  |_||_| |_|_\    \___|  \___/  |_|\_| |___/  \___/  |____|   |_|   |___| |_|\_|  \___| 
; 
; 
; ## CONFIGURACION DE CONEXION A MS SQLSERVER ##
; [mssql] 
;  srv  = string servidor mssql o ip. Si es Local puede ir un punto
;  db   = string con el nombre de la base de datos
;  user = string con el usuario de la base de datos
;  pass = string con el password de la base de datos
; < --- >
; 
; ## ACTIVAR LOGS DE CONEXION A LA DB MSSQL ##
; [logConexion]
;  success  = ingresar un 1 = activo. Dejar vacio si esta inactivo
;  error    = ingresar un 1 = activo. Dejar vacio si esta inactivo.
; < --- >
; 
; ## CONFIGURACION DE CONEXION A LA API DE WORKFLOW DE NOVEDADES (WF) ##
; [api]
;  url  = string con la ruta de conexion a la API workflow. Ejemplo "https://hr-process.com/hrctest/api/novedades/" 
;  user = string con el usuario de autenticación a la API. Ejemplo "Admin"
;  pass = string con el password de autenticación a la API. Ejemplo "Admin"
; < --- >
; 
; ## CONFIGURACION DE CONEXION AL WEBSERVICE DE CONTROL HORARIO (CH) ##
; [webService]
;  url = string con la ruta de conexion al webservice de Control Horario. Ejemplo "http://192.168.1.202:6400/RRHHWebService/" 
; < --- >
; 
; ## ACTIVAR LOGS DE NOVEDADES INGRESADAS CORRECTAMENTE(success) E INCORRECTAMENTE(error) ##
; [logNovedades]
;  success = ingresar un 1 = activo. Dejar vacio si esta inactivo
;  error   = ingresar un 1 = activo. Dejar vacio si esta inactivo
; < --- >
; 
; ## CONFIGURACION DE PROXY. SI LA CONEXION A INTERNET PASAS POR UN PROXY ##
; [proxy]
;  ip      = direccion de ip del proxy
;  puerto  = numero de puerto del proxy
;  enabled = ingresar un 1 = activo. Dejar vacio si esta inactivo
; < --- >
; 
; ## ACTIVAR EL BORRADO DE LOGS ##
; [borrarLogs]
;  estado = ingresar un 1 = activo. Dejar vacio si esta inactivo. "Al estar inactivo nunca se eliminarán los logs que genera el script"
;  dias   = numero con cantidad de días a borrar los logs que genera el script. "Valor minimo 1".
; < --- >
; 
; 
; ##########################################
; ### --> autor  : Norberto CH           ###
; ### --> para   : HR Consulting         ###
; ### --> e-mail : nch@outlook.com.ar    ###
; ##########################################
