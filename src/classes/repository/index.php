<?php

require_once  __DIR__ . '/../../../vendor/autoload.php';

\iutnc\deefy\repository\DeefyRepository::setConfig(__DIR__ . '/../config/deefy.db.ini');

$repo = \iutnc\deefy\repository\DeefyRepository::getInstance();

$playlists = $repo->findAllPlaylists();

foreach ($playlists as $playlist) {
    echo "playlist : " . $playlist['id'] . " : " . $playlist['nom']; 
    echo "<br>";
}


// on enregistre la playlist
$id = $repo->saveEmptyPlaylist("Rock Classics");
print "Playlist insérée avec succès a ligne avec l'id " . $id-1;

// Crée l'objet Track
$track = new \iutnc\deefy\audio\tracks\PodcastTrack(
    'test',          // titre
    'test.mp3',      // filename
    "emission_test", // titre_album ou émission
    'auteur',        // auteur_podcast
    "artiste",       // artiste_album
    10,              // duree
    'genre'          // genre
);

// Sauvegarde la track et récupère l'ID
$trackId = $repo->saveTrack($track->titre,$track->genre,$track->nom_fichier_audio,$track->genre);
echo "<br>";


// Affichage
print "track 2 : " . $track->titre . " : " . get_class($track) . "\n";

// Ajoute à la playlist
$repo->addTrackToPlaylist($trackId, $trackId, 1); 