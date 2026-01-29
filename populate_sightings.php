<?php
//script written to add data to sightings database
require_once 'Database/Database.php';
$db = Database::getInstance();

//Below are sighting descriptions used to generate random reports
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

// generates a random float within a given range
function randomCoord($min, $max) {
    return $min + mt_rand() / mt_getrandmax() * ($max - $min);
}

$totalSightings = 50; // number of random sightings

for ($i = 1; $i <= $totalSightings; $i++) {

    // random pet ID between 1 and 100
    $petID = rand(1, 100);

    //give Zara (userID=1) and Lee (userID=101) around 5 sightings each
    if ($i <= 5) {
        $userID = 1; // Zara
    } elseif ($i <= 10) {
        $userID = 101; // Lee
    } else {
        //rest random between 2–200
        $userID = rand(2, 200);
    }

    // random description
    $description = $descriptions[array_rand($descriptions)];

    // generate random Manchester coordinates
    $latitude = randomCoord(53.4700, 53.4900);
    $longitude = randomCoord(-2.2600, -2.2300);

    //insert sightings into database
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

echo " added $totalSightings sightings (with extra for Zara & Lee)\n";
?>