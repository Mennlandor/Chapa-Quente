<?php
session_start();

$token = $_POST['token'] ?? '';
$tokenDaSessao = $_SESSION['logout_token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($token) || !$tokenDaSessao || !hash_equals($tokenDaSessao, $token)) {
    http_response_code(403);
    exit('Solicitação inválida.');
}

$_SESSION = [];
session_regenerate_id(true);

header('Location: ../page/menu.php', true, 303);
exit;
