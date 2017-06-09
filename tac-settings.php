<div class="wrap">
	<?php 

	echo "<h2>" . __( 'Tweets As Comments | Settings', 'tac_text' ) . "</h2>";
	global $wpdb;
	if( isset( $_POST[ 'new_hidden' ] ) && $_POST[ 'new_hidden' ] == "Y" )
	{
		$excludereplies = $_POST['excludereplies'];

		$exclude_replies = false;
		if($excludereplies == 1) {
			$exclude_replies = true;
		} else if($excludereplies == 0) {
			$exclude_replies = false;
		}

		$optionsArr = [
			'consumerkey' 		=> $_POST[ 'consumerkey' ],
			'consumersecret' 	=> $_POST[ 'consumersecret' ],
			'oauthtoken'		=> $_POST[ 'oauthtoken' ],
			'oauthsecret'		=> $_POST[ 'oauthsecret' ],
			'secure'			=> $_POST[ 'ssl' ],
			'handle'			=> $_POST[ 'handle' ],
			'maxposts'			=> $_POST[ 'maxposts' ],
			'maxtweets'			=> $_POST[ 'maxtweets' ],
			'excludereplies'	=> $exclude_replies,
		];

		update_option( 'tac' , $optionsArr );
		echo "<strong>Updated settings.</strong><br />";
	}

	$settings = get_option( 'tac' );
	?>
	<form name="settings" method="post" action="">
		<input type="hidden" name="new_hidden" value="Y">

		<?php _e("<p>Twitter Consumer Key </p>" ); ?>
		<input type="text" style="font-size: 20px;" value="<? echo $settings[ 'consumerkey' ]; ?>" name="consumerkey" size="50" required>

	 	<?php _e("<p>Twitter Consumer Secret </p>" ); ?>
	 	<input type="text" style="font-size: 20px;" value="<? echo $settings[ 'consumersecret' ]; ?>" name="consumersecret" size="50" required>

	 	<?php _e("<p>Twitter OAuth Token </p>" ); ?>
	 	<input type="text" style="font-size: 20px;" value="<? echo $settings[ 'oauthtoken' ]; ?>" name="oauthtoken" size="50" required>

	 	<?php _e("<p>Twitter OAuth Secret </p>" ); ?>
	 	<input type="text" style="font-size: 20px;" value="<? echo $settings[ 'oauthsecret' ]; ?>" name="oauthsecret" size="50" required>

	 	<?php _e("<p>Are you using SSL? </p>"); ?>
	 	<select name="ssl">
	 		<option <? if($settings[ 'secure' ] == "Yes") { echo "selected='SELECTED'"; } ?>>Yes</option>
	 		<option <? if($settings[ 'secure' ] == "No") { echo "selected='SELECTED'"; } ?>>No</option>
	 	</select>

	 	<?php _e("<p>If you want to exclude your own tweets from being entered into the comments, fill in your twitter handle without the @ sign</p>" ); ?>
	 	<input type="text" style="font-size: 20px;" value="<? echo $settings[ 'handle' ]; ?>" name="handle" size="50">

	 	<?php _e("<p><strong>Advanced Settings</strong></p>");?>

	 	<?php _e("<p>For how many posts do you want to check the twitter mentions? The lower the number, the faster the process and the lower the load on your server. 0 - 30 is recommended.</p>" ); ?>
	 	<input type="text" style="font-size: 20px;" value="<? echo $settings[ 'maxposts' ]; ?>" name="maxposts" size="50">

	 	<?php _e("<p>How many tweets do you want to check? If you normally get 10 - 50 twitter mentions, fill in 60. The lower this number is the faster the process will be. </p> "); ?>
	 	<input type="text" style="font-size: 20px;" value="<? echo $settings[ 'maxtweets' ]; ?>" name="maxtweets" size="50">

	 	<?php _e("<p>Do you want to exclude replies? (if a tweet is a reply to someone else and the link is mentioned than you can exclude this tweet as comment. In the future, maybe I can add a feature 
	 	where the plugin adds it as a subcomment on the 'parent' tweet if applicable, but for now this is not possbile."); ?>
	 	<select name="excludereplies">
	 		<option <? if($settings[ 'excludereplies' ] == "1" || $settings[ 'excludereplies' ] == 1) { echo "selected='SELECTED'"; } ?>>Yes</option>
	 		<option <? if($settings[ 'excludereplies' ] == "0" || $settings[ 'excludereplies' ] == 0) { echo "selected='SELECTED'"; } ?>>No</option>
	 	</select>

	    <p class="submit">
			<input class="button-primary" type="submit" name="Submit" value="<?php _e('Save', 'tac_text' ) ?>" />
		</p>
	</form>
</div>