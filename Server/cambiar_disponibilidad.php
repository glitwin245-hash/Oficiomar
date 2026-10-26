<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'profesional') {
    http_response_code(403);
    echo 'unauthorized';
    exit;
}

include 'conexion.php';

$usuario_id = (int)$_SESSION['id'];
$disponible = isset($_POST['disponible']) && ((int)$_POST['disponible'] === 1) ? 1 : 0;

$stmt = mysqli_prepare($conexion, "UPDATE profesionales SET disponible = ? WHERE usuario_id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $disponible, $usuario_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

echo 'ok';
?>
