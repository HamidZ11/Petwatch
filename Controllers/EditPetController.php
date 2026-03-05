<?php
require_once 'Database/Database.php';
require_once 'Models/PetDataSet.php';

//handles editing an existing pet listing
class EditPetController {
    public $petData;
    public $message = '';

    public function __construct() {

        //start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $db = Database::getInstance();
        $petDataSet = new PetDataSet();

        //make sure user is logged in
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?page=login');
            exit;
        }

        //validate the pet ID from request
        if (!isset($_GET['petID']) || !is_numeric($_GET['petID'])) {
            $this->message = "Invalid Pet ID.";
            return;
        }

        $petID = $_GET['petID'];

        //fetch pet data from database
        $stmt = $db->prepare("SELECT * FROM pets WHERE petID = :petID");
        $stmt->bindParam(':petID', $petID);
        $stmt->execute();
        $pet = $stmt->fetch(PDO::FETCH_ASSOC);

        //stop if pet does not exist
        if (!$pet) {
            $this->message = "Pet not found.";
            return;
        }

        //ensure logged-in user owns this pet listing
        if ($pet['ownerID'] != $_SESSION['user']['userID']) {
            $this->message = "You are not allowed to edit this pet.";
            return;
        }

        $this->petData = $pet;

        //handle update form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $name = trim($_POST['name']);
            $type = trim($_POST['type']);
            $age = trim($_POST['age']);
            $description = trim($_POST['description']);
            $status = $_POST['status'];

            //handle optional new image upload
            $imagePath = $pet['imagePath'];

            if (isset($_FILES['petImage']) && $_FILES['petImage']['error'] === UPLOAD_ERR_OK) {

                $targetDir = __DIR__ . '/../uploads/';

                //create uploads folder if it does not exist
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                $fileName = time() . '_' . basename($_FILES['petImage']['name']);
                $targetFilePath = $targetDir . $fileName;

                $allowedExtensions = ['jpg', 'jpeg'];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                //validate file extension
                if (in_array($fileExt, $allowedExtensions)) {

                    //move uploaded image to uploads folder
                    if (move_uploaded_file($_FILES['petImage']['tmp_name'], $targetFilePath)) {
                        $imagePath = 'uploads/' . $fileName;
                    } else {
                        echo "<script>alert('Upload failed — please check folder permissions.');</script>";
                        $this->message = "Upload failed — please check folder permissions.";
                    }

                } else {
                    echo "<script>alert('Upload failed — maximum file size is 2MB.');</script>";
                    $this->message = "Upload failed — maximum file size is 2MB.";
                }
            }

            //update pet record in database
            $sql = "UPDATE pets 
                    SET name = :name, type = :type, age = :age, description = :description, 
                        status = :status, imagePath = :imagePath 
                    WHERE petID = :petID";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':age', $age);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':imagePath', $imagePath);
            $stmt->bindParam(':petID', $petID);

            //execute update query
            if ($stmt->execute()) {

                $this->message = "Pet listing updated successfully.";

                //refresh page to display updated data
                header("Location: index.php?page=editPet&petID=$petID&updated=1");
                exit;

            } else {

                $this->message = "Failed to update pet listing.";
            }
        }
    }
}
?>