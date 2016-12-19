<?php
if ( !defined('ABSPATH')) exit; // Exit if accessed directly
/**
 * The template for displaying the footer.
 *
 * Contains all content after the closing of the id=main div
 *
 * @package WordPress
 * @subpackage Weaver X
 * @since Weaver Xtreme 1.0
 */

?>
<?php

	weaverx_inject_area('prefooter');		// put the prefooter optional area

	if ( weaverx_getopt( 'footer_hide' ) != 'hide' && !weaverx_is_checked_page_opt('_pp_hide_footer') ) {
		$f_class = weaverx_area_class('footer', 'pad', '-trbl', 'margin-none');
	?>
<footer id="colophon" <?php echo 'class="colophon ' . $f_class . '"'; ?> role="contentinfo">
	<?php

		if ( weaverx_has_widgetarea('footer-widget-area') ) {
		   $p_class = weaverx_area_class('footer_sb', 'pad', '', 'margin-bottom');
		   weaverx_put_widgetarea('footer-widget-area', $p_class);
		   weaverx_clear_both('footer-widget-area');
		}

		/* ======== EXTRA HTML ======== */

		$extra = weaverx_getopt('footer_html_text');

		$hide = weaverx_getopt_default('footer_html_hide', 'hide-none');

		if ( $extra == '' && is_customize_preview() ) {
			echo '<div id="footer-html" style="display:inline;"></div>';		// need the area there for customizer live preview
		} else if ($extra != '' && $hide != 'hide' ) {
			$c_class = weaverx_area_class('footer_html', 'not-pad', '-none', 'margin-none' );
			?>
			<div id="footer-html" class="<?php echo $c_class;?>">
				<?php echo do_shortcode($extra); ?>
			</div> <!-- #footer-html -->
		<?php }

		/* ======== COPYRIGHT AREA ======== */

		$date = getdate();
		$year = $date['year'];
		?>
		<div id="site-ig-wrap">
		<span id="site-info">
		<?php
		$cp = weaverx_getopt('copyright');
		if (strlen($cp) > 0) {
			if ($cp != '&nbsp;')	// really leave nothing if specify blank
				echo do_shortcode($cp) ;
		} else {
			echo '&copy;' . $year . ' - <a href="' . esc_url(home_url( '/' )) . '" title="' .  esc_attr( get_bloginfo( 'name', 'display' ) ) .
				 '" rel="home">'; bloginfo( 'name' ); echo '</a>';
		}
		?>
		</span> <!-- #site-info -->
		<?php /*
		if (! weaverx_getopt('_hide_poweredby')) { ?>
			<span id="site-generator">
			<a href="<?php echo esc_url( __( '//wordpress.org/','weaver-xtreme') ); ?>" title="wordpress.org" target="_blank" rel="nofollow"><?php printf( __( 'Proudly powered by %s','weaver-xtreme'), 'WordPress' ); ?></a> -
			<?php echo(WEAVERX_THEMENAME); ?> by <?php weaverx_site(''); ?>WeaverTheme</a>
		</span> <!-- #site-generator -->
		<?php
		}     */
        wp_nav_menu( array( 'theme_location' => 'footer-menu', 'container_class' => 'footer-menu-class' ) ); 
		weaverx_clear_both('site-generator'); ?>
		</div><!-- #site-ig-wrap -->
		<?php weaverx_clear_both('site-ig-wrap'); ?>
</footer><!-- #colophon -->
<?php
	weaverx_clear_both('colophon');
	} // end if !hide_footer

	do_action('weaverxplus_action','footer');

	weaverx_inject_area('fixedbottom');

	echo "</div><!-- /#wrapper --><div class='clear-wrapper-end' style='clear:both;'></div>\n";
	weaverx_inject_area('postfooter');		// and this is the end options insertion
	do_action('weaverxplus_action','postfooter');
	echo "\n<a href=\"#page-top\" id=\"page-bottom\">&uarr;</a>\n";

	if ( !( $content_h_ff = weaverx_getopt('content_h_font_family') ) ) {
		$content_h_ff = '0';
	}

	$font_size = weaverx_getopt_default('content_h_font_size', 'default');
	switch ( $font_size ) {
		case 'xxs-font-size':
			$h_fontmult = 0.625;
			break;
		case 'xs-font-size':
			$h_fontmult = 0.75;
			break;
		case 's-font-size':
			$h_fontmult = 0.875;
			break;
		case 'l-font-size':
			$h_fontmult = 1.125;
			break;
		case 'xl-font-size':
			$h_fontmult = 1.25;
			break;
		case 'xxl-font-size':
			$h_fontmult = 1.5;
			break;
		default:
			$h_fontmult = 1;
			break;
	}


	if ( isset( $GLOBALS['weaverx_sb_layout'] ) ) {
		$sb_layout = $GLOBALS['weaverx_sb_layout'];
	} else {
		$sb_layout = 'none';
	}

	$local = array(
		'hideTip' => ( weaverx_getopt('hide_tooltip') ) ? '1' : '0',
		'hFontFamily' => $content_h_ff,
		'hFontMult' => $h_fontmult,
		'sbLayout' => $sb_layout,
		'flowColor' =>  (weaverx_getopt('flow_color')) ? '1' : '0',
		'full_browser_height' =>
			(weaverx_getopt('full_browser_height') || weaverx_is_checked_page_opt('_pp_full_browser_height')) ? '1' : '0',
		'primary' => (weaverx_getopt('primary_eq_widgets')) ? '1' : '0',    // #primary-widget-area
		'secondary' => (weaverx_getopt('secondary_eq_widgets')) ? '1' : '0',  // '#secondary-widget-area',
		'top' => (weaverx_getopt('top_eq_widgets')) ? '1' : '0',        // '.widget-area-top',
		'bottom' => (weaverx_getopt('bottom_eq_widgets')) ? '1' : '0',     // '.widget-area-bottom',
		'header_sb' => (weaverx_getopt('header_sb_eq_widgets')) ? '1' : '0',  // '#header-widget-area',
		'footer_sb' => (weaverx_getopt('footer_sb_eq_widgets')) ? '1' : '0'   // '#footer-widget-area'
	);

	wp_localize_script('weaverxJSLibEnd', 'wvrxEndOpts', $local );      // in footer.php because don't know the values yet in functions.php

	wp_footer();

	weaverx_masonry('invoke-code');
	if ( WEAVERX_DEV_MODE ) {
		$end_time = microtime(true);
		weaverx_debug_comment ('Page generated in: '. round($end_time-$GLOBALS['wvrx_timer'], 3) . ' seconds.');
	}
?>
</body>
</html>
