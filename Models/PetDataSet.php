<?php
require_once 'Database/Database.php';
require_once 'Models/PetData.php';

//Handles all database queries related to pets
class PetDataSet {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance(); //Use shared DB connection
    }

    //Fetch all pets, newest first
    public function fetchAllPets() {
        $sql = 'SELECT * FROM pets ORDER BY dateAdded DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $dataSet = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dataSet[] = new PetData($row);
        }
        return $dataSet;
    }

    //function to search pets with option filters/sort
    public function searchPets($keyword = '', $status = '', $type = '', $minAge = '', $maxAge = '', $limit = 10, $offset = 0, $sort = 'dateAdded DESC') {
        $allowedSorts = ['dateAdded DESC', 'dateAdded ASC', 'petID ASC', 'petID DESC', 'name ASC', 'name DESC'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'dateAdded DESC';
        }

        $sql = "
            SELECT p.*,
                   s.latitude AS latestLatitude,
                   s.longitude AS latestLongitude
            FROM pets p
            LEFT JOIN sightings s ON s.sightingID = (
                SELECT s2.sightingID
                FROM sightings s2
                WHERE s2.petID = p.petID
                ORDER BY s2.dateReported DESC, s2.sightingID DESC
                LIMIT 1
            )
            WHERE 1=1
        ";
        $params = [];

        if (!empty($keyword)) {
            $sql .= " AND (name LIKE :keyword OR type LIKE :keyword OR description LIKE :keyword)";
            $params[':keyword'] = "%$keyword%";
        }

        if (!empty($status)) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }

        if (!empty($type)) {
            $sql .= " AND type = :type";
            $params[':type'] = $type;
        }

        if (!empty($minAge)) {
            $sql .= " AND age >= :minAge";
            $params[':minAge'] = $minAge;
        }

        if (!empty($maxAge)) {
            $sql .= " AND age <= :maxAge";
            $params[':maxAge'] = $maxAge;
        }

        $sql .= " ORDER BY $sort LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        $dataSet = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dataSet[] = new PetData($row);
        }

        return $dataSet;
    }

    //function to get recents pets (to display on home page)
    public function getRecentPets($limit = 3) {
        $sql = "SELECT * FROM pets ORDER BY dateAdded DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        $pets = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pets[] = new PetData($row);
        }
        return $pets;
    }

    // Get total pets count for current filters (pagination)
    public function getTotalPetsCount($keyword = '', $status = '', $type = '', $minAge = '', $maxAge = '') {
        $sql = "SELECT COUNT(*) FROM pets WHERE 1=1";
        $params = [];

        if (!empty($keyword)) {
            $sql .= " AND (name LIKE :keyword OR type LIKE :keyword OR description LIKE :keyword)";
            $params[':keyword'] = "%$keyword%";
        }

        if (!empty($status)) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }

        if (!empty($type)) {
            $sql .= " AND type = :type";
            $params[':type'] = $type;
        }

        if (!empty($minAge)) {
            $sql .= " AND age >= :minAge";
            $params[':minAge'] = $minAge;
        }

        if (!empty($maxAge)) {
            $sql .= " AND age <= :maxAge";
            $params[':maxAge'] = $maxAge;
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    //function to get a pet by their ID
    public function getPetByID($petID) {
        $sql = "SELECT * FROM pets WHERE petID = :petID";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':petID', $petID, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new PetData($row) : null;
    }

    //function gets all pets listed by a specific owner
    public function getPetsByOwner($ownerID) {
        $sql = "SELECT * FROM pets WHERE ownerID = :ownerID ORDER BY dateAdded DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':ownerID', $ownerID, PDO::PARAM_INT);
        $stmt->execute();

        $pets = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pets[] = new PetData($row);
        }

        return $pets;
    }

}
?>
