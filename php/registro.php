<?php
require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Capturar información del formulario (Cambiado a "apellido" para coincidir con el HTML)
    $nombres = trim($_POST["user-name"]);
    $apellidos = trim($_POST["user-apellido"]); 
    $telefono = trim($_POST["user-number"]);
    $correo = trim($_POST["user-email"]);


    // Validar campos obligatorios (Se corrigió a '==' para comparar)
    if ($nombres == "" || $apellidos == "" || ) {
        
        echo "El tipo de documento, número de documento, nombres y apellidos son obligatorios.";
        
    } else {

        // Consulta SQL para insertar
        $sql = "INSERT INTO estudiantes
        (tipo_documento, numero_documento, nombres, apellidos,
         telefono, correo, direccion, fecha_nacimiento)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        // Preparar consulta
        $stmt = $conexion->prepare($sql);

        // Asociar los 8 parámetros (8 's' porque son 8 variables)
        $stmt->bind_param(
            "ssssssss",
            $tipo_documento,
            $numero_documento,
            $nombres,
            $apellidos,
            $telefono,
            $correo,
            $direccion,
            $fecha_nacimiento
        );

        // Ejecutar consulta
        if ($stmt->execute()) {

            echo "Estudiante registrado correctamente.";

        } else {

            echo "Error al registrar: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>