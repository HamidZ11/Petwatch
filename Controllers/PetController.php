<?php
require_once 'Models/PetDataSet.php';

//Handles displaying and filtering pets

class PetController {
    public $petsDataSet;
    public int $currentPage = 1;
    public int $totalPages = 1;
    public int $pageSize = 10;
    public int $totalCount = 0;

    public function __construct() {
        $dataSet = new PetDataSet();

        //Filters and Sorting
        $keyword = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $type = $_GET['type'] ?? '';
        $minAge = $_GET['minAge'] ?? '';
        $maxAge = $_GET['maxAge'] ?? '';
        $sort = $_GET['sort'] ?? 'dateAdded DESC';

        //Pagination - only 10 listings per page
        $limit = 10;
        $totalPets = $dataSet->getTotalPetsCount($keyword, $status, $type, $minAge, $maxAge);
        $totalPages = max(1, (int)ceil($totalPets / $limit));

        $page = isset($_GET['pageNum']) ? (int)$_GET['pageNum'] : 1;
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $limit;

        $this->petsDataSet = $dataSet->searchPets($keyword, $status, $type, $minAge, $maxAge, $limit, $offset, $sort);
        $this->currentPage = $page;
        $this->totalPages = $totalPages;
        $this->pageSize = $limit;
        $this->totalCount = $totalPets;
    }

    //Get latest pets (for homepage)
    public function getRecentPets($limit = 3) {
        $dataSet = new PetDataSet();
        return $dataSet->getRecentPets($limit);
    }
}
?>
