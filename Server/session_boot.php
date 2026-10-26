<?php
/**
 * OficioMar - Inicializador de Sesión Persistente y Auto-Login
 * Garantiza persistencia de 30 días y restauración automática ante suspensión de pestañas o reinicio de navegador.
 */

$duracion_sesion = 60 * 60 * 24 * 30; // 30 días en segundos

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', (string)$duracion_sesion);
    ini_set('session.gc_maxlifetime', (string)$duracion_sesion);

    session_set_cookie_params([
        'lifetime' => $duracion_sesion,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false, // Permitir en desarrollo local HTTP
        'httponly' => true,  // Prevenir acceso desde JavaScript (mitigación XSS)
        'samesite' => 'Lax'
    ]);

    session_start();
}

/**
 * Auto-login mediante cookie oficiomar_token si la sesión en memoria se reinició
 */
function intentar_auto_login($conexion) {
    if (!isset($_SESSION['id']) && isset($_COOKIE['oficiomar_token']) && !empty($_COOKIE['oficiomar_token'])) {
        $token = $_COOKIE['oficiomar_token'];
        
        $stmt = mysqli_prepare($conexion, "SELECT id, nombre, email, rol, activo FROM usuarios WHERE token_sesion = ? AND activo = 1 LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $token);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($usuario = mysqli_fetch_assoc($res)) {
                $_SESSION['id']     = (int)$usuario['id'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['email']  = $usuario['email'];
                $_SESSION['rol']    = $usuario['rol'];
            } else {
                // Token inválido o revocado: limpiar cookie
                setcookie('oficiomar_token', '', time() - 3600, '/');
            }
            mysqli_stmt_close($stmt);
        }
    }
}
