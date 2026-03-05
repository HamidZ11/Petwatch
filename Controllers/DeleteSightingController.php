<?php
require_once 'Database/Database.php';

//handles deleting a pet sighting
class DeleteSightingController {
    private $db;

    public function __construct() {

        //start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->db = Database::getInstance();

        //user must be logged in to delete a sighting
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $userID = $_SESSION['user']['userID'];
        $sightingID = $_GET['sightingID'] ?? null;

        //check that a valid sighting ID was provided
        if (!$sightingID) {
            $_SESSION['flashMessage'] = " Invalid sighting ID.";
            header('Location: index.php?page=pets');
            exit;
        }

        //check ownership of the sighting
        $check = $this->db->prepare("SELECT userID FROM sightings WHERE sightingID = :sightingID");
        $check->bindParam(':sightingID', $sightingID, PDO::PARAM_INT);
        $check->execute();
        $ownerID = $check->fetchColumn();

        //only the user who created the sighting can delete it
        if ($ownerID != $userID) {
            $_SESSION['flashMessage'] = " You are not allowed to delete this sighting.";
            header('Location: index.php?page=pets');
            exit;
        }

        //delete the sighting record
        $stmt = $this->db->prepare("DELETE FROM sightings WHERE sightingID = :sightingID AND userID = :userID");
        $success = $stmt->execute([
            ':sightingID' => $sightingID,
            ':userID' => $userID
        ]);

        //set success or error message
        $_SESSION['flashMessage'] = $success
            ? " Sighting deleted successfully!"
            : " Failed to delete sighting.";

        //redirect back to the sightings list for the pet
        $petID = $_GET['petID'] ?? null;
        if ($petID) {
            header("Location: index.php?page=viewSightings&petID=" . urlencode($petID));
        } else {
            header("Location: index.php?page=pets");
        }

        exit;
    }
}
?>