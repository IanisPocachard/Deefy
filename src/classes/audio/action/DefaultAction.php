<?php
declare(strict_types=1);
namespace iutnc\deefy\audio\action;

require_once __DIR__ . '\..\..\..\../vendor/autoload.php';

use iutnc\deefy\audio\action\Action;

class DefaultAction extends Action {

    public function execute(): string {
        return <<<HTML
        <h1>Bienvenue sur DeefyApp </h1>
        <p>Ceci est la page d’accueil de l’application.</p>
        HTML;
    }
}
