<?php
require_once 'Database/Database.php';

//Handles all DB queries related to pet sightings
class SightingsDataSet {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance(); //Connects to DB
    }

    // Fetch all sightings (for All Sightings page) with pagination support
    public function fetchAllSightings($sort = 'dateReported DESC', $limit = 10, $offset = 0) {
        $allowedSorts = ['dateReported DESC', 'dateReported ASC', 'petName ASC', 'petName DESC'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'dateReported DESC';
        }

        $sql = "SELECT s.*, 
                       p.name AS petName, 
                       u.username AS reporterName
                FROM sightings s
                LEFT JOIN pets p ON s.petID = p.petID
                LEFT JOIN users u ON s.userID = u.userID
                ORDER BY $sort
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //Get all sightings created by a specific user
    public function getSightingsByUser($userID) {
        $sql = "SELECT s.*, p.name AS petName
                FROM sightings s
                LEFT JOIN pets p ON s.petID = p.petID
                WHERE s.userID = :userID
                ORDER BY s.dateReported DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get total number of sightings for pagination
    public function getTotalSightingsCount() {
        $sql = "SELECT COUNT(*) FROM sightings";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    // Fetch missing pets with their latest sighting (for map/list)
    public function fetchMissingPetsWithLatestSighting($limit = 500, $offset = 0) {
        $sql = "
            SELECT
                p.petID,
                p.name,
                p.type,
                p.age,
                p.status,
                p.description AS petDescription,
                s.sightingID,
                s.description AS sightingDescription,
                s.latitude,
                s.longitude,
                s.dateReported,
                u.username AS reporterName
            FROM pets p
            LEFT JOIN sightings s ON s.sightingID = (
                SELECT s2.sightingID
                FROM sightings s2
                WHERE s2.petID = p.petID
                ORDER BY s2.dateReported DESC, s2.sightingID DESC
                LIMIT 1
            )
            LEFT JOIN users u ON s.userID = u.userID
            WHERE s.sightingID IS NOT NULL
            ORDER BY p.petID DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Count sightings with pet fields, optionally filtered by search term
    public function countSightingsWithPets($search = '') {
        $search = trim((string)$search);
        $sql = "
            SELECT COUNT(*)
            FROM sightings s
            INNER JOIN pets p ON s.petID = p.petID
            WHERE (
                :search = ''
                OR p.name LIKE :searchLike
                OR p.type LIKE :searchLike
                OR s.description LIKE :searchLike
            )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':search', $search, PDO::PARAM_STR);
        $stmt->bindValue(':searchLike', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    // Fetch sightings with pet details (for map markers), with optional search + pagination
    public function fetchSightingsWithPets($search = '', $limit = 20, $offset = 0) {
        $search = trim((string)$search);
        $sql = "
            SELECT
                s.sightingID,
                s.petID,
                s.description AS sightingDescription,
                s.latitude,
                s.longitude,
                s.dateReported,
                p.name,
                p.type,
                p.status,
                p.description AS petDescription,
                u.username AS reporterName
            FROM sightings s
            INNER JOIN pets p ON s.petID = p.petID
            LEFT JOIN users u ON s.userID = u.userID
            WHERE (
                :search = ''
                OR p.name LIKE :searchLike
                OR p.type LIKE :searchLike
                OR s.description LIKE :searchLike
            )
            ORDER BY s.dateReported DESC, s.sightingID DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':search', $search, PDO::PARAM_STR);
        $stmt->bindValue(':searchLike', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch all sightings for a specific pet (used by "Show on map")
    public function fetchSightingsByPet($petID) {
        $sql = "
            SELECT
                s.sightingID,
                s.petID,
                s.description AS sightingDescription,
                s.latitude,
                s.longitude,
                s.dateReported,
                p.name,
                p.type,
                p.status,
                p.description AS petDescription,
                u.username AS reporterName
            FROM sightings s
            INNER JOIN pets p ON s.petID = p.petID
            LEFT JOIN users u ON s.userID = u.userID
            WHERE s.petID = :petID
            ORDER BY s.dateReported DESC, s.sightingID DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':petID', (int)$petID, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Insert a new sighting (AJAX endpoint will call this)
    public function insertSighting($petId, $userId, $description, $latitude, $longitude) {
        $sql = "
            INSERT INTO sightings (petID, userID, description, latitude, longitude, dateReported)
            VALUES (:petID, :userID, :description, :latitude, :longitude, datetime('now'))
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':petID', (int)$petId, PDO::PARAM_INT);
        $stmt->bindValue(':userID', (int)$userId, PDO::PARAM_INT);
        $stmt->bindValue(':description', (string)$description, PDO::PARAM_STR);
        $stmt->bindValue(':latitude', (float)$latitude);
        $stmt->bindValue(':longitude', (float)$longitude);

        return $stmt->execute();
    }
}
?>
