<?php
// script written to add data to sightings database
require_once 'Database/Database.php';
$db = Database::getInstance();

// Below are sighting descriptions used to generate random reports
$descriptions = [
    'Seen wandering near the park entrance.',
    'Spotted by the riverside café.',
    'Reported hiding under a parked car.',
    'Observed running across the road.',
    'Resident saw it near their garden fence.',
    'Appeared calm near a bus stop.',
    'Found sitting near the playground bench.',
    'Running along the canal path.',
    'Sighted outside the supermarket entrance.',
    'Seen near the community center.'
];

// UK city centres (lat, lng)
$ukCentres = [
    ['name' => 'London',     'lat' => 51.5074, 'lng' => -0.1278],
    ['name' => 'Manchester', 'lat' => 53.4808, 'lng' => -2.2426],
    ['name' => 'Birmingham', 'lat' => 52.4862, 'lng' => -1.8904],
    ['name' => 'Leeds',      'lat' => 53.8008, 'lng' => -1.5491],
    ['name' => 'Liverpool',  'lat' => 53.4084, 'lng' => -2.9916],
    ['name' => 'Newcastle',  'lat' => 54.9783, 'lng' => -1.6178],
    ['name' => 'Sheffield',  'lat' => 53.3811, 'lng' => -1.4701],
    ['name' => 'Bristol',    'lat' => 51.4545, 'lng' => -2.5879],
    ['name' => 'Cardiff',    'lat' => 51.4816, 'lng' => -3.1791],
    ['name' => 'Glasgow',    'lat' => 55.8642, 'lng' => -4.2518],
    ['name' => 'Edinburgh',  'lat' => 55.9533, 'lng' => -3.1883],
    ['name' => 'Belfast',    'lat' => 54.5973, 'lng' => -5.9301],
];

// generates a random float within a given range
function randomFloat($min, $max) {
    return $min + mt_rand() / mt_getrandmax() * ($max - $min);
}

$totalSightings = 50; // number of random sightings
$jitter = 0.05;       // +/- degrees around each city centre (~a few miles)

for ($i = 1; $i <= $totalSightings; $i++) {

    // random pet ID between 1 and 100
    $petID = rand(1, 100);

    // give Zara (userID=1) and Lee (userID=101) around 5 sightings each
    if ($i <= 5) {
        $userID = 1; // Zara
    } elseif ($i <= 10) {
        $userID = 101; // Lee
    } else {
        // rest random between 2–200
        $userID = rand(2, 200);
    }

    // random description
    $description = $descriptions[array_rand($descriptions)];

    // pick a random UK city centre and jitter around it
    $centre = $ukCentres[array_rand($ukCentres)];
    $latitude  = $centre['lat'] + randomFloat(-$jitter, $jitter);
    $longitude = $centre['lng'] + randomFloat(-$jitter, $jitter);

    // clamp to valid ranges (paranoid safety)
    $latitude = max(-90, min(90, $latitude));
    $longitude = max(-180, min(180, $longitude));

    // format to 6dp for consistency
    $latitude = round($latitude, 6);
    $longitude = round($longitude, 6);

    // insert sightings into database
    $sql = "INSERT INTO sightings (petID, description, latitude, longitude, dateReported, userID)
            VALUES (:petID, :description, :latitude, :longitude, datetime('now'), :userID)";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':petID' => $petID,
        ':description' => $description,
        ':latitude' => $latitude,
        ':longitude' => $longitude,
        ':userID' => $userID
    ]);
}

echo "added $totalSightings sightings across the UK (with extra for Zara & Lee)\n";
?>