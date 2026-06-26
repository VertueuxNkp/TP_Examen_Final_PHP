<?php
// ============================================================
// api/friends/get_users.php
// Retourne tous les utilisateurs avec leur statut d'amitié
// par rapport à l'utilisateur connecté.
//
// Statuts possibles retournés :
//   - 'aucun'        → pas de relation
//   - 'en_attente_envoye'   → demande envoyée par moi, en attente
//   - 'en_attente_recu'     → demande reçue de cet utilisateur
//   - 'acceptee'     → on est amis
//
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
        u.id,
        u.nom,
        u.prenom,
        u.avatar,
        u.bio,

        CASE
            WHEN a.statut = 'acceptee'                        THEN 'ami'
            WHEN a.statut = 'en_attente' AND a.user_id = ?   THEN 'demande_envoyee'
            WHEN a.statut = 'en_attente' AND a.ami_id  = ?   THEN 'demande_recue'
            ELSE NULL
        END AS relation,

        a.id AS amitie_id

    FROM utilisateurs u
    LEFT JOIN amities a
        ON  (a.user_id = u.id AND a.ami_id  = ?)
        OR  (a.ami_id  = u.id AND a.user_id = ?)

    WHERE u.id != ?
    ORDER BY u.prenom ASC, u.nom ASC
");

$stmt->execute([$userId, $userId, $userId, $userId, $userId]);
    $utilisateurs = $stmt->fetchAll();

    jsonSuccess($utilisateurs, 'Utilisateurs chargés.');

} catch (PDOException $e) {
    error_log('Erreur DB get_users : ' . $e->getMessage());
    jsonError('Erreur serveur.', 500);
}