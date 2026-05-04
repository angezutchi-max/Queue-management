<?php
/**
 * gestionnaire.php — Point d'entrée espace gestionnaire
 */
if (session_status() === PHP_SESSION_NONE) session_start();

$action = $_GET['action'] ?? 'connexion';
$ok     = ['inscription','connexion','dashboard','deconnexion'];

if (!in_array($action, $ok)) {
    http_response_code(404);
    die('Page introuvable.');
}

require __DIR__ . '/controllers/GestionnaireController.php';
