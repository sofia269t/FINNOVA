<?php

date_default_timezone_set("America/Bogota");

require_once __DIR__ . "/php/sesion.php";
require_once __DIR__ . "/php/conexion.php";

// Verificar que se recibieron los datos por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Capturar los datos y asignar la fecha actual
    $idAhorro = trim($_POST["idAhorro"] ?? "");
    $ahorFecha = date("Y-m-d"); // Fecha actual (ej. 2026-11-23)
    $ahorMonto = trim($_POST["ahorMonto"] ?? "");

    // Validar campos vacíos
    if ($ahorMonto == "") {
        echo "Error: El monto es obligatorio.";
    } elseif (!is_numeric($ahorMonto) || (float)$ahorMonto <= 0) {
        echo "Error: El monto debe ser un número mayor a cero.";
    } else {
        echo "<h1>Ahorro registrado correctamente</h1>";
        echo "ID: " . htmlspecialchars($idAhorro) . "<br>";
        echo "Fecha: " . htmlspecialchars($ahorFecha) . "<br>";
        echo "Monto: " . htmlspecialchars($ahorMonto) . "<br>";
    }

} else {
    echo "Acceso no permitido.";
}
