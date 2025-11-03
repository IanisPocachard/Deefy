<?php
declare(strict_types=1);

namespace iutnc\deefy\auth;

require_once __DIR__ . '/../../../vendor/autoload.php';

use iutnc\deefy\repository\DeefyRepository;
use iutnc\deefy\exception\AuthnException;

class AuthnProvider {

    public static function getSignedInUser(): array {
    if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    // Si l'utilisateur est pas connecté , on retourne un message d'erreur
    if (!isset($_SESSION['user'])) {
        throw new AuthnException("Utilisateur non authentifié");
    }
    return $_SESSION['user'];
}

    // --- SIGNIN ---
    // Fonction pour se connecter
    public static function signin(string $email, string $password): array {
        $repo = DeefyRepository::getInstance();
        $pdo = $repo->getPDO(); // Récupère le PDO via le getter

        // Cherche l'utilisateur dans la table user
        $stmt = $pdo->prepare("SELECT * FROM User WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        // Si pas trouvé alors erreur
        if (!$user || !password_verify($password, $user['passwd'])) {
            throw new AuthnException("Identifiants incorrects !");
        }

        // Connexion OK
        return $user; // retourne l'array de l'utilisateur pour le stocker dans la session
    }

    // --- REGISTER ---
    public static function register(string $email, string $password): int {
        $repo = DeefyRepository::getInstance();
        $pdo = $repo->getPDO(); // Récupère le PDO via le getter

        // Vérifie si l'utilisateur existe déjà
        $stmt = $pdo->prepare("SELECT id FROM User WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new AuthnException("Un compte existe déjà avec cet email !");
        }

        // Vérifie la longueur du mot de passe
        if (strlen($password) < 10) {
            throw new AuthnException("Le mot de passe doit contenir au moins 10 caractères !");
        }

        // Hash du mot de passe
        $hash = password_hash($password, PASSWORD_BCRYPT);

        // Insère l'utilisateur
        $stmt = $pdo->prepare("INSERT INTO User (email, passwd, role) VALUES (?, ?, 1)");
        $stmt->execute([$email, $hash]);

        return (int)$pdo->lastInsertId();
    }
}
