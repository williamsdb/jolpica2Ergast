<?php

// load the config file?
try {
    require __DIR__ . '/config.php';
} catch (\Throwable $th) {
    die('config.php file not found. Have you renamed from config_dummy.php?');
}

// Connect to database
$mysqli = new mysqli($host, $username, $password, $database, $port);
if ($mysqli->connect_error) {
    die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

// Get all tables not starting with 'wp_'
$tables = [];
$result = $mysqli->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $table = $row[0];
    if (strpos($table, 'wp_') !== 0) {
        $tables[] = $table;
    }
}

// Create a temporary directory for CSV files
$tmpDir = sys_get_temp_dir() . '/db_csv_' . uniqid();
mkdir($tmpDir);

// Export each table to CSV
foreach ($tables as $table) {
    $csvFile = fopen("$tmpDir/$table.csv", 'w');
    $res = $mysqli->query("SELECT * FROM `$table`");
    // Write header
    $fields = $res->fetch_fields();
    $headers = [];
    foreach ($fields as $field) {
        $headers[] = $field->name;
    }
    fputcsv($csvFile, $headers);
    // Write rows
    while ($row = $res->fetch_assoc()) {
        fputcsv($csvFile, $row);
    }
    fclose($csvFile);
    $res->free();
}

// Zip all CSV files
$zipFile = $tmpDir . '.zip';
$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
    foreach ($tables as $table) {
        $zip->addFile("$tmpDir/$table.csv", "$table.csv");
    }
    $zip->close();
} else {
    die('Failed to create zip file');
}

// Send zip file for download
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="db_tables.zip"');
header('Content-Length: ' . filesize($zipFile));
readfile($zipFile);

// Cleanup
foreach ($tables as $table) {
    unlink("$tmpDir/$table.csv");
}
rmdir($tmpDir);
unlink($zipFile);

exit;
