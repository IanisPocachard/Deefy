<?php
declare(strict_types=1);


namespace iutnc\deefy\repository;
require_once __DIR__ . '../../../../vendor/autoload.php';

\iutnc\deefy\repository\DeefyRepository::setConfig(__DIR__ . '/../config/deefy.db.ini');

use PDO;
use PDOException;
use iutnc\deefy\audio\lists\Playlist;


class DeefyRepository
{

    private static ?array $config = null;
    private static ?DeefyRepository $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        if (self::$config === null) {
            // Si la configuration n'est pas définie , impossible de lancer le constructeur
            throw new \Exception("Configuration non définie. Appeler setConfig() avant getInstance().");
        }

        // Acces aux attributs du .ini
        $host = self::$config['host'];
        $dbname = self::$config['dbname'];
        $user = self::$config['user'];
        $password = self::$config['password'];

        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";

        try {
            // Tentative de connexion a la BDD
            $this->pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false
            ]);
        } catch (PDOException $e) {
            // Connexion échouée
            throw new \Exception("Erreur de connexion : " . $e->getMessage());
        }
    }

    // --- CONFIGURATION & SINGLETON ---

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    public static function setConfig(string $file): void
    {
        // Si la configuration existe
        if (!file_exists($file)) {
            throw new \Exception("Fichier de config introuvable : $file");
        }
        // On la recupere
        self::$config = parse_ini_file($file);
    }

    // Si l'instance n'existe pas , on en crée une ( constructeur vide )
    public static function getInstance(): DeefyRepository
    {
        if (self::$instance === null) {
            self::$instance = new DeefyRepository();
        }
        // si elle existe dejà on la retourne
        return self::$instance;
    }

    // --- MÉTHODES DU TD13 ---

    // Récupérer toutes les playlists
    public function findAllPlaylists(): array
    {
        // Requete pour récuperer toutes les playlist
        $stmt = $this->pdo->query("SELECT * FROM playlist");
        return $stmt->fetchAll();
    }

    //  Sauvegarder une playlist vide (juste le nom)
    public function saveEmptyPlaylist(string $name): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO playlist (nom) VALUES (?)");
        $stmt->execute([$name]);
        return (int) $this->pdo->lastInsertId();
    }

    // Sauvegarder une piste (track)
    public function saveTrack(
        string $titre,
        string $genre,
        string $filename,
        int $duree,
        ?string $type = null,
        ?string $artiste_album = null,
        ?string $titre_album = null,
        ?int $annee_album = null,
        ?int $numero_album = null,
        ?string $auteur_podcast = null,
        ?string $date_posdcast = null
    ): int {
        $stmt = $this->pdo->prepare("
        INSERT INTO track
            (titre, genre, duree, filename, type, artiste_album, titre_album, annee_album, numero_album, auteur_podcast, date_posdcast)
        VALUES
            (:titre, :genre, :duree, :filename, :type, :artiste_album, :titre_album, :annee_album, :numero_album, :auteur_podcast, :date_posdcast)
    ");
        $stmt->execute([
            ':titre' => $titre,
            ':genre' => $genre,
            ':duree' => $duree,
            ':filename' => $filename,
            ':type' => $type,
            ':artiste_album' => $artiste_album,
            ':titre_album' => $titre_album,
            ':annee_album' => $annee_album,
            ':numero_album' => $numero_album,
            ':auteur_podcast' => $auteur_podcast,
            ':date_posdcast' => $date_posdcast,
        ]);
        return (int) $this->pdo->lastInsertId();
    }


    // Ajouter une piste existante à une playlist existante
    public function addTrackToPlaylist(int $playlist_id, int $track_id, int $no_piste): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO playlist2track (id_pl, id_track, no_piste_dans_liste) VALUES (?, ?, ?)");
        $stmt->execute([$playlist_id, $track_id, $no_piste]);
    }

    // (optionnel) récupérer les pistes d'une playlist id
    public function findTracksByPlaylist(int $playlist_id): array
    {
        $sql = "SELECT t.* FROM track t 
                JOIN playlist2track p2t ON t.id = p2t.id_track 
                WHERE p2t.id_pl = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$playlist_id]);
        return $stmt->fetchAll();
    }
    public function findPlaylistById(int $id): Playlist
    {
        // 1. Récupérer la playlist
        $stmt = $this->pdo->prepare("SELECT * FROM playlist WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        if (!$data) {
            throw new \Exception("Playlist introuvable");
        }

        // 2. Récupérer les pistes associées
        $stmt2 = $this->pdo->prepare(
            "SELECT t.* FROM track t 
         JOIN playlist2track p2t ON t.id = p2t.id_track
         WHERE p2t.id_pl = ?"
        );
        $stmt2->execute([$id]);
        $tracks = $stmt2->fetchAll();

        // 3. Créer l'objet Playlist
        $playlist = new Playlist($data['nom'], $tracks);

        return $playlist;
    }
    // Optionnel , trouver les playlist par l'ID d'un utilisateur
    public function findPlaylistsByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.* 
         FROM playlist p 
         JOIN user2playlist u2p ON p.id = u2p.id_pl 
         WHERE u2p.id_user = ?"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }



}
