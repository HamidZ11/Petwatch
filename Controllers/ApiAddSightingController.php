<?php
require_once('Models/SightingsDataSet.php');

//handles AJAX request to add a new pet sighting to the database
class ApiAddSightingController {

    public function index() {

        //return JSON response to the client
        header('Content-Type: application/json; charset=utf-8');

        //user must be logged in to report a sighting
        if (!isset($_SESSION['user']['userID'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $userId = (int)$_SESSION['user']['userID'];

        //CSRF token check to protect the request
        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'CSRF failed']);
            exit;
        }

        //read JSON data sent from the client
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true);

        $petId = (int)($body['petId'] ?? 0);
        $desc = trim((string)($body['description'] ?? ''));
        $lat = (float)($body['lat'] ?? 0);
        $lng = (float)($body['lng'] ?? 0);

        //validate input values
        if ($petId <= 0 || $desc === '' || strlen($desc) > 280) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid input']);
            exit;
        }

        //validate coordinates
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid coordinates']);
            exit;
        }

        //remove any HTML tags from description
        $desc = strip_tags($desc);

        $ds = new SightingsDataSet();
        $ok = $ds->insertSighting($petId, $userId, $desc, $lat, $lng);

        //return error if database insert fails
        if (!$ok) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'DB insert failed']);
            exit;
        }

        //successful response
        echo json_encode(['ok' => true]);
        exit;
    }
}