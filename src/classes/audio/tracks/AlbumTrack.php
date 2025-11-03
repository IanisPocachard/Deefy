<?php
declare(strict_types=1);

namespace iutnc\deefy\audio\tracks;

use iutnc\deefy\exception\InvalidPropertyNameException;

class AlbumTrack extends AudioTrack {
    private string $album;
    private int $annee;
    private int $num_piste;

    public function __construct(
        string $titre,
        string $nom_fichier_audio,
        string $album,
        int $num_piste,
        string $genre,
        int $duree = 0,
        string $artiste = ""
    ) {
        parent::__construct($titre, $nom_fichier_audio, $artiste, $duree, $genre);

        $this->album = $album;
        $this->num_piste = $num_piste;
        $this->annee = (int)date('Y');
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