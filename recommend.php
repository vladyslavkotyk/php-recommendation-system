<?php

$start_time = microtime(true);
// Require the database connection
require_once("connect.php");

// Assign an user id from the MovieLens database
$user = 1;

/*
    METHODS
    1 - Genre based recommendations
    2 - Collaborative filtering ( non personalized )
    3 - Collaborative filtering ( personalized )
*/

// About the MovieLens ratings:
// Rating 0/5 not 0/10 like imdb


if ($_GET["id"] == 1) {
    
    /*
    
        GENRE BASED RECOMMENDATIONS
    
    */
    
    // Get the users rated movies
    
    $sql = "SELECT * FROM ratings WHERE userId = :userId"; 
    $query = $db->prepare($sql);
    $query->execute(array(":userId" => $user));
    $ratings = $query->fetchAll(\PDO::FETCH_ASSOC);
    
    // Check if array not empty 
    if (!empty($ratings)) {
        
        $query = "( ";
        
        foreach ($ratings as $rating) {
        
            // In this loop we will get the ids from the movies that the user rated
            // Build a query that will get movie data from ids
            $query .= "movieid = " . $rating["movieId"] . " OR ";
        }
        
        // Trim the last OR from the query
        $query = rtrim($query, " OR ");
        $query = $query . ")";
    }
    
    $sql = "SELECT genres FROM movies WHERE " . $query;
    
    $query = $db->prepare($sql);
    $query->execute();
    $genres_user = $query->fetchAll(\PDO::FETCH_ASSOC);
    
    // Array that will contain the genres as key and the number of time the genre is used as value
    // Example -> array("Comedy" => 23);
    $genres = array();
    
    foreach ($genres_user as $genre) {
        
        $genre = explode("|", $genre["genres"]);
        
        foreach($genre as $single_genre) {
        
            if (isset($genres[$single_genre])) {
                
                $genres[$single_genre]++;
                
            } else {
                
                $genres[$single_genre] = 1;
            }
        }
    }
    
    arsort($genres);
    var_dump($genres);
    
    $end_time = microtime(true) - $start_time;
    echo "The script took: " . $end_time . " seconds to run.";
    exit();
}    

    
if ($_GET["id"] == 2) {
    
    // Run prepare_data.php first
    
    /*
    
        Collaborative filtering heavily using mysql
    
    */
    
    $movie_id = 32;
    
    // Non personalized
    // Get recommendations for movie -> $movie_id
    $sql = "SELECT movie_id2, (sum/count) AS average FROM dev WHERE (count > 2) AND (movie_id1 = :movie_id1) ORDER BY (sum/count) DESC LIMIT 10";
    $query = $db->prepare($sql);
    $query->execute(array(":movie_id1" => $movie_id));
    $movies = $query->fetchAll(\PDO::FETCH_ASSOC);
    
    if (!empty($movies)) {
        
        $query = "";
        $data_ids = array();
        
        foreach ($movies as $movie) {
            
            $data_ids[$movie["movie_id2"]] = $movie["average"];
            $query .= "(movieId = " . $movie["movie_id2"] . ") OR ";
        }
        
        $query = rtrim($query, " OR ");
    }
    
    $sql = "SELECT * FROM movies WHERE " . $query;
    $query = $db->prepare($sql);
    $query->execute(array(":movie_id1" => $movie_id));
    $movies_data = $query->fetchAll(\PDO::FETCH_ASSOC);
    
    if (!empty($movies_data)) {
    
        foreach ($movies_data as $movie_data) {
            
            if (isset($data_ids[$movie_data["movieId"]])) {
                
                $data_ids[$movie_data["movieId"]] = array("name" => $movie_data["title"], "average" => $data_ids[$movie_data["movieId"]]);
            }
        }
    }

    var_dump($data_ids);
    
    $end_time = microtime(true) - $start_time;
    echo "The script took: " . $end_time . " seconds to run.";
    exit();
}

if ($_GET["id"] == 3) {
    
    $user = 1;
    
    $sql = "SELECT SQL_NO_CACHE d.movie_id1 as 'item', sum(d.sum + d.count * r.rating) / sum(d.count) as avgrat 
            FROM ratings r, dev d 
            WHERE r.userId = 1 
            AND d.movie_id1 <> r.movieId 
            AND d.movie_id2 = r.movieId
            GROUP BY d.movie_id1 ORDER BY avgrat DESC LIMIT 10";
            
            $query = $db->prepare($sql);
    $query->execute(array(":userID" => $user));
    $movies_data = $query->fetchAll(\PDO::FETCH_ASSOC);
    
    var_dump($movies_data);
    
    $end_time = microtime(true) - $start_time;
    echo "The script took: " . $end_time . " seconds to run.";
    exit();
}
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
