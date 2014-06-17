<?php

require_once __DIR__ . '/TwitterOAuth/TwitterOAuth.php';
require_once __DIR__ . '/TwitterOAuth/Exception/TwitterException.php';


use TwitterOAuth\TwitterOAuth;

date_default_timezone_set('UTC');


/**
 * Array with the OAuth tokens provided by Twitter when you create application
 *
 * output_format - Optional - Values: text|json|array|object - Default: object
 */
$config = array(
    'consumer_key' => '2TGxAJ1uzifU3qqEzFIRZIzac',
    'consumer_secret' => 'JWyRA5zEl38pAWQBYG0LgoNbWuo2606ExqbpPH4KdmCZWWg1S1',
    'oauth_token' => '38855429-1azSD8OtyHTDKBgz2lj6T9E62nWnbPKpom2kXXYhj',
    'oauth_token_secret' => 'qDPU4JhAr0q76c48OIm5iTlUipojRwlutEHN5JVSqsjK5',
    'output_format' => 'object'
);

/**
 * Instantiate TwitterOAuth class with set tokens
 */
$tw = new TwitterOAuth($config);


/**
 * Returns a collection of the most recent Tweets posted by the user
 * https://dev.twitter.com/docs/api/1.1/get/statuses/user_timeline
 */
$params = array(
    'q' => 'http://unicmedia.nl',
    'count' => 50,
    'exclude_replies' => false
);

/**
 * Send a GET call with set parameters
 */
$q = "http://unicmedia.nl";
$response = $tw->get('search/tweets', $params);

//var_dump($response);

foreach($response->statuses as $status){
   // var_dump($status);

    $user = $status->user;

  var_dump($user);

    $img = $user->profile_image_url;

    echo "<img src='$img' />";

    $created = $status->created_at;
  #do stuff
}
echo "<hr />";
echo $created;


