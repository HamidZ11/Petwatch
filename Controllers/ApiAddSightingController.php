<?php
require_once('Models/SightingsDataSet.php');

class ApiAddSightingController {
    public function index() {
        header('Content-Type: application/json; charset=utf-8');

        // must be logged in
        if (!isset($_SESSION['user']['userID'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
            exit;
        }
        $userId = (int)$_SESSION['user']['userID'];

        // CSRF
        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'CSRF failed']);
            exit;
        }

        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true);

        $petId = (int)($body['petId'] ?? 0);
        $desc = trim((string)($body['description'] ?? ''));
        $lat = (float)($body['lat'] ?? 0);
        $lng = (float)($body['lng'] ?? 0);

        // validation
        if ($petId <= 0 || $desc === '' || strlen($desc) > 280) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid input']);
            exit;
        }
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid coordinates']);
            exit;
        }

        // basic XSS protection for storage (still output-encode on render)
        $desc = strip_tags($desc);

        $ds = new SightingsDataSet();
        $ok = $ds->insertSighting($petId, $userId, $desc, $lat, $lng);

        if (!$ok) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'DB insert failed']);
            exit;
        }

        echo json_encode(['ok' => true]);
        exit;
    }
}
