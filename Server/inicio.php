<?php
include 'conexion.php';

if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'turista') {
    header('Location: index.php');
    exit;
}

// Traer profesionales activos con info de perfil, teléfono, sitio web y promedio de reseñas
$consulta = "
    SELECT
        u.id,
        u.nombre,
        p.rubro,
        p.zona,
        p.precio,
        p.telefono,
        p.sitio_web,
        p.disponible,
        IFNULL(AVG(r.calificacion), 0) AS promedio,
        COUNT(r.id) AS total_resenas
    FROM usuarios u
    LEFT JOIN profesionales p ON p.usuario_id = u.id
    LEFT JOIN resenas r ON r.profesional_id = u.id
    WHERE u.rol = 'profesional' AND u.activo = 1
    GROUP BY u.id
    ORDER BY p.disponible DESC, promedio DESC
";
$resultado = mysqli_query($conexion, $consulta);

$profesionales = [];
if ($resultado) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $profesionales[] = $fila;
    }
}

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OficioMar – Encontrá Profesionales en Mar del Plata</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>

<nav>
  <a class="logo" href="inicio.php">
    <span>🌊</span> Oficio<span>Mar</span>
  </a>
  <div class="nav-right">
    <span class="nav-badge-role">Turista</span>
    <span class="nav-usuario">
      <span class="avatar-mini">🏖️</span>
      <span><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
    </span>
    <a href="logout.php" class="btn-salir">Cerrar sesión</a>
  </div>
</nav>

<!-- STRIP DE URGENCIA INTERACTIVO -->
<div class="urgencia-strip" onclick="activarModoUrgencia()">
  🚨 ¿Tenés una emergencia en Mar del Plata? Tocá acá para ver profesionales disponibles ahora mismo →
</div>

<!-- HERO DE BÚSQUEDA -->
<section class="hero">
  <div class="hero-inner">
    <h2>Encontrá profesionales de confianza en <span>Mar del Plata</span></h2>
    <p>Plomeros, electricistas, cerrajeros y más técnicos verificados para tu estadía o vivienda.</p>
    <div class="barra-busqueda">
      <span class="search-icon">🔍</span>
      <input type="text" id="searchInput" placeholder="Buscá por rubro, nombre o zona (ej: Plomero en Güemes)..." oninput="buscarCards()">
      <button class="btn-buscar" onclick="buscarCards()">Buscar</button>
    </div>
  </div>
</section>

<main style="max-width: 1040px; margin: 0 auto; padding: 2rem 1.5rem;">

  <!-- CHIPS DE FILTRADO RÁPIDO -->
  <div class="chips">
    <div class="chip active" onclick="filtrarCards('todos', this)">Todos</div>
    <div class="chip" onclick="filtrarCards('Plomero', this)">🔧 Plomeros</div>
    <div class="chip" onclick="filtrarCards('Electricista', this)">⚡ Electricistas</div>
    <div class="chip" onclick="filtrarCards('Cerrajero', this)">🔑 Cerrajeros</div>
    <div class="chip" onclick="filtrarCards('Técnico', this)">📺 Técnicos</div>
    <div class="chip" onclick="filtrarCards('Gasista', this)">🔥 Gasistas</div>
    <div class="chip" onclick="filtrarCards('Pintor', this)">🎨 Pintores</div>
    <div class="chip" onclick="filtrarCards('Limpieza', this)">🧹 Limpieza</div>
    <div class="chip" data-tipo="disponible" onclick="filtrarCards('disponible', this)">🟢 Disponibles ahora</div>
  </div>

  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h3 style="font-family:'Syne', sans-serif; font-size:1.15rem; font-weight:700; color:var(--azul-oscuro);" id="label">
      <?php echo count($profesionales); ?> profesional<?php echo count($profesionales) !== 1 ? 'es' : ''; ?> disponible<?php echo count($profesionales) !== 1 ? 's' : ''; ?> en Mar del Plata
    </h3>
  </div>

  <!-- GRID DE TARJETAS -->
  <div class="grid" id="grid">
    <?php foreach ($profesionales as $p):
      $rubro_nom  = !empty($p['rubro']) ? $p['rubro'] : 'Profesional';
      $emoji      = emojiRubro($rubro_nom);
      $promedio   = $p['promedio'] > 0 ? number_format($p['promedio'], 1) : '—';
      $estrellas  = $p['promedio'] > 0 ? str_repeat('★', (int)round($p['promedio'])) : '☆☆☆☆☆';
      $disp       = (int)($p['disponible'] ?? 1);
      $zona_desc  = !empty($p['zona']) ? $p['zona'] : 'Mar del Plata';
      $precio_desc = !empty($p['precio']) ? $p['precio'] : 'Consultar tarifa';
      $telefono   = !empty($p['telefono']) ? $p['telefono'] : '';
      $sitio      = !empty($p['sitio_web']) ? $p['sitio_web'] : '';
    ?>
    <div
      class="card"
      data-rubro="<?php echo htmlspecialchars($rubro_nom); ?>"
      data-nombre="<?php echo htmlspecialchars($p['nombre']); ?>"
      data-zona="<?php echo htmlspecialchars($zona_desc); ?>"
      data-disponible="<?php echo $disp; ?>"
    >
      <div class="card-top">
        <div class="av"><?php echo $emoji; ?></div>
        <div>
          <div class="card-nombre"><?php echo htmlspecialchars($p['nombre']); ?></div>
          <div class="card-rubro"><?php echo htmlspecialchars($rubro_nom); ?></div>
        </div>
        <div class="badge-disp <?php echo $disp ? 'disponible' : 'ocupado'; ?>">
          <span class="dot-pulse"></span>
          <span><?php echo $disp ? 'Disponible' : 'Ocupado'; ?></span>
        </div>
      </div>

      <div class="card-mid">
        <div class="estrellas-wrap">
          <span class="estrellas"><?php echo $estrellas; ?></span>
          <span class="calif-num"><?php echo $promedio; ?></span>
          <span class="calif-total">(<?php echo $p['total_resenas']; ?> reseña<?php echo $p['total_resenas'] !== 1 ? 's' : ''; ?>)</span>
        </div>

        <div class="card-detalles">
          <div class="detalle-item">
            <span>📍</span>
            <span><?php echo htmlspecialchars($zona_desc); ?></span>
          </div>
        </div>

        <div class="precio-badge">
          💰 <?php echo htmlspecialchars($precio_desc); ?>
        </div>
      </div>

      <div class="card-bot">
        <button 
          type="button"
          class="btn-contactar"
          onclick="abrirModalContacto(
            '<?php echo $p['id']; ?>',
            '<?php echo htmlspecialchars($p['nombre'], ENT_QUOTES); ?>',
            '<?php echo htmlspecialchars($rubro_nom, ENT_QUOTES); ?>',
            '<?php echo htmlspecialchars($telefono, ENT_QUOTES); ?>',
            '<?php echo htmlspecialchars($sitio, ENT_QUOTES); ?>'
          )"
        >
          ⚡ Contactar
        </button>
        <a href="perfil.php?id=<?php echo $p['id']; ?>" class="btn-ver-perfil">
          Ver perfil →
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ESTADO VACÍO (SIN RESULTADOS) -->
  <div class="empty-state" id="sinResultados" style="<?php echo count($profesionales) === 0 ? 'display:block;' : 'display:none;'; ?>">
    <div class="empty-icon">🔍</div>
    <h3>No encontramos profesionales con ese criterio</h3>
    <p>Probá buscando con otro rubro, nombre de zona o quitando el filtro de búsqueda.</p>
  </div>

</main>

<!-- MODAL DE CONTACTO RÁPIDO Y SOLICITUD -->
<div class="modal-backdrop" id="modalContacto">
  <div class="modal-container">
    <div class="modal-header">
      <div class="modal-icon">📲</div>
      <div>
        <h3 id="modalProfNombre">Contactar profesional</h3>
        <p style="font-size:0.82rem; opacity:0.85;" id="modalProfRubro">Especialidad</p>
      </div>
      <button class="modal-close-btn" onclick="cerrarModalContacto()">✕</button>
    </div>

    <div class="modal-body">
      <div style="display:flex; gap:10px; margin-bottom:1.5rem;">
        <a id="modalBtnWsp" href="#" target="_blank" class="btn-principal" style="background:#25D366; flex:1; text-align:center;">
          💬 WhatsApp
        </a>
        <a id="modalBtnLlamar" href="#" class="btn-secundario" style="flex:1; text-align:center;">
          📞 Llamar
        </a>
      </div>

      <div style="border-top:1px solid var(--borde); padding-top:1.2rem;">
        <h4 style="font-family:'Syne', sans-serif; font-size:0.96rem; color:var(--azul-oscuro); margin-bottom:8px;">
          O enviá una solicitud de presupuesto / trabajo:
        </h4>

        <form action="crear_solicitud.php" method="POST">
          <input type="hidden" name="profesional_id" id="modalProfIdInput" value="">
          <input type="hidden" name="redirect_url" value="inicio.php">

          <div class="campo">
            <label>Tu teléfono de contacto *</label>
            <input type="tel" name="telefono_contacto" placeholder="Ej: 223 555-1234" required>
          </div>

          <div class="campo">
            <label>Zona o dirección en Mar del Plata</label>
            <input type="text" name="zona" placeholder="Ej: Zona Güemes, Playa Grande, Centro">
          </div>

          <div class="campo">
            <label>¿Qué servicio necesitás? *</label>
            <textarea name="mensaje" rows="3" placeholder="Detallá el problema o trabajo que necesitás resolver..." required></textarea>
          </div>

          <button type="submit" class="btn-principal" style="margin-top:6px;">
            📨 Enviar solicitud al profesional
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="javascript.js"></script>
</body>
</html>
