<?php
$servidor = "localhost";
$usuario = "root";
$password = "";
$baseDatos = "BD_v1.2.sql";

//crear conexion
$conexion = new mysqli(
    $servidor,
    $usuario,
    $password,
    $baseDatos
);
//Verificar conexion
if($conexion->connect_error){
    die("Error de conexion: ". $conexion -> connect_error);
}
$conexion->set_charset("utf8mb4"); #identifica el lenguaje

echo "Conexion exitosa a MySQL";

?>