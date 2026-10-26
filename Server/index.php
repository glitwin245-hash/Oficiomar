<?php
include 'conexion.php';

// Si ya hay sesión activa o auto-login mediante token persistente, redirigir directo al panel
if (isset($_SESSION['id']) && isset($_SESSION['rol'])) {
    if ($_SESSION['rol'] === 'turista') {
        header('Location: inicio.php');
        exit;
    } elseif ($_SESSION['rol'] === 'profesional') {
        header('Location: inicio2.php');
        exit;
    } elseif ($_SESSION['rol'] === 'admin') {
        header('Location: admin.php');
        exit;
    }
}

$error = isset($_GET['error']) ? true : false;
$registro_ok = isset($_GET['registro']) && $_GET['registro'] === 'ok';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OficioMar – Iniciar Sesión</title>
  <link rel="stylesheet" href="estilo.css">
  <style>
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--fondo);
      padding: 1.5rem;
    }
  </style>
</head>
<body>

  <div class="auth-container">
    <div class="auth-card">
      
      <!-- LOGO PEQUEÑO ARRIBA -->
      <div class="auth-logo-header">
        <a href="index.php" class="auth-brand">
          <span class="brand-wave">🌊</span>
          <span class="brand-title">Oficio<span>Mar</span></span>
        </a>
        <p class="auth-tagline">Servicios de oficio en Mar del Plata</p>
      </div>

      <!-- TÍTULO DEL FORMULARIO -->
      <div class="auth-header-text">
        <h2>Iniciar sesión</h2>
        <p>Ingresá tus datos para acceder a tu panel</p>
      </div>

      <!-- MENSAJES DE ESTADO -->
      <?php if ($error): ?>
        <div class="alerta-error show">
          ⚠️ Correo electrónico o contraseña incorrectos.
        </div>
      <?php endif; ?>

      <?php if ($registro_ok): ?>
        <div class="alerta-ok show">
          ✅ ¡Cuenta creada exitosamente! Ya podés ingresar.
        </div>
      <?php endif; ?>

      <!-- FORMULARIO LIMPIO Y CENTRADO -->
      <form action="login.php" method="POST" class="auth-form" autocomplete="on">
        <div class="campo">
          <label for="email">Correo electrónico</label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            placeholder="nombre@ejemplo.com" 
            required 
            autofocus
          >
        </div>

        <div class="campo">
          <label for="contrasena">Contraseña</label>
          <input 
            type="password" 
            id="contrasena" 
            name="contrasena" 
            placeholder="••••••••" 
            required
          >
        </div>

        <button type="submit" class="btn-principal auth-btn">
          Ingresar a OficioMar →
        </button>
      </form>

      <!-- ENLACE A REGISTRO -->
      <div class="auth-footer">
        <span>¿No tenés una cuenta todavía?</span>
        <a href="registro.html" class="auth-link">Registrate acá</a>
      </div>

    </div>
  </div>

</body>
</html>
