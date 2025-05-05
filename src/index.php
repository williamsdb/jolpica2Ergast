<?php

/**
 * Library to populate the Ergast database structure from the Jolpica API.
 *
 * @author  Neil Thompson <hi@nei.lt>
 * @see     https://nei.lt/j2e
 * @license GNU Lesser General Public License, version 3
 *
 * This script will update the Ergast database with the latest data from the
 * Jolpica API ensuring that the database is up to date with the latest 
 * information.
 * 
 * It can be run either from the command line or as a web page and takes a single
 * parameter, 'cmd' which can be set to 'STATIC', 'RACE' or 'ALL'. If no parameter
 * is passed, it defaults to 'ALL'.
 * 
 * Ergast table to Jolpica API endpoint mapping
 *
 * +----------------------+----------------------+----------------------+
 * | Ergast table         | Jolpica endpoint     | Type                 |
 * +----------------------+----------------------+----------------------+
 * | circuits             | circuits             | STATIC               |
 * | constructorResults   | Derived              | RACE                 |
 * | constructorStandings | constructorstandings | RACE                 |
 * | constructors         | constructors         | STATIC               |
 * | driverStandings      | driverstandings      | RACE                 |
 * | drivers              | drivers              | STATIC               |
 * | lapTimes             | laps                 | RACE                 |
 * | pitStops             | pitstops             | RACE                 |
 * | qualifying           | qualifying           | RACE                 |
 * | races                | races                | RACE                 |
 * | results              | results              | RACE                 |
 * | seasons              | seasons              | STATIC               |
 * | sprintResults        | sprint               | RACE                 |
 * | status               | status               | STATIC               |
 * +----------------------+----------------------+----------------------+
 *

 **/

// set error handling
error_reporting(E_NOTICE);
ini_set('display_errors', 0);

// have we got a config file?
try {
    require __DIR__ . '/config.php';
} catch (\Throwable $th) {
    die('config.php file not found. Have you renamed from config_dummy.php?');
}

// are we running from the command line?
$cli = (php_sapi_name() == "cli") ? TRUE : FALSE;

// get the parameters which can be:
//   mode: STATIC, RACE or ALL
//   year: the year to process (defaults to current year)
if ($cli) {
    $line_ending = PHP_EOL;
    if ($argc == 2) {
        $cmd = strtoupper($argv[1]);
        if ($cmd != 'STATIC' && $cmd != 'RACE') {
            $cmd = 'ALL';
        }
        $cmdYear = date("Y");
    } elseif ($argc == 3) {
        $cmd = strtoupper($argv[1]);
        if ($cmd != 'STATIC' && $cmd != 'RACE') {
            $cmd = 'ALL';
        }
        $cmdYear = $argv[2];
    } else {
        $cmd = 'RACE';
        $cmdYear = date("Y");
    }
} else {
    $line_ending = "<br>";
    set_time_limit(600);
    if (isset($_GET['cmd'])) {
        $cmd = strtoupper($_GET['cmd']);
        if ($cmd != 'STATIC' && $cmd != 'RACE') {
            $cmd = 'ALL';
        }
    } else {
        $cmd = 'RACE';
    }
    if (isset($_GET['year'])) {
        $cmdYear = $_GET['year'];
    } else {
        $cmd = 'RACE';
        $cmdYear = date("Y");
    }
}

echo '----------------------------------' . $line_ending;
echo 'jolpica2Ergast' . $line_ending;
echo 'Processing: ' . $cmd . ' data' . $line_ending;
echo 'Year: ' . $cmdYear . $line_ending;
echo '----------------------------------' . $line_ending;

// Create a connection to the database using PDO
try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . PHP_EOL);
}

// Update the static data (seasons, drivers, constructors, circuits, status)
if ($cmd == 'STATIC' || $cmd == 'ALL') {

    // ************************************************************
    // * get the season details
    // ************************************************************
    try {

        echo 'Getting season details' . $line_ending;

        $page = 0;
        $total = 0;

        while ($page <= ($total / 100)) {

            // Get a page of season details to process
            $dets = getPageofResults($endpoint . "/seasons/?format=json&limit=100&offset=" . ($page * 100));

            // Cycle through the seasons and check if they are in the database already
            foreach ($dets->MRData->SeasonTable->Seasons as $season) {

                // SQL query to check if the record exists
                $sql = "SELECT 1 FROM `seasons` WHERE `year` = :year";
                $stmt = $pdo->prepare($sql);

                // Bind parameters
                $stmt->bindParam(':year', $season->season, PDO::PARAM_STR);

                // Execute the query
                $stmt->execute();

                if ($stmt->rowCount() == 0) {
                    // SQL query to insert data
                    $sql = "INSERT INTO `seasons` (`year`, `url`) 
                                VALUES (:year, :url)";
                    $stmt2 = $pdo->prepare($sql);

                    // Bind parameters to the query
                    $stmt2->bindParam(':year', $season->season, PDO::PARAM_INT);
                    $stmt2->bindValue(':url', $season->url, PDO::PARAM_STR);

                    // Execute the query
                    if ($stmt2->execute()) {
                        echo ' - season ' . $season->season . ' added to the database' . $line_ending;
                    } else {
                        echo "Error inserting record: " . implode(", ", $stmt2->errorInfo());
                    }
                }
            }

            $total = $dets->MRData->total;

            // Increment the page number
            $page++;
        }

        echo 'Season details complete' . $line_ending;
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage() . PHP_EOL);
    }

    // ************************************************************
    // * get the driver details
    // ************************************************************
    try {

        echo 'Getting driver details' . $line_ending;

        $page = 0;
        $total = 0;

        while ($page <= ($total / 100)) {

            // Get a page of driver details to process
            $dets = getPageofResults($endpoint . "/" . $cmdYear . "/drivers/?format=json&limit=100&offset=" . ($page * 100));

            // Cycle through the drivers and check if they are in the database already
            foreach ($dets->MRData->DriverTable->Drivers as $driver) {

                // SQL query to check if the record exists
                $sql = "SELECT 1 FROM `drivers` WHERE `driverRef` = :driverRef";
                $stmt = $pdo->prepare($sql);

                // Bind parameters
                $stmt->bindParam(':driverRef', $driver->driverId, PDO::PARAM_STR);

                // Execute the query
                $stmt->execute();

                if ($stmt->rowCount() == 0) {
                    // SQL query to insert data
                    $sql = "INSERT INTO `drivers` (`driverRef`, `number`, `code`, `forename`, `surname`, `dob`, `nationality`, `url`) 
                                VALUES (:driverRef, :number, :code, :forename, :surname, :dob, :nationality, :url)";
                    $stmt2 = $pdo->prepare($sql);

                    // Bind parameters to the query
                    $stmt2->bindValue(':driverRef', $driver->driverId, PDO::PARAM_STR);
                    $stmt2->bindValue(':number', $driver->permanentNumber, PDO::PARAM_INT);
                    $stmt2->bindValue(':code', $driver->code, PDO::PARAM_STR);
                    $stmt2->bindValue(':forename', $driver->givenName, PDO::PARAM_STR);
                    $stmt2->bindValue(':surname', $driver->familyName, PDO::PARAM_STR);
                    $stmt2->bindValue(':dob', $driver->dateOfBirth, PDO::PARAM_STR);
                    $stmt2->bindValue(':nationality', $driver->nationality, PDO::PARAM_STR);
                    $stmt2->bindValue(':url', $driver->url, PDO::PARAM_STR);

                    // Execute the query
                    if ($stmt2->execute()) {
                        echo ' - ' . $driver->givenName . ' ' . $driver->familyName . ' added to the database' . $line_ending;
                    } else {
                        echo "Error inserting record: " . implode(", ", $stmt2->errorInfo());
                    }
                }
            }

            $total = $dets->MRData->total;

            // Increment the page number
            $page++;
        }

        echo 'Driver details complete' . $line_ending;
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage() . PHP_EOL);
    }

    // ************************************************************
    // * get the constructor details
    // ************************************************************
    try {

        echo 'Getting constructor details' . $line_ending;

        $page = 0;
        $total = 0;

        while ($page <= ($total / 100)) {

            // Get a page of constructor details to process
            $dets = getPageofResults($endpoint . "/" . $cmdYear . "/constructors/?format=json&limit=100&offset=" . ($page * 100));

            // Cycle through the constructors and check if they are in the database already
            foreach ($dets->MRData->ConstructorTable->Constructors as $constructor) {

                // SQL query to check if the record exists
                $sql = "SELECT 1 FROM `constructors` WHERE `constructorRef` = :constructorRef";
                $stmt = $pdo->prepare($sql);

                // Bind parameters
                $stmt->bindParam(':constructorRef', $constructor->constructorId, PDO::PARAM_STR);

                // Execute the query
                $stmt->execute();

                if ($stmt->rowCount() == 0) {

                    // SQL query to insert data
                    $sql = "INSERT INTO `constructors` (`constructorRef`, `name`, `nationality`, `url`) 
                                VALUES (:constructorRef, :name, :nationality, :url)";
                    $stmt2 = $pdo->prepare($sql);

                    // Bind parameters to the query
                    $stmt2->bindValue(':constructorRef', $constructor->constructorId, PDO::PARAM_STR);
                    $stmt2->bindValue(':name', $constructor->name, PDO::PARAM_STR);
                    $stmt2->bindValue(':nationality', $constructor->nationality, PDO::PARAM_STR);
                    $stmt2->bindValue(':url', $constructor->url, PDO::PARAM_STR);

                    // Execute the query
                    if ($stmt2->execute()) {
                        echo ' - ' . $constructor->name . ' added to the database' . $line_ending;
                    } else {
                        echo "Error inserting record: " . implode(", ", $stmt2->errorInfo());
                    }
                }
            }

            $total = $dets->MRData->total;

            // Increment the page number
            $page++;
        }

        echo 'Constructor details complete' . $line_ending;
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage() . PHP_EOL);
    }

    // ************************************************************
    // * get the circuit details
    // ************************************************************
    try {

        echo 'Getting circuit details' . $line_ending;

        $page = 0;
        $total = 0;

        while ($page <= ($total / 100)) {

            // Get a page of circuit details to process
            $dets = getPageofResults($endpoint . "/" . $cmdYear . "/circuits/?format=json&limit=100&offset=" . ($page * 100));

            // Cycle through the circuits and check if they are in the database already
            foreach ($dets->MRData->CircuitTable->Circuits as $circuit) {

                // SQL query to check if the record exists
                $sql = "SELECT 1 FROM `circuits` WHERE `circuitRef` = :circuitRef";
                $stmt = $pdo->prepare($sql);

                // Bind parameters
                $stmt->bindParam(':circuitRef', $circuit->circuitId, PDO::PARAM_STR);

                // Execute the query
                $stmt->execute();

                if ($stmt->rowCount() == 0) {

                    // SQL query to insert data
                    $sql = "INSERT INTO `circuits` (`circuitRef`, `name`, `location`, `country`, `lat`, `lng`, `url`) 
                                VALUES (:circuitRef, :name, :location, :country, :lat, :lng, :url)";
                    $stmt2 = $pdo->prepare($sql);

                    // Bind parameters to the query
                    $stmt2->bindValue(':circuitRef', $circuit->circuitId, PDO::PARAM_STR);
                    $stmt2->bindValue(':name', $circuit->circuitName, PDO::PARAM_STR);
                    $stmt2->bindValue(':location', $circuit->Location->locality, PDO::PARAM_STR);
                    $stmt2->bindValue(':country', $circuit->Location->country, PDO::PARAM_STR);
                    $stmt2->bindValue(':lat', $circuit->Location->lat, PDO::PARAM_STR); // assuming lat is string (use PDO::PARAM_INT if it's an integer)
                    $stmt2->bindValue(':lng', $circuit->Location->long, PDO::PARAM_STR); // same for lng
                    $stmt2->bindValue(':url', $circuit->url, PDO::PARAM_STR);

                    // Execute the query
                    if ($stmt2->execute()) {
                        echo ' - ' . $circuit->circuitName . ' added to the database' . $line_ending;
                    } else {
                        echo "Error inserting record: " . implode(", ", $stmt2->errorInfo());
                    }
                }
            }

            $total = $dets->MRData->total;

            // Increment the page number
            $page++;
        }

        echo 'Circuit details complete' . $line_ending;
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage() . PHP_EOL);
    }

    // ************************************************************
    // * get the status details
    // ************************************************************
    try {

        echo 'Getting status details' . $line_ending;

        $page = 0;
        $total = 0;

        while ($page <= ($total / 100)) {

            // Get a page of status details to process
            $dets = getPageofResults($endpoint . "/status/?format=json&limit=100&offset=" . ($page * 100));

            // Cycle through the statuses and check if they are in the database already
            foreach ($dets->MRData->StatusTable->Status as $status) {

                if (!empty($status->status)) {
                    // SQL query to check if the record exists
                    $sql = "SELECT 1 FROM `status` WHERE `status` = :status";
                    $stmt = $pdo->prepare($sql);

                    // Bind parameters
                    $stmt->bindParam(':status', $status->status, PDO::PARAM_STR);

                    // Execute the query
                    $stmt->execute();

                    if ($stmt->rowCount() == 0) {

                        // SQL query to insert data
                        $sql = "INSERT INTO `status` (`status`) VALUES (:status)";
                        $stmt2 = $pdo->prepare($sql);

                        // Bind parameters to the query
                        $stmt2->bindValue(':status', $status->status, PDO::PARAM_STR);

                        // Execute the query
                        if ($stmt2->execute()) {
                            echo ' - ' . $status->status . ' added to the database' . $line_ending;
                        } else {
                            echo "Error inserting record: " . implode(", ", $stmt2->errorInfo());
                        }
                    }
                }
            }

            $total = $dets->MRData->total;

            // Increment the page number
            $page++;
        }

        echo 'Status details complete' . $line_ending;
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage() . PHP_EOL);
    }
}


// Update the dynamic data 
if ($cmd == 'RACE' || $cmd == 'ALL') {

    // ************************************************************
    // * get the races details
    // ************************************************************
    try {

        echo 'Getting race details' . $line_ending;

        $page = 0;
        $total = 0;

        while ($page <= ($total / 100)) {

            // Get a page of races details to process
            $dets = getPageofResults($endpoint . "/" . $cmdYear . "/races/?format=json&limit=100&offset=" . ($page * 100));

            // Cycle through the races and check if they are in the database already
            foreach ($dets->MRData->RaceTable->Races as $race) {

                if (!isset($race->FirstPractice->time)) {
                    echo ' - Race round ' . $race->round . ' (' . $race->raceName . ') missing data so skipping' . $line_ending;
                    continue;
                }

                // SQL query to check if the record exists
                $sql = "SELECT 1 FROM `races` WHERE `year` = :year AND `round` = :round";
                $stmt = $pdo->prepare($sql);

                // Bind parameters
                $stmt->bindParam(':year', $race->season, PDO::PARAM_INT);
                $stmt->bindParam(':round', $race->round, PDO::PARAM_INT);

                // Execute the query
                $stmt->execute();

                if ($stmt->rowCount() == 0) {

                    // SQL query to insert data
                    $sql = "INSERT INTO races (`year`, `round`, `circuitId`, `name`, `date`, `time`, `url`, `fp1_date`, `fp1_time`, `fp2_date`, `fp2_time`, `fp3_date`, `fp3_time`, `quali_date`, `quali_time`, `sprint_date`, `sprint_time`)
                                VALUES (:year, :round, 
                                        (SELECT `circuitId` FROM `circuits` WHERE `circuitRef` = :circuitId), 
                                        :name, :date, :time, :url, 
                                        :fp1_date, :fp1_time, :fp2_date, :fp2_time, 
                                        :fp3_date, :fp3_time, :quali_date, :quali_time, :sprint_date, :sprint_time)";

                    $stmt2 = $pdo->prepare($sql);

                    // Bind parameters to the query
                    $fp1_time = (empty($race->FirstPractice->time)) ? null : substr($race->FirstPractice->time, 0, 8);
                    $fp1_date = (empty($race->FirstPractice->date)) ? null : $race->FirstPractice->date;
                    $fp2_time = (empty($race->SecondPractice->time)) ? null : substr($race->SecondPractice->time, 0, 8);
                    $fp2_date = (empty($race->SecondPractice->date)) ? null : $race->SecondPractice->date;
                    $fp3_time = (empty($race->ThirdPractice->time)) ? null : substr($race->ThirdPractice->time, 0, 8);
                    $fp3_date = (empty($race->ThirdPractice->date)) ? null : $race->ThirdPractice->date;
                    $quali_time = substr($race->Qualifying->time, 0, 8);
                    $race_time = substr($race->time, 0, 8);
                    $sprint_date = (empty($race->Sprint->date)) ? null : $race->Sprint->date;
                    $sprint_time = (empty($race->Sprint->time)) ? null : substr($race->Sprint->time, 0, 8);

                    $stmt2->bindParam(':year', $race->season, PDO::PARAM_INT);
                    $stmt2->bindParam(':round', $race->round, PDO::PARAM_INT);
                    $stmt2->bindParam(':circuitId', $race->Circuit->circuitId, PDO::PARAM_STR);
                    $stmt2->bindParam(':name', $race->raceName, PDO::PARAM_STR);
                    $stmt2->bindParam(':date', $race->date, PDO::PARAM_STR);
                    $stmt2->bindParam(':time', $race_time, PDO::PARAM_STR);
                    $stmt2->bindParam(':url', $race->url, PDO::PARAM_STR);

                    $stmt2->bindParam(':fp1_date', $race->FirstPractice->date, PDO::PARAM_STR);
                    $stmt2->bindParam(':fp1_time', $fp1_time, PDO::PARAM_STR);
                    $stmt2->bindParam(':fp2_date', $fp2_date, PDO::PARAM_STR);
                    $stmt2->bindParam(':fp2_time', $fp2_time, PDO::PARAM_STR);
                    $stmt2->bindParam(':fp3_date', $fp3_date, PDO::PARAM_STR);
                    $stmt2->bindParam(':fp3_time', $fp3_time, PDO::PARAM_STR);
                    $stmt2->bindParam(':quali_date', $race->Qualifying->date, PDO::PARAM_STR);
                    $stmt2->bindParam(':quali_time', $quali_time, PDO::PARAM_STR);
                    $stmt2->bindParam(':sprint_date', $sprint_date, PDO::PARAM_STR);
                    $stmt2->bindParam(':sprint_time', $sprint_time, PDO::PARAM_STR);

                    // Execute the query
                    if ($stmt2->execute()) {
                        echo ' - Race round ' . $race->round . ' (' . $race->raceName . ') added to the database' . $line_ending;
                    } else {
                        echo "Error inserting record: " . implode(", ", $stmt2->errorInfo());
                    }
                }
            }

            $total = $dets->MRData->total;

            // Increment the page number
            $page++;
        }

        echo 'Races details complete' . $line_ending;
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage() . PHP_EOL);
    }

    // ************************************************************
    // * get the sprint details & results
    // ************************************************************
    try {

        echo 'Getting sprint details and results' . $line_ending;

        $page = 0;
        $total = 0;

        while ($page <= ($total / 100)) {

            // Get a page of sprint details to process
            $dets = getPageofResults($endpoint . "/" . $cmdYear . "/sprint/?format=json&limit=100&offset=" . ($page * 100));

            // Cycle through the sprint data and check if they are in the database already
            foreach ($dets->MRData->RaceTable->Races as $race) {

                // Get the relevant race id
                $sql = "SELECT `raceId` FROM `races` WHERE `year` = :year AND `round` = :round";
                $stmt2 = $pdo->prepare($sql);

                // Bind parameters
                $stmt2->bindParam(':year', $race->season, PDO::PARAM_INT);
                $stmt2->bindParam(':round', $race->round, PDO::PARAM_INT);

                // Execute the query
                $stmt2->execute();

                if ($stmt2->rowCount() == 0) {
                    die('Race missing');
                }

                // Fetch the raceId
                $raceId = $stmt2->fetchColumn();

                // SQL query to check if the record exists
                $sql = "SELECT 1 FROM `sprintResults` WHERE `raceId` = :raceId";
                $stmt = $pdo->prepare($sql);

                // Bind parameters
                $stmt->bindParam(':raceId', $raceId, PDO::PARAM_INT);

                // Execute the query
                $stmt->execute();

                if ($stmt->rowCount() == 0) {

                    // update the sprint details into the races table
                    $sql = "UPDATE `races`
                                SET `sprint_date` = :sprint_date, `sprint_time` = :sprint_time
                                WHERE `raceId` = :raceId";

                    $stmt2 = $pdo->prepare($sql);

                    // Bind parameters to the query
                    $sprint_time = substr($race->time, 0, 8);

                    $stmt2->bindParam(':sprint_date', $race->date, PDO::PARAM_STR);
                    $stmt2->bindParam(':sprint_time', $race_time, PDO::PARAM_STR);
                    $stmt2->bindParam(':raceId', $raceId, PDO::PARAM_INT);

                    // Execute the query
                    if ($stmt2->execute()) {
                        echo ' - Sprint details updated on ' . $race->round . ' (' . $race->raceName . ') ' . $line_ending;
                    } else {
                        echo "Error inserting record: " . implode(", ", $stmt2->errorInfo());
                    }
                }

                // loop around all the sprint results and insert
                foreach ($race->SprintResults as $result) {

                    // SQL query to check if the record exists
                    $sql = "SELECT 1
                                  FROM `sprintResults`
                                 WHERE `raceId` = (SELECT `raceId` FROM `races` WHERE `round` = :round AND `year` = :year)
                                   AND `driverID` = (SELECT `driverId` FROM `drivers` WHERE `driverRef` = :driverId)";
                    $stmt = $pdo->prepare($sql);

                    // Bind parameters
                    $stmt->bindParam(':year', $race->season, PDO::PARAM_INT);
                    $stmt->bindParam(':round', $race->round, PDO::PARAM_INT);
                    $stmt->bindParam(':driverId', $result->Driver->driverId, PDO::PARAM_STR);

                    // Execute the query
                    $stmt->execute();

                    if ($stmt->rowCount() == 0) {

                        // SQL query to insert data
                        $sql = "INSERT INTO `sprintResults` (`raceId`, `driverId`, `constructorId`, `number`, `grid`, `position`, `positionText`, `positionOrder`, `points`, `laps`, `time`, `milliseconds`, `fastestLap`, `fastestLapTime`, `statusId`)
                                    VALUES ((SELECT `raceId` FROM `races` WHERE `year` = :year AND `round` = :round), 
                                            (SELECT `driverId` FROM `drivers` WHERE `driverRef` = :driverId), 
                                            (SELECT `constructorId` FROM `constructors` WHERE `constructorRef` = :constructorId), 
                                            :number, :grid, :position, :positionText, :positionOrder, :points, :laps, :time, :milliseconds, :fastestLap, :fastestLapTime, 
                                            (SELECT `statusId` FROM `status` WHERE `status` = :status))";

                        $stmt4 = $pdo->prepare($sql);

                        // Check for empty values
                        $time = (empty($result->Time->time)) ? null : $result->Time->time;
                        $millis = (empty($result->Time->millis)) ? null : $result->Time->millis;
                        $fastestLap = (empty($result->FastestLap->lap)) ? null : $result->FastestLap->lap;
                        $fastestLapTime = (empty($result->FastestLap->Time->time)) ? null : $result->FastestLap->Time->time;

                        // Bind parameters to the query
                        $stmt4->bindParam(':year', $race->season, PDO::PARAM_INT);
                        $stmt4->bindParam(':round', $race->round, PDO::PARAM_INT);
                        $stmt4->bindParam(':driverId', $result->Driver->driverId, PDO::PARAM_STR);
                        $stmt4->bindParam(':constructorId', $result->Constructor->constructorId, PDO::PARAM_STR);
                        $stmt4->bindParam(':number', $result->Driver->permanentNumber, PDO::PARAM_INT);
                        $stmt4->bindParam(':grid', $result->grid, PDO::PARAM_INT);
                        $stmt4->bindParam(':position', $result->position, PDO::PARAM_INT);
                        $stmt4->bindParam(':positionText', $result->positionText, PDO::PARAM_STR);
                        $stmt4->bindParam(':positionOrder', $result->position, PDO::PARAM_INT);
                        $stmt4->bindParam(':points', $result->points, PDO::PARAM_STR);
                        $stmt4->bindParam(':laps', $result->laps, PDO::PARAM_INT);
                        $stmt4->bindParam(':time', $time, PDO::PARAM_STR);
                        $stmt4->bindParam(':milliseconds', $millis, PDO::PARAM_INT);
                        $stmt4->bindParam(':fastestLap', $fastestLap, PDO::PARAM_INT);
                        $stmt4->bindParam(':fastestLapTime', $fastestLapTime, PDO::PARAM_STR);
                        $stmt4->bindParam(':status', $result->status, PDO::PARAM_STR);

                        // Execute the query
                        if ($stmt4->execute()) {
                            echo ' - Sprint results round ' . $race->round . ' (' . $race->raceName . ') added to the database' . $line_ending;
                        } else {
                            echo "Error inserting record: " . implode(", ", $stmt4->errorInfo());
                        }
                    }
                }
            }

            $total = $dets->MRData->total;

            // Increment the page number
            $page++;
        }

        echo 'Sprint race details complete' . $line_ending;
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage() . PHP_EOL);
    }

    // ************************************************************
    // * get the qualifying results
    // ************************************************************
    try {

        echo 'Getting qualifying details' . $line_ending;

        $page = 0;
        $total = 0;

        while ($page <= ($total / 100)) {

            // Get a page of qualifying details to process
            $dets = getPageofResults($endpoint . "/" . $cmdYear . "/qualifying/?format=json&limit=100&offset=" . ($page * 100));

            // Cycle through the qualifying and check if they are in the database already
            foreach ($dets->MRData->RaceTable->Races as $race) {

                // loop around all the qualifying results and insert
                foreach ($race->QualifyingResults as $result) {

                    // SQL query to check if the record exists
                    $sql = "SELECT 1
                                  FROM `qualifying`
                                 WHERE `raceId` = (SELECT `raceId` FROM `races` WHERE `round` = :round AND `year` = :year)
                                   AND `driverID` = (SELECT `driverId` FROM `drivers` WHERE `driverRef` = :driverId)";
                    $stmt = $pdo->prepare($sql);

                    // Bind parameters
                    $stmt->bindParam(':year', $race->season, PDO::PARAM_INT);
                    $stmt->bindParam(':round', $race->round, PDO::PARAM_INT);
                    $stmt->bindParam(':driverId', $result->Driver->driverId, PDO::PARAM_STR);

                    // Execute the query
                    $stmt->execute();

                    if ($stmt->rowCount() == 0) {
                        // Get the relevant race id
                        $sql = "SELECT `raceId` FROM `races` WHERE `year` = :year AND `round` = :round";
                        $stmt2 = $pdo->prepare($sql);

                        // Bind parameters
                        $stmt2->bindParam(':year', $race->season, PDO::PARAM_INT);
                        $stmt2->bindParam(':round', $race->round, PDO::PARAM_INT);

                        // Execute the query
                        $stmt2->execute();

                        if ($stmt2->rowCount() == 0) {
                            die('Race missing');
                        }

                        // Fetch the raceId
                        $raceId = $stmt2->fetchColumn();

                        // SQL query to insert data
                        $sql = "INSERT INTO `qualifying` (`raceId`, `driverId`, `constructorId`, `number`, `position`, `q1`, `q2`, `q3`)
                                    VALUES (:raceId, 
                                            (SELECT `driverId` FROM `drivers` WHERE `driverRef` = :driverId), 
                                            (SELECT `constructorId` FROM `constructors` WHERE `constructorRef` = :constructorId), 
                                            :number, :position, :q1, :q2, :q3)";

                        $stmt3 = $pdo->prepare($sql);

                        // Bind parameters to the query
                        $stmt3->bindParam(':raceId', $raceId, PDO::PARAM_INT);
                        $stmt3->bindParam(':driverId', $result->Driver->driverId, PDO::PARAM_STR);
                        $stmt3->bindParam(':constructorId', $result->Constructor->constructorId, PDO::PARAM_STR);
                        $stmt3->bindParam(':number', $result->Driver->permanentNumber, PDO::PARAM_INT);
                        $stmt3->bindParam(':position', $result->position, PDO::PARAM_INT);
                        $stmt3->bindParam(':q1', $result->Q1, PDO::PARAM_STR);
                        $stmt3->bindParam(':q2', $result->Q2, PDO::PARAM_STR);
                        $stmt3->bindParam(':q3', $result->Q3, PDO::PARAM_STR);

                        // Execute the query
                        if ($stmt3->execute()) {
                            echo ' - Qualifying results round ' . $race->round . ' (' . $race->raceName . ') added to the database' . $line_ending;
                        } else {
                            echo "Error inserting record: " . implode(", ", $stmt3->errorInfo());
                        }
                    }
                }
            }

            $total = $dets->MRData->total;

            // Increment the page number
            $page++;
        }

        echo 'Qualifying details complete' . $line_ending;
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage() . PHP_EOL);
    }

    // ************************************************************
    // * get the race results
    // ************************************************************
    try {

        echo 'Getting race results' . $line_ending;

        $page = 0;
        $total = 0;

        while ($page <= ($total / 100)) {

            // Get a page of race details to process
            $dets = getPageofResults($endpoint . "/" . $cmdYear . "/results/?format=json&limit=100&offset=" . ($page * 100));

            // Cycle through the races and check if they are in the database already
            foreach ($dets->MRData->RaceTable->Races as $race) {

                // loop around all the results results and insert
                foreach ($race->Results as $result) {

                    // SQL query to check if the record exists
                    $sql = "SELECT 1
                                  FROM `results`
                                 WHERE `raceId` = (SELECT `raceId` FROM `races` WHERE `round` = :round AND `year` = :year)
                                   AND `driverID` = (SELECT `driverId` FROM `drivers` WHERE `driverRef` = :driverId)";
                    $stmt = $pdo->prepare($sql);

                    // Bind parameters
                    $stmt->bindParam(':year', $race->season, PDO::PARAM_INT);
                    $stmt->bindParam(':round', $race->round, PDO::PARAM_INT);
                    $stmt->bindParam(':driverId', $result->Driver->driverId, PDO::PARAM_STR);

                    // Execute the query
                    $stmt->execute();

                    if ($stmt->rowCount() == 0) {
                        // Get the relevant race id
                        $sql = "SELECT `raceId` FROM `races` WHERE `year` = :year AND `round` = :round";
                        $stmt2 = $pdo->prepare($sql);

                        // Bind parameters
                        $stmt2->bindParam(':year', $race->season, PDO::PARAM_INT);
                        $stmt2->bindParam(':round', $race->round, PDO::PARAM_INT);

                        // Execute the query
                        $stmt2->execute();

                        if ($stmt2->rowCount() == 0) {
                            die('Race missing');
                        }

                        // Fetch the raceId
                        $raceId = $stmt2->fetchColumn();

                        // SQL query to insert data
                        $sql = "INSERT INTO `results` (`raceId`, `driverId`, `constructorId`, `number`, `grid`, `position`, `positionText`, `positionOrder`, `points`, `laps`, `time`, `milliseconds`, `rank`, `fastestLap`, `fastestLapTime`, `fastestLapSpeed`, `statusId`)
                                    VALUES (:raceId, 
                                            (SELECT `driverId` FROM `drivers` WHERE `driverRef` = :driverId), 
                                            (SELECT `constructorId` FROM `constructors` WHERE `constructorRef` = :constructorId), 
                                            :number, :grid, :position, :positionText, :positionOrder, :points, :laps, :time, :milliseconds, :rank, :fastestLap, :fastestLapTime, :fastestLapSpeed,
                                            (SELECT COALESCE((SELECT `statusId` FROM `status` WHERE `status` = :status) ,0) as 'statusId' FROM dual))";

                        $stmt3 = $pdo->prepare($sql);

                        // Check for empty values
                        $time = (empty($result->Time->time)) ? null : $result->Time->time;
                        $millis = (empty($result->Time->millis)) ? null : $result->Time->millis;
                        $rank = (empty($result->FastestLap->rank)) ? null : $result->FastestLap->rank;
                        $lap = (empty($result->FastestLap->lap)) ? null : $result->FastestLap->lap;
                        $fastestTime = (empty($result->FastestLap->Time->time)) ? null : $result->FastestLap->Time->time;
                        $fastestSpeed = (empty($result->FastestLap->AverageSpeed->speed)) ? null : $result->FastestLap->AverageSpeed->speed;

                        // Bind parameters to the query
                        $stmt3->bindParam(':raceId', $raceId, PDO::PARAM_INT);
                        $stmt3->bindParam(':driverId', $result->Driver->driverId, PDO::PARAM_STR);
                        $stmt3->bindParam(':constructorId', $result->Constructor->constructorId, PDO::PARAM_STR);
                        $stmt3->bindParam(':number', $result->Driver->permanentNumber, PDO::PARAM_INT);
                        $stmt3->bindParam(':grid', $result->grid, PDO::PARAM_INT);
                        $stmt3->bindParam(':position', $result->position, PDO::PARAM_INT);
                        $stmt3->bindParam(':positionText', $result->positionText, PDO::PARAM_STR);
                        $stmt3->bindParam(':positionOrder', $result->position, PDO::PARAM_INT);
                        $stmt3->bindParam(':points', $result->points, PDO::PARAM_STR);
                        $stmt3->bindParam(':laps', $result->laps, PDO::PARAM_INT);
                        $stmt3->bindParam(':time', $time, PDO::PARAM_STR);
                        $stmt3->bindParam(':milliseconds', $millis, PDO::PARAM_INT);
                        $stmt3->bindParam(':rank', $rank, PDO::PARAM_INT);
                        $stmt3->bindParam(':fastestLap', $lap, PDO::PARAM_INT);
                        $stmt3->bindParam(':fastestLapTime', $fastesttime, PDO::PARAM_STR);
                        $stmt3->bindParam(':fastestLapSpeed', $fastestSpeed, PDO::PARAM_STR);
                        $stmt3->bindParam(':status', $result->status, PDO::PARAM_STR);

                        // Execute the query
                        if ($stmt3->execute()) {
                            echo ' - Race results round ' . $race->round . ' (' . $race->raceName . ') added to the database' . $line_ending;
                        } else {
                            echo "Error inserting record: " . implode(", ", $stmt3->errorInfo());
                        }

                        // update the constructorResults table
                        $sql = "SELECT `constructorResultsId`
                                      FROM `constructorResults`
                                     WHERE `raceId` = :raceId
                                       AND `constructorId` = (SELECT `constructorId` FROM `constructors` WHERE `constructorRef` = :constructorId)";
                        $stmt4 = $pdo->prepare($sql);

                        // Bind parameters
                        $stmt4->bindParam(':raceId', $raceId, PDO::PARAM_INT);
                        $stmt4->bindParam(':constructorId', $result->Constructor->constructorId, PDO::PARAM_STR);

                        // Execute the query
                        $stmt4->execute();

                        if ($stmt4->rowCount() == 0) {
                            // SQL query to insert data
                            $sql = "INSERT INTO `constructorResults` (`raceId`, `constructorId`, `points`)
                                        VALUES (:raceId, 
                                                (SELECT `constructorId` FROM `constructors` WHERE `constructorRef` = :constructorId), 
                                                :points)";

                            $stmt5 = $pdo->prepare($sql);

                            // Bind parameters to the query
                            $stmt5->bindParam(':raceId', $raceId, PDO::PARAM_INT);
                            $stmt5->bindParam(':constructorId', $result->Constructor->constructorId, PDO::PARAM_STR);
                            $stmt5->bindParam(':points', $result->points, PDO::PARAM_STR);

                            // Execute the query
                            if ($stmt5->execute()) {
                                echo ' - Constructor results inserted ' . $race->round . ' (' . $race->raceName . ') added to the database' . $line_ending;
                            } else {
                                echo "Error inserting record: " . implode(", ", $stmt5->errorInfo());
                            }
                        } else {

                            // Fetch the constructorResultsId
                            $constructorResultsId = $stmt4->fetchColumn();

                            // update the sprint details into the races table
                            $sql = "UPDATE `constructorResults`
                                        SET `points` = :points + `points`
                                        WHERE `constructorResultsId` = :constructorResultsId";

                            $stmt6 = $pdo->prepare($sql);

                            // Bind parameters to the query
                            $stmt6->bindParam(':constructorResultsId', $constructorResultsId, PDO::PARAM_INT);
                            $stmt6->bindParam(':points', $result->points, PDO::PARAM_STR);

                            // Execute the query
                            if ($stmt6->execute()) {
                                echo ' - Constructor results updated ' . $race->round . ' (' . $race->raceName . ') added to the database' . $line_ending;
                            } else {
                                echo "Error updating record: " . implode(", ", $stmt6->errorInfo());
                            }
                        }
                    }
                }
            }

            $total = $dets->MRData->total;

            // Increment the page number
            $page++;
        }

        echo 'Race results complete' . $line_ending;
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage() . PHP_EOL);
    }

    // ************************************************************
    // * get the lap details
    // ************************************************************
    try {

        echo 'Getting lap details' . $line_ending;

        // Prepare the SQL query
        $sql = 'SELECT `round` FROM `races` WHERE `year` = :year  AND `date` <= NOW()';

        // Prepare the statement
        $stmt = $pdo->prepare($sql);

        // Bind the parameter
        $stmt->bindValue(':year', $cmdYear, PDO::PARAM_INT);

        // Execute the query
        $stmt->execute();

        // Fetch and loop through the results
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $page = 0;
            $total = 0;
            $year = $cmdYear;
            $round = $row['round'];

            echo ' - processing year ' . $year . ' round ' . $round . $line_ending;

            // Loop through the laps for this round
            while ($page <= ($total / 100)) {

                // Get a page of laps details to process
                $dets = getPageofResults($endpoint . "/" . $cmdYear . "/" . $row['round'] . "/laps/?format=json&limit=100&offset=" . ($page * 100));

                if (isset($dets->MRData->RaceTable->Races[0])) {
                    // Cycle through the lap and check if they are in the database already
                    foreach ($dets->MRData->RaceTable->Races[0]->Laps as $lap) {

                        foreach ($lap->Timings as $timing) {

                            // SQL query to check if the record exists
                            $sql = "SELECT 1
                                            FROM `lapTimes`
                                            WHERE `raceId` = (SELECT `raceId` FROM `races` WHERE `round` = :round AND `year` = :year)
                                            AND `driverID` = (SELECT `driverId` FROM `drivers` WHERE `driverRef` = :driverId)
                                            AND `lap` = :lap";
                            $stmt2 = $pdo->prepare($sql);

                            // Bind parameters
                            $stmt2->bindParam(':year', $year, PDO::PARAM_INT);
                            $stmt2->bindParam(':round', $round, PDO::PARAM_INT);
                            $stmt2->bindParam(':driverId', $timing->driverId, PDO::PARAM_STR);
                            $stmt2->bindParam(':lap', $lap->number, PDO::PARAM_INT);

                            // Execute the query
                            $stmt2->execute();

                            if ($stmt2->rowCount() == 0) {

                                // SQL query to insert data
                                $sql = "INSERT INTO `lapTimes` (`raceId`, `driverId`, `lap`, `position`, `time`, `milliseconds`)
                                            VALUES ((SELECT `raceId` FROM `races` WHERE `year` = :year AND `round` = :round), 
                                                    (SELECT `driverId` FROM `drivers` WHERE `driverRef` = :driverId),
                                                    :lap, 
                                                    :position,
                                                    :time,
                                                    :milliseconds)";

                                $stmt3 = $pdo->prepare($sql);

                                // Bind parameters to the query
                                $milliseconds = timeToMilliseconds($timing->time);
                                $stmt3->bindParam(':year', $year, PDO::PARAM_INT);
                                $stmt3->bindParam(':round', $round, PDO::PARAM_INT);
                                $stmt3->bindParam(':driverId', $timing->driverId, PDO::PARAM_STR);
                                $stmt3->bindParam(':lap', $lap->number, PDO::PARAM_INT);
                                $stmt3->bindParam(':position', $timing->position, PDO::PARAM_INT);
                                $stmt3->bindParam(':time', $timing->time, PDO::PARAM_STR);
                                $stmt3->bindParam(':milliseconds', $milliseconds, PDO::PARAM_INT);

                                // Execute the query
                                if ($stmt3->execute()) {
                                    echo '  - Lap results round ' . $round . ' lap ' . $lap->number . ' (' . $timing->driverId . ') added to the database' . $line_ending;
                                } else {
                                    echo "Error inserting record: " . implode(", ", $stmt3->errorInfo());
                                }
                            }
                        }
                    }
                }

                $total = $dets->MRData->total;

                // Increment the page number
                $page++;
            }
        }

        echo 'Lap details complete' . $line_ending;
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage() . PHP_EOL);
    }

    // ************************************************************
    // * get the pitstop details
    // ************************************************************
    try {

        echo 'Getting pitstop details' . $line_ending;

        // Prepare the SQL query
        $sql = 'SELECT `round` FROM `races` WHERE `year` = :year AND `date` <= NOW()';

        // Prepare the statement
        $stmt = $pdo->prepare($sql);

        // Bind the parameter
        $stmt->bindValue(':year', $cmdYear, PDO::PARAM_INT);

        // Execute the query
        $stmt->execute();

        // Fetch and loop through the results
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $page = 0;
            $total = 0;
            $year = $cmdYear;
            $round = $row['round'];

            echo ' - processing year ' . $year . ' round ' . $round . $line_ending;

            // Loop through the laps for this round
            while ($page <= ($total / 100)) {

                // Get a page of laps details to process
                $dets = getPageofResults($endpoint . "/" . $cmdYear . "/" . $row['round'] . "/pitstops/?format=json&limit=100&offset=" . ($page * 100));

                if (isset($dets->MRData->RaceTable->Races)) {
                    // Cycle through the lap and check if they are in the database already
                    foreach ($dets->MRData->RaceTable->Races[0]->PitStops as $pit) {

                        // SQL query to check if the record exists
                        $sql = "SELECT 1
                                        FROM `pitStops`
                                        WHERE `raceId` = (SELECT `raceId` FROM `races` WHERE `round` = :round AND `year` = :year)
                                        AND `driverID` = (SELECT `driverId` FROM `drivers` WHERE `driverRef` = :driverId)
                                        AND `lap` = :lap
                                        AND `stop` = :stop";
                        $stmt2 = $pdo->prepare($sql);

                        // Bind parameters
                        $stmt2->bindParam(':year', $year, PDO::PARAM_INT);
                        $stmt2->bindParam(':round', $round, PDO::PARAM_INT);
                        $stmt2->bindParam(':driverId', $pit->driverId, PDO::PARAM_STR);
                        $stmt2->bindParam(':lap', $pit->lap, PDO::PARAM_INT);
                        $stmt2->bindParam(':stop', $pit->stop, PDO::PARAM_INT);

                        // Execute the query
                        $stmt2->execute();

                        if ($stmt2->rowCount() == 0) {

                            // SQL query to insert data
                            $sql = "INSERT INTO `pitStops` (`raceId`, `driverId`, `lap`, `stop`, `time`, `duration`, `milliseconds`)
                                        VALUES ((SELECT `raceId` FROM `races` WHERE `year` = :year AND `round` = :round), 
                                                (SELECT `driverId` FROM `drivers` WHERE `driverRef` = :driverId),
                                                :lap, 
                                                :stop,
                                                :time,
                                                :duration,
                                                :milliseconds)";

                            $stmt3 = $pdo->prepare($sql);

                            // Bind parameters to the query
                            $milliseconds = timeToMilliseconds($pit->time);
                            $stmt3->bindParam(':year', $year, PDO::PARAM_INT);
                            $stmt3->bindParam(':round', $round, PDO::PARAM_INT);
                            $stmt3->bindParam(':driverId', $pit->driverId, PDO::PARAM_STR);
                            $stmt3->bindParam(':lap', $pit->lap, PDO::PARAM_INT);
                            $stmt3->bindParam(':stop', $pit->stop, PDO::PARAM_INT);
                            $stmt3->bindParam(':time', $pit->time, PDO::PARAM_STR);
                            $stmt3->bindParam(':duration', $pit->duration, PDO::PARAM_STR);
                            $stmt3->bindParam(':milliseconds', $milliseconds, PDO::PARAM_INT);

                            // Execute the query
                            if ($stmt3->execute()) {
                                echo '  - Pitstop results round ' . $round . ' lap ' . $pit->lap . ' (' . $pit->driverId . ') added to the database' . $line_ending;
                            } else {
                                echo "Error inserting record: " . implode(", ", $stmt3->errorInfo());
                            }
                        }
                    }
                }

                $total = $dets->MRData->total;

                // Increment the page number
                $page++;
            }
        }

        echo 'Pitstop details complete' . $line_ending;
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage() . PHP_EOL);
    }

    // ************************************************************
    // * get the constructor standing details
    // ************************************************************
    try {

        echo 'Getting constructor standing details' . $line_ending;

        // Prepare the SQL query
        $sql = 'SELECT `round` FROM `races` WHERE `year` = :year AND `date` <= NOW()';

        // Prepare the statement
        $stmt = $pdo->prepare($sql);

        // Bind the parameter
        $stmt->bindValue(':year', $cmdYear, PDO::PARAM_INT);

        // Execute the query
        $stmt->execute();

        // Fetch and loop through the results
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $page = 0;
            $total = 0;
            $year = $cmdYear;
            $round = $row['round'];

            echo ' - processing year ' . $year . ' round ' . $round . $line_ending;

            // Loop through the constructor standings for this year
            while ($page <= ($total / 100)) {

                // Get a page of details to process
                $dets = getPageofResults($endpoint . "/" . $cmdYear . "/" . $row['round'] . "/constructorstandings/?format=json&limit=100&offset=" . ($page * 100));

                // Cycle through the standings and check if they are in the database already
                foreach ($dets->MRData->StandingsTable->StandingsLists[0]->ConstructorStandings as $standing) {

                    // SQL query to check if the record exists
                    $sql = "SELECT 1
                                    FROM `constructorStandings`
                                    WHERE `raceId` = (SELECT `raceId` FROM `races` WHERE `round` = :round AND `year` = :year)
                                    AND `constructorId` = (SELECT `constructorId` FROM `constructors` WHERE `constructorRef` = :constructorId) 
                                    AND `position` = :position";
                    $stmt2 = $pdo->prepare($sql);

                    // Bind parameters
                    $stmt2->bindParam(':year', $year, PDO::PARAM_INT);
                    $stmt2->bindParam(':round', $round, PDO::PARAM_INT);
                    $stmt2->bindParam(':constructorId', $standing->Constructor->constructorId, PDO::PARAM_STR);
                    $stmt2->bindParam(':position', $standing->position, PDO::PARAM_INT);

                    // Execute the query
                    $stmt2->execute();

                    if ($stmt2->rowCount() == 0) {

                        // SQL query to insert data
                        $sql = "INSERT INTO `constructorStandings` (`raceId`, `constructorId`, `points`, `position`, `positionText`, `wins`)
                                    VALUES ((SELECT `raceId` FROM `races` WHERE `year` = :year AND `round` = :round), 
                                            (SELECT `constructorId` FROM `constructors` WHERE `constructorRef` = :constructorId),
                                            :points,
                                            :position,
                                            :positionText,
                                            :wins)";
                        $stmt3 = $pdo->prepare($sql);

                        // Bind parameters to the query
                        $stmt3->bindParam(':year', $year, PDO::PARAM_INT);
                        $stmt3->bindParam(':round', $round, PDO::PARAM_INT);
                        $stmt3->bindParam(':constructorId', $standing->Constructor->constructorId, PDO::PARAM_STR);
                        $stmt3->bindParam(':points', $standing->points, PDO::PARAM_INT);
                        $stmt3->bindParam(':position', $standing->position, PDO::PARAM_INT);
                        $stmt3->bindParam(':positionText', $standing->positionText, PDO::PARAM_STR);
                        $stmt3->bindParam(':wins', $standing->wins, PDO::PARAM_STR);

                        // Execute the query
                        if ($stmt3->execute()) {
                            echo '  - Constructor results round ' . $round . ' round ' . $row['round'] . ' (' . $standing->Constructor->constructorId . ') added to the database' . $line_ending;
                        } else {
                            echo "Error inserting record: " . implode(", ", $stmt3->errorInfo());
                        }
                    }
                }

                $total = $dets->MRData->total;

                // Increment the page number
                $page++;
            }
        }
        echo 'Constructor Standing details complete' . $line_ending;
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage() . PHP_EOL);
    }

    // ************************************************************
    // * get the driver standing details
    // ************************************************************
    try {

        echo 'Getting driver standing details' . $line_ending;

        // Prepare the SQL query
        $sql = 'SELECT `round` FROM `races` WHERE `year` = :year AND `date` <= NOW()';

        // Prepare the statement
        $stmt = $pdo->prepare($sql);

        // Bind the parameter
        $stmt->bindValue(':year', $cmdYear, PDO::PARAM_INT);

        // Execute the query
        $stmt->execute();

        // Fetch and loop through the results
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $page = 0;
            $total = 0;
            $year = $cmdYear;
            $round = $row['round'];

            echo ' - processing year ' . $year . ' round ' . $round . $line_ending;

            // Loop through the drive standings for this year
            while ($page <= ($total / 100)) {

                // Get a page of details to process
                $dets = getPageofResults($endpoint . "/" . $cmdYear . "/" . $row['round'] . "/driverstandings/?format=json&limit=100&offset=" . ($page * 100));

                // Cycle through the standings and check if they are in the database already
                foreach ($dets->MRData->StandingsTable->StandingsLists[0]->DriverStandings as $standing) {

                    // SQL query to check if the record exists
                    $sql = "SELECT 1
                                    FROM `driverStandings`
                                    WHERE `raceId` = (SELECT `raceId` FROM `races` WHERE `round` = :round AND `year` = :year)
                                    AND `driverId` = (SELECT `driverId` FROM `drivers` WHERE `driverRef` = :driverId) 
                                    AND `position` = :position";
                    $stmt2 = $pdo->prepare($sql);

                    // Bind parameters
                    $stmt2->bindParam(':year', $year, PDO::PARAM_INT);
                    $stmt2->bindParam(':round', $round, PDO::PARAM_INT);
                    $stmt2->bindParam(':driverId', $standing->Driver->driverId, PDO::PARAM_STR);
                    $stmt2->bindParam(':position', $standing->position, PDO::PARAM_INT);

                    // Execute the query
                    $stmt2->execute();

                    if ($stmt2->rowCount() == 0) {

                        // SQL query to insert data
                        $sql = "INSERT INTO `driverStandings` (`raceId`, `driverId`, `points`, `position`, `positionText`, `wins`)
                                    VALUES ((SELECT `raceId` FROM `races` WHERE `year` = :year AND `round` = :round), 
                                            (SELECT `driverId` FROM `drivers` WHERE `driverRef` = :driverId),
                                            :points,
                                            :position,
                                            :positionText,
                                            :wins)";
                        $stmt3 = $pdo->prepare($sql);

                        // Bind parameters to the query
                        $stmt3->bindParam(':year', $year, PDO::PARAM_INT);
                        $stmt3->bindParam(':round', $round, PDO::PARAM_INT);
                        $stmt3->bindParam(':driverId', $standing->Driver->driverId, PDO::PARAM_STR);
                        $stmt3->bindParam(':points', $standing->points, PDO::PARAM_INT);
                        $stmt3->bindParam(':position', $standing->position, PDO::PARAM_INT);
                        $stmt3->bindParam(':positionText', $standing->positionText, PDO::PARAM_STR);
                        $stmt3->bindParam(':wins', $standing->wins, PDO::PARAM_STR);

                        // Execute the query
                        if ($stmt3->execute()) {
                            echo '  - Driver results round ' . $round . ' round ' . $row['round'] . ' (' . $standing->Driver->driverId . ') added to the database' . $line_ending;
                        } else {
                            echo "Error inserting record: " . implode(", ", $stmt3->errorInfo());
                        }
                    }
                }

                $total = $dets->MRData->total;

                // Increment the page number
                $page++;
            }
        }
        echo 'Driver Standing details complete' . $line_ending;
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage() . PHP_EOL);
    }
}

// Close the connection
$pdo = null;

echo '----------------------------------' . $line_ending;
echo 'Finished Processing' . $line_ending;

function timeToMilliseconds($time)
{
    // Split the time string into its components
    list($minutes, $seconds) = explode(':', $time);
    if (strpos($seconds, '.') !== false) {
        list($seconds, $milliseconds) = explode('.', $seconds);
    } else {
        $milliseconds = 0;
    }

    // Convert to milliseconds
    $totalMilliseconds = ($minutes * 60 * 1000) + ($seconds * 1000) + $milliseconds;

    return $totalMilliseconds;
}

function getPageofResults($url)
{

    $httpCode = 0;
    $i429 = 0;

    while ($httpCode != 200) {
        // Get a page of details to process
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode == 429 && $i429 < 5) {
            // if we get a 429 error, sleep for a second and try again
            sleep(1);
            $i429++;
        } elseif ($httpCode == 429 && $i429 >= 5) {
            die('Error: ' . $httpCode . ' url: ' . $url . ' ' . $response);
        } elseif ($httpCode != 200) {
            die('Error: ' . $httpCode . ' url: ' . $url . ' ' . $response);
        }
    }

    return json_decode($response);
}
