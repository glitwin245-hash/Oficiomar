<?php
// Comprueba si existe un usuario admin en la base de datos
include 'conexion.php';

$res = mysqli_query($conexion, "SELECT id, nombre, email, activo, fecha_registro FROM usuarios WHERE rol = 'admin' LIMIT 1");
header('Content-Type: text/plain; charset=utf-8');
if (!$res) {
    echo "Error al consultar la base de datos:\n" . mysqli_error($conexion);
    exit;
}

if (mysqli_num_rows($res) > 0) {
    $admin = mysqli_fetch_assoc($res);
    echo "ADMIN ENCONTRADO:\n";
    echo "ID: " . $admin['id'] . "\n";
    echo "Nombre: " . $admin['nombre'] . "\n";
    echo "Email: " . $admin['email'] . "\n";
    echo "Activo: " . ($admin['activo'] ? 'Sí' : 'No') . "\n";
    echo "Fecha registro: " . $admin['fecha_registro'] . "\n";
    echo "\nAccedé a /admin.php con este usuario para entrar al panel.\n";
} else {
    echo "NO SE ENCONTRÓ NINGÚN USUARIO ADMIN.\n\n";
    echo "Opciones para crear uno:\n";
    echo "1) Crear en phpMyAdmin o MySQL con este SQL (ajustá la contraseña):\n";
    echo "   INSERT INTO usuarios (nombre, email, contrasena, rol, activo, fecha_registro) VALUES ('Admin', 'admin@ejemplo.com', 'tu_password', 'admin', 1, NOW());\n";
    echo "\n2) Convertir un usuario existente en admin:\n";
    echo "   UPDATE usuarios SET rol = 'admin', activo = 1 WHERE email = 'tu-email@ejemplo.com';\n";
    echo "\nDespués ingresá en /login.php con ese email/clave y abrí /admin.php.\n";
}

?>
