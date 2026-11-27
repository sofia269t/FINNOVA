<?php
// Carga la sesión para identificar al usuario que envía la solicitud.
require_once __DIR__ . "/sesion.php";

// Solo un usuario autenticado puede intentar eliminar un ingreso.
if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login.php");
    exit;
}

// La eliminación modifica datos; por eso solo se permite mediante POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Allow: POST");
    exit("Esta operación solo acepta solicitudes POST.");
}

// Compara el token enviado con el guardado en la sesión para validar la solicitud.
$tokenEnviado = $_POST["csrf_token"] ?? "";
$tokenSesion = $_SESSION["csrf_token"] ?? null;
if (!is_string($tokenEnviado) || !is_string($tokenSesion)
    || !hash_equals($tokenSesion, $tokenEnviado)) {
    http_response_code(403);
    exit("La solicitud expiró o no es válida. Vuelve a la página de ingresos.");
}

// Lee y valida el ID: debe ser un número entero positivo.
$idEntrada = $_POST["idIngreso"] ?? "";
$idIngreso = is_string($idEntrada)
    ? filter_var($idEntrada, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])
    : false;

if ($idIngreso === false) {
    http_response_code(400);
    exit("El identificador del ingreso no es válido.");
}

// Obtiene el ID de la sesión y abre la conexión a la base de datos.
$usuarioId = (int) $_SESSION["usuario_id"];
require_once __DIR__ . "/conexion.php";

// La consulta exige que coincidan tanto el ID del ingreso como el dueño.
$stmt = $conexion->prepare(
    "DELETE FROM ingreso WHERE idIngreso = ? AND ingUsuario = ?"
);

// Si la consulta no se pudo preparar, registra el detalle técnico y muestra un error.
if (!$stmt) {
    error_log("No se pudo preparar la eliminación del ingreso: " . $conexion->error);
    $conexion->close();
    http_response_code(500);
    exit("No fue posible eliminar el ingreso.");
}

// Envía ambos valores como enteros y ejecuta la eliminación parametrizada.
$stmt->bind_param("ii", $idIngreso, $usuarioId);
if (!$stmt->execute()) {
    error_log("No se pudo eliminar el ingreso: " . $stmt->error);
    $stmt->close();
    $conexion->close();
    http_response_code(500);
    exit("No fue posible eliminar el ingreso.");
}

// Guarda si se eliminó una fila y libera la consulta y la conexión.
$eliminado = $stmt->affected_rows === 1;
$stmt->close();
$conexion->close();

// Si no se borró ninguna fila, el ingreso no existe o no pertenece al usuario.
if (!$eliminado) {
    http_response_code(404);
    exit("No se encontró ese ingreso entre tus registros.");
}

// Después de borrar, vuelve a la lista con una redirección posterior al POST.
header("Location: ingresos.php", true, 303);
exit;
