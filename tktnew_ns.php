<?php

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/imdb_get.php');
require('global_function.php');

date_default_timezone_set("Asia/Calcutta");//Set timezone to India
$current_ts=date("Y/m/d H:i:s");
echo "Job Started on ".$current_ts."\n";

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

$mongo_client = new MongoDB\Client();
$movies_collection = $mongo_client->firedb->movies;
$events_collection = $mongo_client->firedb->events;
$tokens_collection = $mongo_client->firedb->device_tokens;
$counter_collection = $mongo_client->firedb->counter;

$tktnew_url='http://www.ticketnew.com/Movie-Ticket-Online-booking/C/Chennai';
$$running_movies_links = array();
$running_movies_list = array();
$key="";
if(linkcheck($tktnew_url))
{
    $client = new Client();
    $crawler = $client->request('GET', 'http://www.ticketnew.com/Movie-Ticket-Online-booking/C/Chennai');
    $crawler->filter('div[id$="overlay-tab-booking-open"]')->each(function (Crawler $node, $i) {

        $node->filter('div[class$="titled-cornered-block"]')->each(function (Crawler $node, $i) {

            $node->filter('h3,li')->each(function ($node) {

                $content = $node->text();
                $item = trim($content);
                global $key;
                global $running_movies_list;
                if ($item=="Tamil")
                {
                   $key="Tamil";
                }
                else if ($item=="Tamil 3D")
                {
                   $key="Tamil";
                }
                else if($item=="English")
                {
                    $key="English";
                }
                else if($item=="English 2D")
                {
                    $key="English";
                }
                else if($item=="English 3D")
                {
                    $key="English";
                }
                else if($item=="Hindi")
                {
                    $key="Hindi";
                }
                else if($item=="Telugu")
                {
                    $key="Telugu";
                }
                else if($item=="Malayalam")
                {
                    $key="Malayalam";
                }
                else
                {
                    if(!in_array($item, $running_movies_list[$key])) //fix for child div issue for eng 2d 3d section
                        {
                            $running_movies_list[$key][] = $item;
                            $node->filter('a')->each(function (Crawler $node){ //fix for link order issue - tamil and english links clubbed for tamil dubbed english movie
                                global $running_movies_links;
                                $link = $node->link();
                                $uri = $link->getUri();
                                global $key;
                                $running_movies_links[$key][] = $uri;
                            });
                        }
                }
            });
        });
    });

    foreach($running_movies_list as $key=>$values)
    {
        $lang=$key; //sets language as key of the array
        foreach ($values as $key => $value)
        {
            $movie_name=$value; // sets movie name
            //$temp_name=str_replace(" ","-",$movie_name); //temporary variable to get the link of the movie from the array
            $movie_link=$running_movies_links[$lang][$key];
            $present=isPresent($movie_name,$movies_collection);
            if($present)
            {
                $current_type=getDetail($movie_name,$movies_collection,"name","type");
                $prevs_type=getDetail($movie_name,$movies_collection,"name","prev_type");
	        	//$upcoming=isUpcoming($movie_name,$movies_collection);
	        	$type="running";
	        	if($current_type=="upcoming")
	        	{
	        		$result = $movies_collection->updateOne(
	        		['name' => $movie_name],
	        		['$set' => array("type" => $type, "prev_type" => "upcoming", "update_ts" => $current_ts )],
	        		['upsert' => false]
	        		);
	        		$result = $movies_collection->updateOne(
	        		['name' => $movie_name],
	        		['$addToSet' => array("source"=> array("tktnew" => array("link"=>$movie_link,"booking_open_ts"=>$current_ts)))],
	        		['upsert' => false]
	        		);
	        		$events = $events_collection->insertOne(
	        		array("movie_name"=>$movie_name,"lang"=>$lang,"event_id"=>getCounter("event_id",$counter_collection),"event_type" => "UR","notify"=> 'true',"insert_ts" => $current_ts ));
	        	}
	        	elseif($current_type=="running")
	        	{
	        		$result = $movies_collection->updateOne(
	        			['name' => $movie_name],
	        			['$set' => array("type" => $type, "update_ts" => $current_ts )],
	        			['upsert' => false]);
	        			
	        		$result = $movies_collection->updateOne(
	        		    ['name' => $movie_name],
	        		    ['$addToSet' => array("source"=> array("bms" => array("link"=>$movie_link,"booking_open_ts"=>$current_ts)))],
	        		    ['upsert' => false]);
	        	}
	        	elseif($current_type=="closed")
	        	{
	        		$result = $movies_collection->updateOne(
	        			['name' => $movie_name],
	        			['$set' => array( "type" => $type,"prev_type" => "closed", "update_ts" => $current_ts )],
	        			['upsert' => false]);
	        		$result = $movies_collection->updateOne(
	        		    ['name' => $movie_name],
	        		    ['$addToSet' => array("source"=> array("bms" => array("link"=>$movie_link,"booking_open_ts"=>$current_ts)))],
	        		    ['upsert' => false]);
	        	}        
            }else {
                
                $movie_details=get_imdb_det($movie_name);
                if(is_array($movie_details)&&(count($movie_details)==1))
                {
                    foreach($movie_details as $details)
                    {
                        $result = $movies_collection->insertOne(
	        	    	array("name" => $movie_name, "type" => "running", "prev_type"=>"null","det_stat"=>"new","poster_url"=>$details["poster"],"actors"=>$details["cast"],"director"=>$details["director"],"music_director"=>$details["music"],"genre"=>$details["genre"],"producer"=>$details["producer"],"release_ts"=>$details["release"], "disabled"=>"false", "insert_ts" => $current_ts ));
	        	    	
	        	    	$result = $movies_collection->updateOne(
	        		        ['name' => $movie_name],
	        		        ['$addToSet' => array("source"=> array("tktnew" => array("link"=>$movie_link,"booking_open_ts"=>$current_ts)))],
	        		        ['upsert' => false]);
	                    
	        	    	$events = $events_collection->insertOne(
	        	    	array("movie_name"=>$movie_name,"event_id"=>getCounter("event_id",$counter_collection),"event_type" => "FR","notify"=> 'true',"insert_ts" => $current_ts ));
	                    
                    }
                }
                else 
                {
                    $result = $movies_collection->insertOne(
	        	    array("name" => $movie_name, "type" => "running", "prev_type"=>"null","det_stat"=>"new","disabled"=>"false", "insert_ts" => $current_ts ));
	        	    
	        	    $result = $movies_collection->updateOne(
	        		    ['name' => $movie_name],
	        		    ['$addToSet' => array("source"=> array("tktnew" => array("link"=>$movie_link,"booking_open_ts"=>$current_ts)))],
	        		    ['upsert' => false]);
	                    
	        	    $events = $events_collection->insertOne(
	        	    array("movie_name"=>$movie_name, "lang"=>$movies["lang"], "event_id"=>getCounter("event_id",$counter_collection),"event_type" => "FR","notify"=> 'true',"insert_ts" => $current_ts ));
                }
            }
        }
    }
}

$current_ts=date("Y/m/d H:i:s");
echo "Job completed on ".$current_ts."\n";

?>