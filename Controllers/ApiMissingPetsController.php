<?php
require_once('Models/SightingsDataSet.php');

//handles AJAX request to fetch missing pets and their sightings for the map
class ApiMissingPetsController {

    public function index() {

        //return JSON response to the client
        header('Content-Type: application/json; charset=utf-8');

        try {

            //get search query from the request
            $rawSearch = (string)($_GET['search'] ?? '');

            //clean search input and limit length before querying the database
            $search = trim(substr(preg_replace('/[\x00-\x1F\x7F]/u', '', $rawSearch), 0, 100));

            //get current page number (supports several parameter names)
            $pageParam = $_GET['pageNum'] ?? $_GET['page_num'] ?? $_GET['p'] ?? 1;

            //ensure page parameter is numeric
            if (is_string($pageParam) && !ctype_digit($pageParam)) {
                $pageParam = 1;
            }

            $page = max(1, (int)$pageParam);

            //limit number of results returned per request
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            //optional filter when showing a specific pet on the map
            $petID = isset($_GET['petID']) ? (int)$_GET['petID'] : null;

            $ds = new SightingsDataSet();

            if ($petID) {
                //when a specific pet is requested ("Show on map"), return all sightings for that pet
                $rows = $ds->fetchSightingsByPet($petID);
                $total = count($rows);
                $page = 1;
            } else {
                //default behaviour: fetch paginated sightings dataset with optional search
                $rows = $ds->fetchSightingsWithPets($search, $limit, $offset);
                $total = $ds->countSightingsWithPets($search);
            }

            //return successful JSON response
            echo json_encode([
                'ok' => true,
                'data' => $rows,
                'page' => $page,
                'limit' => $limit,
                'count' => count($rows),
                'total' => $total
            ]);

        } catch (Throwable $e) {

            //return error response if something goes wrong
            http_response_code(500);

            echo json_encode([
                'ok' => false,
                'error' => 'Failed to fetch sightings',
                'data' => [],
                'page' => 1,
                'limit' => 20,
                'count' => 0,
                'total' => 0
            ]);
        }

        exit;
    }
}
