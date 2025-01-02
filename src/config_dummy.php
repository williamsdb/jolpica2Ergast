<?php

    //jolpica API endpoint
    $endpoint = "https://api.jolpi.ca/ergast/f1/";

    // Database connection details
    $host = "<hostname>"; // Replace with your database host
    $username = "<username>"; // Replace with your database username
    $password = "<password>"; // Replace with your database password
    $database = "<database>"; // Replace with your database name
    $port = 3306; // Replace with your database port

    // Connection string, adjust for your database
    $dsn = "mysql:host=$host;dbname=$database;port=$port;charset=utf8mb4";
    //$dsn = "sqlite:/path/to/your/database.db";
    //$dsn = "pgsql:host=$host;port=$port;dbname=$database;";
    
?>