<?php

/*
*This PHP script will scrape the MovieCrow website for movie database.
*/

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/src/Browser/Casper.php');

use Browser\Casper;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

$client = new Client();
$crawler = $client->request('GET', 'http://www.moviecrow.com/tamil/new-movies');

$movies_list=array();
$movie_key="";

$crawler->filter('#rank-tab-10')->each(function (Crawler $node, $i) {
             
            $node->filter('div.releasing_in')->each(function (Crawler $node, $i) {
                 
                        $node->filter('span.movieTitle')->each(function ($node) {
                             global $movie_key;
                             $movie_key=$node->text();
                             //echo $movie_key."\n";
                        });
                        $node->filter('span.movieDirector')->each(function ($node) {
                             global $movies_list;
                             global $movie_key;
                             $movies_list[$movie_key]["director"]=$node->text();
                        });
                        $node->filter('span.movieCast')->each(function ($node) {
                             global $movies_list;
                             global $movie_key;
                             $movies_list[$movie_key]["cast"]=$node->text();
                        });
                        $node->filter('span.movieMusicDirector')->each(function ($node) {
                             global $movies_list;
                             global $movie_key;
                             $movies_list[$movie_key]["music"]=$node->text();
                        });
                        $node->filter('span.movieImageLink')->each(function ($node) {
                             global $movies_list;
                             global $movie_key;
                             $movies_list[$movie_key]["link"]=$node->text();
                        });
                        $node->filter('a.btn-play-t')->each(function ($node) {
                             global $movies_list;
                             global $movie_key;
                             global $client;
                             $link = $node->attr('href');
                             $crawler = $client->request('GET', $link);
                             $crawler->filter('iframe')->each(function ($node) {
                                global $movies_list;
                                global $movie_key;
                                $movies_list[$movie_key]["trailer"]=$node->attr('src');
                             });
                        });
                         
             
                         
             });
});

print_r($movies_list);

?>
