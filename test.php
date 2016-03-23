<?php

require_once(__DIR__ . '/vendor/autoload.php');

/*$title = new \Imdb\Title('5440700');
$rating = $title->title();
$plotOutline = $title->plot();

echo $rating.", ".$plotOutline."\n";*/

// include "bootstrap.php"; // Load the class in if you're not using an autoloader
$search = new \Imdb\TitleSearch(); // Optional $config parameter
$results = $search->search('Kadhal 2016', [\Imdb\TitleSearch::MOVIE]); // Optional second parameter restricts types returned

// $results is an array of Title objects
// The objects will have title, year and movietype available
// immediately, but any other data will have to be fetched from IMDb
foreach ($results as $result) { /* @var $result \Imdb\Title */
    //$details=$results->getSearchDetails();
    echo $result->title() . ' (' . $result->year() . ')'.$result->imdbid()."\n";
}
//print_r($results);

?>