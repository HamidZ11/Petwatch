<?php
// script that generates 100 Users and 100 Owners with secure passwords + emails

//config
$dbPath = __DIR__ . '/Database/petwatch.db';   // Path to your SQLite file
$outputCsv = __DIR__ . '/user_passwords_temp.csv';
$totalUsers = 100;
$totalOwners = 100;
$emailDomain = 'petwatch.local';
// ----------------------------------------

//secure strong password generator (based off of brief)
function generateStrongPassword($length = 14) {
    if ($length < 12) $length = 12;
    $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lower = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
    $all = $upper . $lower . $numbers . $symbols;

    //guarantee at least one of each criteria
    $pw = '';
    $pw .= $upper[random_int(0, strlen($upper)-1)];
    $pw .= $lower[random_int(0, strlen($lower)-1)];
    $pw .= $numbers[random_int(0, strlen($numbers)-1)];
    $pw .= $symbols[random_int(0, strlen($symbols)-1)];

    for ($i = 4; $i < $length; $i++) {
        $pw .= $all[random_int(0, strlen($all)-1)];
    }
    return str_shuffle($pw);
}

try {
    //connect to SQLite
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //verify schema
    $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
    $colNames = array_column($cols, 'name');
    if (!in_array('username', $colNames) || !in_array('password', $colNames)) {
        throw new Exception("Your users table must have 'username' and 'password' columns.");
    }
    $hasEmail = in_array('email', $colNames);

    //insert statement
    $insertSql = $hasEmail
        ? 'INSERT INTO users (username, email, password, userType) VALUES (:username, :email, :password, :userType)'
        : 'INSERT INTO users (username, password, userType) VALUES (:username, :password, :userType)';
    $insertStmt = $pdo->prepare($insertSql);

    // open CSV
    $fp = fopen($outputCsv, 'w');
    if (!$fp) throw new Exception("Cannot open output file: $outputCsv");
    fputcsv($fp, ['username', 'email', 'password', 'userType']);

    $pdo->beginTransaction();

    //generate 100 Users
    for ($i = 1; $i <= $totalUsers; $i++) {
        $username = "user" . str_pad($i, 3, '0', STR_PAD_LEFT);
        $email = $username . '@' . $emailDomain;
        $password = generateStrongPassword(14);
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $insertStmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $hash,
            ':userType' => 'User'
        ]);

        fputcsv($fp, [$username, $email, $password, 'User']);
        echo "Created $username ($email) [User]\n";
    }

    //generate 100 Owners
    for ($i = 1; $i <= $totalOwners; $i++) {
        $username = "owner" . str_pad($i, 3, '0', STR_PAD_LEFT);
        $email = $username . '@' . $emailDomain;
        $password = generateStrongPassword(14);
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $insertStmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $hash,
            ':userType' => 'Owner'
        ]);

        fputcsv($fp, [$username, $email, $password, 'Owner']);
        echo "Created $username ($email) [Owner]\n";
    }

    $pdo->commit();
    fclose($fp);

    echo "\n 100 Users + 100 Owners created.\n";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}