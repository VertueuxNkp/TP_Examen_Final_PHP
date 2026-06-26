Réseau Social Web — PHP & AJAX

Projet Final — Examen TP Réseau Social Web  

---

Description du projet

Ce projet est une application web de type réseau social inspirée du modèle Facebook, développée en PHP natif et JavaScript/AJAX sans aucun framework back-end.

L'application fonctionne comme une Single Page Application (SPA) : après le chargement initial, aucune page ne se recharge. Toute la navigation est gérée par un routeur JavaScript personnalisé qui injecte dynamiquement les vues HTML dans le DOM.

Fonctionnalités implémentées

 Module  Fonctionnalités 
------
 Authentification : Inscription avec confirmation email, connexion, mot de passe oublié, réinitialisation par email 
 Fil d'actualité : Publication d'articles (texte + image), likes/dislikes persistants, commentaires AJAX 
 Gestion des amis : Envoi/réception/refus de demandes d'amitié, liste des amis 
 Profil personnel : Modification des informations, photo de profil, changement de mot de passe 
 Chat : Messagerie en temps réel simulée par polling toutes les 3 secondes, envoi de texte et d'images 
 Back-office : Dashboard avec statistiques, gestion des articles et utilisateurs, gestion des admins/modérateurs 

---

Mode de fonctionnement

1. Inscription

Accès : http://localhost/reseau_social/index.html → cliquer sur "S'inscrire"

L'inscription est la première étape pour accéder au réseau social. Le formulaire demande les informations suivantes :

•	Prénom et Nom
•	Adresse email (unique sur la plateforme)
•	Mot de passe (minimum 8 caractères) et sa confirmation

Étapes du processus d'inscription
1	Remplir le formulaire
L'utilisateur saisit ses informations. Une validation est effectuée côté client (JavaScript) avant l'envoi : vérification que les mots de passe correspondent, que l'email est valide, que tous les champs sont remplis.

2	Soumission au serveur
Les données sont envoyées au serveur PHP (api/auth/register.php) via une requête AJAX (fetch). PHP vérifie que l'email n'est pas déjà utilisé, puis hache le mot de passe avec password_hash() avant de l'enregistrer en base de données.

3	Réception de l'email de confirmation
Un email HTML professionnel est automatiquement envoyé à l'adresse fournie via PHPMailer et Gmail SMTP. Il contient un lien de vérification unique valable 24 heures.

4	Activation du compte
En cliquant sur le lien dans l'email, le compte est activé (email_verifie = 1 en base). L'utilisateur est redirigé vers la page de connexion avec un message de confirmation.

2. Connexion

Accès : http://localhost/reseau_social/index.html — Page chargée par défaut

La connexion est la porte d'entrée principale de l'application. Elle est accessible uniquement aux comptes dont l'email a été confirmé.

Étapes de la connexion
1	Saisie des identifiants
L'utilisateur entre son adresse email et son mot de passe. Un bouton "œil" permet d'afficher ou masquer le mot de passe saisi.

2	Vérification serveur
PHP vérifie que l'email existe en base et que le mot de passe correspond au hash stocké (password_verify()). Si le compte n'est pas activé, un message d'erreur spécifique est affiché.

3	Ouverture de session
En cas de succès, une session PHP est ouverte côté serveur. Les données de l'utilisateur (id, nom, prénom, email, avatar) sont stockées dans le sessionStorage du navigateur pour un accès rapide côté JavaScript.

4	Redirection vers le fil d'actualité
Le routeur JavaScript redirige automatiquement l'utilisateur vers la page d'accueil (fil d'actualité) sans rechargement de page.

Mot de passe oublié
Un lien "Mot de passe oublié ?" est disponible sur la page de connexion. Il permet à l'utilisateur de recevoir un email avec un lien de réinitialisation valable 1 heure. En cliquant sur ce lien, un formulaire permet de définir un nouveau mot de passe.

3. Page d'accueil — Fil d'actualité

Accès : Automatique après connexion — Navigation : icône 🏠 Accueil dans la barre de navigation

Le fil d'actualité est la page centrale de l'application. Il affiche l'ensemble des publications de tous les utilisateurs, de la plus récente à la plus ancienne.

Publication d'un article
•	Un formulaire de publication est affiché en haut de la page
•	L'utilisateur peut rédiger un texte (obligatoire) et ajouter une image optionnelle
•	Un aperçu de l'image s'affiche avant la publication
•	Après soumission, le nouvel article apparaît immédiatement en haut du fil, sans rechargement

Structure d'un article
Élément	Description
En-tête	Photo de profil de l'auteur, nom complet, date et heure de publication
Corps	Texte de l'article et image optionnelle
Réactions	Boutons 👍 Like et 👎 Dislike avec compteur — Un seul vote par utilisateur par article, modifiable à tout moment
Commentaires	Bouton pour afficher/masquer les commentaires — Champ de saisie fixe pour ajouter un commentaire sans rechargement de page

Système de likes / dislikes
Chaque utilisateur peut réagir à un article avec un like ou un dislike. La logique est la suivante :
•	Cliquer sur 👍 une première fois : like enregistré, icône colorée en bleu
•	Cliquer sur 👍 une deuxième fois : like annulé (toggle)
•	Cliquer sur 👎 après un like : le like est remplacé par un dislike
Les compteurs se mettent à jour instantanément sans rechargement de page.

4. Gestion des amis

Accès : icône 👥 Amis dans la barre de navigation

La page de gestion des amis est organisée en deux onglets pour faciliter la navigation.

Onglet "Mes amis"
Cet onglet s'affiche par défaut et présente deux sections :
•	Demandes reçues en attente : liste des utilisateurs qui ont envoyé une invitation, avec les boutons Accepter et Refuser
•	Mes amis : liste de tous les amis actuels avec leur photo de profil et biographie

Onglet "Trouver des amis"
Cet onglet affiche tous les utilisateurs inscrits sur la plateforme avec leur statut de relation :

Statut affiché	Bouton	Signification
Aucune relation	➕ Ajouter	Envoyer une demande d'amitié
Demande envoyée	⏳ En attente	Une invitation a été envoyée, en attente de réponse
Demande reçue	✅ / ❌	Accepter ou refuser la demande reçue
Ami	✅ Ami	Relation d'amitié établie

Une barre de recherche permet de filtrer les utilisateurs en temps réel par nom ou prénom, sans appel supplémentaire au serveur.

5. Module Chat — Messagerie

Accès : icône 💬 Messages dans la barre de navigation

Le module de messagerie permet d'échanger des messages privés en temps réel avec les autres utilisateurs. Le temps réel est simulé par un système de polling : le navigateur interroge le serveur toutes les 3 secondes pour récupérer les nouveaux messages.

Interface du chat
L'écran est divisé en deux zones :
•	Sidebar gauche : liste des conversations existantes avec le dernier message et un badge indiquant le nombre de messages non lus
•	Zone principale droite : affichage des messages de la conversation sélectionnée, avec le champ de saisie en bas

Démarrer une conversation
1	Rechercher un ami
Une barre de recherche en haut de la sidebar permet de chercher un ami par son nom. Les résultats s'affichent instantanément (après 300ms de saisie).

2	Sélectionner l'ami
En cliquant sur un résultat, la zone de messages s'ouvre. Si une conversation existe déjà, l'historique complet est chargé. Sinon, la conversation est créée automatiquement au premier envoi.

3	Envoyer un message
L'utilisateur tape son message et appuie sur Entrée ou clique sur le bouton Envoyer. Il peut aussi joindre une image via le bouton 📷. Le message apparaît immédiatement dans la conversation.

Fonctionnement du polling
•	Toutes les 3 secondes, le navigateur appelle get_messages.php avec l'identifiant du dernier message reçu
•	Le serveur ne retourne que les nouveaux messages (après l'ID fourni), ce qui optimise les performances
•	Les messages non lus sont automatiquement marqués comme lus à l'ouverture de la conversation
•	Le badge de messages non lus dans la sidebar se met à jour à chaque cycle de polling
•	Le polling s'arrête automatiquement lorsque l'utilisateur quitte la page de chat

6. Profil personnel

Accès : icône 👤 Profil dans la barre de navigation

La page de profil permet à l'utilisateur de consulter et modifier ses informations personnelles. Elle est organisée en trois blocs distincts.

Bloc 1 — Carte de profil (affichage)
•	Photo de profil
•	Nom complet et adresse email
•	Date d'inscription sur la plateforme
•	Biographie
•	Statistiques : nombre d'articles publiés et nombre d'amis

Bloc 2 — Modifier mes informations
•	Changement de la photo de profil : cliquer sur "Changer la photo", sélectionner une image — un aperçu s'affiche immédiatement avant la sauvegarde
•	Modification du prénom, du nom et de la biographie
•	Après sauvegarde, les données sont mises à jour en base et le sessionStorage est rafraîchi automatiquement

Bloc 3 — Changer le mot de passe
•	L'utilisateur doit saisir son mot de passe actuel pour confirmer son identité
•	Il choisit ensuite un nouveau mot de passe (minimum 8 caractères) et le confirme
•	Le nouveau mot de passe est haché avant d'être enregistré en base de données

7. Déconnexion

Accès : bouton "Déconnexion" dans la barre de navigation (toutes les pages)

La déconnexion est simple et sécurisée :
•	La session PHP est détruite côté serveur (session_destroy())
•	Les données utilisateur sont supprimées du sessionStorage du navigateur
•	L'utilisateur est redirigé vers la page de connexion sans rechargement
Si la session expire automatiquement (après 8 heures d'inactivité), l'utilisateur est redirigé vers la page de connexion lors de la prochaine action.

8. Back-office — Administration

Accès : http://localhost/reseau_social/admin.html — Page de connexion distincte du client

Le back-office est une interface d'administration complètement séparée de l'application client. Elle dispose de sa propre page de connexion, de sa propre session PHP et de son propre système d'authentification.

Connexion au back-office
L'administrateur ou le modérateur accède au back-office via l'URL admin.html. Il saisit son email et son mot de passe d'administrateur. Ces identifiants sont stockés dans une table séparée (administrateurs) de celle des utilisateurs clients, ce qui garantit une isolation totale des deux systèmes.

Les deux rôles
Fonctionnalité	Modérateur	Administrateur
Voir le dashboard	✅	✅
Supprimer des articles	✅	✅
Supprimer des utilisateurs	✅	✅
Voir la liste des admins	❌	✅
Créer un admin/modérateur	❌	✅
Supprimer un admin	❌	✅

Dashboard — Statistiques
Le tableau de bord affiche en temps réel les indicateurs clés de la plateforme :
•	Nombre total d'utilisateurs inscrits
•	Nouveaux utilisateurs des 7 derniers jours
•	Nombre total d'articles publiés
•	Nombre total de commentaires
•	Nombre total de messages échangés
•	Nombre total de relations d'amitié acceptées
Deux tableaux récapitulatifs complètent le dashboard : les 5 derniers utilisateurs inscrits et les 5 articles les plus likés.

Gestion des articles
L'administrateur ou le modérateur peut consulter la liste complète de tous les articles publiés sur la plateforme, avec les informations de l'auteur, le nombre de réactions et la date de publication. Un bouton "Supprimer" permet de retirer un article (avec confirmation avant suppression). La suppression est en cascade : les likes et commentaires associés sont également supprimés.

Gestion des utilisateurs
La page de gestion des utilisateurs présente la liste complète des membres inscrits avec leur activité (nombre d'articles publiés) et leur date d'inscription. La suppression d'un utilisateur entraîne automatiquement la suppression de tous ses articles, likes, commentaires et messages (suppression en cascade).

Gestion des admins / modérateurs (admin uniquement)
L'administrateur dispose d'une section supplémentaire pour gérer les comptes back-office. Il peut créer de nouveaux comptes modérateurs ou administrateurs en renseignant le nom, l'email, le mot de passe et le rôle souhaité. Il peut également supprimer des comptes existants, sauf le sien propre.


### Lancement

 Interface  URL 
------
 Application client  `http://localhost/reseau_social/index.html` 
 Back-office admin  `http://localhost/reseau_social/admin.html` 

---

## Identifiants de test
client : americainnoukpo@gmail.com : newnewnew
admin : vertueuxnoukponkp@gmail.com : Admin123!

---

## Architecture du projet

```
reseau_social/
│
├── index.html                  ← Point d'entrée client (SPA)
├── admin.html                  ← Point d'entrée back-office
├── schema_reseau_social.sql    ← Script de création de la BDD
│
├── assets/
│   ├── css/
│   │   ├── style.css           ← Styles globaux client
│   │   └── admin.css           ← Styles back-office
│   ├── js/
│   │   ├── config.js           ← Constantes globales
│   │   ├── router.js           ← Routeur SPA
│   │   ├── auth.js             ← Authentification
│   │   ├── feed.js             ← Fil d'actualité
│   │   ├── friends.js          ← Gestion des amis
│   │   ├── profile.js          ← Profil personnel
│   │   ├── chat.js             ← Module chat
│   │   └── admin.js            ← Back-office
│   └── images/
│       ├── avatars/            ← Photos de profil
│       ├── posts/              ← Images des articles
│       └── chat/               ← Images chat
│
├── vues/
│   ├── clients/                ← Vues HTML client
│   └── back-office/            ← Vues HTML admin
│
├── api/
│   ├── config/
│   │   ├── database.php        ← Connexion PDO (Singleton)
│   │   └── mailer.php          ← Envoi emails (PHPMailer)
│   ├── helpers/
│   │   ├── response.php        ← Réponses JSON standardisées
│   │   └── auth_check.php      ← Vérification de session
│   ├── auth/                   ← Endpoints authentification
│   ├── posts/                  ← Endpoints articles/likes/commentaires
│   ├── friends/                ← Endpoints amis
│   ├── profile/                ← Endpoints profil
│   ├── chat/                   ← Endpoints messagerie
│   └── admin/                  ← Endpoints back-office
│
└── vendor/                     ← Dépendances Composer (PHPMailer)
```

---

## Technologies utilisées

 Front-end  HTML5, CSS3, JavaScript ES6+ (natif) 
 Back-end  PHP 8.0 natif (aucun framework) 
 Base de données  MySQL via PDO 
 Emails  PHPMailer + Gmail SMTP 
 Architecture  SPA avec routeur JS custom, API REST JSON 
 Temps réel (chat)  Polling JavaScript (`setInterval` toutes les 3s) 

---

## 👥 Membres du groupe

 Nom  Prénom 
------
 AYITCHEDEHOU Ezechias
 HAZOUME  Gildas 
 KOUKOUI Prince
 NOUKPO Mahukpégo Vertueux 
 TOHOUNGBA Steve

---

## Dépôt GitHub/GitLab

https://github.com/VertueuxNkp/TP_Examen_Final_PHP.git
