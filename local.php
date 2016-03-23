<?php

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/src/Browser/Casper.php');
require_once(__DIR__ . '/imdb_get.php');
require('global_function.php');

use Browser\Casper;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

/*$casper = new Casper();

$casper->start('https://in.bookmyshow.com/chennai/movies');
$casper->run();
$html = $casper->getHTML();*/

$client = new MongoDB\Client();
$movies_collection = (new MongoDB\Client)->firedb->movies;
$events_collection = (new MongoDB\Client)->firedb->events;
$tokens_collection = (new MongoDB\Client)->firedb->device_tokens;
$counter_collection = (new MongoDB\Client)->firedb->counter;

date_default_timezone_set("Asia/Calcutta");//Set timezone to India
$current_ts=date("Y/m/d H:i:s");

//echo $html;
//$crawler = new Crawler();
$client = new Client();
$crawler = $client->request('GET', 'https://in.bookmyshow.com/chennai/movies/nowshowing');
//$crawler->addHtmlContent($html);
$upcoming_movies=array();
$i=0;
$j=0;
$crawler->filter('#now-showing > section.now-showing.filter-now-showing > div > div.__col-now-showing')->each(function (Crawler $node, $i) {

    $node->filter('div.detail')->each(function (Crawler $node, $i) {
        global $i,$j;
           $node->filter('div.__name > a')->each(function ($node)
               {
                   global $i;
                   global $upcoming_movies;
                    $upcoming_movies[$i]["name"] = $node->text();
                    
                    $link = $node->attr('href');
                    $upcoming_movies[$i]["link"] = "https://in.bookmyshow.com".$link;
                });
            $node->filter('div.languages > ul > li')->each(function ($node)
               {
                   global $i,$j;
                    global $upcoming_movies;
                    $upcoming_movies[$i]["lang"][$j] = $node->text();
                    $j+=1;
                });
        $i +=1;
        $j=0;
    });
    
});

//print_r($upcoming_movies);

foreach($upcoming_movies as $movies)
{
    $movie_name = $movies["name"];
    $movie_link=$movies["link"];
    $present=isPresent($movies["name"],$movies_collection);
    if($present)
    {
        $current_type=getDetail($movies["name"],$movies_collection,"name","type");
        $prevs_type=getDetail($movies["name"],$movies_collection,"name","prev_type");
		//$upcoming=isUpcoming($movie_name,$movies_collection);
		$type="running";
		if($current_type=="upcoming")
		{
			$result = $movies_collection->updateOne(
			['name' => $movie_name],
			['$set' => array("lang"=>$movies["lang"],"type" => $type, "prev_type" => "upcoming", "update_ts" => $current_ts )],
			['upsert' => false]
			);
			
			$result = $movies_collection->updateOne(
	        		    ['name' => $movie_name],
	        		    ['$addToSet' => array("source"=> array("bms" => array("link"=>$movie_link,"booking_open_ts"=>$current_ts)))],
	        		    ['upsert' => false]);
	        		    
			$events = $events_collection->insertOne(
			array("movie_name"=>$movie_name,"lang"=>$movies["lang"],"event_id"=>getCounter("event_id",$counter_collection),"event_type" => "UR","notify"=> 'true',"insert_ts" => $current_ts ));
		}
		elseif($current_type=="running")
		{
			$result = $movies_collection->updateOne(
				['name' => $movie_name],
				['$set' => array("type" => $type, "update_ts" => $current_ts )],
				['upsert' => false]);
		}
		elseif($current_type=="closed")
		{
			$result = $movies_collection->updateOne(
				['name' => $movie_name],
				['$set' => array( "type" => $type,"prev_type" => "closed", "update_ts" => $current_ts )],
				['upsert' => false]);
		}        
    }else {
        
        $movie_details=get_imdb_det($movies["name"]);
        if(is_array($movie_details)&&(count($movie_details)==1))
        {
            foreach($movie_details as $details)
            {
                $result = $movies_collection->insertOne(
		    	array("lang"=>$movies["lang"] , "name" => $movie_name, "type" => "running", "prev_type"=>"null","det_stat"=>"new", "poster_url"=>$details["poster"],"actors"=>$details["cast"],"director"=>$details["director"],"music_director"=>$details["music"],"genre"=>$details["genre"],"producer"=>$details["producer"],"release_ts"=>$details["release"], "disabled"=>"false", "insert_ts" => $current_ts ));
		    	
		    	$result = $movies_collection->updateOne(
	        		    ['name' => $movie_name],
	        		    ['$addToSet' => array("source"=> array("bms" => array("link"=>$movie_link,"booking_open_ts"=>$current_ts)))],
	        		    ['upsert' => false]);
	            
		    	$events = $events_collection->insertOne(
		    	array("movie_name"=>$movie_name,"event_id"=>getCounter("event_id",$counter_collection),"event_type" => "FR","notify"=> 'true',"insert_ts" => $current_ts ));
	            
            }
        }
        else 
        {
            $result = $movies_collection->insertOne(
		    	array("lang"=>$movies["lang"] , "name" => $movie_name, "type" => "running", "prev_type"=>"null","det_stat"=>"new","disabled"=>"false", "insert_ts" => $current_ts ));
		    	
		    $result = $movies_collection->updateOne(
	        	    ['name' => $movie_name],
	        	    ['$addToSet' => array("source"=> array("bms" => array("link"=>$movie_link,"booking_open_ts"=>$current_ts)))],
	        	    ['upsert' => false]);
	            
		    $events = $events_collection->insertOne(
		    	array("movie_name"=>$movie_name, "lang"=>$movies["lang"], "event_id"=>getCounter("event_id",$counter_collection),"event_type" => "FR","notify"=> 'true',"insert_ts" => $current_ts ));
	            
        }
        
    }
}


?>