<?php
require_once __DIR__ . "/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../registro.html");
    exit;
}

$nombres = trim($_POST["user-name"] ?? "");
$apellidos = trim($_POST["user-apellido"] ?? "");
$telefono = trim($_POST["user-number"] ?? "");
$correo = trim($_POST["user-email"] ?? "");
$contraseña = trim($_POST["password"] ?? "");

if ($nombres === "" || $apellidos === "" || $telefono === "" || $correo === "" || $contraseña === "") {
    echo "<script>alert('Todos los campos son obligatorios.'); window.history.back();</script>";
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Ingresa un correo válido.'); window.history.back();</script>";
    exit;
}

// Nota: Si tus contraseñas de prueba en la BD (como '123456') tienen menos de 8 caracteres, 
// puedes comentar esta validación también si te llega a dar problemas al registrar.
if (strlen($contraseña) < 8) {
    echo "<script>alert('La contraseña debe tener al menos 8 caracteres.'); window.history.back();</script>";
    exit;
}

$consultaExiste = "SELECT idUsuario FROM usuario WHERE usuCorreo = ? LIMIT 1";
$stmtExiste = $conexion->prepare($consultaExiste);
$stmtExiste->bind_param("s", $correo);
$stmtExiste->execute();
$resultado = $stmtExiste->get_result();

if ($resultado->num_rows > 0) {
    echo "<script>alert('Ya existe un usuario con ese correo.'); window.history.back();</script>";
    $stmtExiste->close();
    $conexion->close();
    exit;
}

$stmtExiste->close();

/* =========================================================
   CÓDIGO ORIGINAL CON CIFRADO (COMENTADO)
   =========================================================
   $hash = password_hash($contraseña, PASSWORD_DEFAULT);
   $sql = "INSERT INTO usuario (usuNombre, usuApellido, usuTelefono, usuCorreo, usuContraseña) VALUES (?, ?, ?, ?, ?)";
   $stmt = $conexion->prepare($sql);
   $stmt->bind_param("sssss", $nombres, $apellidos, $telefono, $correo, $hash);
   ========================================================= */

// Registro SIN cifrado (guarda la contraseña tal cual la escribe el usuario)
$sql = "INSERT INTO usuario (usuNombre, usuApellido, usuTelefono, usuCorreo, usuContraseña) VALUES (?, ?, ?, ?, ?)";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("sssss", $nombres, $apellidos, $telefono, $correo, $contraseña);

if ($stmt->execute()) {
    echo "<script>alert('¡Usuario registrado exitosamente!'); window.location.href = '../login.php';</script>";
} else {
    echo "<script>alert('Error al registrar el usuario: " . addslashes($stmt->error) . "'); window.history.back();</script>";
}

$stmt->close();
$conexion->close();
?>