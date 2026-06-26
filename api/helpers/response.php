<?php
// ============================================================
// api/helpers/response.php
// Fonctions utilitaires pour les réponses JSON standardisées
// Toute réponse de l'API suit ce format, succès ou erreur.
// ============================================================

/**
 * Réponse JSON de succès.
 *
 * Format : { "success": true, "message": "...", "data": {...} }
 */
function jsonSuccess(array $data = [], string $message = 'Succès'): void {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Réponse JSON d'erreur.
 *
 * Format : { "success": false, "message": "..." }
 *
 * @param int $code  Code HTTP : 400 (mauvaise requête), 401 (non auth), 403 (interdit), 404 (introuvable)
 */
function jsonError(string $message, int $code = 400): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}