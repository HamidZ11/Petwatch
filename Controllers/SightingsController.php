<?php
require_once('Models/SightingsDataSet.php');

//Controller that loads the All Sightings page
class SightingsController {
    public $view;

    public function __construct() {
        //Create a view object to pass data into the page
        $this->view = new stdClass();
        $this->view->pageTitle = 'All Sightings';

        //Get sort and search input from the URL
        $sort = $_GET['sort'] ?? 'dateReported DESC';
        $search = $_GET['search'] ?? '';

        //Setup pagination values
        $limit = 10;
        $pageNum = isset($_GET['pageNum']) ? max(1, (int)$_GET['pageNum']) : 1;

        //Run database queries for total count and current page results
        $sightingsDataSet = new SightingsDataSet();
        $totalSightings = $sightingsDataSet->getTotalSightingsCount($search);
        $totalPages = max(1, (int)ceil($totalSightings / $limit));

        //Keep page number inside valid range
        $pageNum = min($pageNum, $totalPages);
        $offset = ($pageNum - 1) * $limit;

        //Fetch the filtered sightings and pass them to the view
        $this->view->sightingsDataSet = $sightingsDataSet->fetchAllSightings($sort, $limit, $offset, $search);
        $this->view->currentPage = $pageNum;
        $this->view->totalPages = $totalPages;

        //Load the sightings view
        $view = $this->view;
        require_once('Views/sightings.phtml');
    }
}
?>
