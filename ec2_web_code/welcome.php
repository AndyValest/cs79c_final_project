<?php
$name = $_POST["name"] ?? "";
$name = trim($name);

if ($name === "") {
    exit("Please enter your name.");
}

$file = "/mnt/new-volume/names.txt";

$result = file_put_contents(
    $file,
    $name . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

if ($result === false) {
    http_response_code(500);
    exit("Could not save your name.");
}

echo "Welcome, " . htmlspecialchars($name, ENT_QUOTES, "UTF-8") . "!<br>";
echo "Your name has been saved to the EBS volume.";
?>