<?php
// ============================================================
// api/helpers/auth_check.php
// Vérification de l'authentification sur les endpoints protégés.
// À inclure en tête de tout endpoint qui nécessite une connexion.
// ============================================================

require_once __DIR__ . '/response.php';

/**
 * Vérifie que l'utilisateur (client) est connecté.
 * Bloque la requête avec une erreur 401 sinon.
 *
 * @return array  Données de session : ['id', 'email']
 */
function requireAuth(): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        jsonError('Non authentifié. Veuillez vous connecter.', 401);
    }
    return [
        'id'    => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
    ];
}

/**
 * Vérifie que l'administrateur est connecté (back-office).
 * Vérifie aussi le rôle si précisé.
 *
 * @param string|null $roleRequis  'admin' ou 'moderateur' (null = tout rôle accepté)
 * @return array  Données de session : ['id', 'role']
 */
function requireAdminAuth(?string $roleRequis = null): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['admin_id'])) {
        jsonError('Accès refusé. Connectez-vous au back-office.', 403);
    }
    // Un admin a accès à tout, un modérateur seulement à son rôle
    if ($roleRequis === 'admin' && $_SESSION['admin_role'] !== 'admin') {
        jsonError('Permissions insuffisantes. Réservé aux administrateurs.', 403);
    }
    return [
        'id'   => $_SESSION['admin_id'],
        'role' => $_SESSION['admin_role'],
    ];
}