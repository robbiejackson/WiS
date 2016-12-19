<?php
function register_footer_menu() {
  register_nav_menu('footer-menu',__( 'Footer Menu' ));
}
add_action( 'init', 'register_footer_menu' );
?>