<?php
// ============================================================
// api/friends/respond_request.php
// Accepter ou refuser une demande d'amitié reçue.
// Méthode : POST
// Body JSON : { amitie_id, action }  où action = 'accepter' | 'refuser'
// ============================================================

ini_set('display_errors', 0);
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonError('Méthode non autorisée.', 405); }

$session  = requireAuth();
$userId   = $session['id'];
$body     = json_decode(file_get_contents('php://input'), true);
$amitieid = intval($body['amitie_id'] ?? 0);
$action   = $body['action'] ?? '';

if (!$amitieid || !in_array($action, ['accepter', 'refuser'])) {
    jsonError('Paramètres invalides.');
}

try {
    $pdo = Database::getInstance();

    // Vérifier que la demande existe et que c'est bien l'utilisateur connecté qui la reçoit
    $stmt = $pdo->prepare("
        SELECT id FROM amities
        WHERE id = ? AND ami_id = ? AND statut = 'en_attente'
    ");
    $stmt->execute([$amitieid, $userId]);
    if (!$stmt->fetch()) {
        jsonError('Demande introuvable ou déjà traitée.', 404);
    }

    if ($action === 'accepter') {
        $stmt = $pdo->prepare("UPDATE amities SET statut = 'acceptee' WHERE id = ?");
        $stmt->execute([$amitieid]);
        jsonSuccess([], 'Demande acceptée. Vous êtes maintenant amis !');
    } else {
        // Refuser = supprimer la ligne pour pouvoir renvoyer une demande plus tard
        $stmt = $pdo->prepare("DELETE FROM amities WHERE id = ?");
        $stmt->execute([$amitieid]);
        jsonSuccess([], 'Demande refusée.');
    }

} catch (PDOException $e) {
    error_log('Erreur DB respond_request : ' . $e->getMessage());
    jsonError('Erreur serveur.', 500);
}