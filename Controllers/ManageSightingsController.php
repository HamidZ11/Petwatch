<?php
require_once('Models/SightingsDataSet.php');

//Displays all sightings reported by logged-in user
class ManageSightingsController {
    public $view;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        //Redirect if not logged in
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $this->view = new stdClass();
        $this->view->pageTitle = 'My Sightings';

        $userID = $_SESSION['user']['userID'];

        $sightingsDataSet = new SightingsDataSet();
        $this->view->sightings = $sightingsDataSet->getSightingsByUser($userID);

        // make $view accessible to the view file (.phtml)
        $view = $this->view;


        require_once('Views/manageSightings.phtml');
    }
}
?>