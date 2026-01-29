<?php
require_once 'Database/Database.php';

//Handles reporting new pet sightings

class ReportSightingController {
    public $message;
    private $db;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->db = Database::getInstance(); // ✅ store database instance

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $petID = $_POST['petID'] ?? null;
            $description = trim($_POST['description'] ?? '');
            $latitude = trim($_POST['latitude'] ?? '');
            $longitude = trim($_POST['longitude'] ?? '');
            $userID = $_SESSION['user']['userID'] ?? null;

            //Check if Pet exists
            $check = $this->db->prepare("SELECT COUNT(*) FROM pets WHERE petID = :petID");
            $check->bindParam(':petID', $petID);
            $check->execute();
            $exists = $check->fetchColumn();

            if ($exists == 0) {
                $this->message = "❌ Invalid Pet ID — no such pet exists.";
                return;
            }

            //Insert the new sighting
            $sql = "INSERT INTO sightings (petID, description, latitude, longitude, userID, dateReported)
                    VALUES (:petID, :description, :latitude, :longitude, :userID, datetime('now'))";

            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                ':petID' => $petID,
                ':description' => $description,
                ':latitude' => $latitude,
                ':longitude' => $longitude,
                ':userID' => $userID
            ]);

            $this->message = $success
                ? "✅ Sighting recorded successfully!"
                : "❌ Failed to record sighting.";
        }
    }
}
?>