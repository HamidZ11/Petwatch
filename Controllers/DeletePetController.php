<?php
require_once 'Database/Database.php';

//handles deleting a pet listing and its related sightings
class DeletePetController {
    public $message = '';

    public function __construct() {

        //make sure user is logged in
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?page=login');
            exit;
        }

        //check if petID was provided in the request
        if (!isset($_GET['petID']) || !is_numeric($_GET['petID'])) {
            $this->message = " Invalid Pet ID.";
            return;
        }

        $petID = $_GET['petID'];
        $db = Database::getInstance();

        //fetch pet details to verify it exists
        $stmt = $db->prepare("SELECT ownerID, imagePath FROM pets WHERE petID = :petID");
        $stmt->bindParam(':petID', $petID);
        $stmt->execute();
        $pet = $stmt->fetch(PDO::FETCH_ASSOC);

        //stop if pet cannot be found
        if (!$pet) {
            $this->message = " Pet not found.";
            return;
        }

        //check that the logged-in user owns this listing
        if ($pet['ownerID'] != $_SESSION['user']['userID']) {
            $this->message = " You are not allowed to delete this pet.";
            return;
        }

        //delete associated image file if it exists
        if (!empty($pet['imagePath']) && file_exists($pet['imagePath'])) {
            unlink($pet['imagePath']);
        }

        //delete related sightings first to avoid foreign key constraint errors
        $deleteSightings = $db->prepare("DELETE FROM sightings WHERE petID = :petID");
        $deleteSightings->bindParam(':petID', $petID);
        $deleteSightings->execute();

        //delete the pet listing
        $deleteStmt = $db->prepare("DELETE FROM pets WHERE petID = :petID");
        $deleteStmt->bindParam(':petID', $petID);

        //execute delete query
        if ($deleteStmt->execute()) {
            $this->message = " Pet listing deleted successfully.";
            header('Location: index.php?page=pets&deleted=1');
            exit;
        } else {
            $this->message = " Failed to delete pet listing.";
        }
    }
}
?>