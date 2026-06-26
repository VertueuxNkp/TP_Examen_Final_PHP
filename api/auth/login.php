<?php
// ============================================================
// api/auth/login.php
// Connexion d'un utilisateur.
// Démarre une session PHP + retourne les données pour sessionStorage JS.
// Méthode : POST
// Body JSON : { email, mot_de_passe }
// ============================================================

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

// --- Lire le corps JSON ---
$body = json_decode(file_get_contents('php://input'), true);

$email      = trim(strtolower($body['email'] ?? ''));
$motDePasse = $body['mot_de_passe'] ?? '';

if (empty($email) || empty($motDePasse)) {
    jsonError('Email et mot de passe requis.');
}

try {
    $pdo = Database::getInstance();

    // Récupérer l'utilisateur par email
    $stmt = $pdo->prepare("
        SELECT id, nom, prenom, email, mot_de_passe, avatar, bio, email_verifie
        FROM utilisateurs
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $utilisateur = $stmt->fetch();

    // --- Vérifier que l'utilisateur existe ET que le mot de passe correspond ---
    // password_verify() compare le mot de passe saisi avec le hash en base
    if (!$utilisateur || !password_verify($motDePasse, $utilisateur['mot_de_passe'])) {
        // Message volontairement vague : ne pas indiquer si c'est l'email ou le mdp qui est faux
        jsonError('Identifiants incorrects.');
    }

    // --- Vérifier que l'email est confirmé ---
    if (!$utilisateur['email_verifie']) {
        jsonError('Compte non activé. Vérifiez votre boîte email.');
    }

    ini_set('session.gc_maxlifetime', 28800);
    session_set_cookie_params(28800);

    // --- Démarrer la session PHP (côté serveur) ---
    // Protège contre la fixation de session
    session_start();
    session_regenerate_id(true);

    $_SESSION['user_id']    = $utilisateur['id'];
    $_SESSION['user_email'] = $utilisateur['email'];

    // --- Retourner les données utiles pour le sessionStorage JS ---
    // On ne retourne JAMAIS le mot de passe hashé
    $donneesPourJS = [
        'id'     => $utilisateur['id'],
        'nom'    => $utilisateur['nom'],
        'prenom' => $utilisateur['prenom'],
        'email'  => $utilisateur['email'],
        'avatar' => $utilisateur['avatar'],
        'bio'    => $utilisateur['bio'],
    ];

    jsonSuccess($donneesPourJS, 'Connexion réussie !');

} catch (PDOException $e) {
    error_log('Erreur DB login : ' . $e->getMessage());
    jsonError('Erreur serveur. Veuillez réessayer.', 500);
}