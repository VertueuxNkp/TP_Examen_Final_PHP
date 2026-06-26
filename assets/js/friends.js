// ============================================================
// assets/js/friends.js
// Gestion des amis :
//   - Liste de tous les utilisateurs avec statut de relation
//   - Envoi de demandes d'amitié
//   - Acceptation / refus des demandes reçues
//   - Liste des amis actuels
// ============================================================

function initFriends() {
    chargerOngletActif('amis');
}

// ============================================================
// SECTION 2 : Navigation par onglets
// ============================================================

function chargerOngletActif(onglet) {
    document.querySelectorAll('.onglet').forEach(btn => {
        btn.classList.toggle('actif', btn.dataset.onglet === onglet);
    });
    document.querySelectorAll('.onglet-contenu').forEach(section => {
        section.style.display = section.id === `onglet-${onglet}` ? 'block' : 'none';
    });
    if (onglet === 'amis')         chargerAmisEtDemandes();
    if (onglet === 'utilisateurs') chargerTousLesUtilisateurs();
}

// ============================================================
// SECTION 3 : Onglet "Mes amis" + demandes reçues
// ============================================================

async function chargerAmisEtDemandes() {
    const conteneurAmis     = document.getElementById('liste-amis');
    const conteneurDemandes = document.getElementById('liste-demandes');
    if (!conteneurAmis) return;

    conteneurAmis.innerHTML = '<p class="chargement">Chargement...</p>';
    conteneurDemandes.innerHTML = '';

    try {
        const reponse  = await fetch(CONFIG.API_URL + '/friends/get_friends.php', { credentials: 'include' });
        const resultat = await reponse.json();
        if (!resultat.success) return;

        const { amis, demandes_recues } = resultat.data;

        // Demandes reçues
        if (demandes_recues.length > 0) {
            conteneurDemandes.innerHTML = `
                <h4 class="sous-titre">Demandes reçues <span class="badge">${demandes_recues.length}</span></h4>
                ${demandes_recues.map(u => carteUtilisateur(u)).join('')}`;
        }

        // Amis
        if (amis.length === 0) {
            conteneurAmis.innerHTML = '<p class="vide">Vous n\'avez pas encore d\'amis. Explorez les utilisateurs !</p>';
        } else {
            conteneurAmis.innerHTML = `
                <h4 class="sous-titre">Mes amis (${amis.length})</h4>
                ${amis.map(u => carteUtilisateur(u)).join('')}`;
        }

    } catch (erreur) {
        conteneurAmis.innerHTML = '<p class="erreur">Erreur de chargement.</p>';
    }
}

// ============================================================
// SECTION 4 : Onglet "Tous les utilisateurs"
// ============================================================

async function chargerTousLesUtilisateurs() {
    const conteneur = document.getElementById('liste-utilisateurs');
    if (!conteneur) return;
    conteneur.innerHTML = '<p class="chargement">Chargement...</p>';

    try {
        const reponse  = await fetch(CONFIG.API_URL + '/friends/get_users.php', { credentials: 'include' });
        const resultat = await reponse.json();
        if (!resultat.success) return;

        if (resultat.data.length === 0) {
            conteneur.innerHTML = '<p class="vide">Aucun autre utilisateur inscrit.</p>';
            return;
        }
        conteneur.innerHTML = resultat.data.map(u => carteUtilisateur(u)).join('');

    } catch (erreur) {
        conteneur.innerHTML = '<p class="erreur">Erreur de chargement.</p>';
    }
}

// ============================================================
// SECTION 5 : Construction des cartes utilisateur
// ============================================================

function carteUtilisateur(u) {
    const avatarSrc = u.avatar
        ? CONFIG.BASE_URL + '/assets/images/avatars/' + u.avatar
        : CONFIG.BASE_URL + '/assets/images/default-avatar.png';

    const bio = u.bio ? `<p class="carte-user__bio">${escapeHtml(u.bio)}</p>` : '';

    return `
    <div class="carte-user" id="carte-user-${u.id}">
      <img src="${avatarSrc}" alt="Avatar" class="carte-user__avatar"
           onerror="this.src=CONFIG.BASE_URL+'/assets/images/default-avatar.png'">
      <div class="carte-user__infos">
        <span class="carte-user__nom">${u.prenom} ${u.nom}</span>
        ${bio}
      </div>
      <div class="carte-user__actions" id="actions-${u.id}">
        ${construireBoutons(u)}
      </div>
    </div>`;
}

function construireBoutons(u) {
    switch (u.relation) {
        case 'ami':
            return `<span class="badge badge--vert">Ami</span>`;
        case 'demande_envoyee':
            return `<span class="badge badge--gris">Demande envoyee</span>`;
        case 'demande_recue':
            return `
                <button class="btn btn--primary btn--sm" onclick="repondredemande(${u.amitie_id}, 'accepter', ${u.id})">Accepter</button>
                <button class="btn btn--danger btn--sm"  onclick="repondredemande(${u.amitie_id}, 'refuser',  ${u.id})">Refuser</button>`;
        default:
            return `<button class="btn btn--primary btn--sm" onclick="envoyerDemande(${u.id})">Ajouter</button>`;
    }
}

// ============================================================
// SECTION 6 : Actions
// ============================================================

async function envoyerDemande(amiId) {
    try {
        const reponse  = await fetch(CONFIG.API_URL + '/friends/send_request.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            credentials: 'include', body: JSON.stringify({ ami_id: amiId }),
        });
        const resultat = await reponse.json();
        if (resultat.success) {
            const actions = document.getElementById('actions-' + amiId);
            if (actions) actions.innerHTML = `<span class="badge badge--gris">Demande envoyee</span>`;
        } else {
            alert(resultat.message);
        }
    } catch (e) { console.error('Erreur envoyerDemande :', e); }
}

async function repondredemande(amitieid, action, userId) {
    try {
        const reponse  = await fetch(CONFIG.API_URL + '/friends/respond_request.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            credentials: 'include', body: JSON.stringify({ amitie_id: amitieid, action }),
        });
        const resultat = await reponse.json();
        if (resultat.success) {
            const actions = document.getElementById('actions-' + userId);
            if (actions) {
                actions.innerHTML = action === 'accepter'
                    ? `<span class="badge badge--vert">Ami</span>`
                    : `<button class="btn btn--primary btn--sm" onclick="envoyerDemande(${userId})">Ajouter</button>`;
            }
            chargerAmisEtDemandes();
        } else {
            alert(resultat.message);
        }
    } catch (e) { console.error('Erreur repondredemande :', e); }
}

// ============================================================
// SECTION 7 : Recherche
// ============================================================

function rechercherUtilisateur() {
    const recherche = document.getElementById('input-recherche').value.toLowerCase();
    document.querySelectorAll('.carte-user').forEach(carte => {
        const nom = carte.querySelector('.carte-user__nom').textContent.toLowerCase();
        carte.style.display = nom.includes(recherche) ? 'flex' : 'none';
    });
}

function escapeHtml(texte) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(texte));
    return div.innerHTML;
}