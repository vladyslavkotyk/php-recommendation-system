<?php

// The MovieLense data needs transformation
// We need update the movies table for the first method, add 2 columns: no_of_users_rated and avg_rating
// And with this script we will update those values

// This code is not optimized to run fast, but for easier comprehension

// Require the database connection
require_once("connect.php");

// Remove memory limit
ini_set('memory_limit', '-1');

// Time the script
$start_time = microtime(true);

// Make script execution time unlimited
set_time_limit(0);

// Split the query into chunks of:
$chunks = 5000; 
$curent_chunk = 1;

// Add the 2 columns if not exist
$sql = "SHOW COLUMNS FROM `movies` LIKE 'no_of_users_rated'";
$query = $db->prepare($sql);
$query->execute();

if (count($query->fetchAll()) == 0) {
    
    // Alter the movies table
    $sql = "ALTER TABLE `movies` ADD `no_of_users_rated` INT(11) NOT NULL AFTER `genres`,  ADD `avg_rating` INT(11) NOT NULL AFTER `no_of_users_rated`";
    $query = $db->prepare($sql);
    $query->execute();
}

$start = ($curent_chunk - 1) * $chunks;  

// Start the first chunk, recursive function
handle($start, $chunks);

function handle($start, $chunk) {
    
    global $db;
    global $curent_chunk;
    global $chunks;
    
    echo "Starting chunk: " . $curent_chunk . "\n";
    
    $sql = "SELECT * FROM ratings LIMIT " . $start . "," . $chunks; 
    $query = $db->prepare($sql);
    $query->execute();
    $ratings = $query->fetchAll(\PDO::FETCH_ASSOC);
    
    // Init an array named movies, where the key is the movie ID and the value is number of time the movie has been voted
    $movies = array();
    
    if (!empty($ratings)) {
        
        foreach ($ratings as $rating) {
            
            // Check if the variable is set so that no errors show up
            if (!isset($movies[$rating["movieId"]]["no_of_users_rated"])) {
                
                $movies[$rating["movieId"]]["no_of_users_rated"] = 0;
            }
            
            // Add 1 to the number of users rated
            $movies[$rating["movieId"]]["no_of_users_rated"]++;
            
            // Average Ratings:
            if (!isset($movies[$rating["movieId"]]["avg_rating"])) {
                
                $movies[$rating["movieId"]]["avg_rating"] = 0;
            }
            
            // Add the rating to column
            $movies[$rating["movieId"]]["avg_rating"] = $movies[$rating["movieId"]]["avg_rating"] + $rating["rating"];
        }
        
    } else {
        
        echo "Error: No ratings found on this chunk \n"; 
        end_script();
    }
        
    if (!empty($movies)) {
        
        // Insert the values to the database
    
        foreach ($movies as $movie_id => $movie) {
            
            $movie["avg_rating"] = round($movie["avg_rating"]);
            
            $sql = "UPDATE movies set no_of_users_rated = :no_of_users_rated + no_of_users_rated, avg_rating = ((avg_rating * no_of_users_rated) + :avg_rating) / (:no_of_users_rated + no_of_users_rated) WHERE movieId = :movieId";
            
            $query = $db->prepare($sql);
            $query->execute(array(":no_of_users_rated" => $movie["no_of_users_rated"], ":avg_rating" => $movie["avg_rating"], ":movieId" => $movie_id));
        }
        
    } else {
        
        echo "Error: No movies found on this chunk \n"; 
        end_script();
    }
    
    // Move to next chunk
    $curent_chunk++;
    $start = ($curent_chunk - 1) * $chunks;  
    handle($start, $chunk);
}

function end_script() {
    
    global $curent_chunk;
    global $start_time;
    
    echo "Used " . ( $curent_chunk - 1 ) . " chunks \n";

    // Script end time
    $end_time = microtime(true) - $start_time;
    echo "The script took: " . $end_time . " seconds to run.";
    exit();
}

