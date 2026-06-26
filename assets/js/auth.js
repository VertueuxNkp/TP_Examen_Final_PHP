// ============================================================
// assets/js/auth.js
// Gestion de l'authentification côté client :
// - Inscription, Connexion, Déconnexion
// - Mot de passe oublié / Réinitialisation
// - sessionStorage pour persister les données utilisateur
// ============================================================

// --- Clé utilisée pour stocker l'utilisateur dans sessionStorage ---
const SESSION_KEY = 'user';

// ============================================================
// SECTION 1 : Utilitaires sessionStorage
// ============================================================

/**
 * Sauvegarde les données utilisateur dans sessionStorage après connexion.
 * @param {Object} utilisateur - Données renvoyées par login.php
 */
function sauvegarderSession(utilisateur) {
    sessionStorage.setItem(SESSION_KEY, JSON.stringify(utilisateur));
}

/**
 * Récupère l'utilisateur connecté depuis sessionStorage.
 * @returns {Object|null} - Les données utilisateur, ou null si non connecté
 */
function getUtilisateurConnecte() {
    const data = sessionStorage.getItem(SESSION_KEY);
    return data ? JSON.parse(data) : null;
}

/**
 * Supprime la session côté client (utilisé à la déconnexion).
 */
function supprimerSession() {
    sessionStorage.removeItem(SESSION_KEY);
}

/**
 * Vérifie si un utilisateur est connecté.
 * @returns {boolean}
 */
function estConnecte() {
    return getUtilisateurConnecte() !== null;
}

// ============================================================
// SECTION 2 : Affichage des messages (succès / erreur)
// ============================================================

/**
 * Affiche un message dans un élément HTML.
 * @param {string} elementId  - ID de l'élément HTML cible
 * @param {string} message    - Texte à afficher
 * @param {string} type       - 'success' ou 'error'
 */
function afficherMessage(elementId, message, type = 'error') {
    const el = document.getElementById(elementId);
    if (!el) return;
    el.textContent    = message;
    el.className      = `message message--${type}`;
    el.style.display  = 'block';
}

function cacherMessage(elementId) {
    const el = document.getElementById(elementId);
    if (el) el.style.display = 'none';
}

// ============================================================
// SECTION 3 : Inscription
// ============================================================

/**
 * Gère la soumission du formulaire d'inscription.
 * Appelé par le bouton "S'inscrire" dans register.html.
 */
async function soumettreInscription() {
    const btnSubmit = document.getElementById('btn-inscription');
    cacherMessage('msg-inscription');

    // Récupérer les valeurs du formulaire
    const donnees = {
        nom:              document.getElementById('nom').value.trim(),
        prenom:           document.getElementById('prenom').value.trim(),
        email:            document.getElementById('email').value.trim(),
        mot_de_passe:     document.getElementById('mot_de_passe').value,
        confirmation_mdp: document.getElementById('confirmation_mdp').value,
    };

    // Validation minimale côté client avant d'envoyer la requête
    if (!donnees.nom || !donnees.prenom || !donnees.email || !donnees.mot_de_passe) {
        afficherMessage('msg-inscription', 'Veuillez remplir tous les champs.');
        return;
    }
    if (donnees.mot_de_passe !== donnees.confirmation_mdp) {
        afficherMessage('msg-inscription', 'Les mots de passe ne correspondent pas.');
        return;
    }
    if (donnees.mot_de_passe.length < 8) {
        afficherMessage('msg-inscription', 'Le mot de passe doit contenir au moins 8 caractères.');
        return;
    }

    // Désactiver le bouton pendant la requête
    btnSubmit.disabled   = true;
    btnSubmit.textContent = 'Inscription en cours...';

    try {
        const reponse = await fetch(CONFIG.API_URL + '/auth/register.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(donnees),
        });

        const resultat = await reponse.json();

        if (resultat.success) {
            afficherMessage('msg-inscription', resultat.message, 'success');
            // Vider le formulaire après succès
            //document.getElementById('form-inscription').reset();
        } else {
            afficherMessage('msg-inscription', resultat.message);
        }

    } catch (erreur) {
        afficherMessage('msg-inscription', 'Erreur réseau. Vérifiez votre connexion.');
        console.error('Erreur inscription :', erreur);
    } finally {
        // Réactiver le bouton dans tous les cas
        btnSubmit.disabled    = false;
        btnSubmit.textContent = 'S\'inscrire';
    }
}

// ============================================================
// SECTION 4 : Connexion
// ============================================================

/**
 * Gère la soumission du formulaire de connexion.
 * Après succès, sauvegarde dans sessionStorage et redirige vers le fil.
 */
async function soumettreConnexion() {
    const btnSubmit = document.getElementById('btn-connexion');
    cacherMessage('msg-connexion');

    const donnees = {
        email:       document.getElementById('email').value.trim(),
        mot_de_passe: document.getElementById('mot_de_passe').value,
    };

    if (!donnees.email || !donnees.mot_de_passe) {
        afficherMessage('msg-connexion', 'Veuillez remplir tous les champs.');
        return;
    }

    btnSubmit.disabled    = true;
    btnSubmit.textContent = 'Connexion en cours...';

    try {
        const reponse = await fetch(CONFIG.API_URL + '/auth/login.php', {
            method:      'POST',
            headers:     { 'Content-Type': 'application/json' },
            body:        JSON.stringify(donnees),
            credentials: 'include', // Important : envoie le cookie de session PHP avec la requête
        });

        const resultat = await reponse.json();

        if (resultat.success) {
            // ✅ Sauvegarder les données utilisateur dans sessionStorage
            sauvegarderSession(resultat.data);

            // Rediriger vers le fil d'actualité sans rechargement de page
            // Le router.js gère cette navigation
            if (typeof naviguerVers === 'function') {
                naviguerVers('feed');
            } else {
                // Fallback si le router n'est pas encore chargé
                window.location.href = '/index.html#feed';
            }
        } else {
            afficherMessage('msg-connexion', resultat.message);
        }

    } catch (erreur) {
        afficherMessage('msg-connexion', 'Erreur réseau. Vérifiez votre connexion.');
        console.error('Erreur connexion :', erreur);
    } finally {
        btnSubmit.disabled    = false;
        btnSubmit.textContent = 'Se connecter';
    }
}

// ============================================================
// SECTION 5 : Déconnexion
// ============================================================

/**
 * Déconnecte l'utilisateur :
 * 1. Appelle logout.php pour détruire la session PHP
 * 2. Supprime sessionStorage côté JS
 * 3. Redirige vers la page de connexion
 */
async function seDeconnecter() {
    try {
        await fetch('/reseau_social/api/auth/logout.php', {
            method:      'POST',
            credentials: 'include',
        });
    } catch (erreur) {
        console.error('Erreur lors de la déconnexion serveur :', erreur);
    } finally {
        // Dans tous les cas, on nettoie le côté client
        supprimerSession();
        if (typeof naviguerVers === 'function') {
            naviguerVers('login');
        } else {
            window.location.href = '/index.html';
        }
    }
}

// ============================================================
// SECTION 6 : Mot de passe oublié
// ============================================================

async function soumettreMdpOublie() {
    const btnSubmit = document.getElementById('btn-mdp-oublie');
    cacherMessage('msg-mdp-oublie');

    const email = document.getElementById('email').value.trim();

    if (!email) {
        afficherMessage('msg-mdp-oublie', 'Veuillez saisir votre adresse email.');
        return;
    }

    btnSubmit.disabled    = true;
    btnSubmit.textContent = 'Envoi en cours...';

    try {
        const reponse = await fetch(CONFIG.API_URL + '/auth/forgot_password.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ email }),
        });

        const resultat = await reponse.json();

        // On affiche toujours le message de succès (même si l'email n'existe pas)
        // Pour ne pas révéler quels emails sont inscrits
        afficherMessage('msg-mdp-oublie', resultat.message, 'success');
        document.getElementById('email').value = '';

    } catch (erreur) {
        afficherMessage('msg-mdp-oublie', 'Erreur réseau. Vérifiez votre connexion.');
    } finally {
        btnSubmit.disabled    = false;
        btnSubmit.textContent = 'Envoyer le lien';
    }
}

// ============================================================
// SECTION 7 : Réinitialisation du mot de passe
// ============================================================

/**
 * Récupère le token depuis l'URL (?token=xxx) et le soumet avec le nouveau mdp.
 */
async function soumettreResetPassword() {
    const btnSubmit = document.getElementById('btn-reset');
    cacherMessage('msg-reset');

    // Lire le token dans les paramètres de l'URL
    const params = new URLSearchParams(window.location.search);
    const token  = params.get('token');

    if (!token) {
        afficherMessage('msg-reset', 'Lien invalide. Refaites une demande de réinitialisation.');
        return;
    }

    const donnees = {
        token,
        nouveau_mdp:      document.getElementById('nouveau_mdp').value,
        confirmation_mdp: document.getElementById('confirmation_mdp').value,
    };

    if (donnees.nouveau_mdp.length < 8) {
        afficherMessage('msg-reset', 'Le mot de passe doit contenir au moins 8 caractères.');
        return;
    }
    if (donnees.nouveau_mdp !== donnees.confirmation_mdp) {
        afficherMessage('msg-reset', 'Les mots de passe ne correspondent pas.');
        return;
    }

    btnSubmit.disabled    = true;
    btnSubmit.textContent = 'Enregistrement...';

    try {
        const reponse = await fetch(CONFIG.API_URL + '/auth/reset_password.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(donnees),
        });

        const resultat = await reponse.json();

        if (resultat.success) {
            afficherMessage('msg-reset', resultat.message, 'success');
            // Rediriger vers la connexion après 2 secondes
            setTimeout(() => naviguerVers('login'), 2000);
        } else {
            afficherMessage('msg-reset', resultat.message);
        }

    } catch (erreur) {
        afficherMessage('msg-reset', 'Erreur réseau. Vérifiez votre connexion.');
    } finally {
        btnSubmit.disabled    = false;
        btnSubmit.textContent = 'Réinitialiser';
    }
}

// ============================================================
// SECTION 8 : Vérification des messages au chargement
// (ex: ?auth=compte_active après clic sur lien email)
// ============================================================

/**
 * Détecte les paramètres ?auth=xxx dans l'URL et affiche le bon message.
 * À appeler au chargement de index.html.
 */
function verifierParamsAuth() {
    const params  = new URLSearchParams(window.location.search);
    const authMsg = params.get('auth');

    const messages = {
        compte_active:  { texte: '✅ Compte activé avec succès ! Vous pouvez vous connecter.', type: 'success' },
        token_expire:   { texte: '⏱️ Lien expiré. Réinscrivez-vous ou refaites la demande.', type: 'error' },
        token_invalide: { texte: '❌ Lien invalide.', type: 'error' },
        deja_verifie:   { texte: 'ℹ️ Ce compte est déjà activé.', type: 'success' },
        erreur_serveur: { texte: '❌ Erreur serveur. Réessayez plus tard.', type: 'error' },
    };

    if (authMsg && messages[authMsg]) {
        const { texte, type } = messages[authMsg];
        afficherMessage('msg-connexion', texte, type);
    }
}