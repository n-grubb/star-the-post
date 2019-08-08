# star-the-post
Star The Post Plugin from Seth Godin

You can add the star to a post or archive by including this code in your template: 
```
<?php if ( function_exists( 'stp_display_count' ) ) : ?>
    <div id="star-the-post" class="stp" data-postid="<?php the_ID() ;?>">
        <i class="stp--icon"></i>
        <i class="stp--outline"></i>
        <span class="stp--count"><?php echo stp_display_count( get_the_ID() ); ?></span>
    </div>
<?php endif; ?>
```