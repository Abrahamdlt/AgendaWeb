// Página Explorar (explorar.php · Práctica 11).
// Pide los eventos a api.php (P10) y los pinta sin recargar la página (AJAX).
// Cada búsqueda repite el mismo ciclo: 1 · pedir (fetch) → 2 · revisar (res.ok) → 3 · convertir (res.json()) → 4 · pintar (DOM)

// Los huecos de la página (los id de explorar.php)
const lista    = document.getElementById('lista');
const estado   = document.getElementById('estado');
const campoQ   = document.getElementById('q');
const campoCat = document.getElementById('categoria');

const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

// '2026-11-20' → '20/11/2026' (como formatearFecha() de index.php)
function formatearFecha(fecha) {
    const [a, m, d] = fecha.split('-');
    return `${d}/${m}/${a}`;
}

// Un elemento con su clase y su texto. textContent pone TEXTO, nunca HTML:
// es el e() de JavaScript (un título como <img onerror=...> se ve como letras, no se ejecuta)
function crear(etiqueta, clase, texto) {
    const el = document.createElement(etiqueta);
    el.className = clase;
    if (texto !== undefined) el.textContent = texto;
    return el;
}

// Crea UNA tarjeta a partir de UN evento del JSON, con las mismas clases que mostrarEvento() de index.php
function crearTarjeta(ev, orden) {
    const [, m, d] = ev.fecha.split('-');
    const tarjeta = crear('article', 'tarjeta tarjeta--registro');
    tarjeta.style.setProperty('--orden', orden);   // aparecen escalonadas

    // Cuadro con el día y el mes
    const cuadro = crear('div', 'registro__hora');
    cuadro.append(crear('span', 'registro__dia', String(Number(d))), crear('span', 'registro__mes', MESES[Number(m) - 1]));

    const info = crear('div', 'registro__info');
    info.append(crear('h2', 'registro__titulo', ev.titulo));

    const cuando = crear('p', 'registro__meta');
    const tiempo = crear('time', '', formatearFecha(ev.fecha) + (ev.hora ? ' · ' + ev.hora : ''));
    tiempo.dateTime = ev.fecha;
    cuando.append(tiempo);
    info.append(cuando);

    if (ev.descripcion) {                        // null o '' → no se pinta
        info.append(crear('p', 'registro__texto', ev.descripcion));
    }

    const datos = crear('p', 'registro__datos');
    datos.append(crear('span', 'insignia insignia--categoria', ev.categoria));
    info.append(datos);

    // Editar sigue siendo la página de siempre
    const acciones = crear('div', 'registro__acciones');
    const editar = crear('a', 'boton boton--secundario boton--chico', 'Editar');
    editar.href = 'registrar.php?editar=' + Number(ev.id);
    editar.setAttribute('aria-label', 'Editar «' + ev.titulo + '»');
    acciones.append(editar);
    info.append(acciones);

    tarjeta.append(cuadro, info);
    return tarjeta;
}

// 4 · Pintar: distingue "no tienes eventos" de "tu búsqueda no encontró nada" (como en P9)
function pintar(datos) {
    lista.replaceChildren();                     // borra lo que había antes
    const buscando = datos.filtros.q !== '' || datos.filtros.categoria !== 0;

    if (datos.total === 0) {
        estado.textContent = buscando ? 'Sin resultados para tu búsqueda.' : 'Aún no tienes eventos registrados.';
        return;
    }
    const s = datos.total === 1 ? '' : 's';
    estado.textContent = `${datos.total} evento${s} ` + (buscando ? `encontrado${s}` : `registrado${s}`);
    datos.eventos.forEach((ev, i) => lista.append(crearTarjeta(ev, i)));
}

// El ciclo completo. Nunca deja la pantalla en blanco: sin sesión, sin red o con error, lo dice.
let ultima = 0;   // número de la petición más reciente
async function cargarEventos() {
    // URLSearchParams arma "q=examen&categoria=3" y codifica acentos y símbolos (%, &, espacios)
    const params = new URLSearchParams({ q: campoQ.value.trim(), categoria: campoCat.value });
    const esta = ++ultima;
    estado.textContent = 'Cargando eventos…';

    try {
        const res = await fetch('api.php?' + params);           // 1 · pedir (viaja la cookie de sesión)
        if (esta !== ultima) return;                            // ya se pidió otra búsqueda: esta respuesta llegó tarde

        if (res.status === 401) {                               // 2 · revisar: fetch NO falla con un 401
            estado.innerHTML = 'Tu sesión terminó. <a class="enlace enlace--activo" href="login.php">Inicia sesión</a> de nuevo.';
            lista.replaceChildren();
            return;
        }
        if (!res.ok) {                                          // 500 u otro: se revisa ANTES de res.json()
            throw new Error('La API respondió ' + res.status);  // (un error de conexion.php es texto, no JSON)
        }

        const datos = await res.json();                         // 3 · convertir (JSON.parse: deserializar)
        pintar(datos);                                          // 4 · pintar
    } catch (error) {
        // Sin red, servidor caído o JSON roto
        console.error(error);
        estado.textContent = 'No se pudieron cargar los eventos. Intenta de nuevo más tarde.';
        lista.replaceChildren();
    }
}

// Buscar mientras escribes: espera 300 ms a que dejes de teclear antes de pedir (debounce)
let espera;
campoQ.addEventListener('input', () => {
    clearTimeout(espera);
    espera = setTimeout(cargarEventos, 300);
});
campoCat.addEventListener('change', cargarEventos);

// Si presionan Enter, que no se recargue la página
document.getElementById('filtros').addEventListener('submit', e => e.preventDefault());

cargarEventos();                                                // primera carga
