<?php
// =========================================
// AgendaWeb · Iniciar sesión (Práctica 8)
// =========================================
session_start();
if (isset($_SESSION['uid'])) {   // ya inició sesión
    header('Location: index.php');
    exit;
}
require __DIR__ . '/datos.php';        // conexion.php y buscar_usuario()
require __DIR__ . '/validacion.php';   // texto()
require __DIR__ . '/ayudas.php';       // e(), recurso()

$correo = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = mb_strtolower(texto($_POST, 'correo'));
    $clave  = is_string($_POST['clave'] ?? null) ? $_POST['clave'] : '';

    // 1 · Buscar al usuario por su correo (null si no existe)
    $usuario = buscar_usuario($correo);

    // 2 · ¿Existe y la contraseña coincide con su hash?
    if ($usuario && password_verify($clave, $usuario['clave_hash'])) {
        session_regenerate_id(true);                 // id de sesión nuevo al entrar
        $_SESSION['uid']    = (int) $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        header('Location: index.php', true, 303);
        exit;
    }

    // 3 · Mismo mensaje en los dos casos: no revelamos si el correo existe
    $error = 'Correo o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AgendaWeb · Iniciar sesión</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&family=Source+Serif+4:opsz,wght@8..60,500;8..60,600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= e(recurso('css/styles.css')) ?>">

  <!-- Aplica el tema guardado antes de pintar, para evitar un parpadeo -->
  <script>
    (function () {
      var tema = null;
      try { tema = localStorage.getItem("tema"); } catch (e) {}
      if (!tema) tema = matchMedia("(prefers-color-scheme: light)").matches ? "claro" : "oscuro";
      document.documentElement.dataset.tema = tema;
    })();
  </script>
  <script src="<?= e(recurso('js/tema.js')) ?>" defer></script>
</head>
<body>

  <header class="barra">
    <a href="login.php" class="marca">Agenda<span>Web</span></a>
    <button type="button" class="boton boton--icono" id="boton-tema"
            aria-label="Cambiar a modo claro" hidden>
      <svg class="icono-luna" viewBox="0 0 24 24" fill="none" stroke="currentColor"
           stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>
      </svg>
      <svg class="icono-sol" viewBox="0 0 24 24" fill="none" stroke="currentColor"
           stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="4"/>
        <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>
      </svg>
    </button>
  </header>

  <main class="contenedor contenedor--acceso">
    <section class="tarjeta tarjeta--principal">
      <h1 class="titulo">Iniciar sesión</h1>
      <p class="subtitulo">Entra para ver y organizar tus eventos.</p>

      <?php if (isset($_GET['registrado'])): ?>
        <div class="aviso" role="status"><span>Cuenta creada. Ya puedes iniciar sesión.</span></div>
      <?php endif; ?>
      <?php if (isset($_GET['salio'])): ?>
        <div class="aviso" role="status"><span>Cerraste sesión. ¡Hasta pronto!</span></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="aviso aviso--error" role="alert"><span><?= e($error) ?></span></div>
      <?php endif; ?>

      <form class="formulario" action="login.php" method="post">
        <div class="campo">
          <label class="etiqueta" for="correo">Correo</label>
          <input class="entrada" type="email" id="correo" name="correo" maxlength="120"
                 value="<?= e($correo) ?>" autocomplete="email" required>
        </div>

        <div class="campo">
          <label class="etiqueta" for="clave">Contraseña</label>
          <input class="entrada" type="password" id="clave" name="clave"
                 autocomplete="current-password" required>
        </div>

        <div class="acciones">
          <a class="enlace" href="registro.php">Crear cuenta</a>
          <button type="submit" class="boton boton--primario">Entrar</button>
        </div>
      </form>
    </section>
  </main>

</body>
</html>
