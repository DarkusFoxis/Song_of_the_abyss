<?php
$imageData = $_POST['imageData'];
$name = $_POST['name'];
$imageName = uniqid() . '.png';

file_put_contents('images/' . $imageName, base64_decode(str_replace('data:image/png;base64,', '', $imageData)));

$jsonFile = 'drawings.json';
$jsonData = json_decode(file_get_contents($jsonFile), true);

$jsonData[] = [
'image' => $imageName,
'drawnBy' =>  $name
];

file_put_contents($jsonFile, json_encode($jsonData));
header('Location: draw');