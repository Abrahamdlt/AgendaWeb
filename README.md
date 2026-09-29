# AgendaWeb

Aplicación web para registrar y organizar eventos y citas: formulario de registro,
calendario mensual y edición / eliminación de registros guardados en MySQL.

Hecha con HTML, CSS y JavaScript (sin frameworks), PHP y MySQL.

## Requisitos

- PHP 8.1 o superior con la extensión `mysqli`
- MySQL 8 (o compatible)

> GitHub Pages solo sirve archivos estáticos: ahí se ve el diseño, pero el calendario
> y el formulario necesitan PHP y MySQL para funcionar.

## Cómo correrlo

1. Crea la base de datos con sus tablas y datos iniciales (funciona en MySQL 8 y MariaDB):

   ```bash
   mysql -u root -p -e "CREATE DATABASE agenda CHARACTER SET utf8mb4"
   mysql -u root -p agenda < bd/instalar_agenda.sql
   ```

2. Revisa el usuario, la contraseña y el nombre de la base en `conexion.php`.

3. Desde la carpeta del proyecto, levanta el servidor de PHP y abre http://localhost:8080

   ```bash
   php -S localhost:8080
   ```

## Estructura

| Archivo | Qué hace |
|---|---|
| `index.html` | Formulario para crear o editar un registro (`index.html?editar=ID`) y lista "Próximos" |
| `calendario.html` | Vista mensual con los registros de cada día |
| `conexion.php` | Conexión a MySQL |
| `datos.php` | Consultas a la base (leer, guardar, actualizar, eliminar) |
| `registros.php` | Devuelve los registros en JSON |
| `guardar.php` | Valida y guarda el formulario (crear o actualizar) |
| `eliminar.php` | Elimina un registro |
| `bd/instalar_agenda.sql` | Crea las tablas y los datos iniciales |
| `bd/respaldo_agenda_antes.sql`, `bd/actualizar_agenda.sql` | Base original y cómo se actualizó (historial) |
| `css/styles.css` | Estilos (sistema de marca "Pulso Neón", modo claro/oscuro) |
| `js/` | Calendario, formulario, lista "Próximos" y cambio de tema |
