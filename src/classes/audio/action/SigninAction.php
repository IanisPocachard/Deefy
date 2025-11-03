<?php
declare(strict_types=1);

namespace iutnc\deefy\audio\action;
require_once __DIR__ . '\..\..\..\../vendor/autoload.php';

use iutnc\deefy\auth\AuthnProvider;
use iutnc\deefy\auth\AuthnException;
use iutnc\deefy\audio\action\Action;

class SigninAction extends Action {

    public function execute(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // On recupere en POST depuis le formulaire
        if ($this->http_method === 'POST') {
            $email = $_POST['email'] ?? '';
            $passwd = $_POST['passwd'] ?? '';

            try {
                // On regarde avec AuthnProvider si l'utilisateur existe dans la BDD . Si oui on peut se connecter
                $user = AuthnProvider::signin($email, $passwd);
                // On le stock dans la session ( c'est un tableau avec id , etc)
                $_SESSION['user'] = $user;
                // pour éviter l'injection XSS
                return "<p>Connexion réussie ! Bonjour " . htmlspecialchars($email) . "</p>";
            } catch (AuthnException $e) {
                return "<p style='color:red'>" . $e->getMessage() . "</p>";
            }
        }

        // GET  formulaire
        return <<<HTML
        <form method="post">
            <label>Email: <input type="email" name="email" required></label><br>
            <label>Mot de passe: <input type="password" name="passwd" required></label><br>
            <button type="submit">Se connecter</button>
        </form>
        HTML;
    }
}
