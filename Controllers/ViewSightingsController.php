<?php
require_once('Database/Database.php');

//Displays all sightings recorded to a specific pet
class ViewSightingsController {
    public $sightingsDataSet;

    public function __construct() {
        $this->sightingsDataSet = [];
        $this->fetchSightings();
    }

    //Get all sightings for the selected pet
    private function fetchSightings() {
        $db = new Database();
        $pdo = Database::getInstance();

        if (isset($_GET['petID']) && is_numeric($_GET['petID'])) {
            $petID = $_GET['petID'];
            $stmt = $pdo->prepare('SELECT s.*, u.username FROM sightings s LEFT JOIN users u ON s.userID = u.userID WHERE s.petID = :petID ORDER BY s.dateReported DESC');
            $stmt->bindParam(':petID', $petID);
            $stmt->execute();
            $this->sightingsDataSet = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}
?>