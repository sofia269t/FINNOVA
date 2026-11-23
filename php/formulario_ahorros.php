<?php

// Verificar que se recibieron los datos por POST

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Capturar los datos
    $idAhorro = trim($_POST["idAhorro"] ?? "");
    $ahorFecha = trim($_POST["ahorFecha"] ?? "");
    $ahorMonto = trim($_POST["ahorMonto"] ?? "");

    // Validar campos vacíos
    if (
        $ahorMonto == "" ||
        $ahorFecha == ""
    ) {

        echo "Error: Todos los campos son obligatorios.";

    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        echo "Error: El correo no es válido.";

    } else {

        echo "<h1>Ahorro registrado correctamente</h1>";

        echo "ID: " . htmlspecialchars($idAhorro) . "<br>";
        echo "Fecha: " . htmlspecialchars($ahorFecha) . "<br>";
        echo "Monto: " . htmlspecialchars($ahorMonto) . "<br>";

    }

} else {

    echo "Acceso no permitido.";

}

?>
