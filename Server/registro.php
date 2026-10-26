<?php
include 'conexion.php';

$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$contrasena = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';
$rol = isset($_POST['rol']) ? trim($_POST['rol']) : 'turista';

if (!in_array($rol, ['turista', 'profesional'])) {
    $rol = 'turista';
}

// Validación básica
if (empty($nombre) || empty($email) || empty($contrasena) || strlen($contrasena) < 6) {
    header('Location: registro.html?error=1');
    exit;
}

// Verificar si el email ya existe con Prepared Statement
$stmt_check = mysqli_prepare($conexion, "SELECT id FROM usuarios WHERE email = ? LIMIT 1");
if (!$stmt_check) {
    header('Location: registro.html?error=1');
    exit;
}

mysqli_stmt_bind_param($stmt_check, "s", $email);
mysqli_stmt_execute($stmt_check);
mysqli_stmt_store_result($stmt_check);

if (mysqli_stmt_num_rows($stmt_check) > 0) {
    mysqli_stmt_close($stmt_check);
    header('Location: registro.html?error=1');
    exit;
}
mysqli_stmt_close($stmt_check);

// Hashear la contraseña de forma segura
$hash_contrasena = password_hash($contrasena, PASSWORD_DEFAULT);

// Insertar nuevo usuario
$stmt_ins = mysqli_prepare($conexion, "INSERT INTO usuarios (nombre, email, contrasena, rol, activo, fecha_registro) VALUES (?, ?, ?, ?, 1, NOW())");
if (!$stmt_ins) {
    header('Location: registro.html?error=1');
    exit;
}

mysqli_stmt_bind_param($stmt_ins, "ssss", $nombre, $email, $hash_contrasena, $rol);
$exito = mysqli_stmt_execute($stmt_ins);
$nuevo_id = mysqli_insert_id($conexion);
mysqli_stmt_close($stmt_ins);

if ($exito) {
    // Si es profesional, inicializar su registro en profesionales
    if ($rol === 'profesional' && $nuevo_id > 0) {
        $stmt_prof = mysqli_prepare($conexion, "INSERT INTO profesionales (usuario_id, disponible) VALUES (?, 1)");
        if ($stmt_prof) {
            mysqli_stmt_bind_param($stmt_prof, "i", $nuevo_id);
            mysqli_stmt_execute($stmt_prof);
            mysqli_stmt_close($stmt_prof);
        }
    }
    header('Location: index.php?registro=ok');
    exit;
} else {
    header('Location: registro.html?error=1');
    exit;
}
?>
