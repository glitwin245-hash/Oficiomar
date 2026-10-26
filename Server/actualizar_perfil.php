<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'profesional') {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['ok' => false, 'msg' => 'No autorizado']);
        exit;
    }
    header('Location: index.html');
    exit;
}

include 'conexion.php';

$usuario_id = (int)$_SESSION['id'];
$origen = isset($_POST['origen']) && $_POST['origen'] === 'editar' ? 'editar_perfil.php' : 'inicio2.php';

// Obtener perfil actual para no sobrescribir con valores nulos si no vienen en el POST
$stmt_curr = mysqli_prepare($conexion, "SELECT * FROM profesionales WHERE usuario_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_curr, "i", $usuario_id);
mysqli_stmt_execute($stmt_curr);
$res_curr = mysqli_stmt_get_result($stmt_curr);
$perfil_actual = mysqli_fetch_assoc($res_curr);
mysqli_stmt_close($stmt_curr);

if (!$perfil_actual) {
    // Si aún no tiene perfil, crearlo
    $stmt_crear = mysqli_prepare($conexion, "INSERT INTO profesionales (usuario_id, disponible) VALUES (?, 1)");
    mysqli_stmt_bind_param($stmt_crear, "i", $usuario_id);
    mysqli_stmt_execute($stmt_crear);
    mysqli_stmt_close($stmt_crear);
    $perfil_actual = ['rubro' => '', 'zona' => '', 'precio' => '', 'descripcion' => '', 'anos_experiencia' => 0, 'tiempo_respuesta' => '', 'telefono' => '', 'sitio_web' => ''];
}

$rubro            = isset($_POST['rubro']) ? trim($_POST['rubro']) : ($perfil_actual['rubro'] ?? '');
$zona             = isset($_POST['zona']) ? trim($_POST['zona']) : ($perfil_actual['zona'] ?? '');
$precio           = isset($_POST['precio']) ? trim($_POST['precio']) : ($perfil_actual['precio'] ?? '');
$descripcion      = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : ($perfil_actual['descripcion'] ?? '');
$anos_experiencia = isset($_POST['anos_experiencia']) ? (int)$_POST['anos_experiencia'] : (int)($perfil_actual['anos_experiencia'] ?? 0);
$tiempo_respuesta = isset($_POST['tiempo_respuesta']) ? trim($_POST['tiempo_respuesta']) : ($perfil_actual['tiempo_respuesta'] ?? '');
$telefono         = isset($_POST['telefono']) ? trim($_POST['telefono']) : ($perfil_actual['telefono'] ?? '');
$sitio_web        = isset($_POST['sitio_web']) ? trim($_POST['sitio_web']) : ($perfil_actual['sitio_web'] ?? '');

// Validación básica de campos requeridos
if (empty($rubro) || empty($zona) || empty($precio) || empty($descripcion)) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['ok' => false, 'msg' => 'Completá todos los campos requeridos (*).']);
        exit;
    }
    header("Location: $origen?error=1");
    exit;
}

// Actualizar con Prepared Statement
$stmt_update = mysqli_prepare($conexion, "
    UPDATE profesionales SET 
        rubro = ?, 
        zona = ?, 
        precio = ?, 
        descripcion = ?, 
        anos_experiencia = ?, 
        tiempo_respuesta = ?, 
        telefono = ?, 
        sitio_web = ?
    WHERE usuario_id = ?
");

if ($stmt_update) {
    mysqli_stmt_bind_param(
        $stmt_update,
        "ssssisssi",
        $rubro,
        $zona,
        $precio,
        $descripcion,
        $anos_experiencia,
        $tiempo_respuesta,
        $telefono,
        $sitio_web,
        $usuario_id
    );

    $exito = mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);

    if ($exito) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['ok' => true, 'msg' => 'Perfil actualizado exitosamente']);
            exit;
        }
        header("Location: $origen?guardado=1");
        exit;
    }
}

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    echo json_encode(['ok' => false, 'msg' => 'Error al guardar los cambios en la base de datos']);
    exit;
}
header("Location: $origen?error=3");
exit;
?>
