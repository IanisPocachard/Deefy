<?php
declare(strict_types=1);

namespace iutnc\deefy\audio\action;

require_once __DIR__ . '/../../../../vendor/autoload.php';

use iutnc\deefy\repository\DeefyRepository;

// Configuration du dépôt
try {
    DeefyRepository::setConfig(__DIR__ . '/../../config/deefy.db.ini');
} catch (\Exception $e) {
    die($e->getMessage());
}

class Dispatcher {

    private string $action;
    // On recupere l'action en QUERY STRING , mais au début ya rien ducoup on considere que c'est default
    public function __construct() {
        $this->action = $_GET['action'] ?? 'default';
    }

    public function run(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Actions qui nécessitent une connexion
        $restrictedActions = ['add-playlist', 'add-track', 'add-audio', 'playlist', 'add-user'];
        
        // Si l'action qu'on a eu au début est dans les actions restreintes & qu'on est pas connecté alors on ne peut pas consulter la page
        if (in_array($this->action, $restrictedActions) && empty($_SESSION['user'])) {
            $this->renderPage("<p style='color:red'>Vous devez être connecté pour accéder à cette page.</p>");
            return;
        }

        // Sélection de l’action à exécuter
        switch ($this->action) {
            case 'display-playlist':
                $action = new DisplayPlaylistAction();
                break;
            case 'add-playlist':
                $action = new AddPlaylist();
                break;
            case 'add-track':
                $action = new AddTrackAction();
                break;
            case 'signin':
                $action = new SigninAction();
                break;
            case 'register':
                $action = new RegisterAction();
                break;
            case 'add-user':
                $action = new AddUserAction();
                break;
            case 'logout':
                $action = new LogoutAction();
                break;
            default:
                $action = new DefaultAction();
                break;
        }
        try {
            $html = $action->execute();
        } catch (\Exception $e) {
            $html = $e->getMessage();
        }
        $this->renderPage($html);
    }

    private function renderPage(string $html): void {
        // Menu de navigation
        $nav = "";
        $css = '<link rel="stylesheet" type="text/css" href="src/css/style.css">';


        // Si l'utilisateur est connecté
        if (!empty($_SESSION['user'])) {

            $nav .= '<a href="?action=default">Accueil</a> ';

            // Récupération du tableau utilisateur ( avec id , etc , les données necessaires récupérés dans SigninAction.php lorsque l'user s'est connecté)
            $user = $_SESSION['user'];
            // Création d'une connexion a la BDD
            $repo = DeefyRepository::getInstance();

            // Récupérer les playlist de l'utilisateur connecté
            // Pas besoin de crochets ici car c'est l'execute de la PDO qui en a besoin
            if (isset($user['role']) && (int)$user['role'] === 100) {
                $stmt = $repo->findAllPlaylists();
            } else {
                $stmt = $repo->findPlaylistsByUserId($user['id']);
            }
            $playlists = $stmt;

            // Menu utilisateur
            $nav .= '<a href="?action=add-playlist">Créer Playlist</a> ';
            $nav .= '<a href="?action=add-track">Ajouter Track </a> ';


            // Lister les playlists du user connecté
            $html .= "<div class='playlists'>";
            foreach ($playlists as $pl) {
                $html .= '<a href="?action=display-playlist&id=' . (int)$pl['id'] . '">'
                        . htmlspecialchars($pl['nom']) . '</a> ';
            }
            $html .= "</div>";

            // Ajouter un utilisateur action
            $nav .= '<a href="?action=add-user">Ajouter un utilisateur</a> ';
            // Action déconnexion ( pas encore ajouté )
            $nav .= '<a href="?action=logout">Déconnexion</a>';
        } else {
            // Menu invité
            $nav .= '<a href="?action=signin">Connexion</a> ';
            $nav .= '<a href="?action=register">Inscription</a>';

            $css .= '<link rel="stylesheet" type="text/css" href="src/css/accueil.css">';
        }

        // Affichage HTML
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>DeefyApp</title>
            $css
        </head>
        <body>
            $html
            <nav>$nav</nav>
        </body>
        </html>
        HTML;
    }
}
