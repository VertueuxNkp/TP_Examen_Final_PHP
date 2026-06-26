// ============================================================
// assets/js/router.js
// Gestion de la navigation sans rechargement de page.
//
// Principe :
//   1. Chaque "page" correspond à un fichier HTML dans /vues/
//   2. naviguerVers('feed') charge /vues/clients/feed.html
//      et l'injecte dans <div id="app">
//   3. Les routes protégées redirigent vers 'login' si non connecté
//   4. Le script JS spécifique à chaque vue est chargé à la demande
// ============================================================

// ============================================================
// SECTION 1 : Définition des routes
// ============================================================

const ROUTES = {

    // --- Routes publiques (accessibles sans être connecté) ---
    'login': {
        vue:       CONFIG.VUES_CLIENTS + 'login.html',
        script:    null,            // auth.js est déjà chargé globalement
        protegee:  false,
    },
    'register': {
        vue:       CONFIG.VUES_CLIENTS + 'register.html',
        script:    null,
        protegee:  false,
    },
    'forgot-password': {
        vue:       CONFIG.VUES_CLIENTS + 'forgot-password.html',
        script:    null,
        protegee:  false,
    },
    'reset-password': {
        vue:       CONFIG.VUES_CLIENTS + 'reset-password.html',
        script:    null,
        protegee:  false,
    },

    // --- Routes protégées (nécessitent d'être connecté) ---
    'feed': {
        vue:       CONFIG.VUES_CLIENTS + 'feed.html',
        script:    CONFIG.BASE_URL + '/assets/js/feed.js',
        protegee:  true,
    },
    'friends': {
        vue:       CONFIG.VUES_CLIENTS + 'friends.html',
        script:    CONFIG.BASE_URL + '/assets/js/friends.js',
        protegee:  true,
    },
    'profile': {
        vue:       CONFIG.VUES_CLIENTS + 'profile.html',
        script:    CONFIG.BASE_URL + '/assets/js/profile.js',
        protegee:  true,
    },
    'chat': {
        vue:       CONFIG.VUES_CLIENTS + 'chat.html',
        script:    CONFIG.BASE_URL + '/assets/js/chat.js',
        protegee:  true,
    },
};

// Route affichée par défaut si aucune route n'est trouvée
const ROUTE_PAR_DEFAUT_CONNECTE  = 'feed';
const ROUTE_PAR_DEFAUT_DECONNECTE = 'login';

// ============================================================
// SECTION 2 : Chargement dynamique d'une vue HTML
// ============================================================

/**
 * Charge un fichier HTML via fetch() et l'injecte dans #app.
 * @param {string} cheminVue - Chemin vers le fichier HTML à charger
 */
async function chargerVue(cheminVue) {
    const app = document.getElementById('app');

    try {
        const reponse = await fetch(cheminVue);

        if (!reponse.ok) {
            app.innerHTML = `
                <div style="text-align:center; margin-top:60px;">
                    <h2>Page introuvable</h2>
                    <p>La vue demandée n'existe pas.</p>
                    <a href="#" onclick="naviguerVers('${ROUTE_PAR_DEFAUT_CONNECTE}'); return false;">
                        Retour à l'accueil
                    </a>
                </div>`;
            return;
        }

        // Injecter le contenu HTML dans #app
        app.innerHTML = await reponse.text();

        // Faire défiler vers le haut à chaque changement de vue
        window.scrollTo(0, 0);

    } catch (erreur) {
        console.error('Erreur chargement vue :', erreur);
        app.innerHTML = `
            <div style="text-align:center; margin-top:60px;">
                <h2>Erreur de chargement</h2>
                <p>Impossible de charger la page. Vérifiez votre connexion.</p>
            </div>`;
    }
}

// ============================================================
// SECTION 3 : Chargement dynamique d'un script JS
// ============================================================

// Mémorise les scripts déjà chargés pour ne pas les dupliquer
const scriptsCharges = new Set();

/**
 * Charge un fichier JS à la demande et l'exécute une seule fois.
 * @param {string} cheminScript - Chemin vers le fichier JS
 * @returns {Promise} - Résolu quand le script est prêt
 */
function chargerScript(cheminScript) {
    return new Promise((resolve, reject) => {

        // Si le script est déjà chargé, on résout immédiatement
        if (scriptsCharges.has(cheminScript)) {
            resolve();
            return;
        }

        const script  = document.createElement('script');
        script.src    = cheminScript;
        script.onload = () => {
            scriptsCharges.add(cheminScript);
            resolve();
        };
        script.onerror = () => reject(new Error(`Impossible de charger : ${cheminScript}`));
        document.body.appendChild(script);
    });
}

// ============================================================
// SECTION 4 : Fonction principale de navigation
// ============================================================

/**
 * Navigue vers une route donnée sans recharger la page.
 * C'est la fonction centrale appelée partout dans l'application.
 *
 * @param {string} nomRoute  - Nom de la route (ex: 'feed', 'login', 'profile')
 * @param {Object} params    - Paramètres optionnels passés à la vue (ex: { userId: 5 })
 */
async function naviguerVers(nomRoute, params = {}) {

    // ✅ Arrêter le polling chat si on quitte la vue chat
    if (typeof arreterPolling === 'function') arreterPolling();
    
    // --- Récupérer la config de la route ---
    const route = ROUTES[nomRoute];

    if (!route) {
        console.warn(`Route inconnue : "${nomRoute}". Redirection vers l'accueil.`);
        naviguerVers(ROUTE_PAR_DEFAUT_CONNECTE);
        return;
    }

    // --- Vérification d'accès pour les routes protégées ---
    if (route.protegee && !estConnecte()) {
        console.warn(`Route "${nomRoute}" protégée. Redirection vers login.`);
        naviguerVers(ROUTE_PAR_DEFAUT_DECONNECTE);
        return;
    }

    // --- Mettre à jour l'URL du navigateur (sans rechargement) ---
    // L'utilisateur peut copier l'URL ou utiliser les boutons Précédent/Suivant
    history.pushState({ route: nomRoute, params }, '', `#${nomRoute}`);

    // --- Charger la vue HTML ---
    await chargerVue(route.vue);

    // --- Charger le script JS associé à la vue (si défini) ---
    if (route.script) {
        try {
            await chargerScript(route.script);
        } catch (erreur) {
            console.error(`Erreur chargement script pour "${nomRoute}" :`, erreur);
        }
    }

    // --- Exécuter l'initialisation de la vue si elle existe ---
    // Chaque module JS peut définir une fonction init<NomRoute>()
    // Ex : feed.js définit initFeed(), profile.js définit initProfile()...
    const nomFonctionInit = 'init' + nomRoute.charAt(0).toUpperCase() + nomRoute.slice(1);
    if (typeof window[nomFonctionInit] === 'function') {
        window[nomFonctionInit](params);
    }
}

// ============================================================
// SECTION 5 : Gestion des boutons Précédent / Suivant
// ============================================================

/**
 * Intercepte les clics sur Précédent/Suivant du navigateur
 * pour naviguer dans l'historique sans rechargement.
 */
window.addEventListener('popstate', (event) => {
    if (event.state && event.state.route) {
        naviguerVers(event.state.route, event.state.params || {});
    } else {
        // Aucun état sauvegardé : retourner à la route par défaut
        const routeDefaut = estConnecte()
            ? ROUTE_PAR_DEFAUT_CONNECTE
            : ROUTE_PAR_DEFAUT_DECONNECTE;
        naviguerVers(routeDefaut);
    }
});

// ============================================================
// SECTION 6 : Utilitaire — afficher/masquer le mot de passe
// ============================================================

/**
 * Bascule la visibilité d'un champ mot de passe.
 * Appelé depuis les boutons 👁️ dans les vues HTML.
 *
 * @param {string} champId  - ID du champ input
 * @param {HTMLElement} btn - Le bouton cliqué (pour changer son icône)
 */
function toggleMotDePasse(champId, btn) {
    const champ = document.getElementById(champId);
    if (!champ) return;

    if (champ.type === 'password') {
        champ.type  = 'text';
        btn.textContent = '🙈';
    } else {
        champ.type  = 'password';
        btn.textContent = '👁️';
    }
}

// ============================================================
// SECTION 7 : Démarrage de l'application
// ============================================================

/**
 * Détermine la route initiale au chargement de index.html.
 *
 * Priorités :
 *  1. ?auth=reset_password dans l'URL → vue reset-password
 *  2. #nomRoute dans l'URL → route correspondante
 *  3. Utilisateur connecté → feed
 *  4. Sinon → login
 */
function demarrerApplication() {
    const params  = new URLSearchParams(window.location.search);
    const authMsg = params.get('auth');
    const hash    = window.location.hash.replace('#', '');

    // Cas 1 : lien de réinitialisation de mot de passe
    if (authMsg === 'reset_password') {
        naviguerVers('reset-password');
        return;
    }

    // Cas 2 : une route est dans le hash de l'URL
    if (hash && ROUTES[hash]) {
        naviguerVers(hash);
        return;
    }

    // Cas 3 & 4 : selon l'état de connexion
    if (estConnecte()) {
        naviguerVers(ROUTE_PAR_DEFAUT_CONNECTE);
    } else {
        naviguerVers(ROUTE_PAR_DEFAUT_DECONNECTE);
    }
}

// Lancer l'application dès que le DOM est prêt
document.addEventListener('DOMContentLoaded', demarrerApplication);