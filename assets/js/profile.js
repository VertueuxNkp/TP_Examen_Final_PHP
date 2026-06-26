// ============================================================
// assets/js/profile.js
// Gestion du profil personnel :
//   - Affichage des infos
//   - Modification des infos + avatar
//   - Changement du mot de passe
// ============================================================

// ============================================================
// SECTION 1 : Initialisation (appelée par router.js)
// ============================================================

async function initProfile() {
    await chargerProfil();
    initialiserPreviewAvatar();
}

// ============================================================
// SECTION 2 : Chargement et affichage du profil
// ============================================================

async function chargerProfil() {
    try {
        const reponse  = await fetch(CONFIG.API_URL + '/profile/get_profile.php', {
            credentials: 'include',
        });
        const resultat = await reponse.json();

        if (!resultat.success) {
            console.error('Erreur chargement profil :', resultat.message);
            return;
        }

        const profil = resultat.data;

        // --- Remplir la carte de profil (affichage) ---
        const avatarSrc = profil.avatar
            ? CONFIG.BASE_URL + '/assets/images/avatars/' + profil.avatar
            : CONFIG.BASE_URL + '/assets/images/default-avatar.png';

        document.getElementById('profil-avatar').src             = avatarSrc;
        document.getElementById('profil-nom-complet').textContent = `${profil.prenom} ${profil.nom}`;
        document.getElementById('profil-email').textContent      = profil.email;
        document.getElementById('profil-bio').textContent        = profil.bio || 'Aucune biographie renseignée.';
        document.getElementById('profil-date').textContent       = `Membre depuis le ${profil.date_inscription}`;
        document.getElementById('profil-nb-articles').textContent = profil.nb_articles;
        document.getElementById('profil-nb-amis').textContent    = profil.nb_amis;

        // --- Pré-remplir le formulaire de modification ---
        document.getElementById('input-nom').value    = profil.nom;
        document.getElementById('input-prenom').value = profil.prenom;
        document.getElementById('input-bio').value    = profil.bio || '';

        // --- Aperçu de l'avatar actuel dans le formulaire ---
        document.getElementById('avatar-actuel').src = avatarSrc;

    } catch (erreur) {
        console.error('Erreur chargerProfil :', erreur);
    }
}

// ============================================================
// SECTION 3 : Modification des informations personnelles
// ============================================================

async function sauvegarderProfil() {
    const btn = document.getElementById('btn-sauvegarder');
    cacherMessageProfil();

    const formData = new FormData();
    formData.append('nom',    document.getElementById('input-nom').value.trim());
    formData.append('prenom', document.getElementById('input-prenom').value.trim());
    formData.append('bio',    document.getElementById('input-bio').value.trim());

    const avatarInput = document.getElementById('input-avatar');
    if (avatarInput.files[0]) {
        formData.append('avatar', avatarInput.files[0]);
    }

    if (!formData.get('nom') || !formData.get('prenom')) {
        afficherMessageProfil('Le nom et le prénom sont obligatoires.', 'error');
        return;
    }

    btn.disabled    = true;
    btn.textContent = 'Sauvegarde...';

    try {
        const reponse  = await fetch(CONFIG.API_URL + '/profile/update_profile.php', {
            method:      'POST',
            credentials: 'include',
            body:        formData,
        });
        const resultat = await reponse.json();

        if (resultat.success) {
            afficherMessageProfil('Profil mis à jour avec succès !', 'success');

            // Mettre à jour le sessionStorage avec les nouvelles données
            const userActuel = getUtilisateurConnecte();
            sauvegarderSession({
                ...userActuel,
                nom:    resultat.data.nom,
                prenom: resultat.data.prenom,
                bio:    resultat.data.bio,
                avatar: resultat.data.avatar,
            });

            // Rafraîchir l'affichage du profil
            await chargerProfil();

        } else {
            afficherMessageProfil(resultat.message, 'error');
        }

    } catch (erreur) {
        afficherMessageProfil('Erreur réseau. Réessayez.', 'error');
        console.error('Erreur sauvegarderProfil :', erreur);
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Sauvegarder';
    }
}

// ============================================================
// SECTION 4 : Changement du mot de passe
// ============================================================

async function changerMotDePasse() {
    const btn = document.getElementById('btn-changer-mdp');
    cacherMessageMdp();

    const donnees = {
        ancien_mdp:      document.getElementById('input-ancien-mdp').value,
        nouveau_mdp:     document.getElementById('input-nouveau-mdp').value,
        confirmation_mdp: document.getElementById('input-confirm-mdp').value,
    };

    if (!donnees.ancien_mdp || !donnees.nouveau_mdp || !donnees.confirmation_mdp) {
        afficherMessageMdp('Tous les champs sont obligatoires.', 'error');
        return;
    }
    if (donnees.nouveau_mdp.length < 8) {
        afficherMessageMdp('Le nouveau mot de passe doit contenir au moins 8 caractères.', 'error');
        return;
    }
    if (donnees.nouveau_mdp !== donnees.confirmation_mdp) {
        afficherMessageMdp('Les mots de passe ne correspondent pas.', 'error');
        return;
    }

    btn.disabled    = true;
    btn.textContent = 'Modification...';

    try {
        const reponse  = await fetch(CONFIG.API_URL + '/profile/update_password.php', {
            method:      'POST',
            headers:     { 'Content-Type': 'application/json' },
            credentials: 'include',
            body:        JSON.stringify(donnees),
        });
        const resultat = await reponse.json();

        if (resultat.success) {
            afficherMessageMdp(resultat.message, 'success');
            // Vider les champs après succès
            document.getElementById('input-ancien-mdp').value   = '';
            document.getElementById('input-nouveau-mdp').value  = '';
            document.getElementById('input-confirm-mdp').value  = '';
        } else {
            afficherMessageMdp(resultat.message, 'error');
        }

    } catch (erreur) {
        afficherMessageMdp('Erreur réseau. Réessayez.', 'error');
        console.error('Erreur changerMotDePasse :', erreur);
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Changer le mot de passe';
    }
}

// ============================================================
// SECTION 5 : Prévisualisation de l'avatar avant upload
// ============================================================

function initialiserPreviewAvatar() {
    const input = document.getElementById('input-avatar');
    if (!input) return;

    input.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('avatar-actuel').src = e.target.result;
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
}

// ============================================================
// SECTION 6 : Utilitaires d'affichage des messages
// ============================================================

function afficherMessageProfil(message, type) {
    const el = document.getElementById('msg-profil');
    if (!el) return;
    el.textContent   = message;
    el.className     = `message message--${type}`;
    el.style.display = 'block';
}

function cacherMessageProfil() {
    const el = document.getElementById('msg-profil');
    if (el) el.style.display = 'none';
}

function afficherMessageMdp(message, type) {
    const el = document.getElementById('msg-mdp');
    if (!el) return;
    el.textContent   = message;
    el.className     = `message message--${type}`;
    el.style.display = 'block';
}

function cacherMessageMdp() {
    const el = document.getElementById('msg-mdp');
    if (el) el.style.display = 'none';
}