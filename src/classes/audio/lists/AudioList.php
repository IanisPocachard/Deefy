<?php
declare(strict_types=1);

namespace iutnc\deefy\audio\lists;

use iutnc\deefy\audio\tracks\AudioTrack;
use iutnc\deefy\exception\InvalidPropertyNameException;

class AudioList {
    protected string $nom;
    protected int $nbPistesAudio;
    protected int $dureeTotalAudio;
    protected array $tabAudio;

    public function __construct(string $nom, array $tabAudio = []) {
        $this->nom = $nom;
        $this->tabAudio = $tabAudio;
        $this->nbPistesAudio = count($tabAudio);
        $this->dureeTotalAudio = 0;
        
        foreach($this->tabAudio as $piste) {
            if($piste instanceof AudioTrack) {
                $this->dureeTotalAudio += $piste->__get('duree');
            }
        }
    }

    public function __get(string $attr_name): mixed {
        if (property_exists($this, $attr_name)) {
            return $this->$attr_name;
        }
        throw new InvalidPropertyNameException("Propriété $attr_name inconnue");
    }

    public function __toString(): string {
        return json_encode(get_object_vars($this));
    }
}