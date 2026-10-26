<?php
include 'conexion.php';

if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'profesional') {
    header('Location: index.php');
    exit;
}

$usuario_id = (int)$_SESSION['id'];

// Traer datos del usuario y perfil actual con Prepared Statements
$stmt_u = mysqli_prepare($conexion, "SELECT nombre, email FROM usuarios WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_u, "i", $usuario_id);
mysqli_stmt_execute($stmt_u);
$res_u = mysqli_stmt_get_result($stmt_u);
$usuario = mysqli_fetch_assoc($res_u);
mysqli_stmt_close($stmt_u);

$stmt_p = mysqli_prepare($conexion, "SELECT * FROM profesionales WHERE usuario_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_p, "i", $usuario_id);
mysqli_stmt_execute($stmt_p);
$res_p = mysqli_stmt_get_result($stmt_p);
$perfil = mysqli_fetch_assoc($res_p);
mysqli_stmt_close($stmt_p);

if (!$perfil) {
    mysqli_query($conexion, "INSERT INTO profesionales (usuario_id, disponible) VALUES ($usuario_id, 1)");
    $perfil = ['rubro' => '', 'zona' => '', 'precio' => '', 'descripcion' => '', 'anos_experiencia' => 0, 'tiempo_respuesta' => '', 'telefono' => '', 'sitio_web' => '', 'disponible' => 1];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OficioMar – Editar Perfil Profesional</title>
  <link rel="stylesheet" href="estilo.css">
  <style>
    .edit-container { max-width: 860px; margin: 0 auto; padding: 2rem 1.5rem 4rem; }
    .form-section-title { font-family: 'Syne', sans-serif; font-size: 1.15rem; font-weight: 700; color: var(--azul-oscuro); margin: 1.5rem 0 1rem; padding-bottom: 6px; border-bottom: 2px solid var(--borde); display: flex; align-items: center; gap: 8px; }
  </style>
</head>
<body style="background: var(--fondo);">

<nav>
  <a class="logo" href="inicio2.php">
    <span>🌊</span> Oficio<span>Mar</span>
  </a>
  <div class="nav-right">
    <span class="nav-badge-role">Profesional</span>
    <span class="nav-usuario">
      <span class="avatar-mini">👷</span>
      <span><?php echo htmlspecialchars($usuario['nombre']); ?></span>
    </span>
    <a href="logout.php" class="btn-salir">Salir</a>
  </div>
</nav>

<div class="edit-container">

  <a href="inicio2.php" class="btn-secundario" style="margin-bottom: 1.5rem; padding: 8px 16px; font-size: 0.85rem;">
    ← Volver a mi panel
  </a>

  <div style="margin-bottom: 2rem;">
    <h1 style="font-family:'Syne', sans-serif; font-size:2rem; font-weight:800; color:var(--azul-oscuro);">
      ✏️ Editar Perfil Profesional
    </h1>
    <p style="color:var(--texto-secundario); font-size:0.95rem;">
      Actualizá los datos de tus servicios para que los turistas en Mar del Plata te encuentren rápido.
    </p>
  </div>

  <div class="form-panel-card">
    <form action="actualizar_perfil.php" method="POST">
      <input type="hidden" name="origen" value="editar">

      <div class="form-section-title">👤 Datos de Cuenta</div>
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:12px;">
        <div class="campo">
          <label>Nombre Completo</label>
          <input type="text" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" disabled style="background:#f1f5f9; cursor:not-allowed;">
        </div>
        <div class="campo">
          <label>Email Registrado</label>
          <input type="text" value="<?php echo htmlspecialchars($usuario['email']); ?>" disabled style="background:#f1f5f9; cursor:not-allowed;">
        </div>
      </div>

      <div class="form-section-title">🔧 Datos del Oficio</div>
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:12px;">
        <div class="campo">
          <label>Rubro / Especialidad *</label>
          <select name="rubro" required>
            <option value="">-- Seleccioná tu oficio --</option>
            <option value="Plomero" <?php echo ($perfil['rubro'] === 'Plomero' ? 'selected' : ''); ?>>🔧 Plomero</option>
            <option value="Electricista" <?php echo ($perfil['rubro'] === 'Electricista' ? 'selected' : ''); ?>>⚡ Electricista</option>
            <option value="Cerrajero" <?php echo ($perfil['rubro'] === 'Cerrajero' ? 'selected' : ''); ?>>🔑 Cerrajero</option>
            <option value="Técnico" <?php echo ($perfil['rubro'] === 'Técnico' ? 'selected' : ''); ?>>📺 Técnico en electrodomésticos</option>
            <option value="Gasista" <?php echo ($perfil['rubro'] === 'Gasista' ? 'selected' : ''); ?>>🔥 Gasista matriculado</option>
            <option value="Pintor" <?php echo ($perfil['rubro'] === 'Pintor' ? 'selected' : ''); ?>>🎨 Pintor</option>
            <option value="Limpieza" <?php echo ($perfil['rubro'] === 'Limpieza' ? 'selected' : ''); ?>>🧹 Servicios de limpieza</option>
          </select>
        </div>
        <div class="campo">
          <label>Años de Experiencia</label>
          <input type="number" name="anos_experiencia" min="0" max="60" placeholder="Ej: 8" value="<?php echo htmlspecialchars($perfil['anos_experiencia'] ?? '0'); ?>">
        </div>
      </div>

      <div class="form-section-title">📍 Zonas y Tarifas</div>
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:12px;">
        <div class="campo">
          <label>Zonas de Trabajo en Mar del Plata *</label>
          <input type="text" name="zona" placeholder="Ej: Centro, La Perla, Güemes, Punta Mogotes" value="<?php echo htmlspecialchars($perfil['zona'] ?? ''); ?>" required>
        </div>
        <div class="campo">
          <label>Rango de Precios / Tarifa Estimada *</label>
          <input type="text" name="precio" placeholder="Ej: $10.000 – $20.000 o A convenir" value="<?php echo htmlspecialchars($perfil['precio'] ?? ''); ?>" required>
        </div>
      </div>

      <div class="form-section-title">📞 Contacto y Tiempo de Respuesta</div>
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:12px;">
        <div class="campo">
          <label>Teléfono / WhatsApp *</label>
          <input type="tel" name="telefono" placeholder="Ej: +54 9 223 1234567" value="<?php echo htmlspecialchars($perfil['telefono'] ?? ''); ?>">
        </div>
        <div class="campo">
          <label>Tiempo Promedio de Respuesta</label>
          <input type="text" name="tiempo_respuesta" placeholder="Ej: 30 minutos o En el día" value="<?php echo htmlspecialchars($perfil['tiempo_respuesta'] ?? ''); ?>">
        </div>
        <div class="campo">
          <label>Sitio Web o Redes Sociales</label>
          <input type="url" name="sitio_web" placeholder="https://instagram.com/tu_perfil" value="<?php echo htmlspecialchars($perfil['sitio_web'] ?? ''); ?>">
        </div>
      </div>

      <div class="form-section-title">📝 Descripción Profesional</div>
      <div class="campo">
        <label>Sobre vos y tus servicios *</label>
        <textarea rows="4" name="descripcion" placeholder="Detallá tu experiencia, garantía de tus trabajos y disponibilidad..." required><?php echo htmlspecialchars($perfil['descripcion'] ?? ''); ?></textarea>
      </div>

      <div style="display:flex; gap:12px; margin-top:2rem;">
        <button type="submit" class="btn-principal" style="width:auto; padding:12px 36px;">
          💾 Guardar Cambios
        </button>
        <a href="inicio2.php" class="btn-secundario">
          Cancelar
        </a>
      </div>
    </form>
  </div>

</div>

<script src="javascript.js"></script>
</body>
</html>

