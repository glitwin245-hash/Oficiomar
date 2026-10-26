<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'profesional') {
    echo json_encode(['ok' => false, 'msg' => 'No autorizado']);
    exit;
}

include 'conexion.php';

$profesional_id = (int)$_SESSION['id'];
$solicitud_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$nuevo_estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';

$estados_validos = ['pendiente', 'aceptado', 'rechazado', 'completado'];
if ($solicitud_id <= 0 || !in_array($nuevo_estado, $estados_validos)) {
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos']);
    exit;
}

// Actualizar solicitud perteneciente al profesional logueado
$stmt = mysqli_prepare($conexion, "UPDATE solicitudes SET estado = ? WHERE id = ? AND profesional_id = ?");
if (!$stmt) {
    echo json_encode(['ok' => false, 'msg' => 'Error de consulta']);
    exit;
}

mysqli_stmt_bind_param($stmt, "sii", $nuevo_estado, $solicitud_id, $profesional_id);
$exito = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($exito) {
    // Calcular total de trabajos aceptados/completados este mes
    $query_stat = mysqli_query($conexion, "
        SELECT COUNT(*) AS total 
        FROM solicitudes 
        WHERE profesional_id = $profesional_id 
          AND estado IN ('aceptado', 'completado')
          AND MONTH(fecha_creacion) = MONTH(NOW()) 
          AND YEAR(fecha_creacion) = YEAR(NOW())
    ");
    $trabajos_mes = $query_stat ? (int)mysqli_fetch_assoc($query_stat)['total'] : 0;

    echo json_encode([
        'ok' => true,
        'id' => $solicitud_id,
        'estado' => $nuevo_estado,
        'trabajos_mes' => $trabajos_mes
    ]);
} else {
    echo json_encode(['ok' => false, 'msg' => 'No se pudo actualizar la solicitud']);
}
?>
