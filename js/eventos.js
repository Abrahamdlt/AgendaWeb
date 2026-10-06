// Página Eventos (index.php). Las tarjetas las genera PHP con mostrarEvento();
// esto solo agrega mejoras que no son indispensables:
//  - pedir confirmación antes de eliminar (sin JavaScript el formulario funciona igual)
//  - resaltar el registro recién guardado después de la redirección
(function () {
  // ---------- Confirmar antes de eliminar ----------
  document.addEventListener("submit", function (e) {
    var formulario = e.target.closest(".registro__borrar");
    if (!formulario) return;
    e.preventDefault();
    Registros.confirmar("¿Eliminar este registro?",
                        "«" + formulario.dataset.titulo + "» se borrará de tu agenda. Esta acción no se puede deshacer.")
      .then(function (confirmado) {
        // submit() envía el formulario sin volver a pasar por este evento
        if (confirmado) formulario.submit();
      });
  });

  // ---------- Resaltar el registro recién guardado (index.php?ok=creado&id=5) ----------
  var parametros = new URLSearchParams(location.search);
  var id = parametros.get("id");
  if (/^(creado|actualizado)$/.test(parametros.get("ok") || "") && /^\d+$/.test(id || "")) {
    var tarjeta = document.querySelector('.tarjeta--registro[data-id="' + id + '"]');
    if (tarjeta) {
      tarjeta.classList.add("tarjeta--resaltada");
      tarjeta.scrollIntoView({ block: "nearest" });
    }
  }
})();
