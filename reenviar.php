<?php
// =========================================
// AgendaWeb · Reenviar el enlace de confirmación
// =========================================
session_start();
if (isset($_SESSION['uid'])) {   // ya inició sesión: su correo ya está confirmado
    header('Location: index.php');
    exit;
}
require __DIR__ . '/datos.php';         // conexion.php, buscar_usuario(), nuevo_token()
require __DIR__ . '/validacion.php';    // texto()
require __DIR__ . '/ayudas.php';        // e(), recurso()
require __DIR__ . '/enviar_correo.php'; // enviar_verificacion()

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = mb_strtolower(texto($_POST, 'correo'));
    $usuario = buscar_usuario($correo);

    // Solo se envía si la cuenta existe y aún no está verificada.
    // nuevo_token() da null si ya se pidió uno hace menos de 1 minuto.
    if ($usuario && !$usuario['verificado']) {
        $token = nuevo_token((int) $usuario['id']);
        if ($token !== null) {
            enviar_verificacion($usuario['nombre'], $correo, $token);
        }
    }

    // Mismo mensaje pase lo que pase: así nadie averigua qué correos tienen cuenta (como en login.php)
    header('Location: reenviar.php?listo=1', true, 303);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AgendaWeb · Reenviar enlace</title>

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
      <h1 class="titulo">Reenviar enlace</h1>
      <p class="subtitulo">Escribe el correo con el que creaste tu cuenta y te mandamos un enlace nuevo.</p>

      <?php if (isset($_GET['listo'])): ?>
        <div class="aviso" role="status">
          <span>Si hay una cuenta sin confirmar con ese correo, ya va en camino un enlace nuevo. Revisa también la carpeta de spam.</span>
        </div>
      <?php endif; ?>

      <form class="formulario" action="reenviar.php" method="post">
        <div class="campo">
          <label class="etiqueta" for="correo">Correo</label>
          <input class="entrada" type="email" id="correo" name="correo" maxlength="120"
                 autocomplete="email" required>
        </div>

        <div class="acciones">
          <a class="enlace" href="login.php">Volver a iniciar sesión</a>
          <button type="submit" class="boton boton--primario">Enviar enlace</button>
        </div>
      </form>
    </section>
  </main>

</body>
</html>
