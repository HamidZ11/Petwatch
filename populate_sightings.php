<?php
require_once 'Database/Database.php';
$db = Database::getInstance();

// Number of sightings to generate
$totalSightings = 2500;

// Descriptions
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

// UK city centres
$ukCentres = [
    ['lat'=>51.5074,'lng'=>-0.1278], // London
    ['lat'=>53.4808,'lng'=>-2.2426], // Manchester
    ['lat'=>52.4862,'lng'=>-1.8904], // Birmingham
    ['lat'=>53.8008,'lng'=>-1.5491], // Leeds
    ['lat'=>53.4084,'lng'=>-2.9916], // Liverpool
    ['lat'=>54.9783,'lng'=>-1.6178], // Newcastle
    ['lat'=>53.3811,'lng'=>-1.4701], // Sheffield
    ['lat'=>51.4545,'lng'=>-2.5879], // Bristol
    ['lat'=>51.4816,'lng'=>-3.1791], // Cardiff
    ['lat'=>55.8642,'lng'=>-4.2518], // Glasgow
    ['lat'=>55.9533,'lng'=>-3.1883], // Edinburgh
    ['lat'=>54.5973,'lng'=>-5.9301]  // Belfast
];

// Helper
function randomFloat($min,$max){
    return $min + mt_rand()/mt_getrandmax()*($max-$min);
}

// Get real IDs to avoid FK errors
$petIDs  = $db->query("SELECT petID FROM pets")->fetchAll(PDO::FETCH_COLUMN);
$userIDs = $db->query("SELECT userID FROM users")->fetchAll(PDO::FETCH_COLUMN);

$jitter = 0.05;

// speed improvement
$db->beginTransaction();

$sql = "INSERT INTO sightings
(petID, description, latitude, longitude, dateReported, userID)
VALUES (:petID,:description,:latitude,:longitude,:dateReported,:userID)";

$stmt = $db->prepare($sql);

for($i=0;$i<$totalSightings;$i++){

    $petID = $petIDs[array_rand($petIDs)];
    $userID = $userIDs[array_rand($userIDs)];

    $description = $descriptions[array_rand($descriptions)];

    $centre = $ukCentres[array_rand($ukCentres)];

    $latitude  = round($centre['lat'] + randomFloat(-$jitter,$jitter),6);
    $longitude = round($centre['lng'] + randomFloat(-$jitter,$jitter),6);

    // random date in last 30 days
    $daysAgo = rand(0,30);
    $dateReported = date('Y-m-d H:i:s', strtotime("-$daysAgo days"));

    $stmt->execute([
        ':petID'=>$petID,
        ':description'=>$description,
        ':latitude'=>$latitude,
        ':longitude'=>$longitude,
        ':dateReported'=>$dateReported,
        ':userID'=>$userID
    ]);
}

$db->commit();

echo "Inserted $totalSightings sightings successfully\n";
?>