<?php
/**
 * Plugin Name: Tweets as Comments
 * Plugin URI: https://tweetsascomments.com
 * Description: Add incoming tweets as comments on posts
 * Version: 1.0
 * Author: Andre Woons
 * Author URI: http://andrewoons.com
 * License: GNU General Public License v3.0
 */

require "twitteroauth/autoload.php";
use Abraham\TwitterOAuth\TwitterOAuth;

if ( !class_exists( 'Tweets_As_Comments' ) ) {
	class Tweets_As_Comments {

		/**
		Function that registers the options
		*/
		public static function install() {
			global $wpdb;
			
			$optionsArr = [
				'consumerkey' 		=> '',
				'consumersecret' 	=> '',
				'oauthtoken'		=> '',
				'oauthsecret'		=> '',
				'secure'			=> 'No',
				'handle'			=> '',
				'maxposts'			=> '20',
				'maxtweets'			=> '30',
				'excludereplies'	=> '0',
			];

			$optionsExists = get_option( 'tac' );
			if ( !$optionsExists ) {
				add_option( 'tac' , $optionsArr);
			}
		}

		/**
		Function that registers the action links
		**/
		public static function action_links( $links ) {
			$settings = '<a href="options-general.php?page=tweets-as-comments">' . __( 'Settings' ) . '</a>';
			$setup = '<a href="https://tweetsascomments.com/setup-instructions/">' . __( 'Setup Instructions' ) . '</a>';
    		array_push( $links, $settings, $setup );
    		return $links;
		}

		/**
		Function to unregister the cron job when we deactivate the plugin
		*/
		public static function deactivate() {
			wp_clear_scheduled_hook( 'tac_hourly_check' );
		}

		/**
		Function that registers the settings page
		*/
		public static function register_settings_page() {
			add_options_page( 'Tweet Comments - Settings', 'Tweets As Comments', 'administrator', 'tweets-as-comments', array ( 'Tweets_As_Comments', 'show_settings_page' ));
		}

		/**
		Function that shows the settings page
		*/
		public static function show_settings_page() {
			global $wpdb;
			include("tac-settings.php");
		}

		/**
		Function that registers the cron job
		*/
		public static function schedule_cron() {
			if ( wp_next_scheduled( 'tac_hourly_check' ) )
				return;

			wp_schedule_event( time(), 'hourly', 'tac_hourly_check' );
		}

		/**
		Function that checks for tweets and inserts them as comments if needed
		*/
		public static function check_tweets() {
			global $wpdb;
			$secure = "";
			
			$settings = get_option( 'tac' );
			if ( empty( $settings[ 'consumerkey' ] ) || empty( $settings[ 'consumersecret' ] ) || empty( $settings[ 'oauthtoken' ] ) || empty( $settings[ 'oauthsecret' ] ) ) {
				return;
			}

			$secure = $settings[ 'secure' ];
			$handle = $settings[ 'handle' ];
			$maxposts = $settings[ 'maxposts' ];
			$maxtweets = $settings[ 'maxtweets' ];
			$exclude = $settings[ 'excludereplies' ];

			$excludereplies = false;
			if ( $exclude == 1 ) {
				$excludereplies = true;
			}

			/* Initiate Twitter by authenticating */
			$twitter = new TwitterOAuth( $settings[ 'consumerkey' ], $settings[ 'consumersecret' ], $settings[ 'oauthtoken' ], $settings[ 'oauthsecret' ] );
			global $post;

			$args = array(
				'post_type' => 'post'
			);

			$the_query = new WP_Query( 'posts_per_page='. $maxposts . '' );
			while( $the_query -> have_posts() ) : $the_query -> the_post();
				$post_id = get_the_ID();
				$permalink = get_permalink( $post_id );
				$last_checked_id = get_post_meta ( $post_id, 'tac_last_id', true );

				if ($last_checked_id == "") {
					$last_checked_id = 0;
				}

				if ( isset( $permalink ) && !empty( $permalink ) ) {
					$parameters = array(
						'q'					=> $permalink,
						'count'				=> $maxtweets,
						'exclude_replies'	=> $excludereplies,
						'since_id'			=> $last_checked_id
					);
				}

				$response = $twitter->get('search/tweets', $parameters);
				$maxid = 0;

				foreach( $response->statuses as $status ) {
					$name = $status->user->name;
					$screenname = $status->user->screen_name;

					if($screenname !== $handle) {
						$img = $status->user->profile_image_url;

						/* Save the profile image */
						// TODO: random name AND check if image is already saved
						// $imageSaved = wp_upload_bits( $screenname . ".jpg", null, file_get_contents( $img ) );
						// $imageSavedUrl = $imageSaved['url'];

						$tweetid = $status->id_str;
						$createdat = $status->created_at;

						/* Update maxId if current tweetId is larger */
						if ($tweetid > $maxid) {
							$maxid = $tweetid;
						}

						/* Set author name */
						if ( isset( $name ) && !empty( $name ) ) {
							$author = "$name ($screenname)";
						} else {
							$author = $screenname;
						}

						$gmtdate = get_date_from_gmt( date( 'Y-m-d H:i:s', strtotime( $createdat ) ) );

						/* Set commentdata and insert the comment */
						$commentdata = array(
							'comment_post_ID'		=> $post_id,
							'comment_author'		=> $author,
							'comment_author_email'	=> $screenname . '@twitter.com',
							'comment_author_url' 	=> 'https://twitter.com/' . $screenname . '/status/' . $tweetid . '/',
							'comment_content'		=> $status->text,
							'comment_date_gmt'		=> $gmtdate,
							'comment_type'			=> 'comment'
						);

						$commentdata[ 'comment_post_ID' ] = (int) $commentdata[ 'comment_post_ID' ];
						if ( isset( $commentdata[ 'user_ID' ] ) )
							$commentdata[ 'user_id' ] = $commentdata[ 'user_ID' ] = (int) $commentdata[ 'user_ID' ];
						elseif ( isset( $commentdata[ 'user_id' ] ) )
							$commentdata[ 'user_id' ] = (int) $commentdata[ 'user_id' ]; 

						$commentdata[ 'comment_parent' ] = isset( $commentdata[ 'comment_parent'] ) ? absint( $commentdata[ 'comment_parent'] ) : 0;
						$parent_status = (0 < $commentdata[ 'comment_parent']) ? wp_get_comment_status( $commentdata[ 'comment_parent' ] ) : '';
						$commentdata[ 'comment_parent' ] = ( 'approved' === $parent_status || 'unapproved' === $parent_status ) ? $commentdata[ 'comment_parent' ] : 0;

						$commentdata[ 'comment_author_IP' ] = '';
						$commentdata[ 'comment_agent' ] = 'tac';
						$commentdata[ 'comment_date' ] = $commentdata[ 'comment_date_gmt' ];

						$commentdata = wp_filter_comment( $commentdata );
						$commentdata[ 'comment_approved' ] = wp_allow_comment( $commentdata );

						$comment_ID = wp_insert_comment( $commentdata );
						do_action( 'comment_post' , $comment_ID, $commentdata[ 'comment_approved' ] );

						/* Determine if we need to notify people */
						if ( 'spam' !== $commentdata[ 'comment_approved' ] ) {
							if ( '0' == $commentdata[ 'comment_approved' ] )
								wp_notify_moderator( $comment_ID );

							$post = &get_post( $commentdata[ 'comment_post_ID' ] );

							if ( get_option( 'comments_notify' ) && $commentdata[ 'comment_approved' ] && ( !isset( $commentdata[ 'user_id' ] ) || $post->post_author != $commentdata[ 'user_id'] ) )
								wp_notify_postauthor( $comment_ID, isset( $commentdata[ 'comment_type' ] ) ? $commentdata[ 'comment_type' ] : '' );
						}

						// add_comment_meta( $comment_id, 'tac_url', $imageSavedUrl, false );
						update_post_meta( $post_id, 'tac_last_id', $maxid );
					}
			 	}

			endwhile;

			wp_reset_postdata();
		}

	}

}

/* Register Activation Hook */
register_activation_hook( __FILE__ , array ( 'Tweets_As_Comments', 'install') );

/* Register deactivation hook */
register_deactivation_hook( __FILE__ , array ( 'Tweets_As_Comments', 'deactivate') );

/* Register Settings page */
add_action( 'admin_menu' , array ( 'Tweets_As_Comments', 'register_settings_page' ) );

/* Register info links */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ) , array ( 'Tweets_As_Comments', 'action_links' ) );

/* Register shortcode for manual retrieval of tweets */
add_shortcode( 'tweets_as_comments' , array ( 'Tweets_As_Comments' , 'check_tweets' ) );

/* Register Hourly check */
add_action( 'tac_hourly_check', array ( 'Tweets_As_Comments' , 'check_tweets' ) );

/* Schedule Cron Job */
add_action( 'admin_init' , array ( 'Tweets_As_Comments' , 'schedule_cron' ) );

/* Filter for avatars */
// add_filter( 'get_avatar' , 'filter_avatar' , 10, 2);
?>