<?php
session_start();
include 'conexion.php';

// Verificar autenticación
if (!isset($_SESSION['id'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['ok' => false, 'msg' => 'Debés iniciar sesión']);
        exit;
    }
    header('Location: index.html');
    exit;
}

$turista_id = (int)$_SESSION['id'];
$profesional_id = isset($_POST['profesional_id']) ? (int)$_POST['profesional_id'] : 0;
$nombre_contacto = isset($_POST['nombre_contacto']) ? trim($_POST['nombre_contacto']) : (isset($_SESSION['nombre']) ? $_SESSION['nombre'] : '');
$telefono_contacto = isset($_POST['telefono_contacto']) ? trim($_POST['telefono_contacto']) : '';
$zona = isset($_POST['zona']) ? trim($_POST['zona']) : 'Mar del Plata';
$mensaje = isset($_POST['mensaje']) ? trim($_POST['mensaje']) : '';
$fecha_deseada = isset($_POST['fecha_deseada']) ? trim($_POST['fecha_deseada']) : 'Lo antes posible';
$redirect_url = isset($_POST['redirect_url']) ? $_POST['redirect_url'] : 'inicio.php';

if ($profesional_id <= 0 || empty($mensaje) || empty($telefono_contacto)) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['ok' => false, 'msg' => 'Por favor completá los campos obligatorios']);
        exit;
    }
    header("Location: $redirect_url?error=1");
    exit;
}

// Guardar solicitud con Prepared Statement
$stmt = mysqli_prepare($conexion, "INSERT INTO solicitudes (profesional_id, turista_id, nombre_contacto, telefono_contacto, zona, mensaje, fecha_deseada, estado, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())");

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "iisssss", $profesional_id, $turista_id, $nombre_contacto, $telefono_contacto, $zona, $mensaje, $fecha_deseada);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($ok) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['ok' => true, 'msg' => 'Solicitud enviada correctamente']);
            exit;
        }
        $sep = (strpos($redirect_url, '?') !== false) ? '&' : '?';
        header("Location: $redirect_url{$sep}solicitud=ok");
        exit;
    }
}

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    echo json_encode(['ok' => false, 'msg' => 'Error al guardar la solicitud en la base de datos']);
    exit;
}
header("Location: $redirect_url?error=1");
exit;
?>
