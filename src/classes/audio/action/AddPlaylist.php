<?php
declare(strict_types=1);

namespace iutnc\deefy\audio\action;

require_once __DIR__ . '/../../../../vendor/autoload.php';

use iutnc\deefy\repository\DeefyRepository;

class AddPlaylist extends Action {

    public function execute(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // --- GET : affichage du formulaire ---
        if ($this->http_method === 'GET') {
            return <<<HTML
            <h2>Créer une nouvelle playlist</h2>
            <form method="post">
                <label>Nom de la playlist :</label>
                <input type="text" name="playlist_name" required>
                <button type="submit">Créer</button>
            </form>
            HTML;
        }

        // --- POST : traitement du formulaire ---
        // on recupere le nom de la playlist
        $name = trim($_POST['playlist_name'] ?? '');
        if ($name === '') {
            return "<p style='color:red'>Le nom de la playlist est obligatoire.</p>";
        }
        // on utilise le singleton deefyrepository pour manipuler la BDD
        $repo = DeefyRepository::getInstance();
        $user = $_SESSION['user'] ?? null;

        // Si l'utilisateur n'est pas connecté
        if (!$user) {
            return "<p style='color:red'>Vous devez être connecté pour créer une playlist.</p>";
        }

        // Enregistrer la playlist qu'on vien d'ajouter dans la BDD
        $playlistId = $repo->saveEmptyPlaylist($name);

        // Lier la playlist à l'utilisateur dans user2playlist
        $stmt = $repo->getPDO()->prepare(
            "INSERT INTO user2playlist (id_user, id_pl) VALUES (?, ?)"
        );
        // On lie la playlist a l'ID lié a user
        $stmt->execute([$user['id'], $playlistId]);
        // Stock de la playlist courante en session
        $_SESSION['playlist'] = [
            'id' => $playlistId,
            'nom' => $name,
            'duree' => 0,
            'nbPistesAudio' => 0
        ];

        return "<p style='color:green'> Playlist <strong>$name</strong> créée avec succès !</p>";
    }
}
