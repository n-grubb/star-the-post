<?php
/*
Plugin Name: Star the Post
Description: Add "starring" functionality to the Seth Godin blog
Author: Noah Grubb
Version: 0.0.1
*/

// set version number
global $stp_db_version;
$stp_db_version = '0.1.0';

require_once("social-integration/social-counts.php");
require_once("admin/count.php");

/*
============================================
	Activation / Deactivation Hooks
============================================
*/

/*
 * Actions to perform when the plugin is activated.
 */
function install_stp() {
    stp_add_db_tables(); // add dabatabase tables
    if ( !wp_next_scheduled('daily_share_count') ) {
      wp_schedule_event( time(), 'daily', 'daily_share_count');
      wp_schedule_event( time(), 'hourly', 'hourly_share_count');
    }
}
register_activation_hook(__FILE__, 'install_stp');

/*
 * Actions to perform on plugin deactivation.
 */
function uninstall_stp() {
  $timestamp = wp_next_scheduled( 'get_share_count' );
  wp_unschedule_event( $timestamp, 'daily', 'daily_share_count' );
  wp_unschedule_event( $timestamp, 'hourly', 'hourly_share_count');
}
register_deactivation_hook(__FILE__, 'uninstall_stp');

/*
 * Check if our custom db tables are up to date
 */
function stp_update_db_check() {
    global $stp_db_version;
    if ( get_site_option('stp_db_version') != $stp_db_version ) {
        stp_add_db_tables();
    }
    if ( !wp_next_scheduled('daily_share_count') ) {
      wp_schedule_event( time(), 'daily', 'daily_share_count');
    }
    if ( !wp_next_scheduled('hourly_share_count') ) {
      wp_schedule_event( time(), 'hourly', 'hourly_share_count');
    }

}
add_action( 'plugins_loaded', 'stp_update_db_check' );

/*
 * Add our custom DB tables.
 */
function stp_add_db_tables() {
	global $wpdb, $stp_db_version;
	$installed_ver = get_option( "stp_db_version" );

  if ( !$installed_ver ) {
    $installed_ver = '0.0.0';
  }

	if ( $installed_ver != $stp_db_version ) {

    $stp_table_name  = $wpdb->prefix . 'starred_posts';
		$charset_collate  = $wpdb->get_charset_collate();

    $stp_sql = "CREATE TABLE $stp_table_name (
			post_id mediumint(9) NOT NULL,
      views mediumint(9) NOT NULL DEFAULT '0',
      shares mediumint(9) NOT NULL DEFAULT '0',
      clicks mediumint(9) NOT NULL DEFAULT '0',
      copies mediumint(9) NOT NULL DEFAULT '0',
      total mediumint(9) NOT NULL DEFAULT '0',
      updated datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (post_id)
		) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $stp_sql );
    update_option( 'stp_db_version', $stp_db_version );

  }
}


/*
============================================
	Register Assets
============================================
*/

function stp_enqueue_assets() {
    wp_register_style( 'star-the-post', plugins_url( 'assets/css/stp.css', __FILE__ ), array(), '0.0.5' );
    wp_enqueue_style( 'star-the-post' );

    if( ! is_admin() )
    {
      wp_enqueue_script( 'heartbeat' );
      wp_enqueue_script( 'svg-replace', plugins_url( 'assets/js/svg-replace.js', __FILE__ ), array('jquery')  );
      wp_enqueue_script( 'count-up', plugins_url( 'assets/js/countUp.js', __FILE__ ), array('jquery')  );
      wp_enqueue_script( 'count-up-jquery', plugins_url( 'assets/js/countUp-jquery.js', __FILE__ ), array('jquery', 'count-up')  );
      wp_enqueue_script( 'star-the-post-js', plugins_url( 'assets/js/stp.js', __FILE__ ), array('jquery', 'svg-replace', 'heartbeat', 'count-up-jquery'), '1.02'  );
      wp_localize_script( 'star-the-post-js', 'STP', array('ajaxurl' => admin_url( 'admin-ajax.php' ), 'siteurl' => site_url() ) );
    }

}
add_action( 'wp_enqueue_scripts', 'stp_enqueue_assets' );


/*
============================================
	Read / Write Actions
============================================
*/

// should views only increase once per session ?
function stp_track_action( $post_id, $action ) {

  global $wpdb;
  $stp_table  = $wpdb->prefix . 'starred_posts';

  if ( empty($post_id) )
    return;

  if ( empty($action) )
    $action = 'view';

  $post_row = $wpdb->get_row( "SELECT * FROM $stp_table WHERE post_id = $post_id" );
  if ( !$post_row ) {
    switch( $action ) {
      case 'share':
        $data = array(
          'post_id' => $post_id,
          'views'   => 0,
          'shares'  => 0,
          'clicks'  => 1,
          'copies'  => 0,
          'total'   => 1,
        );
        break;
      case 'click':
        $data = array(
          'post_id' => $post_id,
          'views'   => 0,
          'shares'  => 1,
          'clicks'  => 0,
          'copies'  => 0,
          'total'   => 7,
        );
        break;
      case 'copy':
        $data = array(
          'post_id' => $post_id,
          'views'   => 0,
          'shares'  => 0,
          'clicks'  => 0,
          'copies'  => 1,
          'total'   => 7,
        );
        break;
      case 'view':
      default:
        $data = array(
          'post_id' => $post_id,
          'views'   => 1,
          'shares'  => 0,
          'clicks'  => 0,
          'copies'  => 0,
          'total'   => 0,
        );
    }
    $format = array( '%d', '%d', '%d', '%d', '%d' );
    $wpdb->insert( $stp_table, $data, $format);
  }
  else {
      $data = array();
      $shares = $post_row->shares;
      $clicks = $post_row->clicks;
      $views  = $post_row->views;
      $copies = $post_row->copies;
      switch( $action ) {
        case 'share':
          $shares += 1;
          $data['shares'] = $shares;
          break;
        case 'click':
          $clicks += 1;
          $data['clicks'] = $clicks;
          break;
        case 'copy':
          $copies += 1;
          $data['copies'] = $copies;
          break;
        case 'view':
        default:
          $views += 1;
          $data['views'] = $views;
      }
      $data['total'] = stp_calculate_total( $shares, $clicks, $views, $copies );
      $format = array( '%d', '%d' );
      $wpdb->update( $stp_table, $data, array('post_id' => $post_id), $format );
  }

}


function stp_display_count( $post_id ) {

  global $wpdb;
  $stp_table  = $wpdb->prefix . 'starred_posts';

  if ( empty($post_id) )
    return;

  $post_row = $wpdb->get_row( "SELECT * FROM $stp_table WHERE post_id = $post_id" );
  if ( empty($post_row->total) ) {
    return 0;
  }
  else {
    return $post_row->total;
  }

}


/*
============================================
	Utility Functions
============================================
*/

function stp_calculate_total( $shares = 0, $clicks = 0, $views = 0, $copies = 0 ) {

  /*
  FORMULA:
  Each click = 1 point
  Each view = 1/10 point
  Each social share (twitter, facebook, linkedin) = 7 points
  Each copy URL counts as a social share = 7 points
  */

  $total = 0;
  $total += $clicks;
  $total += ( $views / 10 );
  $total += ( $shares * 7 );
  $total += ( $copies * 7 );
  return round($total);

}



/*
============================================
	AJAX Functions
============================================
*/

add_action( 'wp_ajax_nopriv_star_click', 'star_click' );
add_action( 'wp_ajax_star_click', 'star_click' );
function star_click() {
  if ( !empty($_POST['post_id']) ) {
    $post_id = $_POST['post_id'];
    stp_track_action( $post_id, 'click' );
    echo stp_display_count($post_id);
  }
  wp_die();
}


add_action( 'wp_ajax_nopriv_url_copied', 'url_copied' );
add_action( 'wp_ajax_url_copied', 'url_copied' );
function url_copied() {
  if ( !empty($_POST['post_id']) ) {
    $post_id = $_POST['post_id'];
    stp_track_action( $post_id, 'copy' );
    echo stp_display_count($post_id);
  }
  wp_die();
}


/*
============================================
	Heartbeat Functions
============================================
*/

function stp_heartbeat_settings( $settings ) {
  if( !is_admin() )
  {
    $settings['interval'] = 15; //Anything between 15-60
  }
  return $settings;
}
add_filter( 'heartbeat_settings', 'stp_heartbeat_settings' );

/**
 * Receive Heartbeat data and respond.
 *
 * Processes data received via a Heartbeat request, and returns additional data to pass back to the front end.
 *
 * @param array $response Heartbeat response data to pass back to front end.
 * @param array $data Data received from the front end (unslashed).
 */
function stp_receive_heartbeat( $response, $data ) {
    if ( empty($data['stp_postid']) ) {
      return $response;
    }
    $post_id = $data['stp_postid'];
    $response['stp_count'] = stp_display_count($post_id);
    return $response;
}
add_filter( 'heartbeat_received', 'stp_receive_heartbeat', 10, 2 );
add_filter( 'heartbeat_nopriv_received', 'stp_receive_heartbeat', 10, 2 );



/*
============================================
	Widget Functions
============================================
*/

// Register and load the widget
function stp_load_widgets() {

    include_once plugin_dir_path(__FILE__) . '/widgets/stp-popular-posts.php';
    include_once plugin_dir_path(__FILE__) . '/widgets/story-of-the-week.php';

    register_widget( 'STP_Popular_Posts' );
    register_widget( 'STP_Story_Of_The_Week' );

}
add_action( 'widgets_init', 'stp_load_widgets' );
