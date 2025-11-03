<?php
declare(strict_types=1);

namespace iutnc\deefy\audio\action;

require_once __DIR__ . '/../../../../vendor/autoload.php';

use iutnc\deefy\audio\tracks\AlbumTrack;
use iutnc\deefy\audio\tracks\PodcastTrack;
use iutnc\deefy\auth\Authz;
use iutnc\deefy\repository\DeefyRepository;
use iutnc\deefy\audio\lists\Playlist;
use iutnc\deefy\audio\tracks\AudioTrack;
use iutnc\deefy\render\AudioListRenderer;

class DisplayPlaylistAction extends Action {

    public function execute(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // On recupère l'ID de la session en GET
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            return "<p>Playlist invalide.</p>";
        }

        try {
            // Vérifie que l’utilisateur a le droit d’y accéder
            Authz::checkPlaylistOwner($id);

            // Création du singleton pour manipuler la BDD
            $repo = DeefyRepository::getInstance();
            // On recupere la playlist associés a l'ID $id
            $playlist = $repo->findPlaylistById($id); // objet Playlist déjà créé

            // Récupère les pistes depuis la base
            $tracksData = $repo->findTracksByPlaylist($id);
            $tracks = [];
            foreach ($tracksData as $row) {
                switch ($row['type']) {
                    case "A" :
                        $tracks[] = new AlbumTrack(
                            $row['titre'],
                            $row['filename'],
                            $row['titre_album'],
                            $row['numero_album'],
                            $row['genre'],
                            $row['duree'],
                            $row['artiste_album']
                        );
                        break;
                    case "P" :
                        $tracks[] = new PodcastTrack(
                            $row['titre'],
                            $row['filename'],
                            $row['auteur_podcast'],
                            $row['duree'],
                            $row['genre'],
                            $row['date_posdcast']
                        );
                        break;

                }
            }
            // Puis on associe les tracks au playlist avec mettreAJourProprietes
            // Met à jour les pistes dans l'objet Playlist
            $playlist->mettreAJourProprietes($tracks);

            $dureeTotal = 0;
            foreach ($tracks as $t) {
                $dureeTotal += (int) $t->__get('duree');
            }
            $nbPistes = count($tracks);
            $nomPl = '';
            if (is_object($playlist) && method_exists($playlist, '__get')) {
                try { $nomPl = (string) $playlist->__get('nom'); } catch (\Throwable $e) {}
            }
            // Sauvegarde de la playlist courante
            $_SESSION['playlist'] = [
                'id' => $id,
                'nom' => $nomPl,
                'duree' => $dureeTotal,
                'nbPistesAudio' => $nbPistes
            ];

            // Rend la playlist
            $renderer = new AudioListRenderer($playlist);
            return $renderer->render();

        } catch (\Exception $e) {
            return "<p style='color:red'>" . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}
