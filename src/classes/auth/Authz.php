<?php
declare(strict_types=1);

namespace iutnc\deefy\auth;

require_once __DIR__ . '/../../../vendor/autoload.php';

use iutnc\deefy\repository\DeefyRepository;

class Authz {

    // Fonction pour regarder si l'utilisateur qui accède 
    public static function checkRole(int $roleExpected): void {
        // recupere l'utilisateur connecté si possible
        $user = AuthnProvider::getSignedInUser();
        // Si le role de l'utilisateur est pas celui qu'on attend une erreur se produit
        if ((int)$user['role'] !== $roleExpected) {
            throw new AuthnException("Accès refusé : rôle insuffisant.");
        }
    }

    // Fonction pour check si la playlist avec l'id $playlistId appartient bien a l'utilisateur qui la demande
    public static function checkPlaylistOwner(int $playlistId): void {
        // On recupere l'utilisateur connecté & on créer un singleton pour manipule la BDD
        $user = AuthnProvider::getSignedInUser();
        $repo = DeefyRepository::getInstance();

        // Vérifie si la playlist appartient à l'utilisateur
        $stmt = $repo->getPDO()->prepare("
            SELECT id_pl FROM user2playlist 
            WHERE id_user = ? AND id_pl = ?
        ");
        $stmt->execute([$user['id'], $playlistId]);
        $owned = $stmt->fetch();

        // Si elle ne lui appartient pas & qu'il n'est pas admin il ne peut pas la voir !
        if (!$owned && (int)$user['role'] !== 100) {
            throw new AuthnException("Accès refusé : cette playlist ne vous appartient pas.");
        }
    }
}
