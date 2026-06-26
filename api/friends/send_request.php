<?php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonError('Méthode non autorisée.', 405); }

$session = requireAuth();
$userId  = $session['id'];
$body    = json_decode(file_get_contents('php://input'), true);
$amiId   = intval($body['ami_id'] ?? 0);

if (!$amiId || $amiId === $userId) { jsonError('Identifiant utilisateur invalide.'); }

try {
    $pdo = Database::getInstance();

    $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE id = ?');
    $stmt->execute([$amiId]);
    if (!$stmt->fetch()) { jsonError('Utilisateur introuvable.', 404); }

    // Vérifier qu'une relation n'existe pas déjà dans les deux sens
    $stmt = $pdo->prepare("
        SELECT id, statut FROM amities
        WHERE (user_id = ? AND ami_id = ?) OR (user_id = ? AND ami_id = ?)
    ");
    $stmt->execute([$userId, $amiId, $amiId, $userId]);
    $relation = $stmt->fetch();

    if ($relation) {
        if ($relation['statut'] === 'acceptee')   { jsonError('Vous êtes déjà amis.'); }
        if ($relation['statut'] === 'en_attente') { jsonError('Une demande est déjà en cours.'); }
    }

    $stmt = $pdo->prepare("INSERT INTO amities (user_id, ami_id, statut) VALUES (?, ?, 'en_attente')");
    $stmt->execute([$userId, $amiId]);

    jsonSuccess(['amitie_id' => $pdo->lastInsertId()], 'Demande d\'amitié envoyée.');

} catch (PDOException $e) {
    error_log('Erreur DB send_request : ' . $e->getMessage());
    jsonError('Erreur serveur.', 500);
}