<?php
// =========================================
// AgendaWeb · El guardia: solo pasa quien inició sesión
// =========================================
// Va como PRIMERA línea de cada página privada:  require __DIR__ . '/partes/sesion.php';
session_start();

if (!isset($_SESSION['uid'])) {
    // Al JavaScript (calendario, "Próximos") se le responde JSON; a una página, se la manda al login
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Inicia sesión.']);
    } else {
        header('Location: login.php');
    }
    exit;
}

// Quién está usando la app
$usuarioId     = (int) $_SESSION['uid'];
$usuarioNombre = $_SESSION['nombre'] ?? '';
