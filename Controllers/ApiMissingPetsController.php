<?php
require_once('Models/SightingsDataSet.php');

class ApiMissingPetsController {
    public function index() {
        header('Content-Type: application/json; charset=utf-8');

        $ds = new SightingsDataSet();
        $rows = $ds->fetchSightingsWithPets();

        echo json_encode([
            'ok' => true,
            'data' => $rows
        ]);
        exit;
    }
}
