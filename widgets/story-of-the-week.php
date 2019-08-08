<?php
class STP_Story_Of_The_Week extends WP_Widget {

  /**
	 * Register widget with WordPress.
	 */
  function __construct() {
    parent::__construct(
      'stp_story_of_the_week',
      'Star the Post - Story of the Week',
      array( 'description' => 'Highlights the most popular post this week (by star count).' )
    );
  }

  /**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
  public function widget( $args, $instance ) {

    global $wpdb;
    $stp_table  = $wpdb->prefix . 'starred_posts';

    $title = apply_filters( 'widget_title', $instance['title'] );
    echo $args['before_widget'];
    if ( ! empty( $title ) ) {
      echo $args['before_title'] . $title . $args['after_title'];
    }

    $weekly_posts = array();

    // get posts from last 7 days.
    $args = array(
      'post_type' => 'post',
      'post_status' => 'publish',
      'orderby' => 'date',
      'order' => 'DESC',
      'date_query' => array(
        array(
            'after' => '1 week ago'
        )
      )
    );
    $weekly_posts_query = new WP_Query( $args );
    if ( $weekly_posts_query->have_posts() ) {
	      while ( $weekly_posts_query->have_posts() ) {
		         $weekly_posts_query->the_post();
             $weekly_posts[] = get_the_ID();
        }
    }
    if ( count($weekly_posts) < 1 ) {
      $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
        'posts_per_page' => 7
      );
      $weekly_posts_query = new WP_Query( $args );
      while ( $weekly_posts_query->have_posts() ) {
           $weekly_posts_query->the_post();
           $weekly_posts[] = get_the_ID();
      }
    }
    $weekly_posts = implode( ',', $weekly_posts );

    $top_post = $wpdb->get_results( "SELECT * FROM $stp_table WHERE post_id IN ({$weekly_posts}) ORDER BY shares DESC LIMIT 1" );
    $tp = get_post($top_post[0]->post_id);
    $excerpt = wp_trim_words( $tp->post_content, apply_filters('excerpt_length', 55) , '...' )
    ?>
    <div>
    <h2><a href="<?php echo get_permalink( $tp->ID ); ?>"><?php echo $tp->post_title; ?></a></h2>
      <?php echo apply_filters('the_content', get_post_field('post_content', $tp->ID)); ?>
    </div>
    <p class="byline">
      <span class="date"><?php echo get_the_date('F j, Y', $tp->ID); ?></span>
    </p>
    <div class="post-footer">
      <?php if ( function_exists( 'stp_display_count' ) ) : ?>
      <div id="star-the-post" class="stp" data-postid="<?php echo $tp->ID; ?>">
        <i class="stp--icon"></i>
        <i class="stp--outline"></i>
        <span class="stp--count"><?php echo stp_display_count( $tp->ID ); ?></span>
      </div>
      <?php endif; ?>

      <ul class="social-icons">
          <li><a class="js-social-share" href="http://www.facebook.com/sharer/sharer.php?u=<?php echo get_permalink( $tp->ID ); ?>&title=<?php echo $tp->post_title; ?>"><img class="svg" src="<?php echo get_template_directory_uri(); ?>/img/socialshare_facebook.svg" /></a></li>
          <li><a class="js-social-share" href="http://twitter.com/intent/tweet?status=<?php echo $tp->post_title; ?>+<?php echo get_permalink( $tp->ID ); ?>"><img class="svg" src="<?php echo get_template_directory_uri(); ?>/img/socialshare_twitter.svg" /></a></li>
          <li><a class="js-social-share" href="http://www.linkedin.com/shareArticle?mini=true&url=<?php echo get_permalink( $tp->ID ); ?>&title=<?php echo $tp->post_title; ?>&source=<?php echo site_url();?>"><img class="svg" src="<?php echo get_template_directory_uri(); ?>/img/socialshare_linkedin.svg" /></a></li>
          <li><a class="js-copy-url" href=""><textarea style="position: fixed; left: -110%"><?php echo get_permalink( $tp->ID ); ?></textarea><img class="svg" src="<?php echo get_template_directory_uri(); ?>/img/socialshare_link.svg" /></a></li>
      </ul>
    </div>
    <?php
    echo $args['after_widget'];
  }

  /**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
  public function form( $instance ) {
    if ( isset( $instance[ 'title' ] ) ) {
      $title = $instance[ 'title' ];
    }
    else {
      $title = __( 'New title', 'wpb_widget_domain' );
    }
    // Widget admin form
    ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    </p>
    <?php
  }

  /**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
  public function update( $new_instance, $old_instance ) {
    $instance = array();
    $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
    return $instance;
  }

}
