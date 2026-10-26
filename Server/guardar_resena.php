<?php
session_start();
include 'conexion.php';

// Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION['id'])) {
    header('Location: index.html');
    exit;
}

$turista_id = (int)$_SESSION['id'];
$profesional_id = isset($_POST['profesional_id']) ? (int)$_POST['profesional_id'] : 0;
$calificacion = isset($_POST['calificacion']) ? (int)$_POST['calificacion'] : 0;
$comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';

// Validaciones
if ($profesional_id <= 0 || $calificacion < 1 || $calificacion > 5 || empty($comentario)) {
    header("Location: perfil.php?id=$profesional_id&error=1");
    exit;
}

// No permitir que un profesional se califique a sí mismo
if ($turista_id === $profesional_id) {
    header("Location: perfil.php?id=$profesional_id&error=auto_resena");
    exit;
}

// Insertar reseña con Prepared Statement
$stmt = mysqli_prepare($conexion, "INSERT INTO resenas (profesional_id, turista_id, calificacion, comentario, fecha) VALUES (?, ?, ?, ?, NOW())");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "iiis", $profesional_id, $turista_id, $calificacion, $comentario);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($ok) {
        header("Location: perfil.php?id=$profesional_id&resena=ok");
        exit;
    }
}

header("Location: perfil.php?id=$profesional_id&error=1");
exit;
?>
