<?php


date_default_timezone_set("America/Bogota");

require_once __DIR__ . "/php/sesion.php";
require_once __DIR__ . "/php/conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $ahorFecha = date("Y-m-d");
    $ahorMonto = trim($_POST["ahorMonto"] ?? "");

    if ($ahorMonto == "") {

        die("Error: El monto es obligatorio.");

    } elseif (!is_numeric($ahorMonto) || (float)$ahorMonto <= 0) {

        die("Error: El monto debe ser mayor a cero.");

    } else {

        $sql = "INSERT INTO ahorros (ahorFecha, ahorMonto)
                VALUES (?, ?)";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            die("Error SQL: " . $conexion->error);
        }

        $stmt->bind_param(
            "sd",
            $ahorFecha,
            $ahorMonto
        );

        if ($stmt->execute()) {

            echo "<h1>Ahorro registrado correctamente</h1>";
            echo "ID generado: " . $conexion->insert_id . "<br>";
            echo "Fecha: " . htmlspecialchars($ahorFecha) . "<br>";
            echo "Monto: " . htmlspecialchars($ahorMonto) . "<br>";

        } else {

            echo "Error al guardar: " . $stmt->error;
        }

        $stmt->close();
    }

} else {

    echo "Acceso no permitido.";
}

?>

