<?php
require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Capturar información del formulario (Cambiado a "apellido" para coincidir con el HTML)
    $nombres = trim($_POST["user-name"]);
    $apellidos = trim($_POST["user-apellido"]); 
    $telefono = trim($_POST["user-number"]);
    $correo = trim($_POST["user-email"]);
    $contraseña = trim($_POST["password"]);


    // Validar campos obligatorios (Se corrigió a '==' para comparar)
    if ($nombres == "" || $apellidos == "" || $telefono == "" || $correo == "" || $contraseña == "") {
        echo "Todos los campos son obligatorios.";
        
    } else {

        // Consulta SQL para insertar
        $sql = "INSERT INTO usuario 
        (usuNombre, usuApellido, usuTelefono, usuCorreo, usuContraseña)
        VALUES (?, ?, ?, ?, ?)";

        // Preparar consulta
        $stmt = $conexion->prepare($sql);

        // Asociar los 5 parámetros (5 's' porque son 5 variables)
        $stmt->bind_param(
            "ssiss",
            $nombres,
            $apellidos,
            $telefono,
            $correo,
            $contraseña
        );

        // Ejecutar consulta
        if ($stmt->execute()) {
    echo "<script>
        alert('¡Usuario registrado exitosamente!');
        window.location.href = '../login.html'; // Cambia esto por la ruta de tu archivo destino
        </script>";
    exit();
    }   else {
    echo "<script>
        alert('Error al registrar el usuario: " . $stmt->error . "');
        window.history.back(); // Lo devuelve al formulario si falla
    </script>";
    }

        // Cerrar declaración
        $stmt->close();
    }
}
?>