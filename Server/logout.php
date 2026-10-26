<?php
include 'conexion.php';

// Si el usuario tenía sesión iniciada, invalidar el token en la base de datos
if (isset($_SESSION['id'])) {
    $usuario_id = (int)$_SESSION['id'];
    $stmt_del = mysqli_prepare($conexion, "UPDATE usuarios SET token_sesion = NULL WHERE id = ?");
    if ($stmt_del) {
        mysqli_stmt_bind_param($stmt_del, "i", $usuario_id);
        mysqli_stmt_execute($stmt_del);
        mysqli_stmt_close($stmt_del);
    }
}

// Limpiar la cookie de persistencia oficiomar_token
if (isset($_COOKIE['oficiomar_token'])) {
    setcookie('oficiomar_token', '', time() - 3600, '/');
    unset($_COOKIE['oficiomar_token']);
}

// Limpiar la cookie de sesión del navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir todas las variables de sesión
$_SESSION = [];
session_unset();
session_destroy();

// Redirigir al inicio de sesión
header('Location: index.php');
exit;
?>
