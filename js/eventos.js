// Página Eventos (index.php): todos los registros pendientes, agrupados por día.
(function () {
  var lista = document.getElementById("eventos-lista");
  var resumen = document.getElementById("eventos-resumen");
  if (!lista) return;

  // Después de guardar o editar (PRG) se resalta ese registro en la lista
  var parametros = new URLSearchParams(location.search);
  var idResaltado = /^(creado|actualizado)$/.test(parametros.get("ok") || "") ? parametros.get("id") : null;

  function crearGrupo(fechaClave, registros, orden, alCambiar) {
    var fecha = Registros.desdeClave(fechaClave);
    var grupo = Registros.crear("section", "eventos__grupo");
    grupo.style.setProperty("--orden", orden);

    var titulo = Registros.crear("h2", "eventos__fecha");
    titulo.appendChild(Registros.crear("span", null, Registros.nombreFecha(fecha)));
    titulo.appendChild(Registros.crear("span", "eventos__cuando", Registros.cuando(fecha)));

    var tarjetas = Registros.crear("div", "lista-registros lista-registros--rejilla");
    Registros.pintarLista(tarjetas, registros, "", alCambiar);

    grupo.append(titulo, tarjetas);
    return grupo;
  }

  function pintar(registros, trasEliminar) {
    var pendientes = Registros.pendientes(registros);
    var total = pendientes.length;
    resumen.textContent = total === 0 ? "No tienes eventos pendientes."
      : total === 1 ? "Tienes 1 evento pendiente." : "Tienes " + total + " eventos pendientes.";

    if (!total) {
      lista.replaceChildren(Registros.crearVacia("Cuando agregues un evento o una cita, aparecerá aquí."));
    } else {
      // Agrupa los registros consecutivos del mismo día (ya vienen ordenados por fecha y hora)
      var grupos = [];
      pendientes.forEach(function (r) {
        var ultimo = grupos[grupos.length - 1];
        if (!ultimo || ultimo.fecha !== r.fecha) grupos.push(ultimo = { fecha: r.fecha, registros: [] });
        ultimo.registros.push(r);
      });
      var fragmento = document.createDocumentFragment();
      grupos.forEach(function (g, i) {
        fragmento.appendChild(crearGrupo(g.fecha, g.registros, i, recargarTrasEliminar));
      });
      lista.replaceChildren(fragmento);
    }

    if (idResaltado && !trasEliminar) {
      var tarjeta = lista.querySelector('[data-id="' + idResaltado + '"]');
      if (tarjeta) {
        tarjeta.classList.add("tarjeta--resaltada");
        tarjeta.scrollIntoView({ block: "nearest" });
      }
    }
    // Al eliminar, la tarjeta (y su botón con el foco) desaparece: el foco pasa al resumen,
    // que además anuncia cuántos quedan
    if (trasEliminar) resumen.focus();
  }

  function cargar(trasEliminar) {
    return Registros.cargar()
      .then(function (registros) { pintar(registros, trasEliminar); })
      .catch(function (error) {
        console.error(error);
        lista.replaceChildren(Registros.crearVacia(
          "No se pudieron cargar los eventos. Abre la página desde el servidor PHP."));
      });
  }

  function recargarTrasEliminar() {
    return cargar(true);
  }

  cargar(false);
})();
