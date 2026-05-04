<?php
/**
 * medecin.php — Point d'entrée de l'espace médecin
 */
if (session_status() === PHP_SESSION_NONE) session_start();

$action = $_GET['action'] ?? 'connexion';
$ok     = ['inscription','connexion','dashboard','deconnexion','api_sous_services'];

if (!in_array($action, $ok)) {
    http_response_code(404);
    die('Page introuvable.');
}

require __DIR__ . '/controllers/MedecinController.php';
