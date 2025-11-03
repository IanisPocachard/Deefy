<?php
declare(strict_types=1);

namespace iutnc\deefy\render;

interface Renderer {

    const COMPACT = 1, LONG = 2;

    public function render(int $selector): string;
}