<?php
declare(strict_types=1);

namespace iutnc\deefy\audio\action;

require_once __DIR__ . '/../../../../vendor/autoload.php';

class LogoutAction extends Action {

    public function execute(): string {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION = [];
        // On supprime ce quil y'a dans la session
        session_destroy();

        return '<p>Déconnexion effectuée.</p><p><a href="?action=default">Retour à l\'accueil</a></p>';
    }
}
