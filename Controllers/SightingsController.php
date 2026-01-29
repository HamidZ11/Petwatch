<?php
require_once('Models/SightingsDataSet.php');

//Displays ALL reported pet sightings
class SightingsController {
    public $view;

    public function __construct() {
        $this->view = new stdClass();
        $this->view->pageTitle = 'All Sightings';

        // Default sort order
        $sort = $_GET['sort'] ?? 'dateReported DESC';

        // Pagination setup
        $limit = 10;
        $pageNum = isset($_GET['pageNum']) ? max(1, (int)$_GET['pageNum']) : 1;

        $sightingsDataSet = new SightingsDataSet();
        $totalSightings = $sightingsDataSet->getTotalSightingsCount();
        $totalPages = max(1, (int)ceil($totalSightings / $limit));
        $pageNum = min($pageNum, $totalPages);
        $offset = ($pageNum - 1) * $limit;

        $this->view->sightingsDataSet = $sightingsDataSet->fetchAllSightings($sort, $limit, $offset);
        $this->view->currentPage = $pageNum;
        $this->view->totalPages = $totalPages;

        // Load view
        $view = $this->view;
        require_once('Views/sightings.phtml');
    }
}
?>
