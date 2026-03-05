<?php
// Populate pets table with large realistic dataset

$dbPath = __DIR__ . '/Database/petwatch.db';
$numPets = 1500; // target dataset size

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Pet types and available images
$types = [
    'Dog' => ['dog1.jpg','dog2.jpg','dog3.jpg','dog4.jpg','dog5.jpg','dog6.jpg','dog7.jpg'],
    'Cat' => ['cat1.jpg','cat2.jpg','cat3.jpg','cat4.jpg','cat5.jpg','cat6.jpg','cat7.jpg'],
    'Rabbit' => ['rabbit1.jpg','rabbit2.jpg','rabbit3.jpg','rabbit4.jpg','rabbit5.jpg','rabbit6.jpg'],
    'Bird' => ['bird1.jpg','bird2.jpg','bird3.jpg','bird4.jpg','bird5.jpg','bird6.jpg','bird7.jpg'],
    'Hamster' => ['hamster1.jpg','hamster2.jpg','hamster3.jpg','hamster4.jpg','hamster5.jpg','hamster6.jpg'],
    'Fish' => ['fish1.jpg','fish2.jpg','fish3.jpg','fish4.jpg','fish5.jpg','fish6.jpg'],
    'Turtle' => ['turtle1.jpg','turtle2.jpg','turtle3.jpg'],
    'Other' => ['other1.jpg','other2.jpg','other3.jpg']
];

// Larger name pool to avoid repetition
$names = [
    'Buddy','Max','Luna','Coco','Daisy','Charlie','Milo','Bella','Rocky','Nibbles',
    'Ollie','Rosie','Ruby','Archie','Toby','Shadow','Rex','Chester','Peanut','Poppy',
    'Simba','Leo','Loki','Zeus','Misty','Willow','Oscar','Buster','Sammy','Lucky',
    'Jasper','Marley','Mocha','Pumpkin','Biscuit','Pepper','Storm','Honey','Sunny','Hazel',
    'Scout','Finn','Remy','Ziggy','Maple','Olive','Thor','Atlas','Blue','Copper'
];

$descriptions = [
    'Seen wandering near the park entrance.',
    'Spotted outside the supermarket entrance.',
    'Found sitting near the playground bench.',
    'Observed running across the road.',
    'Resident saw it near their garden fence.',
    'Appeared calm near a bus stop.',
    'Hiding under a parked car.',
    'Walking along the pavement slowly.',
    'Seen exploring a small alleyway.',
    'Resting near a shop doorway.',
    'Spotted crossing the street quickly.',
    'Moving cautiously through a park path.',
    'Sitting quietly beside a building.',
    'Curiously exploring a nearby garden.',
    'Seen near a local café outdoor seating.',
    'Appeared friendly but cautious.',
    'Walking near a row of houses.',
    'Resting in a shaded corner.',
    'Observed wandering slowly.',
    'Seen near a public bench.'
];

$statuses = ['missing','found'];

$insert = $pdo->prepare('
INSERT INTO pets (name, type, age, description, ownerID, status, dateAdded, imagePath)
VALUES (:name, :type, :age, :description, :ownerID, :status, :dateAdded, :imagePath)
');

for ($i = 0; $i < $numPets; $i++) {

    $type = array_rand($types);
    $image = $types[$type][array_rand($types[$type])];
    $name = $names[array_rand($names)];
    $description = $descriptions[array_rand($descriptions)];
    $age = random_int(1, 15);
    $status = $statuses[array_rand($statuses)];

    // distribute across 100 owners
    $ownerID = 101 + ($i % 100);

    // random timestamp within last 60 days
    $dateAdded = date('Y-m-d H:i:s', strtotime('-' . random_int(0, 60) . ' days'));

    $imagePath = 'uploads/' . $image;

    $insert->execute([
        ':name' => $name,
        ':type' => $type,
        ':age' => $age,
        ':description' => $description,
        ':ownerID' => $ownerID,
        ':status' => $status,
        ':dateAdded' => $dateAdded,
        ':imagePath' => $imagePath
    ]);

    echo "Inserted pet: $name ($type) → Owner $ownerID\n";
}

echo "\n$numPets pets added to database.\n";
?>