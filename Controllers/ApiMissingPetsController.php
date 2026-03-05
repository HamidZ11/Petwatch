<?php
require_once('Models/SightingsDataSet.php');

class ApiMissingPetsController {
    public function index() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $rawSearch = (string)($_GET['search'] ?? '');
            // Keep search input bounded and strip control chars before querying.
            $search = trim(substr(preg_replace('/[\x00-\x1F\x7F]/u', '', $rawSearch), 0, 100));
            $pageParam = $_GET['pageNum'] ?? $_GET['page_num'] ?? $_GET['p'] ?? 1;
            // Router already uses "page=api_missing_pets", so pagination uses pageNum/page_num/p.
            if (is_string($pageParam) && !ctype_digit($pageParam)) {
                $pageParam = 1;
            }
            $page = max(1, (int)$pageParam);

            $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            $petID = isset($_GET['petID']) ? (int)$_GET['petID'] : null;

            $ds = new SightingsDataSet();

            if ($petID) {
                // When a specific pet is requested ("Show on map"), bypass pagination
                $rows = $ds->fetchSightingsByPet($petID);
                $total = count($rows);
                $page = 1;
            } else {
                // Default behaviour: paginated + searchable dataset
                $rows = $ds->fetchSightingsWithPets($search, $limit, $offset);
                $total = $ds->countSightingsWithPets($search);
            }

            echo json_encode([
                'ok' => true,
                'data' => $rows,
                'page' => $page,
                'limit' => $limit,
                'count' => count($rows),
                'total' => $total
            ]);
        } catch (Throwable $e) {
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
