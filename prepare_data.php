<?php

// Require the database connection
require_once("connect.php");

// Remove memory limit
ini_set('memory_limit', '-1');

// Make script execution time unlimited
set_time_limit(0);

// Time the script
$start_time = microtime(true);

// This script prepares MovieLens Data for the 2nd method, run it with console

$sql = "SELECT DISTINCT userID FROM ratings";

$query = $db->prepare($sql);
$query->execute();
$users = $query->fetchAll(\PDO::FETCH_COLUMN);

//$end_time_1 = microtime(true) - $start_time;

if (!empty($users)) {
    
    $count_user = count($users);
    
    foreach ($users as $key => $user) {
        
        if ($key > 323) {
            
            echo "Handling user " . ( $key + 1 ) . "/" . $count_user . "\n";
        
            // SELECT the users ratings
            $sql = "SELECT movieId FROM ratings WHERE userId = :userId";
    
            $query = $db->prepare($sql);
            $query->execute(array(":userId" => $user));
            $ratings = $query->fetchAll(\PDO::FETCH_ASSOC);
            
            //$end_time_2 = microtime(true) - $start_time;
            
            $count_ratings = count($ratings);
            
            foreach ($ratings as $key => $rating) {
                
                echo " - Handling rating " . ( $key + 1 ). "/" . $count_ratings . "\n";
                
                rateDiff($user, $rating["movieId"]);
            }
        }
    }
}

function rateDiff($user, $movie_id) {
    
    //$start_time2 = microtime(true);
    
    global $db;
    
    // Get's all of the users ratings and subtracts for each movie the rating value from every other movie
    
    $sql = "SELECT DISTINCT r.movieId, r2.rating - r.rating AS rating_difference
            FROM ratings r, ratings r2 WHERE r.userId = :userId AND r2.movieId = :movieId AND r2.userID = :userId";
            
    $query = $db->prepare($sql);
    $query->execute(array(":userId" => $user, ":movieId" => $movie_id));
    $ratings_difference = $query->fetchAll(\PDO::FETCH_ASSOC);
    
    // Check if not empty to avoid errors    
    if (!empty($ratings_difference)) {
        
        foreach ($ratings_difference as $rating_diff) {
            
            $array = array();
            $array["rating_difference"] = $rating_diff["rating_difference"];
            $array["movie_id1"] = $movie_id;
            $array["movie_id2"] = $rating_diff["movieId"];
            $array = json_encode($array) . "\n";
            file_put_contents("insert_after_lele", $array, FILE_APPEND);
            
            /*// For every one of the user's rating pairs we update the dev table
            
            // Check if pair already exists
            $sql = "SELECT COUNT(movie_id1) FROM dev WHERE movie_id1 = :movie_id1 AND movie_id2 = :movie_id2";
            
            $query = $db->prepare($sql);
            $query->execute(array(":movie_id1" => $rating_diff["movieId"], ":movie_id2" => $movie_id));
            $count = $query->fetchColumn();
            
            if ($count == 0) {
                
                // Insert pair into dev
                
                $sql = "INSERT INTO dev VALUES (:movie_id1, :movie_id2, 1, :rating_difference)";
                
                $query = $db->prepare($sql);
                $query->execute(array(":rating_difference" => $rating_diff["rating_difference"], ":movie_id1" => $movie_id, ":movie_id2" => $rating_diff["movieId"]));
                
                // Notice the diferente movie_id1 and movie_id2
                if ($rating_diff["movieId"] != $movie_id) {
                    
                    $sql = "INSERT INTO dev VALUES (:movie_id1, :movie_id2, 1, :rating_difference)";
                
                    $query = $db->prepare($sql);
                    $query->execute(array(":rating_difference" => $rating_diff["rating_difference"], ":movie_id1" => $rating_diff["movieId"], ":movie_id2" => $movie_id));
                }
                
            } else {
                
                // Update the 2 pairs
                // (x, y) and (y, x)
                
                $sql = "UPDATE dev SET count = count + 1, sum = sum + :rating_difference WHERE movie_id1 = :movie_id1 AND movie_id2 = :movie_id2";
                
                $query = $db->prepare($sql);
                $query->execute(array(":rating_difference" => $rating_diff["rating_difference"], ":movie_id1" => $rating_diff["movieId"], ":movie_id2" => $movie_id));
                
                // Notice the diferente movie_id1 and movie_id2
                if ($rating_diff["movieId"] != $movie_id) {
                    
                    $sql = "UPDATE dev SET count = count + 1, sum = sum + :rating_difference WHERE movie_id1 = :movie_id1 AND movie_id2 = :movie_id2";
                
                    $query = $db->prepare($sql);
                    $query->execute(array(":rating_difference" => $rating_diff["rating_difference"], ":movie_id1" => $movie_id, ":movie_id2" => $rating_diff["movieId"]));
                }
            }*/
        }    
    }
    
    /*global $end_time_1,$end_time_2, $start_time;
    
    echo "The get users id took: " . $end_time_1 . " seconds to run.\n";
    
    echo "The get users ratings took: " . $end_time_2 . " seconds to run.\n";
    
    $end_time_33 = microtime(true) - $start_time2;
    echo "The function rateDiff took: " . $end_time_33 . " seconds to run.\n";
    
    
    $end_time_3 = microtime(true) - $start_time;
    echo "The script took: " . $end_time_3 . " seconds to run.\n";
    exit();*/
}

// Script end time
$end_time = microtime(true) - $start_time;
echo "The script took: " . $end_time . " seconds to run.\n";
exit();