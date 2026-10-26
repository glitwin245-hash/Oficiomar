<?php
// Conexión a la base de datos MySQL
$servidor = "localhost";
$usuario_bd = "root";
$clave_bd = "";
$nombre_bd = "oficioMar";

$conexion = mysqli_connect($servidor, $usuario_bd, $clave_bd, $nombre_bd);

if (!$conexion) {
    die("Error al conectar con la base de datos: " . mysqli_connect_error());
}

mysqli_set_charset($conexion, "utf8mb4");

// Garantizar que la tabla solicitudes exista en la base de datos
$tabla_solicitudes = "
CREATE TABLE IF NOT EXISTS solicitudes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  profesional_id INT NOT NULL,
  turista_id INT NOT NULL,
  nombre_contacto VARCHAR(100) NOT NULL,
  telefono_contacto VARCHAR(50) NOT NULL,
  zona VARCHAR(150),
  mensaje TEXT NOT NULL,
  fecha_deseada VARCHAR(100),
  estado ENUM('pendiente', 'aceptado', 'rechazado', 'completado') DEFAULT 'pendiente',
  fecha_creacion DATETIME DEFAULT NOW(),
  FOREIGN KEY (profesional_id) REFERENCES usuarios(id),
  FOREIGN KEY (turista_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
@mysqli_query($conexion, $tabla_solicitudes);

// Garantizar que columnas adicionales en profesionales existan
$cols_necesarias = [
    'anos_experiencia' => 'INT DEFAULT 0',
    'tiempo_respuesta' => 'VARCHAR(100) NULL',
    'telefono' => 'VARCHAR(100) NULL',
    'sitio_web' => 'VARCHAR(255) NULL'
];
$res_cols = @mysqli_query($conexion, "SHOW COLUMNS FROM profesionales");
if ($res_cols) {
    $cols_existentes = [];
    while ($col = mysqli_fetch_assoc($res_cols)) {
        $cols_existentes[$col['Field']] = true;
    }
    foreach ($cols_necesarias as $nombre_col => $definicion) {
        if (!isset($cols_existentes[$nombre_col])) {
            @mysqli_query($conexion, "ALTER TABLE profesionales ADD COLUMN $nombre_col $definicion");
        }
    }
}

// Garantizar que la columna token_sesion exista en usuarios para persistencia
$res_token_col = @mysqli_query($conexion, "SHOW COLUMNS FROM usuarios LIKE 'token_sesion'");
if ($res_token_col && mysqli_num_rows($res_token_col) === 0) {
    @mysqli_query($conexion, "ALTER TABLE usuarios ADD COLUMN token_sesion VARCHAR(255) NULL");
}

// Cargar inicializador de sesión persistente e intentar auto-login
require_once __DIR__ . '/session_boot.php';
intentar_auto_login($conexion);
?>
