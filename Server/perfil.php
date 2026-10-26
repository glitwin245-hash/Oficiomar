<?php
include 'conexion.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int) $_GET['id'];

// Obtener información del usuario profesional de manera segura
$stmt_u = mysqli_prepare($conexion, "SELECT id, nombre, email, rol, activo FROM usuarios WHERE id = ? AND rol = 'profesional' AND activo = 1 LIMIT 1");
mysqli_stmt_bind_param($stmt_u, "i", $id);
mysqli_stmt_execute($stmt_u);
$res_u = mysqli_stmt_get_result($stmt_u);
$usuario = mysqli_fetch_assoc($res_u);
mysqli_stmt_close($stmt_u);

if (!$usuario) {
    header('Location: index.php');
    exit;
}

// Obtener perfil profesional
$stmt_p = mysqli_prepare($conexion, "SELECT * FROM profesionales WHERE usuario_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_p, "i", $id);
mysqli_stmt_execute($stmt_p);
$res_p = mysqli_stmt_get_result($stmt_p);
$perfil = mysqli_fetch_assoc($res_p);
mysqli_stmt_close($stmt_p);

if (!$perfil) {
    mysqli_query($conexion, "INSERT INTO profesionales (usuario_id, disponible) VALUES ($id, 1)");
    $perfil = ['rubro' => '', 'zona' => '', 'precio' => '', 'descripcion' => '', 'anos_experiencia' => 0, 'tiempo_respuesta' => '', 'telefono' => '', 'sitio_web' => '', 'disponible' => 1];
}

// Obtener reseñas
$stmt_r = mysqli_prepare($conexion, "
    SELECT r.*, u.nombre AS turista_nombre
    FROM resenas r
    JOIN usuarios u ON r.turista_id = u.id
    WHERE r.profesional_id = ?
    ORDER BY r.fecha DESC
");
mysqli_stmt_bind_param($stmt_r, "i", $id);
mysqli_stmt_execute($stmt_r);
$res_resenas = mysqli_stmt_get_result($stmt_r);

// Estadísticas de reseñas
$stmt_stats = mysqli_prepare($conexion, "SELECT AVG(calificacion) AS promedio, COUNT(*) AS total FROM resenas WHERE profesional_id = ?");
mysqli_stmt_bind_param($stmt_stats, "i", $id);
mysqli_stmt_execute($stmt_stats);
$res_stats = mysqli_stmt_get_result($stmt_stats);
$stat_data = mysqli_fetch_assoc($res_stats);
mysqli_stmt_close($stmt_stats);

$promedio = $stat_data['promedio'] ? round($stat_data['promedio'], 1) : 0;
$total_resenas = (int)$stat_data['total'];

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

$rubro_nom       = !empty($perfil['rubro']) ? $perfil['rubro'] : 'Profesional';
$emoji           = emojiRubro($rubro_nom);
$tiempo_resp     = !empty($perfil['tiempo_respuesta']) ? $perfil['tiempo_respuesta'] : 'A coordinar';
$zona            = !empty($perfil['zona']) ? $perfil['zona'] : 'Todo Mar del Plata';
$precio          = !empty($perfil['precio']) ? $perfil['precio'] : 'A convenir';
$descripcion     = !empty($perfil['descripcion']) ? $perfil['descripcion'] : 'Este profesional aún no ha redactado una descripción.';
$telefono        = !empty($perfil['telefono']) ? trim($perfil['telefono']) : '';
$sitio_web       = !empty($perfil['sitio_web']) ? trim($perfil['sitio_web']) : '';
$disponible      = !empty($perfil['disponible']);
$anos_exp        = isset($perfil['anos_experiencia']) && (int)$perfil['anos_experiencia'] > 0 ? (int)$perfil['anos_experiencia'] : null;

// Preparar enlace de WhatsApp
$wsp_link = '';
if ($telefono !== '') {
    $tel_clean = preg_replace('/[^0-9]/', '', $telefono);
    if (strlen($tel_clean) === 10) $tel_clean = '549' . $tel_clean;
    $msg_wsp = urlencode("¡Hola {$usuario['nombre']}! Te contacto desde OficioMar por tus servicios de $rubro_nom en Mar del Plata.");
    $wsp_link = "https://wa.me/{$tel_clean}?text={$msg_wsp}";
}

$usuario_logueado = isset($_SESSION['id']);
$es_propio_perfil = $usuario_logueado && ((int)$_SESSION['id'] === $id);
$es_turista       = $usuario_logueado && ($_SESSION['rol'] ?? '') === 'turista';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OficioMar – <?php echo htmlspecialchars($usuario['nombre']); ?> (<?php echo htmlspecialchars($rubro_nom); ?>)</title>
  <link rel="stylesheet" href="estilo.css">
  <style>
    .perfil-container { max-width: 920px; margin: 0 auto; padding: 2rem 1.5rem 4rem; }
    .perfil-card { background: white; border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--sombra-lg); border: 1px solid var(--borde); margin-bottom: 2rem; }
    .perfil-hero { background: linear-gradient(135deg, #091a2e 0%, #0f325a 50%, #154c82 100%); color: white; padding: 2.2rem; display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: center; }
    .perfil-avatar { width: 92px; height: 92px; border-radius: var(--radius-md); background: rgba(255,255,255,0.14); border: 2.5px solid rgba(255,255,255,0.25); display: flex; align-items: center; justify-content: center; font-size: 2.8rem; box-shadow: 0 8px 24px rgba(0,0,0,0.2); }
    .perfil-meta { flex: 1; min-width: 220px; }
    .perfil-meta h1 { font-family: 'Syne', sans-serif; font-size: 1.85rem; font-weight: 800; letter-spacing: -0.5px; }
    .perfil-badge-rubro { display: inline-flex; align-items: center; gap: 6px; background: rgba(56, 189, 248, 0.2); border: 1px solid rgba(56, 189, 248, 0.4); color: var(--cyan-brillo); padding: 5px 14px; border-radius: 999px; font-weight: 700; font-size: 0.85rem; margin: 8px 0; }
    .perfil-body { padding: 2rem; }
    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.8rem; }
    .info-box { background: var(--fondo-alt); border-radius: var(--radius-sm); padding: 1.1rem; border-left: 4px solid var(--azul-vibrante); }
    .info-box .etiqueta { font-size: 0.72rem; font-weight: 700; color: var(--texto-tenue); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
    .info-box .valor { font-size: 0.98rem; font-weight: 700; color: var(--azul-oscuro); word-break: break-word; }
    .bio-box { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 2rem; }
    .bio-box h4 { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700; color: #0369a1; margin-bottom: 8px; }
    .bio-box p { color: #0f2b4c; line-height: 1.7; font-size: 0.95rem; }
    .contacto-bar { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 2rem; }
    .resenas-container { background: white; border-radius: var(--radius-lg); padding: 2rem; border: 1px solid var(--borde); box-shadow: var(--sombra-md); margin-bottom: 2rem; }
    .resena-item { background: var(--fondo-alt); border-radius: var(--radius-sm); padding: 1.2rem; margin-bottom: 1rem; border-left: 4px solid var(--naranja); }
  </style>
</head>
<body>

<nav>
  <a class="logo" href="<?php echo $usuario_logueado ? (($_SESSION['rol'] === 'profesional') ? 'inicio2.php' : 'inicio.php') : 'index.html'; ?>">
    <span>🌊</span> Oficio<span>Mar</span>
  </a>
  <div class="nav-right">
    <?php if ($usuario_logueado): ?>
      <span class="nav-usuario">
        <span class="avatar-mini"><?php echo ($_SESSION['rol'] === 'profesional') ? '👷' : '🏖️'; ?></span>
        <span><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
      </span>
      <a href="logout.php" class="btn-salir">Cerrar sesión</a>
    <?php else: ?>
      <a href="index.html" class="btn-principal" style="padding:8px 18px; font-size:0.85rem;">Iniciar sesión</a>
    <?php endif; ?>
  </div>
</nav>

<div class="perfil-container">

  <a href="javascript:history.back()" class="btn-secundario" style="margin-bottom: 1.5rem; padding: 8px 16px; font-size: 0.85rem;">
    ← Volver atrás
  </a>

  <!-- TARJETA PRINCIPAL DEL PERFIL -->
  <div class="perfil-card">
    <div class="perfil-hero">
      <div class="perfil-avatar"><?php echo $emoji; ?></div>
      <div class="perfil-meta">
        <h1><?php echo htmlspecialchars($usuario['nombre']); ?></h1>
        <div>
          <span class="perfil-badge-rubro"><?php echo $emoji; ?> <?php echo htmlspecialchars($rubro_nom); ?></span>
        </div>
        <div style="display:flex; align-items:center; gap:8px; margin-top:4px;">
          <span style="color:#f59e0b; font-size:1.1rem;"><?php echo $total_resenas > 0 ? str_repeat('★', (int)round($promedio)) : '☆☆☆☆☆'; ?></span>
          <span style="font-weight:700; font-size:1.05rem;"><?php echo $total_resenas > 0 ? $promedio : '0.0'; ?></span>
          <span style="opacity:0.8; font-size:0.85rem;">(<?php echo $total_resenas; ?> reseña<?php echo $total_resenas !== 1 ? 's' : ''; ?>)</span>
        </div>
      </div>

      <div style="margin-left:auto;">
        <span class="badge-disp <?php echo $disponible ? 'disponible' : 'ocupado'; ?>" style="font-size:0.85rem; padding:6px 14px;">
          <span class="dot-pulse"></span>
          <span><?php echo $disponible ? 'Disponible ahora' : 'No disponible'; ?></span>
        </span>
      </div>
    </div>

    <div class="perfil-body">
      <!-- CUADRÍCULA DE DETALLES -->
      <div class="info-grid">
        <div class="info-box">
          <div class="etiqueta">Zona de trabajo</div>
          <div class="valor">📍 <?php echo htmlspecialchars($zona); ?></div>
        </div>
        <div class="info-box">
          <div class="etiqueta">Tarifa estimada</div>
          <div class="valor">💰 <?php echo htmlspecialchars($precio); ?></div>
        </div>
        <div class="info-box">
          <div class="etiqueta">Tiempo de respuesta</div>
          <div class="valor">⏱️ <?php echo htmlspecialchars($tiempo_resp); ?></div>
        </div>
        <div class="info-box">
          <div class="etiqueta">Experiencia</div>
          <div class="valor">⭐ <?php echo $anos_exp ? htmlspecialchars($anos_exp . ' años') : 'A convenir'; ?></div>
        </div>
      </div>

      <!-- DESCRIPCIÓN DEL PROFESIONAL -->
      <div class="bio-box">
        <h4>Acerca de <?php echo htmlspecialchars($usuario['nombre']); ?></h4>
        <p><?php echo nl2br(htmlspecialchars($descripcion)); ?></p>
      </div>

      <!-- BOTONES DE CONTACTO DIRECTO -->
      <h3 style="font-family:'Syne', sans-serif; font-size:1.1rem; color:var(--azul-oscuro); margin-bottom:12px;">
        Vías de contacto directo:
      </h3>
      <div class="contacto-bar">
        <?php if ($wsp_link): ?>
          <a href="<?php echo $wsp_link; ?>" target="_blank" class="btn-principal" style="background:#25D366; flex:1 1 200px;">
            💬 Chatear por WhatsApp
          </a>
        <?php endif; ?>

        <?php if ($telefono): ?>
          <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9]/', '', $telefono)); ?>" class="btn-secundario" style="flex:1 1 180px;">
            📞 Llamar por teléfono
          </a>
        <?php endif; ?>

        <?php if ($sitio_web): ?>
          <a href="<?php echo htmlspecialchars($sitio_web); ?>" target="_blank" class="btn-secundario" style="flex:1 1 180px;">
            🌐 Visitar sitio web
          </a>
        <?php endif; ?>

        <button type="button" class="btn-secundario" onclick="copiarEnlacePerfil()" style="flex:1 1 160px;">
          📋 Compartir perfil
        </button>
      </div>

      <!-- FORMULARIO DE SOLICITUD DE TRABAJO IN SITU -->
      <?php if (!$es_propio_perfil): ?>
      <div style="background:var(--fondo-alt); border-radius:var(--radius-md); padding:1.5rem; margin-top:2rem; border:1px solid var(--borde);">
        <h3 style="font-family:'Syne', sans-serif; font-size:1.15rem; color:var(--azul-oscuro); margin-bottom:6px;">
          📩 ¿Querés pedirle un presupuesto o servicio a <?php echo htmlspecialchars($usuario['nombre']); ?>?
        </h3>
        <p style="font-size:0.88rem; color:var(--texto-secundario); margin-bottom:1.2rem;">
          Completá el pedido y el profesional lo recibirá en su panel de control para contactarte.
        </p>

        <form action="crear_solicitud.php" method="POST">
          <input type="hidden" name="profesional_id" value="<?php echo $id; ?>">
          <input type="hidden" name="redirect_url" value="perfil.php?id=<?php echo $id; ?>">

          <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">
            <div class="campo">
              <label>Tu teléfono de contacto *</label>
              <input type="tel" name="telefono_contacto" placeholder="Ej: 223 555-1234" required>
            </div>
            <div class="campo">
              <label>Dirección o barrio en Mar del Plata</label>
              <input type="text" name="zona" placeholder="Ej: Playa Chica, Centro, La Perla">
            </div>
            <div class="campo">
              <label>Fecha o urgencia requerida</label>
              <input type="text" name="fecha_deseada" placeholder="Ej: Hoy mismo, Este fin de semana">
            </div>
          </div>

          <div class="campo">
            <label>Descripción del trabajo o problema *</label>
            <textarea name="mensaje" rows="3" placeholder="Detallá qué trabajo necesitás realizar..." required></textarea>
          </div>

          <button type="submit" class="btn-principal" style="width:auto; padding:12px 28px;">
            🚀 Enviar solicitud a <?php echo htmlspecialchars($usuario['nombre']); ?>
          </button>
        </form>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <!-- SECCIÓN DE RESEÑAS -->
  <div class="resenas-container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:10px;">
      <h3 style="font-family:'Syne', sans-serif; font-size:1.3rem; color:var(--azul-oscuro);">
        ⭐ Opiniones y Reseñas de Clientes
      </h3>
      <span style="font-weight:700; color:var(--azul-vibrante); font-size:0.95rem;">
        Promedio: <?php echo $promedio; ?> / 5.0 (<?php echo $total_resenas; ?> reseñas)
      </span>
    </div>

    <!-- FORMULARIO PARA DEJAR RESEÑA (SOLO TURISTAS LOGUEADOS) -->
    <?php if ($es_turista && !$es_propio_perfil): ?>
      <div style="background:var(--fondo-alt); border-radius:var(--radius-md); padding:1.5rem; margin-bottom:2rem; border:1px solid var(--borde);">
        <h4 style="font-family:'Syne', sans-serif; font-size:1rem; color:var(--azul-oscuro); margin-bottom:8px;">
          ✍️ ¿Contrataste a <?php echo htmlspecialchars($usuario['nombre']); ?>? Contá tu experiencia:
        </h4>

        <form action="guardar_resena.php" method="POST">
          <input type="hidden" name="profesional_id" value="<?php echo $id; ?>">
          <input type="hidden" name="calificacion" id="inputCalificacion" value="5">

          <div style="margin-bottom:10px;">
            <label style="font-size:0.85rem; font-weight:600; color:var(--texto-secundario); display:block; margin-bottom:4px;">Tu calificación:</label>
            <div class="star-rating-box">
              <span class="star selected" data-val="1">★</span>
              <span class="star selected" data-val="2">★</span>
              <span class="star selected" data-val="3">★</span>
              <span class="star selected" data-val="4">★</span>
              <span class="star selected" data-val="5">★</span>
              <span class="rating-score-desc" id="starRatingDesc">5 - ¡Excelente servicio! 🌟</span>
            </div>
          </div>

          <div class="campo">
            <label>Tu comentario o testimonio *</label>
            <textarea name="comentario" rows="3" placeholder="Contá cómo fue la atención, puntualidad, precio y calidad del trabajo..." required></textarea>
          </div>

          <button type="submit" class="btn-principal" style="width:auto; padding:10px 24px;">
            Publicar reseña
          </button>
        </form>
      </div>
    <?php elseif (!$usuario_logueado): ?>
      <div style="background:var(--fondo-alt); padding:1rem; border-radius:var(--radius-sm); text-align:center; margin-bottom:1.5rem; font-size:0.9rem;">
        Para dejar una opinión sobre este profesional, <a href="index.html" style="color:var(--azul-vibrante); font-weight:700;">iniciá sesión</a> como turista.
      </div>
    <?php endif; ?>

    <!-- LISTA DE RESEÑAS -->
    <?php if ($total_resenas > 0): ?>
      <?php while ($resena = mysqli_fetch_assoc($res_resenas)): ?>
        <div class="resena-item">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; flex-wrap:wrap; gap:6px;">
            <div style="font-weight:700; color:var(--azul-oscuro); font-size:0.96rem;">
              👤 <?php echo htmlspecialchars($resena['turista_nombre']); ?>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
              <span style="color:#f59e0b; font-size:0.95rem;"><?php echo str_repeat('★', max(1, min(5, (int)$resena['calificacion']))); ?></span>
              <span style="font-size:0.78rem; color:var(--texto-tenue);"><?php echo date('d/m/Y', strtotime($resena['fecha'])); ?></span>
            </div>
          </div>
          <div style="color:var(--texto-secundario); font-size:0.92rem; line-height:1.6;">
            <?php echo nl2br(htmlspecialchars($resena['comentario'])); ?>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon">💬</div>
        <h3>Todavía no hay reseñas para este profesional</h3>
        <p>Sé el primer turista en calificar su trabajo en Mar del Plata.</p>
      </div>
    <?php endif; ?>

  </div>

</div>

<script src="javascript.js"></script>
</body>
</html>

