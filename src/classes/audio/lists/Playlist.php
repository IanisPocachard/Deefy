<?php
declare(strict_types=1);

namespace iutnc\deefy\audio\lists;

use iutnc\deefy\audio\tracks\AudioTrack;
use iutnc\deefy\exception\InvalidPropertyValueException;

class Playlist extends AudioList {
    
    public function __construct(string $nom, array $tabAudio = []) {
        parent::__construct($nom, $tabAudio);
    }

    public function ajouterPiste(AudioTrack $a): void {
        // Récupérer le tableau actuel via le getter
        $tabAudio = $this->__get('tabAudio');
        $tabAudio[] = $a;
        
        // Mettre à jour les propriétés
        $this->mettreAJourProprietes($tabAudio);
    }

    public function supprimerPiste(int $indice): void {
        $tabAudio = $this->__get('tabAudio');
        if (isset($tabAudio[$indice])) {
            unset($tabAudio[$indice]);
            $tabAudio = array_values($tabAudio);
            $this->mettreAJourProprietes($tabAudio);
        }
    }

    public function ajouter_listePiste(array $pistes): void {
        $tabAudio = $this->__get('tabAudio');
        
        foreach($pistes as $a) {
            if ($a instanceof AudioTrack && !in_array($a, $tabAudio, true)) {
                $tabAudio[] = $a;
            }
        }
        
        $this->mettreAJourProprietes($tabAudio);
    }

    // Méthode public pour mettre à jour toutes les propriétés
    public function mettreAJourProprietes(array $nouveauTabAudio): void {
        // On ne peut pas modifier directement les propriétés public du parent
        // On doit recréer l'objet ou utiliser une autre approche
        // Pour l'instant, on va stocker le tableau dans une propriété locale
        $this->tabAudioLocal = $nouveauTabAudio;
        $this->nbPistesAudioLocal = count($nouveauTabAudio);
        $this->dureeTotalAudioLocal = 0;
        
        foreach($nouveauTabAudio as $piste) {
            $this->dureeTotalAudioLocal += $piste->__get('duree');
        }
    }

    // Override du getter pour utiliser nos propriétés locales si elles existent
    public function __get(string $attr_name): mixed {
        if ($attr_name === 'tabAudio' && isset($this->tabAudioLocal)) {
            return $this->tabAudioLocal;
        }
        if ($attr_name === 'nbPistesAudio' && isset($this->nbPistesAudioLocal)) {
            return $this->nbPistesAudioLocal;
        }
        if ($attr_name === 'dureeTotalAudio' && isset($this->dureeTotalAudioLocal)) {
            return $this->dureeTotalAudioLocal;
        }
        return parent::__get($attr_name);
    }
}