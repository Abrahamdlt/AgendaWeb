// Cambio entre modo claro y oscuro. El tema inicial lo aplica el script en <head>.
(function () {
  var raiz = document.documentElement;
  var boton = document.getElementById("boton-tema");
  if (!boton) return;

  function actualizarBoton() {
    var claro = raiz.dataset.tema === "claro";
    boton.setAttribute("aria-label", claro ? "Cambiar a modo oscuro" : "Cambiar a modo claro");
    boton.title = boton.getAttribute("aria-label");
  }

  function cambiarTema() {
    raiz.dataset.tema = raiz.dataset.tema === "claro" ? "oscuro" : "claro";
    try { localStorage.setItem("tema", raiz.dataset.tema); } catch (e) {}
    actualizarBoton();
  }

  boton.addEventListener("click", function () {
    var sinMovimiento = matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (!document.startViewTransition || sinMovimiento) {
      cambiarTema();
      return;
    }

    // El nuevo tema se revela en un círculo que crece desde el botón
    var caja = boton.getBoundingClientRect();
    var x = caja.left + caja.width / 2;
    var y = caja.top + caja.height / 2;
    var radio = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));

    var transicion = document.startViewTransition(cambiarTema);
    transicion.ready.then(function () {
      raiz.animate(
        { clipPath: ["circle(0px at " + x + "px " + y + "px)",
                     "circle(" + radio + "px at " + x + "px " + y + "px)"] },
        { duration: 700, easing: "cubic-bezier(0.32, 0.72, 0, 1)",
          pseudoElement: "::view-transition-new(root)" }
      );
    });
  });

  actualizarBoton();
  boton.hidden = false;   // sin JS el botón no serviría, así que solo se muestra aquí
})();
