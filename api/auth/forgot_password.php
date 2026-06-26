<?php
// ============================================================
// api/auth/forgot_password.php
// Demande de réinitialisation du mot de passe.
// Génère un token, l'enregistre en base et envoie un email HTML.
// Méthode : POST
// Body JSON : { email }
// ============================================================

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mailer.php';
require_once __DIR__ . '/../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

$body  = json_decode(file_get_contents('php://input'), true);
$email = trim(strtolower($body['email'] ?? ''));

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonError('Adresse email invalide.');
}

try {
    $pdo = Database::getInstance();

    // Chercher l'utilisateur
    $stmt = $pdo->prepare('SELECT id, prenom FROM utilisateurs WHERE email = ?');
    $stmt->execute([$email]);
    $utilisateur = $stmt->fetch();

    // Sécurité : toujours répondre "succès" même si l'email n'existe pas.
    // Cela évite l'énumération d'emails (attaque qui vérifie quels emails sont inscrits).
    if (!$utilisateur) {
        jsonSuccess([], 'Si cet email est enregistré, un lien de réinitialisation vous a été envoyé.');
    }

    // --- Générer un token sécurisé valable 1 heure ---
    $token = bin2hex(random_bytes(32));

    $stmt = $pdo->prepare("
        UPDATE utilisateurs
        SET token_reset  = ?,
            token_expire = DATE_ADD(NOW(), INTERVAL 1 HOUR)
        WHERE id = ?
    ");
    $stmt->execute([$token, $utilisateur['id']]);

    // --- Construire le lien de reset ---
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'];
    $dossierProjet = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    $lienReset     = "{$protocol}://{$host}{$dossierProjet}/index.html?auth=reset_password&token={$token}";

    // --- Envoyer l'email ---
    $corpsEmail = templateResetPassword($utilisateur['prenom'], $lienReset);
    envoyerEmail($email, 'Réinitialisation de votre mot de passe', $corpsEmail);

    jsonSuccess([], 'Si cet email est enregistré, un lien de réinitialisation vous a été envoyé.');

} catch (PDOException $e) {
    error_log('Erreur DB forgot_password : ' . $e->getMessage());
    jsonError('Erreur serveur. Veuillez réessayer.', 500);
}