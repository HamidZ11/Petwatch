<?php
require_once 'Models/PetDataSet.php';
require_once 'Database/Database.php';

//Displays all pet listings by the logged-in user

class ManageListingsController {
    public $view;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        //Redirect users who are not logged in
        if (!isset($_SESSION['user']) || $_SESSION['user']['userType'] !== 'Owner') {
            header('Location: index.php?page=login');
            exit;
        }

        $this->view = new stdClass();
        $this->view->pageTitle = 'Manage My Listings';

        $ownerID = $_SESSION['user']['userID'];
        $petDataSet = new PetDataSet();

        //Get all pets for logged-in owner
        $this->view->pets = $petDataSet->getPetsByOwner($ownerID);

        // Get number of sightings per pet for this owner's listings
        $db = Database::getInstance();
        $countSql = "
            SELECT p.petID, COUNT(s.sightingID) AS sightingCount
            FROM pets p
            LEFT JOIN sightings s ON p.petID = s.petID
            WHERE p.ownerID = :ownerID
            GROUP BY p.petID
        ";
        $countStmt = $db->prepare($countSql);
        $countStmt->bindValue(':ownerID', (int)$ownerID, PDO::PARAM_INT);
        $countStmt->execute();

        $this->view->sightingCounts = [];
        foreach ($countStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->view->sightingCounts[(int)$row['petID']] = (int)$row['sightingCount'];
        }

        //Pass $view to the view page (.phmtl)
        $view = $this->view;
        require 'Views/manageListings.phtml';
    }
}
