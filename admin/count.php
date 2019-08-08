<?php
/*
 * Create a page in the WP backend to monitor the Star counts.
 */

// create custom plugin settings menu
add_action('admin_menu', 'stp_create_plugin_menu');
function stp_create_plugin_menu() {
  $page_hook = add_plugins_page(
    'Star the Post - Star Counts',
    'Star the Post',
    'manage_options',
    'star-the-post',
    'stp_count_display'
  );
}

function stp_count_display() {

  global $wpdb;
  $stp_table  = $wpdb->prefix . 'starred_posts';
  $stp_posts = $wpdb->get_results( "SELECT * FROM $stp_table ORDER BY total DESC LIMIT 1000" );
  $counter   = 1;
  ?>
  <style>
    .column-id { width: 5% }
    .column-title { width: 30%; }
    .weight {
      font-size: 10px;
      vertical-align: top;
    }
  </style>
  <div class="wrap">
    <h1>Star the Post - Star Counts</h1>
    <table class="widefat fixed" cellspacing="0">
      <thead>
      <tr>
        <th class="column-id num" scope="col">Rank</th>
        <th class="column-id num" scope="col">ID</th>
        <th class="column-title" scope="col">Title</th>
        <th class="column-clicks num" scope="col">Star Clicks <span class="weight">(1)</span></th>
        <th class="column-views num" scope="col">Tracked Views <span class="weight">(1/10)</span></th>
        <th class="column-shares num" scope="col">Social Shares <span class="weight">(7)</span></th>
        <th class="column-copies num" scope="col">URL Copies <span class="weight">(7)</span></th>
        <th class="column-total num" scope="col">Total</th>
        <th class="column-total num" scope="col">Last Updated</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ( $stp_posts as $stp_post ) : ?>
      <tr>
        <td class="column-id num"><?php echo $counter++; ?></td>
        <td class="column-id num"><?php echo $stp_post->post_id; ?></td>
        <?php
        $post_url = get_permalink($stp_post->post_id);
        $post_url = str_replace( 'http://local.dev', 'http://sethgodin.typepad.com', $post_url );
        $post_url = str_replace( 'http://162.144.142.57/~sethgodi', 'http://sethgodin.typepad.com/seths_blog', $post_url );
        $post_url = substr_replace( $post_url, '.html', -1 );
        ?>
        <td class="column-title"><a href="<?php echo $post_url; ?>"><?php echo get_the_title( $stp_post->post_id ); ?></a></td>
        <td class="column-clicks num"><?php echo $stp_post->clicks; ?></td>
        <td class="column-views num"><?php echo $stp_post->views; ?></td>
        <td class="column-shares num"><?php echo $stp_post->shares; ?></td>
        <td class="column-copies num"><?php echo $stp_post->copies; ?></td>
        <td class="column-total num"><?php echo $stp_post->total; ?></td>
        <td class="column-total num"><?php echo $stp_post->updated; ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    </table>
  </div>

  <?php
}
