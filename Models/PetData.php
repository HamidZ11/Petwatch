<?php
//This file represents a single pet record
//And it stores pet details and provides getter methods
class PetData {
    private $petID, $name, $type, $description, $age, $ownerID, $status, $dateAdded;
    private $imagePath;
    private $latestLatitude, $latestLongitude;

    //create a PetData object from a database row
    public function __construct($row) {
        $this->petID = $row['petID'];
        $this->name = $row['name'];
        $this->type = $row['type'];
        $this->description = $row['description'];
        $this->age = $row['age'];
        $this->ownerID = $row['ownerID'];
        $this->status = $row['status'];
        $this->dateAdded = $row['dateAdded'];
        $this->imagePath = $row['imagePath'];
        $this->latestLatitude = $row['latestLatitude'] ?? null;
        $this->latestLongitude = $row['latestLongitude'] ?? null;
    }

    //Getters
    public function getPetID() { return $this->petID; }
    public function getName() { return $this->name; }
    public function getType() { return $this->type; }
    public function getDescription() { return $this->description; }
    public function getAge() { return $this->age; }
    public function getOwnerID() { return $this->ownerID; }
    public function getStatus() { return $this->status; }
    public function getDateAdded() { return $this->dateAdded; }
    public function getImagePath() { return $this->imagePath; }
    public function getLatestLatitude() { return $this->latestLatitude; }
    public function getLatestLongitude() { return $this->latestLongitude; }
    public function hasLatestCoords() {
        return is_numeric($this->latestLatitude) && is_numeric($this->latestLongitude);
    }
}
?>
