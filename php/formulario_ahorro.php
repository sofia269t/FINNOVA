<?php

date_default_timezone_set("America/Bogota");

require_once __DIR__ . "/sesion.php";
require_once __DIR__ . "/conexion.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $usuarioId = (int) $_SESSION["usuario_id"];
    $ahorFecha = date("Y-m-d");
    $ahorMontoRaw = trim($_POST["cantidad"] ?? "");

    if ($ahorMontoRaw === "") {
        $_SESSION["mensaje_ahorro"] = "Error: El monto es obligatorio.";
        header("Location: ../ahorros.php");
        exit;
    }

    if (!is_numeric($ahorMontoRaw) || (float) $ahorMontoRaw <= 0) {
        $_SESSION["mensaje_ahorro"] = "Error: El monto debe ser mayor a cero.";
        header("Location: ../ahorros.php");
        exit;
    }

    $ahorMonto = (float) $ahorMontoRaw;

    $sql = "INSERT INTO ahorro (ahorFecha, ahorMonto, ahorUsuario)
            VALUES (?, ?, ?)";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        $_SESSION["mensaje_ahorro"] = "Error al preparar el registro: " . $conexion->error;
        header("Location: ../ahorros.php");
        exit;
    }

    $stmt->bind_param("sdi", $ahorFecha, $ahorMonto, $usuarioId);

    if ($stmt->execute()) {
        $_SESSION["mensaje_ahorro"] = "Ahorro registrado correctamente.";
    } else {
        $_SESSION["mensaje_ahorro"] = "Error al guardar el ahorro: " . $stmt->error;
    }

    $stmt->close();
    header("Location: ../ahorros.php");
    exit;
}

http_response_code(405);
exit("Acceso no permitido.");