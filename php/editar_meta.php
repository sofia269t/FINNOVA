<?php
require_once __DIR__ . "/sesion.php";
require_once __DIR__ . "/conexion.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login.php");
    exit;
}

$usuarioId = (int) $_SESSION["usuario_id"];
$usuarioNombre = htmlspecialchars($_SESSION["usuario_nombre"] ?? "Usuario", ENT_QUOTES, "UTF-8");
$idMeta = filter_input(INPUT_POST, "idMeta", FILTER_VALIDATE_INT);
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $idMeta = filter_input(INPUT_GET, "idMeta", FILTER_VALIDATE_INT);
}

if (!$idMeta || $idMeta < 1) {
    http_response_code(400);
    exit("La meta solicitada no es válida.");
}

function volverAlFormulario(int $idMeta, string $mensaje): void
{
    header("Location: editar_meta.php?idMeta=" . $idMeta . "&error=" . rawurlencode($mensaje));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim((string) ($_POST["metNombre"] ?? ""));
    $montoEnviado = (string) ($_POST["metMontoObjetivo"] ?? "");
    $fechaInicio = (string) ($_POST["metFechaInicio"] ?? "");
    $fechaFin = (string) ($_POST["metFechaFin"] ?? "");

    if ($nombre === "" || preg_match('/^.{1,100}$/us', $nombre) !== 1) {
        volverAlFormulario($idMeta, "El nombre es obligatorio y no debe superar 100 caracteres.");
    }
    if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D', $montoEnviado) || (float) $montoEnviado <= 0) {
        volverAlFormulario($idMeta, "Ingresa un monto objetivo válido mayor que cero.");
    }

    $validarFecha = static function (string $fecha): bool {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $fecha, $partes) !== 1) {
            return false;
        }
        return checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1]);
    };

    if (!$validarFecha($fechaInicio) || !$validarFecha($fechaFin)) {
        volverAlFormulario($idMeta, "Ingresa fechas válidas.");
    }
    if ($fechaFin <= $fechaInicio) {
        volverAlFormulario($idMeta, "La fecha límite debe ser posterior a la fecha de inicio.");
    }

    $monto = (float) $montoEnviado;
    $stmt = $conexion->prepare(
        "UPDATE meta
         SET metNombre = ?, metMontoObjetivo = ?, metFechaInicio = ?, metFechaFin = ?
         WHERE idMeta = ? AND metUsuario = ?"
    );
    if (!$stmt) {
        error_log("No fue posible preparar la edición de la meta: " . $conexion->error);
        volverAlFormulario($idMeta, "No fue posible guardar los cambios.");
    }

    $stmt->bind_param("sdssii", $nombre, $monto, $fechaInicio, $fechaFin, $idMeta, $usuarioId);
    if (!$stmt->execute()) {
        error_log("No fue posible editar la meta: " . $stmt->error);
        $stmt->close();
        volverAlFormulario($idMeta, "No fue posible guardar los cambios.");
    }

    $stmt->close();
    $conexion->close();
    $_SESSION["mensaje_meta"] = "La meta se actualizó correctamente.";
    header("Location: ../metas.php");
    exit;
}

$sql = "SELECT idMeta, metNombre, metMontoObjetivo, metFechaInicio, metFechaFin
        FROM meta
        WHERE idMeta = ? AND metUsuario = ?
        LIMIT 1";
$stmt = $conexion->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    exit("No fue posible cargar la meta.");
}
$stmt->bind_param("ii", $idMeta, $usuarioId);
if (!$stmt->execute()) {
    http_response_code(500);
    exit("No fue posible cargar la meta.");
}
$meta = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conexion->close();

if (!$meta) {
    http_response_code(404);
    exit("No se encontró la meta solicitada.");
}

$error = filter_input(INPUT_GET, "error", FILTER_UNSAFE_RAW);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar meta</title>
    <link rel="stylesheet" href="../Estilos/editar_meta.css">
</head>
<body>
    <header>
        <a href="../pag.principal.php"><img class="logo" src="../Estilos/img/logo_img.png" alt="Logo"></a>
        <a href="../pag.principal.php"><img class="logo-nombre" src="../Estilos/img/Logo.png" alt="FINNOVA"></a>
        <h1>Editar meta</h1>
        <div class="user-icon">
            <p><?= $usuarioNombre ?></p>
            <a href="../perfil.php"><img src="../Estilos/img/user-icon.png" alt="Perfil"></a>
        </div>
</header>

    <main>
        <div class="menu">
            <ul>
                <li><a href="ingresos.html">Ingresos</a></li>
                <li><a href="gastos.html" class="activo">Gastos</a></li>
                <li><a href="ahorros.php">Ahorros</a></li>
                <li><a href="metas.php">Metas</a></li>
                <li><a href="presupuesto.php">Presupuesto</a></li>
                <li><a href="reportes/finanzas.php">Reportes</a></li>

                <li class="total">
                    <label for="total-restante">Total restante:</label>
                    <input type="text" id="total-restante"
                           name="total-restante" value="$0.00" readonly>
                </li>
            </ul>
        </div>

        <section class="metas editar-meta">
            <h2>Datos de la meta</h2>
            <?php if ($error): ?>
                <p class="error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>

            <form action="editar_meta.php" method="post">
                <input type="hidden" name="idMeta" value="<?= (int) $meta["idMeta"] ?>">

                <label for="metNombre">Nombre de la meta
                    <input type="text" id="metNombre" name="metNombre" maxlength="100"
                           value="<?= htmlspecialchars($meta["metNombre"], ENT_QUOTES, "UTF-8") ?>" required>
                </label>

                <label for="metMontoObjetivo">Monto objetivo
                    <input type="number" id="metMontoObjetivo" name="metMontoObjetivo"
                           min="0.01" max="9999999999.99" step="0.01"
                           value="<?= htmlspecialchars(number_format((float) $meta["metMontoObjetivo"], 2, ".", ""), ENT_QUOTES, "UTF-8") ?>" required>
                </label>

                <label for="metFechaInicio">Fecha de inicio
                    <input type="date" id="metFechaInicio" name="metFechaInicio"
                           value="<?= htmlspecialchars($meta["metFechaInicio"], ENT_QUOTES, "UTF-8") ?>" required>
                </label>

                <label for="metFechaFin">Fecha límite
                    <input type="date" id="metFechaFin" name="metFechaFin"
                           value="<?= htmlspecialchars($meta["metFechaFin"], ENT_QUOTES, "UTF-8") ?>" required>
                </label>

                <button type="submit">Guardar cambios</button>
            </form>
            <a class="btn-graficas" href="../metas.php">Cancelar</a>
        </section>
    </main>

    <script>
        const fechaInicio = document.getElementById("metFechaInicio");
        const fechaFin = document.getElementById("metFechaFin");
        fechaFin.min = fechaInicio.value;

        fechaInicio.addEventListener("change", () => {
            const siguiente = new Date(`${fechaInicio.value}T00:00:00`);
            siguiente.setDate(siguiente.getDate() + 1);
            const minimo = `${siguiente.getFullYear()}-${String(siguiente.getMonth() + 1).padStart(2, "0")}-${String(siguiente.getDate()).padStart(2, "0")}`;
            fechaFin.min = minimo;
            if (!fechaFin.value || fechaFin.value <= fechaInicio.value) {
                fechaFin.value = minimo;
            }
        });
    </script>
</body>
</html>
