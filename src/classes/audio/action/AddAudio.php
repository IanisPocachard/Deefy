<?php
declare(strict_types=1);

namespace iutnc\deefy\audio\action;

require_once __DIR__ . '/../../../../vendor/autoload.php';

use iutnc\deefy\audio\action\Action;
use iutnc\deefy\repository\DeefyRepository;

class AddAudio extends Action {

    // Fonction qui gère l’upload du fichier audio
    private function handleUpload(): string {
        // Vérifie qu’un fichier a bien été envoyé et qu’il n’y a pas d’erreur
        if (!isset($_FILES['userfile']) || $_FILES['userfile']['error'] !== UPLOAD_ERR_OK) {
            return "<p style='color:red;'>Erreur pendant l'envoi du fichier.</p>";
        }

        $fichier = $_FILES['userfile'];

        // Vérifie que c'est bien un MP3
        if (substr($fichier['name'], -4) !== '.mp3' || $fichier['type'] !== 'audio/mpeg') {
            return "<p style='color:red;'>Le fichier doit être un MP3 valide.</p>";
        }

        // Vérifie qu'une playlist courante est bien définie en session
        $playlistId = (int)($_SESSION['playlist']['id'] ?? 0);
        if ($playlistId === 0) {
            return "<p style='color:red;'>Aucune playlist courante. Ouvrez d’abord une playlist.</p>";
        }

        // Crée un nom unique pour éviter les doublons
        $nouveau_nom = uniqid('track_', true) . '.mp3';
        $destination = __DIR__ . '/../../audio/audios/' . $nouveau_nom;
        move_uploaded_file($fichier['tmp_name'], $destination);

        // Création du singleton deefyrepository pour manipuler la BDD
        $repo = DeefyRepository::getInstance();

        // --- calcul de la durée réelle du MP3 ---
        // on ne savait pas comment faire et on a récupéré du code sur internet
        $duration = 0;
        if (is_file($destination)) {
            $out = [];
            $ret = 0;
            @exec('ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' . escapeshellarg($destination), $out, $ret);
            if ($ret === 0 && !empty($out) && is_numeric($out[0])) {
                $duration = (int) round((float)$out[0]);
            }
            // Si ffprobe échoue, on estime la durée via la taille du fichier
            if ($duration <= 0) {
                $size = @filesize($destination);
                if ($size && $size > 0) {
                    $duration = (int) round(($size * 8) / 128000);
                }
            }
            // Si toujours rien, on met une durée minimale par défaut
            if ($duration <= 0) {
                $duration = 1;
            }
        }

        // Sauvegarde de l'audio via une track dans la BDD
        $trackId = $repo->saveTrack($fichier['name'], 'inconnu', $nouveau_nom, $duration, 'mp3');

        // Ajoute le track à la playlist
        $existingTracks = $repo->findTracksByPlaylist($playlistId);
        $noPiste = count($existingTracks) + 1;
        $repo->addTrackToPlaylist($playlistId, $trackId, $noPiste);

        // mettre à jour la durée totale de la playlist (BD + session)
        $after = $repo->findTracksByPlaylist($playlistId);
        $totalDuration = 0;
        foreach ($after as $row) {
            if (isset($row['duree']) && is_numeric($row['duree'])) {
                $totalDuration += (int) $row['duree'];
            }
        }
        // Update de la durée totale de la playlist
        $stmtUpd = $repo->getPDO()->prepare("UPDATE playlist SET duree = ? WHERE id = ?");
        $stmtUpd->execute([$totalDuration, $playlistId]);

        // On regarde la playlist courante et on update ces attributs
        if (isset($_SESSION['playlist']) && (int)($_SESSION['playlist']['id'] ?? 0) === $playlistId) {
            $_SESSION['playlist']['duree'] = $totalDuration;
            $_SESSION['playlist']['nbPistesAudio'] = count($after);
        }

        return "<p style='color:green;'>Fichier MP3 uploadé et ajouté à la playlist avec succès !</p>";
    }

    public function execute(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $repo = DeefyRepository::getInstance();
        // on recupere l'user ID si ya rien c'est null
        // ( user est un tableau qu'on stock dans la session depuis AuthnProvider.php)
        $userId = $_SESSION['user']['id'] ?? null;

        if (!$userId) {
            // Si l'utilisateur n'est pas connecté on retourne un message d'erreur
            return "<p style='color:red;'>Vous devez être connecté pour ajouter un fichier audio.</p>";
        }

        // Récupère la playlist courante en session
        $playlistId = (int)($_SESSION['playlist']['id'] ?? 0);
        if ($playlistId === 0) {
            return "<p style='color:red;'>Aucune playlist courante. Ouvrez d’abord une playlist.</p>";
        }

        // Si l'utilisateur accède à la page en GET
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Formulaire d'upload
            return <<<HTML
            <h2>Ajouter un fichier audio à la playlist courante</h2>
            <form method="post" enctype="multipart/form-data" action="?action=add-audio">
                <label for="userfile">Choisir un fichier audio (.mp3) :</label><br>
                <input type="file" name="userfile" id="userfile" accept=".mp3,audio/mpeg" required><br><br>

                <button type="submit">Uploader</button>
            </form>
            HTML;
        }

        // Une fois que le formulaire est envoyé on récupère en POST et on appelle la méthode handleUpload
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->handleUpload();
        }

        // Si c'est HEAD ou autre, ya rien à faire
        return "<p>Erreur : méthode HTTP non supportée.</p>";
    }
}
