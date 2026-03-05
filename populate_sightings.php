<?php
// Populate sightings across the UK but keep each pet clustered in one city

$dbPath = __DIR__ . '/Database/petwatch.db';
$totalSightings = 2300;

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/*
UK city coordinates
*/
$cities = [
    ['name' => 'Manchester', 'lat' => 53.4808, 'lng' => -2.2426],
    ['name' => 'London', 'lat' => 51.5072, 'lng' => -0.1276],
    ['name' => 'Birmingham', 'lat' => 52.4862, 'lng' => -1.8904],
    ['name' => 'Leeds', 'lat' => 53.8008, 'lng' => -1.5491],
    ['name' => 'Liverpool', 'lat' => 53.4084, 'lng' => -2.9916],
    ['name' => 'Sheffield', 'lat' => 53.3811, 'lng' => -1.4701],
    ['name' => 'Bristol', 'lat' => 51.4545, 'lng' => -2.5879],
    ['name' => 'Newcastle', 'lat' => 54.9783, 'lng' => -1.6178],
    ['name' => 'Glasgow', 'lat' => 55.8642, 'lng' => -4.2518],
    ['name' => 'Edinburgh', 'lat' => 55.9533, 'lng' => -3.1883]
];

/*
Get all pets
*/
$pets = $pdo->query("SELECT petID FROM pets")->fetchAll(PDO::FETCH_COLUMN);

if (!$pets) {
    die("No pets found. Run populatePets first.\n");
}

/*
Assign EACH pet a home city
*/
$petCity = [];

foreach ($pets as $petID) {
    $petCity[$petID] = $cities[array_rand($cities)];
}

/*
Reporter user IDs
*/
$userIDs = range(1,100);

/*
Descriptions
*/
$descriptions = [
    'Seen wandering near a park entrance.',
    'Spotted outside a supermarket.',
    'Found sitting near a playground bench.',
    'Observed crossing the road.',
    'Resident saw it near their garden fence.',
    'Appeared calm near a bus stop.',
    'Hiding under a parked car.',
    'Walking slowly along the pavement.',
    'Seen exploring a small alleyway.',
    'Resting near a shop doorway.',
    'Spotted crossing the street quickly.',
    'Moving cautiously through a park path.',
    'Sitting quietly beside a building.',
    'Exploring a nearby garden.',
    'Seen near outdoor café seating.',
    'Appeared friendly but cautious.',
    'Walking near a row of houses.',
    'Resting in a shaded corner.',
    'Observed wandering slowly.',
    'Seen near a public bench.'
];

/*
Insert statement
*/
$insert = $pdo->prepare("
INSERT INTO sightings
(petID, description, latitude, longitude, dateReported, userID)
VALUES
(:petID, :description, :lat, :lng, :dateReported, :userID)
");

/*
Generate sightings
*/
for ($i = 0; $i < $totalSightings; $i++) {

    $petID = $pets[array_rand($pets)];
    $city = $petCity[$petID];

    $baseLat = $city['lat'];
    $baseLng = $city['lng'];

    // small radius around the city (~2km)
    $lat = $baseLat + (mt_rand(-200,200) / 10000);
    $lng = $baseLng + (mt_rand(-200,200) / 10000);

    $description = $descriptions[array_rand($descriptions)];
    $userID = $userIDs[array_rand($userIDs)];

    $dateReported = date(
        'Y-m-d H:i:s',
        strtotime('-' . random_int(0,30) . ' days')
    );

    $insert->execute([
        ':petID' => $petID,
        ':description' => $description,
        ':lat' => $lat,
        ':lng' => $lng,
        ':dateReported' => $dateReported,
        ':userID' => $userID
    ]);

    echo "Inserted sighting for pet $petID in {$city['name']}\n";
}

echo "\n$totalSightings sightings inserted.\n";
?>