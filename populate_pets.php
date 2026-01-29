<?php
//This is a script to add data to the pets table
$dbPath = __DIR__ . '/Database/petwatch.db';
$uploadsDir = __DIR__ . '/uploads';
$numPets = 100;

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//below are different variables to be randomly assigned to pets
$types = [
    'Dog' => ['dog1.jpg', 'dog2.jpg', 'dog3.jpg'],
    'Cat' => ['cat1.jpg', 'cat2.jpg', 'cat3.jpg'],
    'Rabbit' => ['rabbit1.jpg', 'rabbit2.jpg', 'rabbit3.jpg'],
    'Bird' => ['bird1.jpg', 'bird2.jpg', 'bird3.jpg'],
    'Hamster' => ['hamster1.jpg', 'hamster2.jpg', 'hamster3.jpg'],
    'Fish' => ['fish1.jpg', 'fish2.jpg', 'fish3.jpg'],
    'Turtle' => ['turtle1.jpg', 'turtle2.jpg', 'turtle3.jpg'],
    'Other' => ['other1.jpg', 'other2.jpg', 'other3.jpg']
];

$names = [
    'Buddy', 'Max', 'Luna', 'Coco', 'Daisy', 'Charlie', 'Milo', 'Bella',
    'Rocky', 'Nibbles', 'Ollie', 'Rosie', 'Ruby', 'Archie', 'Toby', 'Shadow',
    'Rex', 'Chester', 'Peanut', 'Poppy'
];

$descriptions = [
    'Small and playful temperament.',
    'Curious and energetic with bright eyes.',
    'Gentle and calm, enjoys attention.',
    'Has soft fur and a friendly nature.',
    'Loves to explore and very alert.',
    'Playful, loyal and loves people.',
    'Quiet and affectionate personality.',
    'Has a shiny coat and wagging tail.',
    'Timid at first but warms up quickly.',
    'Has distinctive markings and a calm attitude.',
    'Curious and loves being around humans.',
    'Very social and enjoys company.',
    'Adventurous and always exploring.',
    'Soft fur and expressive eyes.',
    'Clever and easily trained.',
    'Loves to nap in sunny spots.',
    'Calm and independent.',
    'Gentle and affectionate companion.',
    'Very small with round features.',
    'Friendly and lively little pet.'
];

$statuses = ['missing', 'found'];

$insert = $pdo->prepare('INSERT INTO pets (name, type, age, description, ownerID, status, dateAdded, imagePath)
                         VALUES (:name, :type, :age, :description, :ownerID, :status, :dateAdded, :imagePath)');

//for loop to randomly generate pets with the above given data
//array_rand randomly assigns each variable
for ($i = 0; $i < $numPets; $i++) {
    $type = array_rand($types);
    $image = $types[$type][array_rand($types[$type])];
    $name = $names[array_rand($names)];
    $description = $descriptions[array_rand($descriptions)];
    $age = random_int(1, 15);
    $status = $statuses[array_rand($statuses)];
    $ownerID = 101 + ($i % 100); // distribute across 100 owners
    $dateAdded = date('Y-m-d H:i:s');
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

    echo " Inserted pet: $name ($type) → Owner $ownerID\n";
}

echo "\n $numPets pets added to database.\n";
?>