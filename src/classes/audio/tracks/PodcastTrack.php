<?php
declare(strict_types=1);

namespace iutnc\deefy\audio\tracks;

use iutnc\deefy\exception\InvalidPropertyNameException;

class PodcastTrack extends AudioTrack {

    private string $datePodcast;

    public function __construct(
        string $titre,
        string $nom_fichier_audio,
        string $artiste = "",
        int $duree = 0,
        string $genre = "",
        string $date = ""
    ) {
        parent::__construct($titre, $nom_fichier_audio, $artiste, $duree, $genre);
        $this->datePodcast = $date;
    }

    public function __get(string $name): mixed {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        return parent::__get($name);
    }

    public function __toString(): string {
        return json_encode(get_object_vars($this), JSON_PRETTY_PRINT);
    }
}