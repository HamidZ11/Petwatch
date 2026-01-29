
<?php
require_once 'Database/Database.php';

//Handles editing for reported sightings

class EditSightingController {
    public $view;
    public $message;
    private $db;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->db = Database::getInstance();
        $this->view = new stdClass();

        //Make sure user is logged in
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $userID = $_SESSION['user']['userID'];
        $sightingID = $_GET['sightingID'] ?? null;

        //Fetch existing sighting
        if ($sightingID) {
            $stmt = $this->db->prepare("SELECT * FROM sightings WHERE sightingID = :sightingID");
            $stmt->bindParam(':sightingID', $sightingID, PDO::PARAM_INT);
            $stmt->execute();
            $sighting = $stmt->fetch(PDO::FETCH_ASSOC);

            //Verify it belongs to the logged-in user
            if (!$sighting || $sighting['userID'] != $userID) {
                $this->message = "❌ You do not have permission to edit this sighting.";
                $this->view->sighting = null;
                require 'Views/editSighting.phtml';
                return;
            }

            $this->view->sighting = $sighting;
        } else {
            $this->message = "❌ Invalid sighting ID.";
            $this->view->sighting = null;
            require 'Views/editSighting.phtml';
            return;
        }

        //Handle update
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $description = trim($_POST['description'] ?? '');
            $latitude = trim($_POST['latitude'] ?? '');
            $longitude = trim($_POST['longitude'] ?? '');

            if (empty($description)) {
                $this->message = "⚠️ Description cannot be empty.";
            } else {
                $update = $this->db->prepare("
                    UPDATE sightings 
                    SET description = :description, latitude = :latitude, longitude = :longitude
                    WHERE sightingID = :sightingID AND userID = :userID
                ");
                $success = $update->execute([
                    ':description' => $description,
                    ':latitude' => $latitude,
                    ':longitude' => $longitude,
                    ':sightingID' => $sightingID,
                    ':userID' => $userID
                ]);

                if ($success) {
                    $this->message = "✅ Sighting updated successfully!";
                    //Refresh record
                    $stmt = $this->db->prepare("SELECT * FROM sightings WHERE sightingID = :sightingID");
                    $stmt->bindParam(':sightingID', $sightingID, PDO::PARAM_INT);
                    $stmt->execute();
                    $this->view->sighting = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $this->message = "❌ Failed to update sighting.";
                }
            }
        }

        require 'Views/editSighting.phtml';
    }
}
