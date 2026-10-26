<?php
include 'conexion.php';

if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'profesional') {
    header('Location: index.php');
    exit;
}

$usuario_id = (int)$_SESSION['id'];

// Obtener datos del usuario
$stmt_u = mysqli_prepare($conexion, "SELECT id, nombre, email FROM usuarios WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_u, "i", $usuario_id);
mysqli_stmt_execute($stmt_u);
$res_u = mysqli_stmt_get_result($stmt_u);
$usuario = mysqli_fetch_assoc($res_u);
mysqli_stmt_close($stmt_u);

// Obtener datos del perfil profesional
$stmt_p = mysqli_prepare($conexion, "SELECT * FROM profesionales WHERE usuario_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_p, "i", $usuario_id);
mysqli_stmt_execute($stmt_p);
$res_p = mysqli_stmt_get_result($stmt_p);
$perfil = mysqli_fetch_assoc($res_p);
mysqli_stmt_close($stmt_p);

if (!$perfil) {
    mysqli_query($conexion, "INSERT INTO profesionales (usuario_id, disponible) VALUES ($usuario_id, 1)");
    $perfil = [
        'rubro' => '',
        'zona' => '',
        'precio' => '',
        'descripcion' => '',
        'anos_experiencia' => 0,
        'tiempo_respuesta' => '',
        'telefono' => '',
        'sitio_web' => '',
        'disponible' => 1
    ];
}

// Estadísticas reales desde base de datos
// 1. Trabajos aceptados/completados este mes
$res_mes = mysqli_query($conexion, "
    SELECT COUNT(*) AS total 
    FROM solicitudes 
    WHERE profesional_id = $usuario_id 
      AND estado IN ('aceptado', 'completado')
      AND MONTH(fecha_creacion) = MONTH(NOW()) 
      AND YEAR(fecha_creacion) = YEAR(NOW())
");
$trabajos_mes = $res_mes ? (int)mysqli_fetch_assoc($res_mes)['total'] : 0;

// 2. Calificación promedio y total de reseñas
$res_rating = mysqli_query($conexion, "
    SELECT IFNULL(AVG(calificacion), 0) AS promedio, COUNT(*) AS total 
    FROM resenas 
    WHERE profesional_id = $usuario_id
");
$rating_data = mysqli_fetch_assoc($res_rating);
$promedio_rating = $rating_data['promedio'] ? round($rating_data['promedio'], 1) : 0;
$total_resenas = (int)$rating_data['total'];

// 3. Solicitudes de servicio recibidas
$stmt_sol = mysqli_prepare($conexion, "
    SELECT s.*, u.nombre AS turista_nombre_cuenta, u.email AS turista_email 
    FROM solicitudes s 
    LEFT JOIN usuarios u ON s.turista_id = u.id 
    WHERE s.profesional_id = ? 
    ORDER BY s.fecha_creacion DESC
");
mysqli_stmt_bind_param($stmt_sol, "i", $usuario_id);
mysqli_stmt_execute($stmt_sol);
$res_solicitudes = mysqli_stmt_get_result($stmt_sol);

$solicitudes = [];
$total_pendientes = 0;
while ($sol = mysqli_fetch_assoc($res_solicitudes)) {
    if ($sol['estado'] === 'pendiente') $total_pendientes++;
    $solicitudes[] = $sol;
}
mysqli_stmt_close($stmt_sol);

// 4. Reseñas recibidas
$stmt_res = mysqli_prepare($conexion, "
    SELECT r.*, u.nombre AS turista_nombre 
    FROM resenas r 
    JOIN usuarios u ON r.turista_id = u.id 
    WHERE r.profesional_id = ? 
    ORDER BY r.fecha DESC
");
mysqli_stmt_bind_param($stmt_res, "i", $usuario_id);
mysqli_stmt_execute($stmt_res);
$res_lista = mysqli_stmt_get_result($stmt_res);

$resenas_recibidas = [];
while ($r = mysqli_fetch_assoc($res_lista)) {
    $resenas_recibidas[] = $r;
}
mysqli_stmt_close($stmt_res);

// Calcular porcentaje de completitud del perfil
$porcentaje_completitud = 0;
if (!empty($perfil['rubro'])) $porcentaje_completitud += 20;
if (!empty($perfil['zona'])) $porcentaje_completitud += 15;
if (!empty($perfil['precio'])) $porcentaje_completitud += 15;
if (!empty($perfil['descripcion']) && strlen($perfil['descripcion']) >= 20) $porcentaje_completitud += 20;
if (!empty($perfil['telefono'])) $porcentaje_completitud += 15;
if (!empty($perfil['tiempo_respuesta'])) $porcentaje_completitud += 10;
if (!empty($perfil['sitio_web']) || (isset($perfil['anos_experiencia']) && $perfil['anos_experiencia'] > 0)) $porcentaje_completitud += 5;

function emojiRubro($rubro) {
    $emojis = [
        'Plomero'      => '🔧',
        'Electricista' => '⚡',
        'Cerrajero'    => '🔑',
        'Técnico'      => '📺',
        'Limpieza'     => '🧹',
        'Pintor'       => '🎨',
        'Gasista'      => '🔥',
    ];
    return isset($emojis[$rubro]) ? $emojis[$rubro] : '🛠️';
}

$rubro_nom = !empty($perfil['rubro']) ? $perfil['rubro'] : 'Especialidad no definida';
$emoji = emojiRubro($rubro_nom);
$disponible = !empty($perfil['disponible']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OficioMar – Panel del Profesional</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body style="background: var(--fondo);">

<!-- NAVEGACIÓN SUPERIOR -->
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
    <a href="perfil.php?id=<?php echo $usuario_id; ?>" target="_blank" class="btn-secundario" style="padding:7px 14px; font-size:0.82rem;">
      👁️ Ver mi perfil público
    </a>
    <a href="logout.php" class="btn-salir">Cerrar sesión</a>
  </div>
</nav>

<div class="dashboard-wrapper">

  <!-- CABECERA PROFESIONAL HERO -->
  <div class="prof-hero-card">
    <div class="prof-hero-left">
      <div class="prof-hero-avatar"><?php echo $emoji; ?></div>
      <div class="prof-hero-info">
        <h2>Hola, <?php echo htmlspecialchars(explode(' ', $usuario['nombre'])[0]); ?> 🛠️</h2>
        <p>Gestioná tus solicitudes, tarifas y disponibilidad en tiempo real.</p>
        <span class="prof-rubro-pill">
          <span><?php echo $emoji; ?></span>
          <span id="headerRubroTitle"><?php echo htmlspecialchars($rubro_nom); ?></span>
        </span>
      </div>
    </div>

    <!-- SWITCH DE DISPONIBILIDAD -->
    <div class="disp-toggle-box">
      <div class="disp-toggle-text">
        <div class="disp-status-title" id="dispStatusTitle">
          <?php echo $disponible ? '🟢 Disponible' : '🔴 No disponible'; ?>
        </div>
        <div class="disp-status-sub" id="dispStatusSub">
          <?php echo $disponible ? 'Visible en búsquedas' : 'Pausado temporalmente'; ?>
        </div>
      </div>
      <label class="switch-wrap">
        <input type="checkbox" id="dispCheckbox" <?php echo $disponible ? 'checked' : ''; ?> onchange="toggleDisponibilidad(this)">
        <span class="switch-slider"></span>
      </label>
    </div>
  </div>

  <!-- BARRA DE COMPLETITUD DEL PERFIL -->
  <div class="completitud-card">
    <div class="completitud-info">
      <div class="completitud-header">
        <span>Nivel de completitud de tu perfil</span>
        <span><?php echo $porcentaje_completitud; ?>%</span>
      </div>
      <div class="progress-track">
        <div class="progress-bar" style="width: <?php echo $porcentaje_completitud; ?>%;"></div>
      </div>
    </div>
    <?php if ($porcentaje_completitud < 100): ?>
      <button class="btn-secundario btn-sm" onclick="cambiarTab('tabPerfil', document.querySelectorAll('.tab-btn')[1])">
        ✏️ Completar datos (+ visitas)
      </button>
    <?php else: ?>
      <span style="color:var(--verde); font-weight:700; font-size:0.86rem;">
        ✅ ¡Perfil optimizado al máximo!
      </span>
    <?php endif; ?>
  </div>

  <!-- TARJETAS DE MÉTRICAS KPI -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">📥</div>
      <div class="stat-val" id="kpi_trabajos_mes"><?php echo $trabajos_mes; ?></div>
      <div class="stat-desc">Trabajos aceptados este mes</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">⭐</div>
      <div class="stat-val"><?php echo $promedio_rating > 0 ? $promedio_rating : '—'; ?></div>
      <div class="stat-desc">Calificación promedio</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">💬</div>
      <div class="stat-val"><?php echo $total_resenas; ?></div>
      <div class="stat-desc">Reseñas de turistas recibidas</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">⏱️</div>
      <div class="stat-val" style="font-size:1.35rem; padding-top:6px;">
        <?php echo !empty($perfil['tiempo_respuesta']) ? htmlspecialchars($perfil['tiempo_respuesta']) : 'A convenir'; ?>
      </div>
      <div class="stat-desc">Tiempo promedio de respuesta</div>
    </div>
  </div>

  <!-- NAVEGACIÓN DE PESTAÑAS (TABS) -->
  <div class="nav-tabs">
    <button class="tab-btn active" onclick="cambiarTab('tabSolicitudes', this)">
      📥 Solicitudes de Clientes
      <?php if ($total_pendientes > 0): ?>
        <span class="badge-count"><?php echo $total_pendientes; ?></span>
      <?php endif; ?>
    </button>
    <button class="tab-btn" onclick="cambiarTab('tabPerfil', this)">
      ✏️ Mi Perfil y Servicios
    </button>
    <button class="tab-btn" onclick="cambiarTab('tabPreview', this)">
      👁️ Vista Previa de mi Tarjeta
    </button>
    <button class="tab-btn" onclick="cambiarTab('tabResenas', this)">
      ⭐ Mis Reseñas (<?php echo $total_resenas; ?>)
    </button>
  </div>

  <!-- PESTAÑA 1: SOLICITUDES DE TRABAJO -->
  <div class="tab-content active" id="tabSolicitudes">
    <div style="margin-bottom:1rem; display:flex; justify-content:space-between; align-items:center;">
      <h3 style="font-family:'Syne', sans-serif; font-size:1.2rem; color:var(--azul-oscuro);">
        Pedidos y contactos de turistas
      </h3>
      <span style="font-size:0.85rem; color:var(--texto-secundario);">
        Total: <?php echo count($solicitudes); ?> solicitud<?php echo count($solicitudes) !== 1 ? 'es' : ''; ?>
      </span>
    </div>

    <?php if (count($solicitudes) > 0): ?>
      <div class="solicitudes-list">
        <?php foreach ($solicitudes as $s): 
          $s_id = $s['id'];
          $s_estado = $s['estado'];
          $s_nombre = !empty($s['nombre_contacto']) ? $s['nombre_contacto'] : ($s['turista_nombre_cuenta'] ?? 'Turista');
          $s_tel = preg_replace('/[^0-9]/', '', $s['telefono_contacto']);
          if (strlen($s_tel) === 10) $s_tel = '549' . $s_tel;
          $s_wsp = "https://wa.me/{$s_tel}?text=" . urlencode("¡Hola {$s_nombre}! Te contacto en respuesta a tu solicitud en OficioMar.");
        ?>
        <div class="solicitud-item" id="solicitudCard_<?php echo $s_id; ?>">
          <div class="solicitud-head">
            <div>
              <span class="solicitud-user">👤 <?php echo htmlspecialchars($s_nombre); ?></span>
              <span class="solicitud-fecha"> • <?php echo date('d/m/Y H:i', strtotime($s['fecha_creacion'])); ?></span>
            </div>
            <span class="badge-estado-sol <?php echo $s_estado; ?>" id="solicitudBadge_<?php echo $s_id; ?>">
              <?php echo ucfirst($s_estado); ?>
            </span>
          </div>

          <div class="solicitud-cuerpo">
            <strong>Mensaje:</strong> <?php echo nl2br(htmlspecialchars($s['mensaje'])); ?>
          </div>

          <div class="solicitud-meta">
            <span>📞 <strong>Tel:</strong> <?php echo htmlspecialchars($s['telefono_contacto']); ?></span>
            <span>📍 <strong>Zona:</strong> <?php echo htmlspecialchars($s['zona'] ?: 'Mar del Plata'); ?></span>
            <span>📅 <strong>Fecha deseada:</strong> <?php echo htmlspecialchars($s['fecha_deseada'] ?: 'A convenir'); ?></span>
          </div>

          <div class="solicitud-actions">
            <?php if (!empty($s_tel)): ?>
              <a href="<?php echo $s_wsp; ?>" target="_blank" class="btn-sm btn-verde">
                💬 Abrir WhatsApp
              </a>
              <a href="tel:<?php echo $s_tel; ?>" class="btn-sm btn-secundario">
                📞 Llamar
              </a>
            <?php endif; ?>

            <?php if ($s_estado === 'pendiente'): ?>
              <button type="button" class="btn-sm btn-verde" onclick="cambiarEstadoSolicitud(<?php echo $s_id; ?>, 'aceptado', this)">
                ✅ Aceptar
              </button>
              <button type="button" class="btn-sm btn-rojo" onclick="cambiarEstadoSolicitud(<?php echo $s_id; ?>, 'rechazado', this)">
                ✕ Rechazar
              </button>
            <?php elseif ($s_estado === 'aceptado'): ?>
              <button type="button" class="btn-sm btn-verde" onclick="cambiarEstadoSolicitud(<?php echo $s_id; ?>, 'completado', this)">
                🎉 Marcar como completado
              </button>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon">📬</div>
        <h3>Todavía no recibiste solicitudes</h3>
        <p>Cuando un turista te pida presupuesto o atención por OficioMar, lo vas a ver acá con su teléfono para responderle de inmediato.</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- PESTAÑA 2: EDICIÓN DE PERFIL PROFESIONAL -->
  <div class="tab-content" id="tabPerfil">
    <div class="form-panel-card">
      <div style="margin-bottom:1.5rem; border-bottom:1px solid var(--borde); padding-bottom:1rem;">
        <h3 style="font-family:'Syne', sans-serif; font-size:1.3rem; color:var(--azul-oscuro);">
          Información de tu Perfil y Servicios
        </h3>
        <p style="font-size:0.88rem; color:var(--texto-secundario);">
          Mantené actualizados tus precios y datos para aparecer en más búsquedas en Mar del Plata.
        </p>
      </div>

      <form action="actualizar_perfil.php" method="POST">
        <input type="hidden" name="origen" value="inicio2">

        <div class="form-grid">
          <div class="campo">
            <label>Tu nombre completo (registrado)</label>
            <input type="text" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" disabled style="background:#f1f5f9; cursor:not-allowed;">
          </div>

          <div class="campo">
            <label>Rubro / Oficio principal *</label>
            <select name="rubro" required>
              <option value="">Seleccioná tu oficio</option>
              <option value="Plomero" <?php echo ($perfil['rubro'] === 'Plomero' ? 'selected' : ''); ?>>🔧 Plomero</option>
              <option value="Electricista" <?php echo ($perfil['rubro'] === 'Electricista' ? 'selected' : ''); ?>>⚡ Electricista</option>
              <option value="Cerrajero" <?php echo ($perfil['rubro'] === 'Cerrajero' ? 'selected' : ''); ?>>🔑 Cerrajero</option>
              <option value="Técnico" <?php echo ($perfil['rubro'] === 'Técnico' ? 'selected' : ''); ?>>📺 Técnico en electrodomésticos</option>
              <option value="Gasista" <?php echo ($perfil['rubro'] === 'Gasista' ? 'selected' : ''); ?>>🔥 Gasista matriculado</option>
              <option value="Pintor" <?php echo ($perfil['rubro'] === 'Pintor' ? 'selected' : ''); ?>>🎨 Pintor</option>
              <option value="Limpieza" <?php echo ($perfil['rubro'] === 'Limpieza' ? 'selected' : ''); ?>>🧹 Servicios de limpieza</option>
            </select>
          </div>
        </div>

        <div class="form-grid">
          <div class="campo">
            <label>Zonas de trabajo en Mar del Plata *</label>
            <input type="text" name="zona" placeholder="Ej: Centro, La Perla, Güemes, Playa Grande" value="<?php echo htmlspecialchars($perfil['zona'] ?? ''); ?>" required>
            <div class="hint">Podés ingresar varios barrios separados por comas</div>
          </div>

          <div class="campo">
            <label>Tarifa o rango de precios estimado *</label>
            <input type="text" name="precio" placeholder="Ej: $15.000 – $25.000 o A convenir" value="<?php echo htmlspecialchars($perfil['precio'] ?? ''); ?>" required>
            <div class="hint">Ayuda al turista a tener una referencia rápida</div>
          </div>
        </div>

        <div class="form-grid">
          <div class="campo">
            <label>Años de experiencia</label>
            <input type="number" name="anos_experiencia" min="0" max="60" placeholder="Ej: 12" value="<?php echo htmlspecialchars($perfil['anos_experiencia'] ?? '0'); ?>">
          </div>

          <div class="campo">
            <label>Tiempo promedio de respuesta</label>
            <input type="text" name="tiempo_respuesta" placeholder="Ej: 20 a 40 minutos o En el día" value="<?php echo htmlspecialchars($perfil['tiempo_respuesta'] ?? ''); ?>">
          </div>
        </div>

        <div class="form-grid">
          <div class="campo">
            <label>Teléfono de contacto / WhatsApp *</label>
            <input type="tel" name="telefono" placeholder="Ej: +54 9 223 555-1234" value="<?php echo htmlspecialchars($perfil['telefono'] ?? ''); ?>">
            <div class="hint">Indispensable para que los turistas te escriban directo</div>
          </div>

          <div class="campo">
            <label>Sitio web o Instagram (opcional)</label>
            <input type="url" name="sitio_web" placeholder="https://instagram.com/tu_perfil" value="<?php echo htmlspecialchars($perfil['sitio_web'] ?? ''); ?>">
          </div>
        </div>

        <div class="campo">
          <label>Descripción profesional y servicios que realizás *</label>
          <textarea rows="4" name="descripcion" placeholder="Contá tu experiencia, herramientas, trabajos de urgencia y qué te diferencia..." required><?php echo htmlspecialchars($perfil['descripcion'] ?? ''); ?></textarea>
          <div class="hint">Una buena descripción genera mayor confianza en turistas que están alquilando</div>
        </div>

        <div style="display:flex; gap:12px; margin-top:1.5rem;">
          <button type="submit" class="btn-principal" style="width:auto; padding:12px 36px;">
            💾 Guardar cambios de mi perfil
          </button>
          <a href="perfil.php?id=<?php echo $usuario_id; ?>" target="_blank" class="btn-secundario">
            👁️ Ver cómo quedó
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- PESTAÑA 3: VISTA PREVIA EN VIVO -->
  <div class="tab-content" id="tabPreview">
    <div style="margin-bottom:1.5rem;">
      <h3 style="font-family:'Syne', sans-serif; font-size:1.3rem; color:var(--azul-oscuro);">
        Así se ve tu tarjeta en la página principal de turistas:
      </h3>
      <p style="font-size:0.88rem; color:var(--texto-secundario);">
        Los cambios en el formulario se actualizan acá automáticamente.
      </p>
    </div>

    <div style="max-width:340px; margin: 0 auto 2rem;">
      <div class="card" id="cardLivePreview">
        <div class="card-top">
          <div class="av" id="previewEmoji"><?php echo $emoji; ?></div>
          <div>
            <div class="card-nombre"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
            <div class="card-rubro" id="previewRubro"><?php echo htmlspecialchars($rubro_nom); ?></div>
          </div>
          <div class="badge-disp <?php echo $disponible ? 'disponible' : 'ocupado'; ?>">
            <span class="dot-pulse"></span>
            <span><?php echo $disponible ? 'Disponible' : 'Ocupado'; ?></span>
          </div>
        </div>

        <div class="card-mid">
          <div class="estrellas-wrap">
            <span class="estrellas"><?php echo $total_resenas > 0 ? str_repeat('★', (int)round($promedio_rating)) : '☆☆☆☆☆'; ?></span>
            <span class="calif-num"><?php echo $promedio_rating > 0 ? $promedio_rating : '—'; ?></span>
            <span class="calif-total">(<?php echo $total_resenas; ?> reseñas)</span>
          </div>

          <div class="card-detalles">
            <div class="detalle-item">
              <span>📍</span>
              <span id="previewZona"><?php echo htmlspecialchars($perfil['zona'] ?: 'Mar del Plata'); ?></span>
            </div>
          </div>

          <div class="precio-badge" id="previewPrecio">
            💰 <?php echo htmlspecialchars($perfil['precio'] ?: 'Consultar tarifa'); ?>
          </div>
        </div>

        <div class="card-bot">
          <button type="button" class="btn-contactar" style="pointer-events:none;">
            ⚡ Contactar
          </button>
          <a href="perfil.php?id=<?php echo $usuario_id; ?>" target="_blank" class="btn-ver-perfil">
            Ver perfil →
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- PESTAÑA 4: RESEÑAS RECIBIDAS -->
  <div class="tab-content" id="tabResenas">
    <div style="margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
      <h3 style="font-family:'Syne', sans-serif; font-size:1.3rem; color:var(--azul-oscuro);">
        Reseñas y valoraciones de turistas
      </h3>
      <span style="font-weight:700; color:var(--azul-vibrante);">
        Promedio general: <?php echo $promedio_rating; ?> / 5.0 ⭐
      </span>
    </div>

    <?php if (count($resenas_recibidas) > 0): ?>
      <div class="solicitudes-list">
        <?php foreach ($resenas_recibidas as $r): ?>
          <div class="resena-item">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
              <span style="font-weight:700; color:var(--azul-oscuro);">👤 <?php echo htmlspecialchars($r['turista_nombre']); ?></span>
              <div style="display:flex; align-items:center; gap:8px;">
                <span style="color:#f59e0b; font-size:1rem;"><?php echo str_repeat('★', max(1, min(5, (int)$r['calificacion']))); ?></span>
                <span style="font-size:0.78rem; color:var(--texto-tenue);"><?php echo date('d/m/Y', strtotime($r['fecha'])); ?></span>
              </div>
            </div>
            <p style="color:var(--texto-secundario); font-size:0.92rem; line-height:1.6;">
              <?php echo nl2br(htmlspecialchars($r['comentario'])); ?>
            </p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon">⭐</div>
        <h3>Todavía no recibiste reseñas</h3>
        <p>A medida que completes trabajos para turistas, podrán dejar su puntuación y testimonio en tu perfil.</p>
      </div>
    <?php endif; ?>
  </div>

</div>

<script src="javascript.js"></script>
</body>
</html>
