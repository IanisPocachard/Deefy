<?php
declare(strict_types=1);

namespace iutnc\deefy\render;

use iutnc\deefy\audio\tracks\AlbumTrack;

class AlbumTrackRenderer extends AudioTrackRenderer {
    private AlbumTrack $track;

    public function __construct(AlbumTrack $track) {
        $this->track = $track;
    }

    public function renderCompact(): string {
        return <<<HTML
        <div class="album-track compact">
            Musique : <strong>{$this->track->__get('titre')}</strong> - {$this->track->__get('album')}
            <audio controls>
                <source src="src/classes/audio/audios/{$this->track->__get('nom_fichier_audio')}" type="audio/mpeg">
                Votre navigateur ne supporte pas la balise audio.
            </audio>
        </div>
        HTML;
    }

    public function renderLong(): string {
        return <<<HTML
        <div class="album-track long">
            <h2>Musique : {$this->track->__get('titre')}</h2>
            <p>Album : {$this->track->__get('album')}</p>
            <p>Numéro de piste : {$this->track->__get('num_piste')}</p>
            <p>Année : {$this->track->__get('annee')}</p>
            <p>Genre : {$this->track->__get('genre')}</p>
            <audio controls>
                <source src="src/classes/audio/audios/{$this->track->__get('nom_fichier_audio')}" type="audio/mpeg">
                Votre navigateur ne supporte pas la balise audio.
            </audio>
        </div>
        HTML;
    }
}