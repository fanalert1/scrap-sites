<?php

/*
*This PHP script will scrape the wikipedia website for movie database.
*/
require('global_function.php');
date_default_timezone_set("Asia/Calcutta");//Set timezone to India
$current_ts=date("Y/m/d H:i:s");
echo "Job Started on ".$current_ts."\n";
require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/src/Browser/Casper.php');
date_default_timezone_set("Asia/Calcutta");

use Browser\Casper;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

$client = new Client();
$crawler = $client->request('GET', 'https://en.wikipedia.org/wiki/List_of_Tamil_films_of_2016');
$movies_list=array();
$movie="";
$field="";
$info="";
$info1=array();
$crawler->filter('table[class$="wikitable sortable"]')->each(function (Crawler $node, $i) {
    $node->filter('i > a')->each(function (Crawler $node, $i) {
        global $client;
        global $movie;
        $movie=$node->text();
        $link = "https://en.wikipedia.org".$node->attr('href');
        //echo $link;
        $crawler = $client->request('GET', $link);
        $crawler->filter('table.infobox.vevent')->each(function (Crawler $node, $i) 
        {
            $node->filter('tr')->each(function (Crawler $node, $i) 
            {
                global $movies_list;
                global $movie;
                global $field;
                global $info;
                global $info1;
                $node->filter('th')->each(function ($node) {
                                 global $field;
                                 $field=trim($node->text());
                            });
                $node->filter('td')->each(function ($node) 
                {
                     global $info;
                     global $field;
                     global $info1;
                     $filter=$node->filter('a');
                     if (iterator_count($filter) > 1) 
                     {
                        // iterate over filter results
                        foreach ($filter as $i => $content) {
                        // create crawler instance for result
                        $crawler = new Crawler($content);
                        // extract the values needed
                        $info1[$i] = $crawler->filter('a')->text();
                        $info="";
            
                    }
                    } else {
                        $info=trim($node->text());
                    }
                    
                });
                $node->filter('img')->each(function ($node) {
                     global $info;
                     global $field;
                     $field="poster";
                     $info=trim($node->attr('src'));
                });
                
                if ($field=="Directed by") {
                    $movies_list[$movie]["director"]= empty($info) ? $info1 : $info;
                }elseif ($field=="Produced by") {
                    $movies_list[$movie]["producer"]=empty($info) ? $info1 : $info;
                }elseif ($field=="Written by") {
                    $movies_list[$movie]["writer"]=empty($info) ? $info1 : $info;
                }elseif ($field=="Starring") {
                    $movies_list[$movie]["cast"]=empty($info) ? $info1 : $info;
                }elseif ($field=="Music by") {
                    $movies_list[$movie]["music"]=empty($info) ? $info1 : $info;
                }elseif ($field=="Release dates") {
                    $movies_list[$movie]["release"]=empty($info) ? $info1 : $info;
                }elseif ($field=="Language") {
                    $movies_list[$movie]["lang"]=empty($info) ? $info1 : $info;
                }elseif ($field=="poster") {
                    $movies_list[$movie]["poster"]=empty($info) ? $info1 : $info;
                }
            });
            
        });
        $crawler->filter('p:nth-child(2)')->each(function (Crawler $node, $i) {
            global $movies_list;
            global $movie;
            $movies_list[$movie]["synopsis"]=$node->text();
            
        });
        
    });
    
});
print_r($movies_list);
$client = new MongoDB\Client;
$movies_collection = (new MongoDB\Client)->firedb->movies;
$events_collection = (new MongoDB\Client)->firedb->events;
$tokens_collection = (new MongoDB\Client)->firedb->device_tokens;
$current_ts=date("Y/m/d H:i:s");
$type = "new";

foreach($movies_list as $key=>$values)
    {
        $movie_name=$key;
        $present=isPresent($movie_name,$movies_collection);
        if(!$present){
        $result = $movies_collection->insertOne(
            array("lang"=> "Tamil" , "name" => $movie_name,"poster_url"=>$values["poster"], "type" => $type, "id"=>$movie_id,"link"=>$movie_link,"actors"=>$values["cast"],"director"=>$values["director"],"music_director"=>$values["music"],"genre"=>$genre,"producer"=>$values["producer"],"release_ts"=>date("Y/m/d",strtotime($values["release"])),"synopsis"=>$values["synopsis"],"disabled"=>"false","insert_ts" => $current_ts ));
        }
        $movie_id+=1;
    }
$current_ts=date("Y/m/d H:i:s");
echo "Job completed on ".$current_ts."\n";

?>