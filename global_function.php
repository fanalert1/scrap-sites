<?php

require_once(__DIR__ . '/imdb_get.php');

function isUpcoming($value,$collection)
{
     $count = $collection->count(["name"=>$value,"type"=>"upcoming"]);
     if($count>0)
     {
         return true;
     }
     else
     {
         return false;
     }
}


function isRunning($value,$collection)
{
     $count = $collection->count(["name"=>$value,"type"=>"running"]);
     if($count>0)
     {
         return true;
     }
     else
     {
         return false;
     }
}

function isPresent($value,$collection)
{
     $count = $collection->count(["name"=>$value]);
     if($count>0)
     {
         return true;
     }
     else
     {
         return false;
     }
}

function getDetail($value,$collection,$inField,$outField)
{
    $count = $collection->findOne([$inField=>$value]);
    $temp = json_encode($count);
    $json = json_decode($temp , true);
    return $json[$outField];
}

function getCounter($name,$collection)
{
    $test = $collection->findOne(["name"=>$name]);
    $temp = json_encode($test);
    $json = json_decode($temp , true);
    $test = $collection->updateOne(["name"=>$name],
    ['$set' => array("count"=> $json["count"]+1)],
    ['upsert' => false]);
    return $json["count"];
}

function getMovieDetails($movie_name)
{
    $movie_details=get_imdb_det($movie_name);
    if(is_array($movie_details))
    {
        return $movie_details;
    }
    
}

function linkcheck($url)
{
    $handle = curl_init($url);
    curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($handle);
    $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    if($httpCode == 200) 
    {
        curl_close($handle);
        return true;
    }else
    {
        curl_close($handle);
        //need to add slack integration for the failed links
        return false;
    }
}

?>
