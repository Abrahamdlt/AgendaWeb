<?php
// =========================================
// AgendaWeb · Eventos (página principal)
// =========================================
// Lista los eventos pendientes (los pinta js/eventos.js desde registros.php).
// También es el destino de la redirección PRG de registrar.php:
//   index.php?ok=creado&id=5      index.php?ok=actualizado&id=5      index.php?ok=eliminado
// y muestra el mensaje de éxito correspondiente.
require __DIR__ . '/datos.php';
require __DIR__ . '/ayudas.php';

$aviso = null;   // ['texto' => ..., 'enlace' => ['texto' => ..., 'href' => ...]]
$ok = $_GET['ok'] ?? '';

if ($ok === 'creado' || $ok === 'actualizado') {
    $id = numero_de_url('id');
    $registro = $id !== null ? obtener_registro($id) : null;
    if ($registro !== null) {
        $aviso = [
            'texto' => $ok === 'creado'
                ? 'Se guardó «' . $registro['titulo'] . '».'
                : 'Se guardaron los cambios de «' . $registro['titulo'] . '».',
            'enlace' => ['texto' => 'Ver en el calendario', 'href' => 'calendario.php?fecha=' . $registro['fecha']],
        ];
    } else {
        $aviso = ['texto' => $ok === 'creado' ? 'Se guardó el registro.' : 'Se guardaron los cambios.'];
    }
} elseif ($ok === 'eliminado') {
    $aviso = ['texto' => 'Se eliminó el registro.'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AgendaWeb · Eventos</title>

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
  <script src="<?= e(recurso('js/registros.js')) ?>" defer></script>
  <script src="<?= e(recurso('js/eventos.js')) ?>" defer></script>
</head>
<body>

  <header class="barra">
    <a href="index.php" class="marca">Agenda<span>Web</span></a>
    <nav class="barra__nav">
      <a href="index.php" class="enlace enlace--activo" aria-current="page">Eventos</a>
      <a href="calendario.php" class="enlace">Calendario</a>
      <a href="registrar.php" class="enlace">Nuevo registro</a>
    </nav>
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

  <main class="contenedor contenedor--sencillo">

    <section class="eventos" aria-labelledby="eventos-titulo">
      <div class="eventos__cabecera">
        <div>
          <h1 class="titulo" id="eventos-titulo">Eventos</h1>
          <p class="subtitulo" id="eventos-resumen" tabindex="-1">Tus eventos y citas pendientes.</p>
        </div>
        <a class="boton boton--primario" href="registrar.php">Nuevo registro</a>
      </div>

      <?php if ($aviso): ?>
        <!-- Mensaje de éxito después de la redirección (patrón PRG) -->
        <div class="aviso" role="status">
          <span><?= e($aviso['texto']) ?></span>
          <?php if (!empty($aviso['enlace'])): ?>
            <a class="enlace enlace--activo" href="<?= e($aviso['enlace']['href']) ?>"><?= e($aviso['enlace']['texto']) ?></a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Pendientes agrupados por día (los pinta js/eventos.js) -->
      <div class="eventos__lista" id="eventos-lista">
        <div class="tarjeta tarjeta--vacia"><p>Cargando eventos…</p></div>
      </div>
    </section>

  </main>

</body>
</html>
