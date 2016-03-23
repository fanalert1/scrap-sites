<?php

/*
*This PHP script will scrape the Filmibeat website for movie database.
*/

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/src/Browser/Casper.php');
date_default_timezone_set("Asia/Calcutta");
use Browser\Casper;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

$current_month = date("M");
$date = new DateTime(date("Y/m/d"));
$date->modify('first day of +1 month');
$next_month =  $date->format('M');

$client = new Client();
$crawler = $client->request('GET', 'http://www.filmibeat.com/tamil/upcoming-movies.html');

$movies_list=array();
$movie_key="";


$crawler->filter('#Mar')->each(function (Crawler $node, $i) {
    $node->filter('li')->each(function (Crawler $node, $i) {
        $node->filter('h3.filmibeat-db-upcoming-movie-title > a')->each(function ($node) {
            global $movie_key;
            $movie_key=trim($node->text());
           echo $node->attr('href');
        });
        $node->filter('div.filmibeat-db-nextchange-reldate')->each(function ($node) {
            global $movies_list;
            global $movie_key;
            $release=explode("-",$node->text());
            echo $movie_key."  ".trim($release[1])."\n";
            $movies_list[$movie_key]["release"]=date("Y/m/d H:i:s",strtotime($release[1]));
        });
    });
});

print_r($movies_list);

?>
