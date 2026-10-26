<?php
include 'conexion.php';

if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// --- ESTADÍSTICAS ---
$total_usuarios      = (int)mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS total FROM usuarios WHERE rol != 'admin'"))['total'];
$total_turistas      = (int)mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'turista'"))['total'];
$total_profesionales = (int)mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'profesional'"))['total'];
$total_activos       = (int)mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS total FROM usuarios WHERE activo = 1 AND rol != 'admin'"))['total'];

// --- LISTA DE USUARIOS ---
$usuarios = mysqli_query($conexion, "SELECT id, nombre, email, rol, activo, fecha_registro FROM usuarios WHERE rol != 'admin' ORDER BY fecha_registro DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OficioMar – Panel de Administración</title>
  <link rel="stylesheet" href="estilo.css">
  <style>
    .admin-container { max-width: 1040px; margin: 0 auto; padding: 2rem 1.5rem 4rem; }
    .tabla-wrap { background: white; border: 1px solid var(--borde); border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--sombra-sm); }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    thead { background: var(--fondo-alt); border-bottom: 2px solid var(--borde); }
    th { padding: 14px 16px; font-size: 0.78rem; font-weight: 700; color: var(--texto-secundario); text-transform: uppercase; letter-spacing: 0.5px; }
    td { padding: 14px 16px; font-size: 0.9rem; border-top: 1px solid var(--borde); color: var(--texto-principal); vertical-align: middle; }
    tr:hover td { background: #f8fafc; }
    .badge-rol { font-size: 0.74rem; font-weight: 700; padding: 4px 10px; border-radius: 999px; text-transform: capitalize; }
    .badge-turista { background: var(--cyan-suave); color: #0284c7; }
    .badge-profesional { background: #dbeafe; color: #1d4ed8; }
    .estado-badge { font-size: 0.74rem; font-weight: 700; padding: 4px 10px; border-radius: 999px; }
    .estado-badge.activo { background: var(--verde-suave); color: #047857; }
    .estado-badge.inactivo { background: var(--rojo-suave); color: #b91c1c; }
  </style>
</head>
<body style="background: var(--fondo);">

<nav>
  <a class="logo" href="admin.php">
    <span>🌊</span> Oficio<span>Mar</span>
  </a>
  <div class="nav-right">
    <span class="nav-badge-role" style="background:#dc2626;">Administrador</span>
    <span class="nav-usuario">
      <span class="avatar-mini">🛡️</span>
      <span><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
    </span>
    <a href="logout.php" class="btn-salir">Cerrar sesión</a>
  </div>
</nav>

<div class="admin-container">

  <div style="margin-bottom: 2rem;">
    <h1 style="font-family:'Syne', sans-serif; font-size:2rem; font-weight:800; color:var(--azul-oscuro);">
      🛡️ Panel de Administración
    </h1>
    <p style="color:var(--texto-secundario); font-size:0.95rem;">
      Supervisión general de usuarios registrados, roles y control de acceso.
    </p>
  </div>

  <!-- TARJETAS DE ESTADÍSTICAS -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">👥</div>
      <div class="stat-val" id="stat_total_usuarios"><?php echo $total_usuarios; ?></div>
      <div class="stat-desc">Usuarios totales registrados</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🏖️</div>
      <div class="stat-val" id="stat_turistas"><?php echo $total_turistas; ?></div>
      <div class="stat-desc">Cuentas de turistas</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🔧</div>
      <div class="stat-val" id="stat_profesionales"><?php echo $total_profesionales; ?></div>
      <div class="stat-desc">Cuentas de profesionales</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🟢</div>
      <div class="stat-val" id="stat_activos"><?php echo $total_activos; ?></div>
      <div class="stat-desc">Usuarios activos</div>
    </div>
  </div>

  <!-- FILTROS Y BÚSQUEDA DE TABLA -->
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:1.2rem;">
    <div class="chips" style="margin-bottom:0;">
      <div class="chip active" onclick="filtrarTabla('todos', this)">Todos los usuarios</div>
      <div class="chip" onclick="filtrarTabla('turista', this)">🏖️ Turistas</div>
      <div class="chip" onclick="filtrarTabla('profesional', this)">🔧 Profesionales</div>
    </div>

    <div style="display:flex; gap:8px; min-width:260px;">
      <input id="adminSearch" oninput="adminSearch()" placeholder="Buscar por nombre o email..." style="padding:8px 14px; border-radius:var(--radius-sm); border:1.5px solid var(--borde); font-family:inherit; font-size:0.88rem; outline:none; width:100%;">
    </div>
  </div>

  <!-- TABLA DE USUARIOS -->
  <div class="tabla-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nombre</th>
          <th>Email</th>
          <th>Registro</th>
          <th>Rol</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($u = mysqli_fetch_assoc($usuarios)): ?>
        <tr class="fila-<?php echo $u['rol']; ?>">
          <td><strong><?php echo $u['id']; ?></strong></td>
          <td><?php echo htmlspecialchars($u['nombre']); ?></td>
          <td><?php echo htmlspecialchars($u['email']); ?></td>
          <td><?php echo date('d/m/Y', strtotime($u['fecha_registro'])); ?></td>
          <td>
            <span class="badge-rol badge-<?php echo $u['rol']; ?>">
              <?php echo ucfirst($u['rol']); ?>
            </span>
          </td>
          <td>
            <span class="estado-badge <?php echo $u['activo'] ? 'activo' : 'inactivo'; ?>">
              <?php echo $u['activo'] ? '● Activo' : '● Inactivo'; ?>
            </span>
          </td>
          <td>
            <div style="display:flex; gap:6px; flex-wrap:wrap;">
              <?php if ($u['rol'] === 'profesional'): ?>
                <a href="perfil.php?id=<?php echo $u['id']; ?>" target="_blank" class="btn-sm btn-secundario">
                  Ver perfil
                </a>
              <?php endif; ?>

              <?php if ($u['activo']): ?>
                <button type="button" onclick="adminToggle(<?php echo $u['id']; ?>, 'desactivar', this)" class="btn-sm btn-rojo">
                  Desactivar
                </button>
              <?php else: ?>
                <button type="button" onclick="adminToggle(<?php echo $u['id']; ?>, 'activar', this)" class="btn-sm btn-verde">
                  Activar
                </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

</div>

<script src="javascript.js"></script>
<script>
function filtrarTabla(rol, btn) {
  document.querySelectorAll('.chips .chip').forEach(function(c) { c.classList.remove('active'); });
  btn.classList.add('active');

  document.querySelectorAll('tbody tr').forEach(function(fila) {
    if (rol === 'todos') {
      fila.style.display = '';
    } else {
      fila.style.display = fila.classList.contains('fila-' + rol) ? '' : 'none';
    }
  });
}
</script>
</body>
</html>
