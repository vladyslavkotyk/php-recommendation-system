<?php

// Require the database connection
require_once("connect.php");

$file = "insert_after_lele";

$filesize = get_file_size($file);
$fp = @fopen($file, "r");

$passes = ($filesize / (1<<20));
$passes = round($passes);

// if handle $fp to file was created, go ahead
if ($fp) {
    $position = 0;
    $i = 1;
   while(!feof($fp)){
      $chunk_size = (1<<20); // 16MB arbitrary
      
        echo "Pass " . $i . " out of " . $passes . "\n";
      // move pointer to $position in file
      fseek($fp, $position);

      // take a slice of $chunk_size bytes
      $chunk = fread($fp,$chunk_size);

      // searching the end of last full text line
      $last_lf_pos = strrpos($chunk, "\n");
      $chunk = NULL;

      // $buffer will contain full lines of text
      // starting from $position to $last_lf_pos
      $buffer = fread($fp,$last_lf_pos);

      ////////////////////////////////////////////////////
      //// ... DO SOMETHING WITH THIS BUFFER HERE ... ////
      ////////////////////////////////////////////////////
      preg_match_all('/{(.*?)}/', $buffer, $matches);
      
      //dd($matches);
      
      // Time the script
$start_time = microtime(true);
      
      if (!empty($matches)) {
          
          //echo count($matches[0]); exit();
          
            $insert1 = array();
            $insert2 = array();
            
            $update1 = array();
            $update2 = array();
          
          foreach($matches[0] as $match) {
              
              $match = json_decode($match, true);
              
                // For every one of the user's rating pairs we update the dev table
                
                // Check if pair already exists
                $sql = "SELECT COUNT(movie_id1) FROM dev WHERE movie_id1 = :movie_id1 AND movie_id2 = :movie_id2";
                
                $query = $db->prepare($sql);
                $query->execute(array(":movie_id1" => $match["movie_id2"], ":movie_id2" => $match["movie_id1"]));
                $count = $query->fetchColumn();
                
                if ($count == 0) {
                    
                    $insert1[] = "(" . $match["movie_id1"] . ", " . $match["movie_id2"] . ", 1, " . $match["rating_difference"] . ")";
                    
                    // Notice the diferente movie_id1 and movie_id2
                    if ($match["movie_id2"] != $match["movie_id1"]) {
                        
                        $insert2[] = "(" . $match["movie_id2"] . ", " . $match["movie_id1"] . ", 1, " . $match["rating_difference"] . ")";
                    }
                    
                } else {
                    
                    // Update the 2 pairs
                    // (x, y) and (y, x)
                    
                    $sql = "UPDATE dev SET count = count + 1, sum = sum + :rating_difference WHERE movie_id1 = :movie_id1 AND movie_id2 = :movie_id2";
                    
                    $query = $db->prepare($sql);
                    $query->execute(array(":rating_difference" => $match["rating_difference"], ":movie_id1" => $match["movie_id2"], ":movie_id2" => $match["movie_id1"]));
                    
                    // Notice the diferente movie_id1 and movie_id2
                    if ($match["movie_id2"] != $match["movie_id1"]) {
                        
                        $sql = "UPDATE dev SET count = count + 1, sum = sum + :rating_difference WHERE movie_id1 = :movie_id1 AND movie_id2 = :movie_id2";
                    
                        $query = $db->prepare($sql);
                        $query->execute(array(":rating_difference" => $match["rating_difference"], ":movie_id1" => $match["movie_id1"], ":movie_id2" => $match["movie_id2"]));
                    }
                }
          }
          
          // inserts and updates
                
            // Insert pair into dev
            echo "Saved " . count($insert1) . " queries \n";
            $sql = "INSERT IGNORE INTO dev VALUES " . implode(", ", $insert1);
            
            $query = $db->prepare($sql);
            $query->execute();
            
            $sql = "INSERT IGNORE INTO dev VALUES " . implode(", ", $insert2);
            
            $query = $db->prepare($sql);
            $query->execute();
      }
      
    $i++;
      
      // Move $position
      $position += $last_lf_pos;

      // if remaining is less than $chunk_size, make $chunk_size equal remaining
      if(($position+$chunk_size) > $filesize) $chunk_size = $filesize-$position;
      $buffer = NULL;
   }
   fclose($fp);
}
$end_time = microtime(true) - $start_time;
    echo "The script took: " . $end_time . " seconds to run.";
    exit();
    


function get_file_size ($file) {
   $fp = @fopen($file, "r");
   @fseek($fp,0,SEEK_END);
   $filesize = @ftell($fp);
   fclose($fp);
   return $filesize;
}