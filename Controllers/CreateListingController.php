<?php
require_once 'Database/Database.php';

//Handles creating a pet listing with an optional image upload
class CreateListingController {
    public $message;

    public function __construct() {
        $db = Database::getInstance();
        if (!isset($_SESSION['user'])) {
            return; //Stops if user is not logged in, but stay on the same page
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Image upload
            $imagePath = null;
            if (
                isset($_FILES['petImage']) &&
                $_FILES['petImage']['error'] === UPLOAD_ERR_OK
            ) {
                $fileTmpPath = $_FILES['petImage']['tmp_name'];
                $fileName = $_FILES['petImage']['name'];
                $fileSize = $_FILES['petImage']['size'];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $fileMimeType = mime_content_type($fileTmpPath);

                if ($fileSize > 2 * 1024 * 1024) {
                    $this->message = "⚠️ Upload failed — maximum file size is 2MB.";
                    echo "<script>alert('⚠️ Upload failed — maximum file size is 2MB.');</script>";
                } else {
                    $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/x-png', 'image/gif'];
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

                    if (in_array($fileMimeType, $allowedMimeTypes) && in_array($fileExt, $allowedExtensions)) {
                        $uploadsDir = __DIR__ . '/../uploads/';
                        if (!is_dir($uploadsDir)) {
                            mkdir($uploadsDir, 0755, true);
                        }

                        $uniqueName = uniqid('pet_', true) . '.' . $fileExt;
                        $destPath = $uploadsDir . $uniqueName;

                        if (move_uploaded_file($fileTmpPath, $destPath)) {
                            // store relative path for database
                            $imagePath = 'uploads/' . $uniqueName;
                        } else {
                            $this->message = "⚠️ File upload failed — please check folder permissions.";
                        }
                    } else {
                        echo "<script>alert('⚠️ Upload failed — maximum file size is 2MB.');</script>";
                        $this->message = "⚠️ Upload failed — maximum file size is 2MB.";
                    }
                }
            }

            //Inserts new pet listings into database
            $sql = "INSERT INTO pets (name, type, age, description, ownerID, status, dateAdded, imagePath)
                    VALUES (:name, :type, :age, :description, :ownerID, :status, datetime('now'), :imagePath)";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':name', $_POST['name']);
            $stmt->bindParam(':type', $_POST['type']);
            $stmt->bindParam(':age', $_POST['age']);
            $stmt->bindParam(':description', $_POST['description']);
            $ownerID = $_SESSION['user']['userID'];
            $stmt->bindParam(':ownerID', $ownerID);
            $stmt->bindParam(':status', $_POST['status']);
            $stmt->bindParam(':imagePath', $imagePath);

            if ($stmt->execute()) {
                $this->message = "✅ Pet listing added successfully!";
            } else {
                $this->message = "❌ Failed to add pet listing.";
            }
        }
    }
}
?>
