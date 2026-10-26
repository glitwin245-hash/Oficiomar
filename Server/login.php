<?php
include 'conexion.php';

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$contrasena = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';

if (empty($email) || empty($contrasena)) {
    header('Location: index.php?error=1');
    exit;
}

// Búsqueda segura con Prepared Statement
$stmt = mysqli_prepare($conexion, "SELECT id, nombre, email, contrasena, rol, activo FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1");
if (!$stmt) {
    header('Location: index.php?error=1');
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$usuario = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($stmt);

if ($usuario) {
    $login_valido = false;

    // Verificar con password_verify o fallback a texto plano para usuarios heredados
    if (password_verify($contrasena, $usuario['contrasena'])) {
        $login_valido = true;
    } elseif ($usuario['contrasena'] === $contrasena) {
        $login_valido = true;
        // Migración progresiva: actualizar automáticamente al nuevo hash seguro
        $nuevo_hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $update_stmt = mysqli_prepare($conexion, "UPDATE usuarios SET contrasena = ? WHERE id = ?");
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, "si", $nuevo_hash, $usuario['id']);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
        }
    }

    if ($login_valido) {
        $_SESSION['id']     = (int)$usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['email']  = $usuario['email'];
        $_SESSION['rol']    = $usuario['rol'];

        // Generar token criptográfico para persistencia de sesión por 30 días
        $token_sesion = bin2hex(random_bytes(32));
        $stmt_tok = mysqli_prepare($conexion, "UPDATE usuarios SET token_sesion = ? WHERE id = ?");
        if ($stmt_tok) {
            mysqli_stmt_bind_param($stmt_tok, "si", $token_sesion, $usuario['id']);
            mysqli_stmt_execute($stmt_tok);
            mysqli_stmt_close($stmt_tok);
        }

        // Configurar cookie segura de 30 días
        setcookie('oficiomar_token', $token_sesion, [
            'expires'  => time() + (86400 * 30),
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        if ($usuario['rol'] === 'turista') {
            header('Location: inicio.php');
            exit;
        } elseif ($usuario['rol'] === 'profesional') {
            header('Location: inicio2.php');
            exit;
        } elseif ($usuario['rol'] === 'admin') {
            header('Location: admin.php');
            exit;
        }
    }
}

header('Location: index.php?error=1');
exit;
?>
