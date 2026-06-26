<?php
// ============================================================
// api/config/database.php
// Connexion PDO unique (pattern Singleton)
// ============================================================

class Database {

    private static ?PDO $instance = null;

    // --- Paramètres de connexion ---
    private string $host     = 'localhost';
    private string $dbname   = 'reseau_social';
    private string $user     = 'root';
    private string $password = '';          // Adapter selon ton environnement

    // Constructeur privé : on ne peut pas instancier la classe directement
    private function __construct() {}

    /**
     * Retourne la connexion PDO unique.
     * Crée la connexion au premier appel, la réutilise ensuite.
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dsn     = "mysql:host=localhost;dbname=reseau_social;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Lance des exceptions sur erreur SQL
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Retourne des tableaux associatifs
                PDO::ATTR_EMULATE_PREPARES   => false,                    // Requêtes préparées réelles (sécurité)
            ];
            self::$instance = new PDO($dsn, 'root', '', $options);
        }
        return self::$instance;
    }
}