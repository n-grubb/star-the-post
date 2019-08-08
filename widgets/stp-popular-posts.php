<?php
class STP_Popular_Posts extends WP_Widget {

  /**
	 * Register widget with WordPress.
	 */
  function __construct() {
    parent::__construct(
      'stp_popular_posts',
      'Star the Post - Popular Posts',
      array( 'description' => 'List popular posts by star count.' )
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

    $top_posts = $wpdb->get_results( "SELECT * FROM $stp_table ORDER BY total DESC LIMIT 25" );
    // lets show 5
    ?>
      <ol class="toc">
        <?php foreach ( $top_posts as $single_post ) : ?>
    	  <li><a href="<?php echo get_permalink( $single_post->post_id ); ?>"><strong><?php echo get_the_title( $single_post->post_id ); ?></strong> (<?php echo $single_post->total; ?>)</a></li>
        <?php endforeach; ?>
      </ol>
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
