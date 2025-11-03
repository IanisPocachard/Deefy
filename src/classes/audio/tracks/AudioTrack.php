<?php
declare(strict_types=1);

namespace iutnc\deefy\audio\tracks;

use iutnc\deefy\exception\InvalidPropertyNameException;
use iutnc\deefy\exception\InvalidPropertyValueException;

class AudioTrack {
    protected string $titre;
    protected string $artiste;
    protected int $duree;
    protected string $nom_fichier_audio;
    protected string $genre;

    public function __construct(
        string $titre,
        string $nom_fichier_audio,
        string $artiste = "",
        int $duree = 0,
        string $genre = ""
    ) {
        $this->titre = $titre;
        $this->nom_fichier_audio = $nom_fichier_audio;
        $this->artiste = $artiste;
        $this->duree = $duree;
        $this->genre = $genre;
    }

    public function __get(string $attr): mixed {
        if (property_exists($this, $attr)) {
            return $this->$attr;
        } else {
            throw new InvalidPropertyNameException("Propriété $attr inconnue");
        }
    }

    public function __toString(): string {
        return json_encode(get_object_vars($this));
    }
}