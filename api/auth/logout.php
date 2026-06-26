<?php
// ============================================================
// api/auth/logout.php
// Déconnexion : détruit la session PHP.
// Le JS supprimera sessionStorage de son côté.
// Méthode : POST
// ============================================================

header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../helpers/response.php';

session_start();
session_unset();    // Vide les variables de session
session_destroy();  // Détruit la session côté serveur

jsonSuccess([], 'Déconnecté avec succès.');