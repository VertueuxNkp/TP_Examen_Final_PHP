// assets/js/utils.js
// Wrapper fetch qui intercepte les 401 globalement

async function apiFetch(url, options = {}) {
    const reponse = await fetch(url, {
        ...options,
        credentials: 'include',
    });

    // Si session expirée → vider sessionStorage et retourner à login
    if (reponse.status === 401) {
        supprimerSession();
        if (typeof arreterPolling === 'function') arreterPolling();
        naviguerVers('login');
        return null;
    }

    return reponse;
}