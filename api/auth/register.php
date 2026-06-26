<?php
// ============================================================
// api/auth/register.php
// Inscription d'un nouvel utilisateur.
// Envoi d'un email HTML de confirmation.
// Méthode : POST
// Body JSON : { nom, prenom, email, mot_de_passe, confirmation_mdp }
// ============================================================
ini_set('display_errors', 0);  // ← empêche le Warning de s'afficher dans la réponse
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');            // À restreindre en production
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mailer.php';
require_once __DIR__ . '/../helpers/response.php';

// --- Accepter uniquement POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

// --- Lire et décoder le JSON envoyé par fetch() ---
$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    jsonError('Corps de la requête invalide.');
}

// --- Récupérer et nettoyer les champs ---
$nom             = trim($body['nom'] ?? '');
$prenom          = trim($body['prenom'] ?? '');
$email           = trim(strtolower($body['email'] ?? ''));
$motDePasse      = $body['mot_de_passe'] ?? '';
$confirmationMdp = $body['confirmation_mdp'] ?? '';

// --- Validations ---
if (empty($nom) || empty($prenom) || empty($email) || empty($motDePasse)) {
    jsonError('Tous les champs sont obligatoires.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonError('Adresse email invalide.');
}
if (strlen($motDePasse) < 8) {
    jsonError('Le mot de passe doit contenir au moins 8 caractères.');
}
if ($motDePasse !== $confirmationMdp) {
    jsonError('Les mots de passe ne correspondent pas.');
}

try {
    $pdo = Database::getInstance();

    // --- Vérifier si l'email est déjà utilisé ---
    $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonError('Cette adresse email est déjà utilisée.');
    }

    // --- Hacher le mot de passe (jamais en clair en base) ---
    $hashMotDePasse = password_hash($motDePasse, PASSWORD_DEFAULT);

    // --- Générer un token de vérification email ---
    // bin2hex(random_bytes(32)) produit une chaîne hexadécimale de 64 caractères aléatoires
    $tokenVerification = bin2hex(random_bytes(32));

    // --- Insérer l'utilisateur en base ---
    $stmt = $pdo->prepare("
        INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, token_reset, token_expire)
        VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))
    ");
    // On réutilise token_reset pour stocker le token de vérification email
    $stmt->execute([$nom, $prenom, $email, $hashMotDePasse, $tokenVerification]);
    $newId = $pdo->lastInsertId();
    //$pdo->prepare("UPDATE utilisateurs SET email_verifie = 1 WHERE id = ?")->execute([$newId]);

    // --- Construire le lien de vérification ---
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'];
    $dossierProjet    = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
$lienVerification = "{$protocol}://{$host}{$dossierProjet}/api/auth/verify_email.php?token={$tokenVerification}";

    // --- Envoyer l'email de confirmation ---
    $corpsEmail = templateVerificationEmail($prenom, $lienVerification);
    $emailEnvoye = envoyerEmail($email, 'Confirmez votre adresse email', $corpsEmail);

    if (!$emailEnvoye) {
        // En dev (XAMPP sans serveur mail), l'envoi peut échouer.
        // On inscrit quand même l'utilisateur et on log l'erreur.
        error_log("Échec envoi email de vérification pour : {$email}");
    }

    jsonSuccess([], 'Inscription réussie ! Vérifiez votre boîte email pour activer votre compte.');

} catch (PDOException $e) {
    error_log('Erreur DB register : ' . $e->getMessage());
    jsonError('Erreur serveur. Veuillez réessayer.', 500);
}