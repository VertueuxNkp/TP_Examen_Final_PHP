<?php
// ============================================================
// api/friends/get_friends.php
// Retourne la liste des amis acceptés ET les demandes reçues
// en attente pour l'utilisateur connecté.
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
    $pdo = Database::getInstance();

    // --- Liste des amis acceptés ---
    $stmt = $pdo->prepare("
        SELECT
            u.id, u.nom, u.prenom, u.avatar, u.bio,
            'ami' AS relation,
            a.id  AS amitie_id
        FROM amities a
        JOIN utilisateurs u
            ON u.id = IF(a.user_id = :u1, a.ami_id, a.user_id)
        WHERE (a.user_id = :u2 OR a.ami_id = :u3)
          AND a.statut = 'acceptee'
        ORDER BY u.prenom ASC
    ");
    $stmt->execute([':u1' => $userId, ':u2' => $userId, ':u3' => $userId]);
    $amis = $stmt->fetchAll();

    // --- Demandes reçues en attente ---
    $stmt = $pdo->prepare("
        SELECT
            u.id, u.nom, u.prenom, u.avatar, u.bio,
            'demande_recue' AS relation,
            a.id AS amitie_id
        FROM amities a
        JOIN utilisateurs u ON u.id = a.user_id
        WHERE a.ami_id = ? AND a.statut = 'en_attente'
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$userId]);
    $demandesRecues = $stmt->fetchAll();

    jsonSuccess([
        'amis'           => $amis,
        'demandes_recues' => $demandesRecues,
    ], 'Données chargées.');

} catch (PDOException $e) {
    error_log('Erreur DB get_friends : ' . $e->getMessage());
    jsonError('Erreur serveur.', 500);
}