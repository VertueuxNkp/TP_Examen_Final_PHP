// assets/js/config.js
const CONFIG = {

    // Détecte automatiquement le chemin de base du projet
    // Ex : http://localhost/reseau-social/  →  BASE_URL = '/reseau-social'
    // Ex : http://localhost/                →  BASE_URL = ''
    BASE_URL: window.location.pathname.replace(/\/index\.html$/, '').replace(/\/$/, ''),

    API_URL:          '',   // sera construit dynamiquement
    POLLING_INTERVAL: 3000,
    VUES_CLIENTS:     '',   // idem
    VUES_BACKOFFICE:  '',   // idem
};

// Construire les chemins à partir du BASE_URL
CONFIG.API_URL          = CONFIG.BASE_URL + '/api';
CONFIG.VUES_CLIENTS     = CONFIG.BASE_URL + '/vues/clients/';
CONFIG.VUES_BACKOFFICE  = CONFIG.BASE_URL + '/vues/back-office/';