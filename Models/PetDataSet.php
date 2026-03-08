<?php
require_once 'Database/Database.php';
require_once 'Models/PetData.php';

//Handles all database queries related to pets
class PetDataSet {
    private $db;

    public function __construct() {
        //Connect to the database using the shared instance
        $this->db = Database::getInstance(); //Use shared DB connection
    }

    //Gets all pets sorted by newest first
    public function fetchAllPets() {
        //SQL query to get all pets
        $sql = 'SELECT * FROM pets ORDER BY dateAdded DESC';
        //Prepare and run the query
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        //Turn each row into a PetData object
        $dataSet = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dataSet[] = new PetData($row);
        }
        return $dataSet;
    }

    //Searches pets with filters, sort, and pagination
    public function searchPets($keyword = '', $status = '', $type = '', $minAge = '', $maxAge = '', $limit = 10, $offset = 0, $sort = 'dateAdded DESC') {
        //Only allow safe sort options
        $allowedSorts = ['dateAdded DESC', 'dateAdded ASC', 'petID ASC', 'petID DESC', 'name ASC', 'name DESC'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'dateAdded DESC';
        }

        //Main query also grabs latest sighting coords for map links
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
        //Store query parameters before binding
        $params = [];

        //If search is a number, match by petID only, otherwise search by name only
        if ($keyword !== '') {
            if (ctype_digit((string)$keyword)) {
                $sql .= " AND p.petID = :petID";
                $params[':petID'] = (int)$keyword;
            } else {
                $sql .= " AND p.name LIKE :name";
                $params[':name'] = "%$keyword%";
            }
        }

        //Extra filters from the form
        if (!empty($status)) {
            $sql .= " AND p.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($type)) {
            $sql .= " AND p.type = :type";
            $params[':type'] = $type;
        }

        if (!empty($minAge)) {
            $sql .= " AND p.age >= :minAge";
            $params[':minAge'] = $minAge;
        }

        if (!empty($maxAge)) {
            $sql .= " AND p.age <= :maxAge";
            $params[':maxAge'] = $maxAge;
        }

        $sql .= " ORDER BY $sort LIMIT :limit OFFSET :offset";

        //Prepare SQL query
        $stmt = $this->db->prepare($sql);

        //Bind all filter values safely
        foreach ($params as $key => $value) {
            if ($key === ':petID') {
                $stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
                continue;
            }
            $stmt->bindValue($key, $value);
        }

        //Bind pagination values
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        //Execute query
        $stmt->execute();

        //Convert query rows into objects for the view
        $dataSet = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dataSet[] = new PetData($row);
        }

        return $dataSet;
    }

    //Gets recent pets for the homepage cards
    public function getRecentPets($limit = 3) {
        //SQL query for latest pets
        $sql = "SELECT * FROM pets ORDER BY dateAdded DESC LIMIT :limit";
        //Prepare, bind, and execute query
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        $pets = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pets[] = new PetData($row);
        }
        return $pets;
    }

    //Gets total count for pagination using the same filters
    public function getTotalPetsCount($keyword = '', $status = '', $type = '', $minAge = '', $maxAge = '') {
        //Count query used for pagination
        $sql = "SELECT COUNT(*) FROM pets WHERE 1=1";
        //Keep filter params in one array
        $params = [];

        //Same search rule as main query: number = petID, text = name
        if ($keyword !== '') {
            if (ctype_digit((string)$keyword)) {
                $sql .= " AND petID = :petID";
                $params[':petID'] = (int)$keyword;
            } else {
                $sql .= " AND name LIKE :name";
                $params[':name'] = "%$keyword%";
            }
        }

        //Apply optional filters
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
        //Bind filters safely before running count query
        foreach ($params as $key => $value) {
            if ($key === ':petID') {
                $stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
                continue;
            }
            $stmt->bindValue($key, $value);
        }
        //Execute count query and return total
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    //Gets one pet record by ID
    public function getPetByID($petID) {
        //SQL query to get one pet by id
        $sql = "SELECT * FROM pets WHERE petID = :petID";
        //Prepare query and bind id parameter
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':petID', $petID, PDO::PARAM_INT);
        //Run query and return pet object or null
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new PetData($row) : null;
    }

    //Gets all pets listed by one owner account
    public function getPetsByOwner($ownerID) {
        //SQL query to get pets for one owner
        $sql = "SELECT * FROM pets WHERE ownerID = :ownerID ORDER BY dateAdded DESC";
        //Prepare query and bind owner id
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':ownerID', $ownerID, PDO::PARAM_INT);
        //Execute query
        $stmt->execute();

        //Build PetData objects for manage listings page
        $pets = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pets[] = new PetData($row);
        }

        return $pets;
    }

}
?>
