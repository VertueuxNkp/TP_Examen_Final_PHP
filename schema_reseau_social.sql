-- ============================================================
-- Script de création de la base de données : Réseau Social Web
-- Examen Final - TP Réseau Social en PHP et AJAX
-- ============================================================

CREATE DATABASE IF NOT EXISTS reseau_social
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE reseau_social;

-- ============================================================
-- Table : UTILISATEURS (clients du réseau social)
-- ============================================================
CREATE TABLE utilisateurs (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  nom             VARCHAR(100) NOT NULL,
  prenom          VARCHAR(100) NOT NULL,
  email           VARCHAR(150) NOT NULL UNIQUE,
  mot_de_passe    VARCHAR(255) NOT NULL,          -- haché avec password_hash()
  avatar          VARCHAR(255) DEFAULT 'default-avatar.png',
  bio             VARCHAR(500) DEFAULT NULL,
  email_verifie   TINYINT(1) NOT NULL DEFAULT 0,
  token_reset     VARCHAR(255) DEFAULT NULL,       -- pour mot de passe oublié
  token_expire    DATETIME DEFAULT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Table : ARTICLES (posts du fil d'actualité)
-- ============================================================
CREATE TABLE articles (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT NOT NULL,
  description     TEXT NOT NULL,
  image           VARCHAR(255) DEFAULT NULL,       -- image optionnelle
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Table : LIKES (likes / dislikes sur les articles)
-- ============================================================
CREATE TABLE likes (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  article_id      INT NOT NULL,
  user_id         INT NOT NULL,
  type            ENUM('like', 'dislike') NOT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
  UNIQUE KEY unique_reaction (article_id, user_id)  -- 1 seule réaction par user/article
) ENGINE=InnoDB;

-- ============================================================
-- Table : COMMENTAIRES
-- ============================================================
CREATE TABLE commentaires (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  article_id      INT NOT NULL,
  user_id         INT NOT NULL,
  contenu         TEXT NOT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Table : AMITIES (demandes et relations d'amitié)
-- user_id = celui qui envoie la demande
-- ami_id  = celui qui la reçoit
-- ============================================================
CREATE TABLE amities (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT NOT NULL,
  ami_id          INT NOT NULL,
  statut          ENUM('en_attente', 'acceptee', 'refusee') NOT NULL DEFAULT 'en_attente',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
  FOREIGN KEY (ami_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
  UNIQUE KEY unique_relation (user_id, ami_id)
) ENGINE=InnoDB;

-- ============================================================
-- Table : CONVERSATIONS (une conversation = 2 utilisateurs)
-- ============================================================
CREATE TABLE conversations (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user1_id        INT NOT NULL,
  user2_id        INT NOT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user1_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
  FOREIGN KEY (user2_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
  UNIQUE KEY unique_conversation (user1_id, user2_id)
) ENGINE=InnoDB;

-- ============================================================
-- Table : MESSAGES
-- ============================================================
CREATE TABLE messages (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT NOT NULL,
  sender_id       INT NOT NULL,
  contenu         TEXT DEFAULT NULL,
  image           VARCHAR(255) DEFAULT NULL,       -- envoi d'images en chat
  lu              TINYINT(1) NOT NULL DEFAULT 0,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Table : ADMINISTRATEURS (back-office : admin + modérateur)
-- Table séparée des utilisateurs : auth indépendante du client
-- ============================================================
CREATE TABLE administrateurs (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  nom             VARCHAR(100) NOT NULL,
  email           VARCHAR(150) NOT NULL UNIQUE,
  mot_de_passe    VARCHAR(255) NOT NULL,
  role            ENUM('admin', 'moderateur') NOT NULL DEFAULT 'moderateur',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Index utiles pour les performances (feed, chat, recherche)
-- ============================================================
CREATE INDEX idx_articles_created ON articles(created_at DESC);
CREATE INDEX idx_messages_conversation ON messages(conversation_id, created_at);
CREATE INDEX idx_amities_statut ON amities(ami_id, statut);

-- ============================================================
-- Compte admin par défaut pour les tests
-- Mot de passe en clair : Admin123!
-- Hash généré avec password_hash('Admin123!', PASSWORD_DEFAULT)
-- /!\ Remplace ce hash par le tien généré en PHP avant utilisation
-- ============================================================
INSERT INTO administrateurs (nom, email, mot_de_passe, role) VALUES
('Super Admin', 'admin@reseau-social.test', '$2y$10$replaceWithRealPasswordHashGeneratedInPHP', 'admin');
