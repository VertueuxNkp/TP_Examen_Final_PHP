<?php
// ============================================================
// api/profile/get_profile.php
// Retourne les informations du profil de l'utilisateur connecté.
// Méthode : GET
// ============================================================

ini_set('display_errors', 0);
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$session = requireAuth();
$userId  = $session['id'];

try {
    $pdo  = Database::getInstance();
    $stmt = $pdo->prepare("
        SELECT
            id, nom, prenom, email, avatar, bio, created_at,
            (SELECT COUNT(*) FROM articles WHERE user_id = u.id) AS nb_articles,
            (SELECT COUNT(*) FROM amities
             WHERE (user_id = u.id OR ami_id = u.id)
             AND statut = 'acceptee') AS nb_amis
        FROM utilisateurs u
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $profil = $stmt->fetch();

    if (!$profil) {
        jsonError('Utilisateur introuvable.', 404);
    }

    $profil['date_inscription'] = (new DateTime($profil['created_at']))->format('d/m/Y');
    unset($profil['created_at']); // Ne pas exposer la date brute

    jsonSuccess($profil, 'Profil chargé.');

} catch (PDOException $e) {
    error_log('Erreur DB get_profile : ' . $e->getMessage());
    jsonError('Erreur serveur.', 500);
}