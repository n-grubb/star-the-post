<?php

function daily_share_count_update() {
  // daily cron will process the last 50 posts (sorted by post ID)
  get_share_count('daily', 50);
}
add_action( 'daily_share_count',  'daily_share_count_update' );

function hourly_share_count_update() {
  // this needs to loop through 7500 posts!
  // split it up to batches of 100 every hour, sorted by time since last updated
  get_share_count('hourly', 100);
}
add_action( 'hourly_share_count',  'hourly_share_count_update' );

function recent_post_updates() {
  get_share_count('recent', 5);
}
add_action( 'hourly_share_count',  'recent_post_updates' );

function get_share_count($type, $limit) {

  write_log('Running '.$type.' share count update. Limit: '.$limit);
  $time_start = microtime(true);

  set_time_limit(0);
  ignore_user_abort();

  global $wpdb;
  $stp_table  = $wpdb->prefix . 'starred_posts';

  if ( $type == 'daily' ) {
    $posts = $wpdb->get_results( "SELECT * FROM $stp_table ORDER BY post_id DESC" );
  }
  elseif ( $type == 'recent' ) {
    $args = array( 
      'post_type'      => 'post',
      'orderby'        => 'date', 
      'order'          => 'DESC',
      'posts_per_page' => 5, 
    );
    $recent_posts = get_posts( $args );
    $search_criteria = [];
    foreach( $recent_posts as $rp ) {
      $search_criteria[] = $rp->ID;
    }
    $posts = $wpdb->get_results( "SELECT * FROM $stp_table where post_id IN (".implode(',', $search_criteria).")" );
  }
  else {
    $posts = $wpdb->get_results( "SELECT * FROM $stp_table ORDER BY updated ASC" );
  }

  $count = 0;
  foreach ( $posts as $post ) {
    
    $post_url = get_permalink($post->post_id);

    // if the post has been or trashed, delete it from stp table.
    $post_obj = get_post($post->post_id);
    if ( $post_obj->post_status == 'trash' || $post_url == false ) {
      $wpdb->delete( $stp_table, [ 'post_id' => $post->post_id ], [ '%d' ] );
      echo "Deleted Post " . $post->post_id . " from stp_table.";
    }
    else {

      $local_url = $post_url;
      // write_log($local_url);

      $blog_slug = 'seths_blog';
      $categories = get_the_category($post->post_id);
    	if ( !empty($categories[0]) && $categories[0]->name !== 'Uncategorized' ) {
    		$blog_slug = $categories[0]->slug;
        $blog_slug = str_replace( '-', '_', $blog_slug);
      }

      $post_url = str_replace( 'local.dev', 'sethgodin.typepad.com/seths_blog', $post_url );
      $post_url = str_replace( 'https://seths.blog', 'http://sethgodin.typepad.com/'.$blog_slug, $post_url );

      if(substr($post_url, -1) == '/') {
        $post_url = substr($post_url, 0, -1);
      }
      $post_url = $post_url . '.html';
      // write_log($post_url);

      $social_shares = 0;
      $social_shares += get_facebook_count($post_url);
      $social_shares += get_twitter_count($post_url);
      $social_shares += get_linkedin_count($post_url);

      // run count for old shares too!
      $social_shares += get_facebook_count($local_url);
      $social_shares += get_twitter_count($local_url);
      $social_shares += get_linkedin_count($local_url);

      // write_log($social_shares . ' total shares for: '.$post_url.' + '.$local_url);

      echo '<p>Post ID: '.$post->post_id.'<br />Typepad link: <a href="'.$post_url.'">'.$post_url.'</a><br/>Social Shares: '.$social_shares.'<br/>Previously Updated: '.$post->updated.'</p>';
      update_share_count( $post, $social_shares );
    }

    $count++;

    if ( $count >= $limit ) {
      break;
    }
  }

  $time_end = microtime(true);
  $execution_time = ($time_end - $time_start)/60;
  echo '<b>Total Execution Time:</b> '.number_format((float)$execution_time, 2, '.', '').' Mins';
  write_log('Finished running '.$type.' update. Execution time: '.number_format((float)$execution_time, 2, '.', '') );

}


add_action( 'init', function() {
  if ( !isset( $_GET['cron_test'] ) ) {
    return;
  }
  do_action( 'daily_share_count' );
  die();
} );


function get_facebook_count( $post_url ) {
  $app_id = '775096196029489';
  $app_secret = '24354dc7bb864d3b91cc713bd4fd3a7d';
  $access_token = $app_id.'|'.$app_secret;

	$api_url = 'https://graph.facebook.com/?fields=engagement&id=' . urlencode( $post_url ) . '&access_token=' . $access_token;
	$fb_connect = curl_init(); // initializing
	curl_setopt( $fb_connect, CURLOPT_URL, $api_url );
	curl_setopt( $fb_connect, CURLOPT_RETURNTRANSFER, 1 ); // return the result, do not print
	curl_setopt( $fb_connect, CURLOPT_TIMEOUT, 20 );
	$result = curl_exec( $fb_connect ); // connect and get json data
	curl_close( $fb_connect ); // close connection

  $fb_shares = 0;
  $response_json = json_decode($result);
  if ( !empty($response_json->engagement) ) {
    $fb_shares += $response_json->engagement->reaction_count;
    $fb_shares += $response_json->engagement->comment_count;
    $fb_shares += $response_json->engagement->share_count;
  }
  // write_log('Facebook shares: ' . $fb_shares);
  return $fb_shares;

}

function get_twitter_count( $post_url ) {

  $request_url    = 'http://opensharecount.com/count.json?url='. $post_url;

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_URL, $request_url);
  $result = curl_exec($ch);
  curl_close($ch);

  $response_json = json_decode($result);
  $twitter_shares = $response_json->count;
  // write_log('Twitter shares: ' . $twitter_shares);
  return $twitter_shares;

}

function get_linkedin_count( $post_url ) {

  $request_url    = 'https://www.linkedin.com/countserv/count/share?url=' . $post_url . '&format=json';

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_URL, $request_url);
  $result = curl_exec($ch);
  curl_close($ch);

  $response_json = json_decode($result);
  $li_shares = $response_json->count;
  // write_log('LinkedIn shares: ' . $li_shares);
  return $li_shares;

}

function update_share_count( $post, $social_shares ) {

  if ( empty($post->post_id) ) { return 'Post object does not exist.'; }

  global $wpdb;
  $stp_table  = $wpdb->prefix . 'starred_posts';

  $data = array();
  $data['shares']   = $social_shares;
  $data['total']    = stp_calculate_total( $social_shares, $post->clicks, $post->views, $post->copies );
  $data['updated']  = date("Y-m-d H:i:s");
  $format = array( '%d', '%d', '%s' );
  $wpdb->update( $stp_table, $data, array('post_id' => $post->post_id), $format );

}


if ( ! function_exists('write_log')) {
   function write_log ( $log )  {
      if ( is_array( $log ) || is_object( $log ) ) {
         error_log( print_r( $log, true ) );
      } else {
         error_log( $log );
      }
   }
}
