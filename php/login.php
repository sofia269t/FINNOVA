<?php

require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $correo = trim($_POST["user-email"] ?? "");
    $contraseña = trim($_POST["password"] ?? "");

    if ($correo === "") {
        die("Debe ingresar un correo.");
    }

    // Consulta preparada
    $sql = "SELECT * FROM usuario
            WHERE usuCorreo = ?";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param("s", $correo);

    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($fila = $resultado->fetch_assoc()) {
        if (password_verify($contraseña, $fila["usuContraseña"])) {
            session_start();
            header("Location: ../pag.principal.html");
        } else {
            echo "No se encontró el usuario por favor registre su cuenta.";
        }
    } else {
        echo "No se encontró el usuario por favor registre su cuenta.";
    }

    $stmt->close();
}

$conexion->close();
?>
