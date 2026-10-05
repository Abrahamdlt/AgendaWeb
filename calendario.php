<?php
// Calendario mensual. Es .php solo para pedir el CSS y el JS con su versión (ver ayudas.php).
require __DIR__ . '/ayudas.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AgendaWeb · Calendario</title>

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
  <script src="<?= e(recurso('js/calendario.js')) ?>" defer></script>
</head>
<body>

  <header class="barra">
    <a href="index.php" class="marca">Agenda<span>Web</span></a>
    <nav class="barra__nav">
      <a href="index.php" class="enlace">Eventos</a>
      <a href="calendario.php" class="enlace enlace--activo" aria-current="page">Calendario</a>
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

  <main class="contenedor contenedor--ancho">

    <div class="contenedor__columna">

      <!-- Vista mensual -->
      <section class="tarjeta tarjeta--principal calendario">
        <div class="calendario__cabecera">
          <h1 class="titulo" id="calendario-mes" aria-live="polite">Calendario</h1>
          <div class="calendario__controles">
            <button type="button" class="boton boton--secundario" id="calendario-hoy">Hoy</button>
            <button type="button" class="boton boton--icono" id="calendario-anterior" aria-label="Mes anterior">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                   stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M15 18l-6-6 6-6"/>
              </svg>
            </button>
            <button type="button" class="boton boton--icono" id="calendario-siguiente" aria-label="Mes siguiente">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                   stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M9 18l6-6-6-6"/>
              </svg>
            </button>
          </div>
        </div>
        <p class="subtitulo">Selecciona un día para ver sus registros abajo. También puedes moverte con las flechas del teclado.</p>

        <div class="calendario__semana" aria-hidden="true">
          <span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span><span>Dom</span>
        </div>
        <div class="calendario__dias" id="calendario-dias" role="group" aria-labelledby="calendario-mes"></div>

        <div class="calendario__leyenda">
          <span><span class="dia__punto"></span> Con registros</span>
          <span><span class="dia__punto dia__punto--alta"></span> Prioridad alta</span>
        </div>
      </section>

      <!-- Registros del día seleccionado -->
      <section class="detalle-dia" aria-labelledby="detalle-titulo">
        <div class="detalle-dia__cabecera">
          <h2 class="titulo titulo--chico" id="detalle-titulo">Día</h2>
          <a class="boton boton--secundario boton--chico" id="detalle-agregar" href="registrar.php">Agregar registro este día</a>
        </div>
        <div class="lista-registros lista-registros--rejilla" id="detalle-lista"></div>
      </section>

    </div>

    <!-- Solo el evento más cercano -->
    <aside class="lateral proximo" aria-labelledby="proximo-titulo">
      <h2 class="titulo titulo--chico" id="proximo-titulo">Próximo evento</h2>
      <p class="proximo__cuando" id="proximo-cuando"></p>
      <div class="lista-registros" id="proximo-lista"></div>
      <div class="proximo__enlaces">
        <a class="enlace" id="proximo-ver-dia" href="calendario.php" hidden>Ver ese día</a>
        <a class="enlace" href="index.php">Ver todos los eventos</a>
      </div>
    </aside>

  </main>

</body>
</html>
