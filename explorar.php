<?php
require __DIR__ . '/partes/sesion.php';   // página: sin sesión → login (aquí sí va el guardia de siempre)
// =========================================
// AgendaWeb · Explorar (Práctica 11)
// =========================================
// La página llega VACÍA: no consulta eventos ni tiene foreach. js/explorar.js le pide los datos
// a api.php (P10) con fetch y pinta las tarjetas en el navegador. Mientras escribes, filtra
// sin recargar la página (AJAX).
require __DIR__ . '/datos.php';        // conexion.php ($conexion)
require __DIR__ . '/ayudas.php';       // e(), recurso()

// Las categorías del filtro: son pocas y no cambian, las pone PHP
$categorias = $conexion->query('SELECT id, nombre FROM categoria ORDER BY nombre')->fetch_all(MYSQLI_ASSOC);
$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AgendaWeb · Explorar</title>

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
    <a href="index.php" class="marca">Agenda<span>Web</span></a>
    <nav class="barra__nav">
      <a href="index.php" class="enlace">Eventos</a>
      <a href="explorar.php" class="enlace enlace--activo" aria-current="page">Explorar</a>
      <a href="calendario.php" class="enlace">Calendario</a>
      <a href="registrar.php" class="enlace">Nuevo registro</a>
    </nav>
    <div class="barra__usuario">
      <span class="barra__saludo">Hola, <?= e($usuarioNombre) ?></span>
      <a href="logout.php" class="enlace">Salir</a>
    </div>
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

    <section class="eventos" aria-labelledby="explorar-titulo">
      <!-- Tres "huecos" que llena JavaScript: #estado, los filtros y #lista -->
      <div class="eventos__cabecera">
        <div>
          <h1 class="titulo" id="explorar-titulo">Explorar</h1>
          <p class="subtitulo" id="estado" role="status">Cargando eventos…</p>
        </div>
      </div>

      <!-- Sin botón "Buscar": filtra mientras escribes -->
      <form class="buscador buscador--en-vivo" id="filtros" role="search">
        <div class="campo">
          <label class="etiqueta" for="q">Buscar</label>
          <input class="entrada" type="search" id="q" maxlength="100" placeholder="Escribe para buscar…">
        </div>
        <div class="campo">
          <label class="etiqueta" for="categoria">Categoría</label>
          <select class="entrada" id="categoria">
            <option value="0">Todas</option>
            <?php foreach ($categorias as $cat): ?>
              <option value="<?= (int) $cat['id'] ?>"><?= e($cat['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>

      <section class="lista-registros lista-registros--rejilla" id="lista" aria-label="Eventos" aria-live="polite"></section>
    </section>

  </main>

  <!-- Al final: cuando el navegador lee el script, #lista y #estado ya existen -->
  <script src="<?= e(recurso('js/explorar.js')) ?>"></script>
</body>
</html>
