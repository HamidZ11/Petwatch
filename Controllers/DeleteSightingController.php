<?php
require_once 'Database/Database.php';

class DeleteSightingController {
    private $db;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->db = Database::getInstance();

        // Must be logged in
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $userID = $_SESSION['user']['userID'];
        $sightingID = $_GET['sightingID'] ?? null;

        if (!$sightingID) {
            $_SESSION['flashMessage'] = "❌ Invalid sighting ID.";
            header('Location: index.php?page=pets');
            exit;
        }

        // Check ownership
        $check = $this->db->prepare("SELECT userID FROM sightings WHERE sightingID = :sightingID");
        $check->bindParam(':sightingID', $sightingID, PDO::PARAM_INT);
        $check->execute();
        $ownerID = $check->fetchColumn();

        if ($ownerID != $userID) {
            $_SESSION['flashMessage'] = "⚠️ You are not allowed to delete this sighting.";
            header('Location: index.php?page=pets');
            exit;
        }

        // Delete the sighting
        $stmt = $this->db->prepare("DELETE FROM sightings WHERE sightingID = :sightingID AND userID = :userID");
        $success = $stmt->execute([
            ':sightingID' => $sightingID,
            ':userID' => $userID
        ]);

        $_SESSION['flashMessage'] = $success
            ? "✅ Sighting deleted successfully!"
            : "❌ Failed to delete sighting.";

        // Redirect to the pet's sightings list
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