<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['nombre']) || $_SESSION['rol'] != 'admin') {
    echo json_encode(['ok' => false, 'msg' => 'No autorizado']);
    exit;
}
include 'conexion.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

if (!isset($input['id']) || !isset($input['accion'])) {
    echo json_encode(['ok' => false, 'msg' => 'Datos incompletos']);
    exit;
}

$id = intval($input['id']);
$accion = $input['accion'] === 'activar' ? 'activar' : 'desactivar';
$nuevo = $accion === 'activar' ? 1 : 0;

$stmt = mysqli_prepare($conexion, "UPDATE usuarios SET activo = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'ii', $nuevo, $id);
mysqli_stmt_execute($stmt);

// recalcular estadísticas
$res = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM usuarios WHERE rol != 'admin'");
$total_usuarios = intval(mysqli_fetch_assoc($res)['total']);
$res = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'turista'");
$total_turistas = intval(mysqli_fetch_assoc($res)['total']);
$res = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'profesional'");
$total_profesionales = intval(mysqli_fetch_assoc($res)['total']);
$res = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM usuarios WHERE activo = 1 AND rol != 'admin'");
$total_activos = intval(mysqli_fetch_assoc($res)['total']);

echo json_encode([
    'ok' => true,
    'id' => $id,
    'accion' => $accion,
    'nuevos' => [
        'total_usuarios' => $total_usuarios,
        'total_turistas' => $total_turistas,
        'total_profesionales' => $total_profesionales,
        'total_activos' => $total_activos
    ]
]);

?>
