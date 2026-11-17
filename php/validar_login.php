<?php
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/sesion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit;
}

$correo = trim($_POST["user-email"] ?? "");
$contraseña = trim($_POST["password"] ?? "");

if ($correo === "" || $contraseña === "") {
    $_SESSION["mensaje"] = "Debe ingresar un correo y una contraseña.";
    header("Location: login.php");
    exit;
}

$sql = "SELECT idUsuario, usuNombre, usuApellido, usuTelefono, usuCorreo, usuContraseña FROM usuario WHERE usuCorreo = ? LIMIT 1";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error en la consulta SQL: " . $conexion->error);
}

$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

if (!$usuario) {
    $_SESSION["mensaje"] = "No se encontró el usuario, por favor registre su cuenta.";
    header("Location: ../login.php");
    exit;
}

/* =========================================================
   CÓDIGO ORIGINAL CON ENCRIPTACIÓN (COMENTADO)
   =========================================================
   $passwordValida = password_verify($contraseña, $usuario["usuContraseña"]);

   if (!$passwordValida && hash_equals($usuario["usuContraseña"] ?? "", $contraseña)) {
       $passwordValida = true;
       $nuevoHash = password_hash($contraseña, PASSWORD_DEFAULT);
       $update = $conexion->prepare("UPDATE usuario SET usuContraseña = ? WHERE usuId = ?");
       if ($update) {
           $update->bind_param("si", $nuevoHash, $usuario["usuId"]);
           $update->execute();
           $update->close();
       }
   }
   ========================================================= */

// Validación SIN encriptación (comparación directa de texto plano)
$passwordValida = ($contraseña === $usuario["usuContraseña"]);

if (!$passwordValida) {
    $_SESSION["mensaje"] = "Contraseña incorrecta.";
    header("Location: ../login.php");
    exit;
}

session_regenerate_id(true);

$_SESSION["usuario_id"] = $usuario["idUsuario"];
$_SESSION["usuario_nombre"] = $usuario["usuNombre"];
$_SESSION["usuario_apellido"] = $usuario["usuApellido"];
$_SESSION["usuario_correo"] = $usuario["usuCorreo"];
$_SESSION["usuario_telefono"] = $usuario["usuTelefono"];

$stmt->close();
$conexion->close();

header("Location: ../pag.principal.php");
exit;
?>