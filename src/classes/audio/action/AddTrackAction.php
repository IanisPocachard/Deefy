<?php
declare(strict_types=1);

namespace iutnc\deefy\audio\action;

use iutnc\deefy\repository\DeefyRepository;
use getID3;

class AddTrackAction extends Action {

    public function execute(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Création du singleton deefyrepository pour manipuler la BDD
        $repo = DeefyRepository::getInstance();
        // Si l'id n'existe pas dans la BDD , il faut se connecter
        $userId = $_SESSION['user']['id'] ?? null;

        if (!$userId) {
            return "<p style='color:red'>Vous devez être connecté pour ajouter un track.</p>";
        }

        // Récupérer les playlists de l'utilisateur
        $playlists = $repo->findPlaylistsByUserId($userId);
        // Si aucune playlist , il faut en créer au moins une
        if (empty($playlists)) {
            return "<p>Veuillez d’abord créer une playlist avant d’ajouter un track.</p>";
        }

        // --- GET : formulaire ---
        if ($this->http_method === 'GET') {
            $currentPlId = (int) ($_SESSION['playlist']['id'] ?? 0);
            if ($currentPlId <= 0) {
                return "<p style='color:red;'>Aucune playlist courante. Ouvrez d’abord une playlist.</p>";
            }
            return <<<HTML
                <h2>Ajouter un track à une playlist</h2>
                <form method='post' action='?action=add-track' enctype='multipart/form-data'> 
                <p>
                    <label for='track'> Titre : </label>
                    <input type='text' id='track' name='track' placeholder='Titre' autofocus required>
                    <label for='genre'> Genre : </label>
                    <input type='text' id='genre' name='genre' placeholder='Genre' required>
                    <div class='choixTrack'>
                        <div>
                            <input type='radio' name='choix' id='choix1' value='AlbumTrack' checked>
                            <label for='choix1'> Je souhaite importer une piste de musique </label> <br>
                            <label for='artiste_album'> Artiste : </label>
                            <input type='text' id='artiste_album' name='artiste_album' placeholder='Artiste'>
                            <label for='titre_album'> Album : </label>
                            <input type='text' id='titre_album' name='titre_album' placeholder='Album'>
                            <label for='annee_album'> Année : </label>
                            <input type='number' id='annee_album' name='annee_album' placeholder='Année'>
                        </div>
                        <div>
                            <input type='radio' name='choix' id='choix2' value='PodcastTrack'>
                            <label for='choix2'> Je souhaite importer un podcast </label> <br>
                            <label for='auteur_podcast'> Auteur : </label>
                            <input type='text' id='auteur_podcast' name='auteur_podcast' placeholder='Auteur'>
                            <label for='date_posdcast'> Date : </label>
                            <input type='date' id='date_posdcast' name='date_posdcast' placeholder='Date'>
                        </div>
                    </div>
                    
                </p>
                <p>
                    <input type='file' name='fichier' id='fichier' accept='audio/*' required>
                </p>
                <p>
                    <button type='submit'> Ajouter </button>
                </p>
            </form>
            HTML;
        }

        // --- POST : traitement du formulaire ---
        // On recupere ce quil y'a dans le post

        // Vérifie que c'est bien un MP3
        $fichier = $_FILES['fichier'];
        if (substr($fichier['name'], -4) !== '.mp3' || $fichier['type'] !== 'audio/mpeg') {
            return "<p style='color:red;'>Le fichier doit être un MP3 valide.</p>";
        }

        // Vérifie qu'une playlist courante est bien définie en session
        $playlistId = (int)($_SESSION['playlist']['id'] ?? 0);
        if ($playlistId === 0) {
            return "<p style='color:red;'>Aucune playlist courante. Ouvrez d’abord une playlist.</p>";
        }

        $trackName  = trim($_POST['track'] ?? '');
        if (!$trackName) {
            return "<p style='color:red;'>Veuillez remplir tous les champs correctement.</p>";
        }

        switch ($_POST['choix']) {
            case 'AlbumTrack':
                if ($_POST['artiste_album'] == "" ||
                    $_POST['titre_album'] == "" ||
                    $_POST['annee_album'] == "") {
                    return "<p style='color:red;'>Veuillez remplir tous les champs correctement.</p>";
                } else {
                    $type = 'A';
                    $artiste_album = trim((string)($_POST['artiste_album'] ?? ''));
                    $titre_album = trim((string)($_POST['titre_album'] ?? ''));
                    $annee_album = ($_POST['annee_album'] ?? '') === '' ? null : (int)$_POST['annee_album'];
                    $numero_album = 1;
                    $auteur_podcast = null;
                    $date_posdcast = null;
                }
                break;

            case 'PodcastTrack':
                if ($_POST['auteur_podcast'] == "" ||
                    $_POST['date_posdcast'] == "") {
                    return "<p style='color:red;'>Veuillez remplir tous les champs correctement.</p>";
                } else {
                    $type = 'P';
                    $artiste_album = null;
                    $titre_album = null;
                    $annee_album = null;
                    $numero_album = null;
                    $auteur_podcast = trim((string)($_POST['auteur_podcast'] ?? ''));
                    $date_posdcast = trim((string)($_POST['date_posdcast'] ?? ''));
                }
                break;
        }

        $genre          = trim((string)($_POST['genre'] ?? ''));
        $filename       = uniqid('track_', true) . '.mp3';

        // Crée un nom unique pour éviter les doublons
        if (! is_dir(__DIR__ . '/../../audio/audios/')) {
            mkdir(__DIR__ . '/../../audio/audios/');
        }
        $destination = __DIR__ . '/../../audio/audios/' . $filename;
        move_uploaded_file($fichier['tmp_name'], $destination);

        // --- calcul de la durée réelle du MP3 ---
        // on utilise getID3
        $getID3 = new getID3();
        $info = $getID3->analyze($destination);
        $duration = $info['playtime_seconds'] ?? 0;

        // On sauvegarde le track dans la base de donnée
        $pdo = $repo->getPDO();
        $stmt = $pdo->prepare("
            INSERT INTO track
                (titre, genre, duree, filename, type, artiste_album, titre_album, annee_album, numero_album, auteur_podcast, date_posdcast)
            VALUES
                (:titre, :genre, :duree, :filename, :type, :artiste_album, :titre_album, :annee_album, :numero_album, :auteur_podcast, :date_posdcast)
        ");
        $stmt->execute([
            ':titre'          => $trackName,
            ':genre'          => ($genre !== '') ? $genre : null,
            ':duree'          => $duration,
            ':filename'       => ($filename !== '') ? $filename : null,
            ':type'           => ($type !== '') ? $type : null,
            ':artiste_album'  => ($artiste_album !== '') ? $artiste_album : null,
            ':titre_album'    => ($titre_album !== '') ? $titre_album : null,
            ':annee_album'    => $annee_album,
            ':numero_album'   => $numero_album,
            ':auteur_podcast' => ($auteur_podcast !== '') ? $auteur_podcast : null,
            ':date_posdcast'  => ($date_posdcast !== '') ? $date_posdcast : null,
        ]);
        $trackId = (int)$pdo->lastInsertId();

        // Ajouter le track à la playlist
        $existingTracks = $repo->findTracksByPlaylist($playlistId);
        // On met a jour le nombre de pistes
        $noPiste = count($existingTracks) + 1;
        // On ajoute le track a la playlist
        $repo->addTrackToPlaylist($playlistId, $trackId, $noPiste);

        // Mettre à jour la durée totale de la playlist
        $totalDuration = array_sum(array_column($existingTracks, 'duree')) + $duration;
        $stmt2 = $repo->getPDO()->prepare("UPDATE playlist SET duree = ? WHERE id = ?");
        $stmt2->execute([$totalDuration, $playlistId]);

        if (isset($_SESSION['playlist']) && (int)($_SESSION['playlist']['id'] ?? 0) === $playlistId) {
            $_SESSION['playlist']['duree'] = (int)$totalDuration;
            $_SESSION['playlist']['nbPistesAudio'] = $noPiste;
        }

        return "<p>Track <strong>" . htmlspecialchars($trackName) . "</strong> ajouté à la playlist. Durée totale mise à jour : {$totalDuration}s.</p>";
    }

}
