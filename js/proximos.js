// Lista "Próximos" de la página del formulario: los siguientes registros desde hoy.
var Proximos = (function () {
  var CUANTOS = 3;
  var lista = document.getElementById("proximos-lista");

  function actualizar() {
    if (!lista) return;
    Registros.cargar()
      .then(function (registros) {
        var siguientes = Registros.pendientes(registros).slice(0, CUANTOS);
        Registros.pintarLista(lista, siguientes, "No hay registros próximos.", actualizar);
      })
      .catch(function (error) {
        console.error(error);
        lista.replaceChildren(Registros.crearVacia(
          "No se pudieron cargar los registros. Recarga la página para intentarlo de nuevo."));
      });
  }

  actualizar();
  return { actualizar: actualizar };   // el formulario la vuelve a llamar después de guardar
})();
