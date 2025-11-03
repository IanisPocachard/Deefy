<?php
declare(strict_types=1);

namespace iutnc\deefy\audio\action;

require_once __DIR__ . '/../../../../vendor/autoload.php';

use iutnc\deefy\audio\action\Action;
use iutnc\deefy\repository\DeefyRepository;

class AddUserAction extends Action {

    public function execute(): string {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Vérifie si l'utilisateur est connecté et admin
        $user = $_SESSION['user'] ?? null;
        // Si l'utilisateur n'est pas connecté ou pas admin on refuse l'acccès
        if (!$user || (int)$user['role'] !== 100) {
            return "<p style='color:red;'>Accès refusé : vous devez être administrateur pour ajouter un utilisateur.</p>";
        }

        //  GET : affichage du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return <<<HTML
            <h2>Ajouter un utilisateur</h2>
            <form method="post" action="?action=add-user">
                <label for="nom">Nom :</label><br>
                <input type="text" id="nom" name="nom" required><br><br>

                <label for="email">Email :</label><br>
                <input type="email" id="email" name="email" required><br><br>

                <label for="role">Rôle :</label><br>
                <select id="role" name="role" required>
                    <option value="1">Utilisateur</option>
                    <option value="100">Admin</option>
                </select><br><br>

                <button type="submit">Ajouter</button>
            </form>
            HTML;
        }

        // POST : traitement du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // pour éviter l'injection SQL
            $nom = filter_var($_POST['nom'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
            $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            $role = (int)($_POST['role'] ?? 1);
            // Si un des 3 champs n'existe pas , on retourne une erreur
            if (empty($nom) || empty($email)) {
                return "<p style='color:red;'>Veuillez remplir tous les champs.</p>";
            }

            try {
                // On recupere le singleton deefyrepository pour manipuler la BDD
                $repo = DeefyRepository::getInstance();
                // On ajoute l'utilisateur a la BDD
                $stmt = $repo->getPDO()->prepare(
                    "INSERT INTO user (nom, email, role) VALUES (?, ?, ?, ?)"
                );
                $stmt->execute([$nom, $email, $role]);

                // L'utilisateur est ajouté , on retourne un message de succès
                return "<p>Utilisateur ajouté : Nom = {$nom}, Email = {$email}, Rôle = {$role}</p>";
            } catch (\Exception $e) {
                // Si il ya un problème lors de l'ajout , on retourne une erreur tout en utilisant htmlspecialchars pour éviter une injection SQL
                return "<p style='color:red;'>Erreur lors de l'ajout : " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        // Si c'est un HEAD par exemple , on une erreur

        return "<p>Requête invalide.</p>";
    }
}
