<?php
if ( !defined('ABSPATH')) exit; // Exit if accessed directly
/**
 *   The Template for displaying all single posts.
 */
/*! ** DO NOT EDIT THIS FILE! It will be overwritten when the theme is updated! ** */

	global $weaverx_cur_page_ID;
	$weaverx_cur_page_ID = get_the_ID();

	$sb_layout = weaverx_page_lead( 'single' );

	// and next the content area.
	weaverx_sb_precontent('single');

	// generate page content


	$cats = weaverx_getopt_checked('single_nav_link_cats');
	while ( have_posts() ) {
		weaverx_post_count_clear();
        
		the_post(); ?>
	<nav id="nav-above" class="navigation">
	<h3 class="assistive-text"><?php echo __( 'Post navigation','weaver-xtreme'); ?></h3>
	<?php if (weaverx_getopt('single_nav_style')=='prev_next') { ?>
		<div class="nav-previous"><?php previous_post_link( '%link', __( '<span class="meta-nav">&larr;</span> Previous','weaver-xtreme'), $cats ); ?></div>
		<div class="nav-next"><?php next_post_link( '%link', __( 'Next <span class="meta-nav">&rarr;</span>','weaver-xtreme'), $cats); ?></div>
	<?php } else { ?>
		<div class="nav-previous"><?php previous_post_link( '%link', '<span class="meta-nav">' . _x( '&larr;', 'Previous post link','weaver-xtreme') . '</span> %title', $cats ); ?>
		</div>
		<div class="nav-next"><?php next_post_link( '%link', '%title <span class="meta-nav">' . _x( '&rarr;', 'Next post link','weaver-xtreme') . '</span>' , $cats); ?></div>                 <?php } ?>
	</nav><!-- #nav-above -->
	<?php //get_template_part( 'templates/content', 'single' ); ?>
    <?php add_filter('the_content', 'add_walk_details', 5);
    function add_walk_details( $content ) {
        $slug = pods_v( 'last', 'url' ); 
        $walk = pods( 'walk', get_the_id() );
        if ( $walk->exists() ) {
            $grade = $walk->field('walk_grade');
            $walk_grade = pods ('grade', $grade['term_id']);
            $content .= "<p>The grade of this walk is " . $walk->field('walk_grade.name') . ", which means <br/> " . $walk->field('walk_grade.meaning') . " </p>";
            $content .= "<p>The difficulty of this walk is " . $walk->field('walk_grade.name') . ", which means <br/> " . $walk->field('walk_grade.description') . " </p>";
        }  
        return $content;
    }
?>
    <?php the_content(); ?>
<article id="post-<?php the_ID(); ?>" <?php post_class('content-single post-content ' . weaverx_post_class(true)); ?>>
	<?php weaverx_single_title( '' );
	weaverx_post_div('content');
    echo "before weaverx_the_post_full_single()"; 
	weaverx_the_post_full_single();
    echo "after weaverx_the_post_full_single()"; 
	wp_link_pages( array( 'before' => '<div class="page-link"><span>' . __( 'Pages:','weaver-xtreme') . '</span>', 'after' => '</div>' ) ); ?>
	</div><!-- .entry-content -->

	<footer class="entry-utility entry-author-info">
	<?php
	weaverx_post_bottom_info('single');
	weaverx_author_info();
	?>

	</footer><!-- .entry-utility -->
<?php   weaverx_inject_area('postpostcontent');	// inject post comment body ?>
</article

    <p>After get_template_part</p>
	<nav id="nav-below" class="navigation">
	<h3 class="assistive-text"><?php echo __( 'Post navigation','weaver-xtreme'); ?></h3>
	<?php if (weaverx_getopt('single_nav_style')=='prev_next') { ?>
		<div class="nav-previous"><?php previous_post_link( '%link', __( '<span class="meta-nav">&larr;</span> Previous','weaver-xtreme'), $cats ); ?></div>
		<div class="nav-next"><?php next_post_link( '%link', __( 'Next <span class="meta-nav">&rarr;</span>','weaver-xtreme'), $cats ); ?></div>
	<?php } else { ?>
		<div class="nav-previous"><?php previous_post_link( '%link', '<span class="meta-nav">' . _x( '&larr;', 'Previous post link','weaver-xtreme') . '</span> %title', weaverx_getopt_checked('single_nav_link_cats') ); ?></div>
		<div class="nav-next"><?php next_post_link( '%link', '%title <span class="meta-nav">' . _x( '&rarr;', 'Next post link','weaver-xtreme') . '</span>', $cats ); ?></div>
	<?php } ?>
	</nav><!-- #nav-above -->
    <p>Before comments_template</p>
	<?php comments_template( '', true );
    ?><p>After comments_template</p><?php

	} // end of the loop.

    ?><p>Before weaverx_sb_postcontent</p><?php
	weaverx_sb_postcontent('single');
    ?><p>After weaverx_sb_postcontent</p><?php

	weaverx_page_tail( 'single', $sb_layout );    // end of page wrap
?>
