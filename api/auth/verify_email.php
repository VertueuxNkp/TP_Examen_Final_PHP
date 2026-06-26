<?php
// ============================================================
// api/auth/verify_email.php
// Activation du compte après clic sur le lien email.
// Méthode : GET  (?token=xxxx)
// Redirige vers index.html après vérification.
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    // Rediriger vers la page de connexion avec un message d'erreur
    header('Location: /index.html?auth=token_invalide');
    exit;
}

try {
    $pdo = Database::getInstance();

    // Chercher l'utilisateur dont le token correspond et n'est pas expiré
    $stmt = $pdo->prepare("
        SELECT id, email_verifie
        FROM utilisateurs
        WHERE token_reset = ?
          AND token_expire > NOW()
    ");
    $stmt->execute([$token]);
    $utilisateur = $stmt->fetch();

    if (!$utilisateur) {
        // Token invalide ou expiré
        header('Location: /index.html?auth=token_expire');
        exit;
    }

    if ($utilisateur['email_verifie'] === 1) {
        // Compte déjà activé, rediriger vers connexion
        header('Location: /index.html?auth=deja_verifie');
        exit;
    }

    // Activer le compte et effacer le token de vérification
    $stmt = $pdo->prepare("
        UPDATE utilisateurs
        SET email_verifie = 1,
            token_reset   = NULL,
            token_expire  = NULL
        WHERE id = ?
    ");
    $stmt->execute([$utilisateur['id']]);

    // Rediriger vers la page de connexion avec message de succès
    header('Location: ../../index.html?auth=compte_active');
    exit;

} catch (PDOException $e) {
    error_log('Erreur DB verify_email : ' . $e->getMessage());
    header('Location: /index.html?auth=erreur_serveur');
    exit;
}