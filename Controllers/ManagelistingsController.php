<?php
require_once 'Models/PetDataSet.php';

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

        //Pass $view to the view page (.phmtl)
        $view = $this->view;
        require 'Views/manageListings.phtml';
    }
}