<?php
declare(strict_types=1);

namespace iutnc\deefy\render;

abstract class AudioTrackRenderer implements Renderer {
    public function render(int $selector): string {
        switch ($selector) {
            case Renderer::COMPACT:
                return $this->renderCompact();
            case Renderer::LONG:
                return $this->renderLong();
            default:
                return "<p>Mode d'affichage inconnu.</p>";
        }
    }

    abstract public function renderCompact(): string;
    abstract public function renderLong(): string;
}