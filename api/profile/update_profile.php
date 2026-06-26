<?php
// ============================================================
// api/profile/update_profile.php
// Modification des informations personnelles + photo de profil.
// Méthode : POST (multipart/form-data à cause de l'upload avatar)
// Body : nom, prenom, bio, avatar (fichier optionnel)
// ============================================================

ini_set('display_errors', 0);
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

$session = requireAuth();
$userId  = $session['id'];

$nom    = trim($_POST['nom']    ?? '');
$prenom = trim($_POST['prenom'] ?? '');
$bio    = trim($_POST['bio']    ?? '');

if (empty($nom) || empty($prenom)) {
    jsonError('Le nom et le prénom sont obligatoires.');
}

try {
    $pdo = Database::getInstance();

    // --- Récupérer l'avatar actuel pour pouvoir supprimer l'ancien fichier ---
    $stmt = $pdo->prepare('SELECT avatar FROM utilisateurs WHERE id = ?');
    $stmt->execute([$userId]);
    $utilisateur   = $stmt->fetch();
    $ancienAvatar  = $utilisateur['avatar'];
    $nouvelAvatar  = $ancienAvatar; // Par défaut on garde l'ancien

    // --- Gestion de l'upload du nouvel avatar ---
    if (!empty($_FILES['avatar']['name'])) {
        $fichier    = $_FILES['avatar'];
        $extension  = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
        $typesAutorisés = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($extension, $typesAutorisés)) {
            jsonError('Format d\'image non supporté. Utilisez JPG, PNG, GIF ou WEBP.');
        }
        if ($fichier['size'] > 2 * 1024 * 1024) { // 2 Mo max pour un avatar
            jsonError('L\'avatar ne doit pas dépasser 2 Mo.');
        }

        $dossierUpload = __DIR__ . '/../../assets/images/avatars/';
        if (!is_dir($dossierUpload)) {
            mkdir($dossierUpload, 0755, true);
        }

        $nouvelAvatar = uniqid('avatar_', true) . '.' . $extension;

        if (!move_uploaded_file($fichier['tmp_name'], $dossierUpload . $nouvelAvatar)) {
            jsonError('Erreur lors de l\'upload de l\'avatar.');
        }

        // Supprimer l'ancien avatar (sauf si c'est le défaut)
        if ($ancienAvatar && $ancienAvatar !== 'default-avatar.png') {
            $cheminAncien = $dossierUpload . $ancienAvatar;
            if (file_exists($cheminAncien)) {
                unlink($cheminAncien);
            }
        }
    }

    // --- Mettre à jour en base ---
    $stmt = $pdo->prepare("
        UPDATE utilisateurs
        SET nom = ?, prenom = ?, bio = ?, avatar = ?
        WHERE id = ?
    ");
    $stmt->execute([$nom, $prenom, $bio, $nouvelAvatar, $userId]);

    // Retourner les données mises à jour pour refresher le sessionStorage côté JS
    jsonSuccess([
        'nom'    => $nom,
        'prenom' => $prenom,
        'bio'    => $bio,
        'avatar' => $nouvelAvatar,
    ], 'Profil mis à jour avec succès.');

} catch (PDOException $e) {
    error_log('Erreur DB update_profile : ' . $e->getMessage());
    jsonError('Erreur serveur.', 500);
}