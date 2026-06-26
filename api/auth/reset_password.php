<?php
// ============================================================
// api/auth/reset_password.php
// Enregistrement du nouveau mot de passe après clic sur le lien.
// Méthode : POST
// Body JSON : { token, nouveau_mdp, confirmation_mdp }
// ============================================================

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

$body            = json_decode(file_get_contents('php://input'), true);
$token           = trim($body['token'] ?? '');
$nouveauMdp      = $body['nouveau_mdp'] ?? '';
$confirmationMdp = $body['confirmation_mdp'] ?? '';

// --- Validations ---
if (empty($token)) {
    jsonError('Token manquant.');
}
if (strlen($nouveauMdp) < 8) {
    jsonError('Le mot de passe doit contenir au moins 8 caractères.');
}
if ($nouveauMdp !== $confirmationMdp) {
    jsonError('Les mots de passe ne correspondent pas.');
}

try {
    $pdo = Database::getInstance();

    // Vérifier le token et sa validité temporelle
    $stmt = $pdo->prepare("
        SELECT id FROM utilisateurs
        WHERE token_reset  = ?
          AND token_expire > NOW()
    ");
    $stmt->execute([$token]);
    $utilisateur = $stmt->fetch();

    if (!$utilisateur) {
        jsonError('Lien invalide ou expiré. Faites une nouvelle demande.');
    }

    // Hacher le nouveau mot de passe
    $hashNouveauMdp = password_hash($nouveauMdp, PASSWORD_DEFAULT);

    // Mettre à jour le mot de passe et effacer le token
    $stmt = $pdo->prepare("
        UPDATE utilisateurs
        SET mot_de_passe = ?,
            token_reset  = NULL,
            token_expire = NULL
        WHERE id = ?
    ");
    $stmt->execute([$hashNouveauMdp, $utilisateur['id']]);

    jsonSuccess([], 'Mot de passe réinitialisé avec succès. Vous pouvez vous connecter.');

} catch (PDOException $e) {
    error_log('Erreur DB reset_password : ' . $e->getMessage());
    jsonError('Erreur serveur. Veuillez réessayer.', 500);
}