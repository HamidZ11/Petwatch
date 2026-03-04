<?php
session_start();

// CSRF token (used by AJAX POST endpoints)
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Determine which page to show
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Whitelist of allowed pages (security)
$allowedPages = [
    'home',
    'pets',
    'login',
    'createListing',
    'editPet',
    'deletePet',
    'reportSighting',
    'viewSightings',
    'manageListings',
    'sightings',
    'manageSightings',
    'editSighting',
    'deleteSighting',
    'map',
    'api_missing_pets',
    'api_add_sighting',
    'about'
];

if (!in_array($page, $allowedPages, true)) {
    $page = 'home';
}

// Load shared view container
$view = new stdClass();

// Routing (switch-case)
switch ($page) {
    case 'login':
        require_once 'Controllers/LoginController.php';
        $controller = new LoginController();
        $view->message = $controller->message ?? null;
        require_once 'Views/login.phtml';
        break;

    case 'map':
        require_once 'Controllers/MapController.php';
        $controller = new MapController();
        $controller->index();
        break;

    case 'api_missing_pets':
        require_once 'Controllers/ApiMissingPetsController.php';
        $controller = new ApiMissingPetsController();
        $controller->index();
        break;

    case 'api_add_sighting':
        require_once 'Controllers/ApiAddSightingController.php';
        $controller = new ApiAddSightingController();
        $controller->index();
        break;

    case 'createListing':
        require_once 'Controllers/CreateListingController.php';
        $controller = new CreateListingController();
        $view->message = $controller->message ?? null;
        require_once 'Views/createListing.phtml';
        break;

    case 'home':
        require_once 'Controllers/PetController.php';
        $petController = new PetController();
        $view->recentPets = $petController->getRecentPets(3); // latest 3 pets
        require_once 'Views/home.phtml';
        break;

    case 'pets':
        require_once 'Controllers/PetController.php';
        $petController = new PetController();
        $view->petsDataSet = $petController->petsDataSet;
        $view->currentPage = $petController->currentPage ?? 1;
        $view->totalPages = $petController->totalPages ?? 1;
        require_once 'Views/pets.phtml';
        break;

    case 'editPet':
        require_once 'Controllers/EditPetController.php';
        $controller = new EditPetController();
        $view->petData = $controller->petData ?? null;
        $view->message = $controller->message ?? null;
        require_once 'Views/editPet.phtml';
        break;

    case 'deletePet':
        require_once 'Controllers/DeletePetController.php';
        $controller = new DeletePetController();
        $view->message = $controller->message ?? null;
        require_once 'Controllers/PetController.php';
        $petController = new PetController();
        $view->petsDataSet = $petController->petsDataSet;
        $view->currentPage = $petController->currentPage ?? 1;
        $view->totalPages = $petController->totalPages ?? 1;
        require_once 'Views/pets.phtml';
        break;

    case 'reportSighting':
        require_once 'Controllers/ReportSightingController.php';
        $controller = new ReportSightingController();
        $view->message = $controller->message ?? null;
        require_once 'Views/reportSighting.phtml';
        break;

    case 'viewSightings':
        require_once 'Controllers/ViewSightingsController.php';
        $controller = new ViewSightingsController();
        $view->sightingsDataSet = $controller->sightingsDataSet;
        require_once 'Views/viewSightings.phtml';
        break;

    case 'about':
        require_once 'Views/about.phtml';
        break;

    case 'manageListings':
        require_once 'Controllers/ManageListingsController.php';
        new ManageListingsController();
        break;

    case 'editSighting':
        require_once 'Controllers/EditSightingController.php';
        new EditSightingController();
        break;

    case 'deleteSighting':
        require_once 'Controllers/DeleteSightingController.php';
        new DeleteSightingController();
        break;

    case 'manageSightings':
        require_once 'Controllers/ManageSightingsController.php';
        $controller = new ManageSightingsController();
        break;

    case 'sightings':
        require_once 'Controllers/SightingsController.php';
        $controller = new SightingsController();
        break;

    default:
        header('Location: index.php?page=home');
        exit;
}
?>
