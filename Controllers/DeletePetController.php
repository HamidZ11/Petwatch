<?php
require_once 'Database/Database.php';

//Handles deleting a pet listing (and associated sightings)
class DeletePetController {
    public $message = '';

    public function __construct() {

        //Make sure user is logged in
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?page=login');
            exit;
        }

        //Check if petID is provided
        if (!isset($_GET['petID']) || !is_numeric($_GET['petID'])) {
            $this->message = "❌ Invalid Pet ID.";
            return;
        }

        $petID = $_GET['petID'];
        $db = Database::getInstance();

        //Verify pet exists
        $stmt = $db->prepare("SELECT ownerID, imagePath FROM pets WHERE petID = :petID");
        $stmt->bindParam(':petID', $petID);
        $stmt->execute();
        $pet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pet) {
            $this->message = "❌ Pet not found.";
            return;
        }

        //Check if logged-in user actually is the one who owns the pet listing
        if ($pet['ownerID'] != $_SESSION['user']['userID']) {
            $this->message = "🚫 You are not allowed to delete this pet.";
            return;
        }

        //Delete associated image if exists
        if (!empty($pet['imagePath']) && file_exists($pet['imagePath'])) {
            unlink($pet['imagePath']);
        }

        //Delete related sightings first (to avoid FK constraint)
        $deleteSightings = $db->prepare("DELETE FROM sightings WHERE petID = :petID");
        $deleteSightings->bindParam(':petID', $petID);
        $deleteSightings->execute();

        //Delete the pet record
        $deleteStmt = $db->prepare("DELETE FROM pets WHERE petID = :petID");
        $deleteStmt->bindParam(':petID', $petID);

        if ($deleteStmt->execute()) {
            $this->message = "✅ Pet listing deleted successfully.";
            header('Location: index.php?page=pets&deleted=1');
            exit;
        } else {
            $this->message = "❌ Failed to delete pet listing.";
        }
    }
}
?>
