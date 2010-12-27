<?php
/**
 * @file
 * User has successfully authenticated with Twitter. Access tokens saved to session and DB.
 */

/* Load required lib files. */
session_start();
require_once('twitteroauth/twitteroauth.php');
require_once('config.ini.php');
require_once('common_helper.php');

/* If access tokens are not available redirect to connect page. */
if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret'])) {
    header('Location: ./clearsessions.php');
}
/* Get user access tokens out of the session. */
$access_token = $_SESSION['access_token'];

/* Create a TwitterOauth object with consumer/user tokens. */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

/* If method is set change API call made. Test is called by default. */
$user_data = $connection->get('account/verify_credentials');

/* Some example calls */
//$connection->get('users/show', array('screen_name' => 'abraham')));
//$connection->post('statuses/update', array('status' => date(DATE_RFC822)));
//$connection->post('statuses/destroy', array('id' => 5437877770));
//$connection->post('friendships/create', array('id' => 9436992)));
//$connection->post('friendships/destroy', array('id' => 9436992)));

$timeline_data = $connection->get('statuses/friends_timeline', array('count' => '190'));


$friend_data = array();
foreach ($timeline_data as $tweet_obj) {

	$friend_id = $tweet_obj->user->id;
	//print($friend_id . "/");
	$is_reply = $tweet_obj->in_reply_to_status_id || $tweet_obj->in_reply_to_screen_name || 
				$tweet_obj->in_reply_to_status_id_str || $tweet_obj->in_reply_to_user_id ||
				$tweet_obj->in_reply_to_user_id_str;
	$is_retweet = ($tweet_obj->retweet_count > 0);

	if (!isset($friend_data[$friend_id]["tweet_count"])) {
		$friend_data[$friend_id] = array("tweet_count" => 1,
										"reply_count" => ($is_reply ? 1 : 0),
										"retweet_count" => ($is_retweet ? 1 : 0),
										"screen_name" => $tweet_obj->user->screen_name, 
										"name" => $tweet_obj->user->name,
										"location" => $tweet_obj->user->location,
										"follows_you" => ($tweet_obj->user->following==1 ? 'yes' : 'no'),
										"profile_image_url" => $tweet_obj->user->profile_image_url); 
	} else {
		$friend_data[$friend_id]["tweet_count"] = $friend_data[$friend_id]["tweet_count"] + 1;
		$friend_data[$friend_id]["reply_count"] = $friend_data[$friend_id]["reply_count"] + ($is_reply ? 1 : 0);
		$friend_data[$friend_id]["retweet_count"] = $friend_data[$friend_id]["retweet_count"] + ($is_retweet ? 1 : 0);
	}
	$last_date = $tweet_obj->created_at;
}

// $date_diff
$date_diff = time_diff_rel(strtotime($last_date));

// Sort friend_data by field "tweet_count"
uasort($friend_data,
   create_function(
      '$a, $b',
      'return ( ($a["tweet_count"] < $b["tweet_count"]) ? 1 : ($a["tweet_count"] == $b["tweet_count"] ? 0 : -1) );' ) ); 

//print_r($friend_data);
//print_r($timeline_data);


/* Include HTML to display on the page */
include('templates/index.html');
