<?php
declare(strict_types=1);

namespace iutnc\deefy\render;

use iutnc\deefy\audio\lists\AudioList;
use iutnc\deefy\audio\tracks\AlbumTrack;
use iutnc\deefy\audio\tracks\AudioTrack;
use iutnc\deefy\audio\tracks\PodcastTrack;

class AudioListRenderer implements Renderer {
    private AudioList $al;

    public function __construct(AudioList $a) {
        $this->al = $a;
    }

    public function render(int $selector = Renderer::COMPACT): string {
        // récupérer les propriétés
        $nom = $this->al->__get('nom');
        $pistes = $this->al->__get('tabAudio');
        $nbPistesAudio = $this->al->__get('nbPistesAudio');
        $dureeTotalAudio = $this->al->__get('dureeTotalAudio');

        // construire le HTML
        $html = "<div class='audio-list'>";
        $html .= "<h2>$nom</h2>";

        // afficher chaque piste
        foreach ($pistes as $piste) {
            if ($piste instanceof AlbumTrack) {
                $renderer = new AlbumTrackRenderer($piste);
            } else if ($piste instanceof PodcastTrack) {
                $renderer = new PodcastTrackRenderer($piste);
            } else {
                die("Type de piste inconnu");
            }
            $html .= "<p>" . $renderer->render(Renderer::COMPACT) . "</p>";
        }

        // infos de fin
        $html .= "<p>Nombre de pistes : $nbPistesAudio</p>";
        $html .= "<p>Durée totale : $dureeTotalAudio secondes</p>";
        $html .= "</div>";

        return $html;
    }
}