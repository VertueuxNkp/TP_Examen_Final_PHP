<?php
// ============================================================
// api/profile/update_password.php
// Modification du mot de passe de l'utilisateur connecté.
// Vérifie l'ancien mot de passe avant d'autoriser le changement.
// Méthode : POST
// Body JSON : { ancien_mdp, nouveau_mdp, confirmation_mdp }
// ============================================================

ini_set('display_errors', 0);
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

$session = requireAuth();
$userId  = $session['id'];

$body           = json_decode(file_get_contents('php://input'), true);
$ancienMdp      = $body['ancien_mdp']      ?? '';
$nouveauMdp     = $body['nouveau_mdp']     ?? '';
$confirmationMdp = $body['confirmation_mdp'] ?? '';

// --- Validations ---
if (empty($ancienMdp) || empty($nouveauMdp) || empty($confirmationMdp)) {
    jsonError('Tous les champs sont obligatoires.');
}
if (strlen($nouveauMdp) < 8) {
    jsonError('Le nouveau mot de passe doit contenir au moins 8 caractères.');
}
if ($nouveauMdp !== $confirmationMdp) {
    jsonError('Les nouveaux mots de passe ne correspondent pas.');
}
if ($ancienMdp === $nouveauMdp) {
    jsonError('Le nouveau mot de passe doit être différent de l\'ancien.');
}

try {
    $pdo = Database::getInstance();

    // Récupérer le mot de passe actuel hashé
    $stmt = $pdo->prepare('SELECT mot_de_passe FROM utilisateurs WHERE id = ?');
    $stmt->execute([$userId]);
    $utilisateur = $stmt->fetch();

    // Vérifier que l'ancien mot de passe est correct
    if (!password_verify($ancienMdp, $utilisateur['mot_de_passe'])) {
        jsonError('L\'ancien mot de passe est incorrect.');
    }

    // Hacher et enregistrer le nouveau mot de passe
    $hashNouveauMdp = password_hash($nouveauMdp, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?');
    $stmt->execute([$hashNouveauMdp, $userId]);

    jsonSuccess([], 'Mot de passe modifié avec succès.');

} catch (PDOException $e) {
    error_log('Erreur DB update_password : ' . $e->getMessage());
    jsonError('Erreur serveur.', 500);
}