<?php
declare(strict_types=1);

namespace iutnc\deefy\audio\lists;

use iutnc\deefy\audio\tracks\AudioTrack;
use iutnc\deefy\exception\InvalidPropertyValueException;

class AlbumList extends AudioList {
    private string $artiste;
    private string $date_sortie;
    
    public function __construct(string $nom, array $tabAudio) {
        if (empty($tabAudio)) {
            throw new InvalidPropertyValueException("Un album doit avoir au moins un élément");
        }
        parent::__construct($nom, $tabAudio);
        $this->artiste = "Inconnu";
        $this->date_sortie = "Inconnu";
    }

    public function __get(string $attr_name): mixed {
        if (property_exists($this, $attr_name)) {
            return $this->$attr_name;
        }
        return parent::__get($attr_name);
    }

    public function setArtiste(string $art): void {
        $this->artiste = $art;
    }

    public function setDate(string $date): void {
        $this->date_sortie = $date;
    }
}