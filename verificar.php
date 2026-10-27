<?php
// =========================================
// AgendaWeb · Confirmar el correo
// =========================================
// Aquí llega el enlace del correo: verificar.php?token=<código secreto>.
// Si el código existe y no ha vencido, la cuenta queda verificada y ya puede iniciar sesión.
require __DIR__ . '/datos.php';    // conexion.php y verificar_token()
require __DIR__ . '/ayudas.php';   // e(), recurso()

$token = $_GET['token'] ?? '';

// Un código válido son exactamente 64 caracteres hexadecimales (0-9, a-f)
if (is_string($token) && strlen($token) === 64 && ctype_xdigit($token) && verificar_token($token)) {
    header('Location: login.php?verificado=1', true, 303);
    exit;
}
// Si llega aquí: el enlace está mal copiado, ya se usó o venció
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AgendaWeb · Enlace no válido</title>

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
      <h1 class="titulo">Enlace no válido</h1>
      <p class="subtitulo">
        Este enlace ya se usó o venció (dura 24 horas). Si ya confirmaste tu correo, solo inicia sesión;
        si no, pide uno nuevo.
      </p>

      <div class="acciones">
        <a class="enlace" href="login.php">Iniciar sesión</a>
        <a class="boton boton--primario" href="reenviar.php">Enviar otro enlace</a>
      </div>
    </section>
  </main>

</body>
</html>
