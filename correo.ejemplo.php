<?php
// Plantilla de correo.php (el archivo real no se sube a GitHub: está en .gitignore).
// Cópiala como correo.php y pon los datos de la cuenta que enviará los correos.
//
// Con Gmail: activa la verificación en 2 pasos y crea una "contraseña de aplicación"
// en https://myaccount.google.com/apppasswords. Esa va en 'clave', NO tu contraseña normal.
//
// Si 'servidor' se deja vacío, los correos no se envían: se guardan como .html en la carpeta
// correos/ (sirve para probar en tu computadora sin cuenta de correo).
return [
    'servidor'  => 'smtp.gmail.com',
    'puerto'    => 465,                       // 465 = cifrado desde el inicio; 587 = STARTTLS
    'usuario'   => 'tu.correo@gmail.com',
    'clave'     => 'TU_CONTRASEÑA_DE_APLICACION',
    'remitente' => 'AgendaWeb',               // nombre que ve quien recibe el correo
    'url_sitio' => 'http://localhost:8080',   // dirección de la página, para armar el enlace
];
