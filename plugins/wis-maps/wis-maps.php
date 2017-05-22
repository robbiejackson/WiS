<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://www.walksinspain.org
 * @since             1.0.0
 * @package           wis-maps
 *
 * @wordpress-plugin
 * Plugin Name:       WIS Maps
 * Plugin URI:        http://example.com/wis-maps-uri/
 * Description:       Maps for Walks in Spain website
 * Version:           1.0.0
 * Author:            Robbie Jackson
 * Author URI:        
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wis-maps
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/** 
 *  For debugging purposes
 */

if(!function_exists('_log')){
  function _log( $message ) {
    if( WP_DEBUG === true ){
      if( is_array( $message ) || is_object( $message ) ){
        error_log( var_export( $message, true ) );
      } else {
        error_log( $message );
      }
    }
  }
}

/** 
 *  CSS Styling - for admin and for the normal site
 *  The CSS files go into the directory of this plugin (plugins/wis-maps/)
 */

function wismap_add_admin_stylesheet() {
    wp_register_style('wis-maps-admin-stylesheet', plugins_url('admin-style.css',__FILE__ ));
    wp_enqueue_style ('wis-maps-admin-stylesheet');
}
add_action('admin_enqueue_scripts', 'wismap_add_admin_stylesheet');

function wismap_add_stylesheet() {
    wp_register_style('wis-maps-stylesheet', plugins_url('style.css',__FILE__ ));
    wp_enqueue_style ('wis-maps-stylesheet');
    wp_register_style('wis-maps-datatable-cdn', "//cdn.datatables.net/1.10.12/css/jquery.dataTables.css");
    wp_enqueue_style ('wis-maps-datatable-cdn');
    wp_register_style('wis-maps-datatable-responsive-cdn', "//cdn.datatables.net/responsive/2.1.0/css/responsive.dataTables.min.css");
    wp_enqueue_style ('wis-maps-datatable-responsive-cdn');
}
add_action('wp_enqueue_scripts', 'wismap_add_stylesheet');

/** 
 *  Javascript - just used on the main site, not within the admin functionality
 *  The Javascript files go into the js subdirectory of this plugin (plugins/wis-maps/js/)
 *  This function is called only if there's a shortcode on the page which results in a map to be shown
 *  and as this is known only when wp is generating the content, the scripts have to be loaded later (actually in the footer).
 *  Scripts also need to be loaded in the footer because wp_localize_script is called when generating the page
 *  content, and these variables are passed to the javascript. 
 */

function wismap_add_js_scripts() {
    
    $googlemaps = '//maps.googleapis.com/maps/api/js?key=' . get_option('google_maps_API_key') . '&libraries=drawing&v=3';
    wp_register_script('wismap-googlemaps', $googlemaps, array('jquery'));
    wp_enqueue_script('wismap-googlemaps', null, null, null, true);  // p5 true to get scripts loaded in the footer
    wp_register_script('wismap-datatables', '//cdn.datatables.net/1.10.12/js/jquery.dataTables.js', array('jquery'));  // DataTables CDN
    wp_enqueue_script('wismap-datatables', null, null, null, true);  // p5 true to get scripts loaded in the footer
    wp_register_script('wismap-datatables-responsive', '//cdn.datatables.net/responsive/2.1.0/js/dataTables.responsive.min.js', array('jquery'));  // DataTables CDN
    wp_enqueue_script('wismap-datatables-responsive', null, null, null, true);  // p5 true to get scripts loaded in the footer
    wp_register_script('wismap', WP_PLUGIN_URL.'/wis-maps/wismap-min.js', array('wismap-googlemaps','wismap-datatables'));  // nb wismap script requires googlemaps loaded before it
    wp_enqueue_script('wismap', null, null, null, true);   
    wp_localize_script('wismap', 'wisAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
}

function wismaps_disable_comment_url($fields) { 
    unset($fields['url']);
    return $fields;
}
add_filter('comment_form_default_fields','wismaps_disable_comment_url');

//add_action( 'wp_print_scripts', 'wismap_add_js_scripts'); //- commented out as scripts aren't added automatically, but only if necessary


/** 
 *  Admin menu
 *  The following functions define what are to be the settings for this plugin and the 
 *    admin display for selecting the values of these settings.
 *  The settings are:
 *    - the google maps API key
 *    - whether the overall Spain and Canary Islands maps should be by default google maps or open cycle map
 *    - whether the maps of individual areas should be by default google maps or open cycle map
 */

 
 // register the settings we're using
add_action( 'admin_init', 'wis_maps_plugin_settings' );
function wis_maps_plugin_settings() {
    register_setting( 'wis_maps-plugin-settings-group', 'google_maps_API_key' );
    register_setting( 'wis_maps-plugin-settings-group', 'thunderforest_API_key' );
	register_setting( 'wis_maps-plugin-settings-group', 'default_country_maptype' );
	register_setting( 'wis_maps-plugin-settings-group', 'default_area_maptype' );
    register_setting( 'wis_maps-plugin-settings-group', 'default_walk_maptype' );
    register_setting( 'wis_maps-plugin-settings-group', 'ign-copyright-text' );
    register_setting( 'wis_maps-plugin-settings-group', 'map_explanation' );
    register_setting( 'wis_maps-plugin-settings-group', 'list_explanation' );
}
 
 
// get wp to add our wis maps functionality on the admin menu
add_action('admin_menu', 'wis_maps_plugin_admin_menu');
function wis_maps_plugin_admin_menu() {
	add_menu_page('WiS Plugin Settings', 'WiS Settings', 'edit_pages', 'wis-maps-settings', 'wis_maps_settings_page', 'dashicons-admin-generic');
}

// define the html to enable the admin to select the settings
function wis_maps_settings_page() { 
 
 ?>
<div class="wrap">
<h2><?php _e( 'Map Details', 'wis-maps-plugin' ) ?></h2>

<form method="post" action="options.php">
    <?php settings_fields( 'wis_maps-plugin-settings-group' ); ?>
    <?php do_settings_sections( 'wis_maps-plugin-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Default Spain Map type</th>
        <td><select name="default_country_maptype" value="<?php echo esc_attr( get_option('default_country_maptype') ); ?>" />
            <option value="Google Maps" <?php echo (get_option('default_country_maptype') == "Google Maps" ? "selected" : ""); ?> >Google Maps</option>
            <option value="Open Cycle Map" <?php echo (get_option('default_country_maptype') == "Open Cycle Map" ? "selected" : ""); ?> >Open Cycle Map</option>
            <option value="Open Street Map" <?php echo (get_option('default_country_maptype') == "Open Street Map" ? "selected" : ""); ?> >Open Street Map</option>
            <option value="Thunderforest Landscape" <?php echo (get_option('default_country_maptype') == "Thunderforest Landscape" ? "selected" : ""); ?> >Thunderforest Landscape</option>
            </select>
        </td>
        </tr>
         
        <tr valign="top">
        <th scope="row">Default Area Map type</th>
        <td><select name="default_area_maptype" value="<?php echo esc_attr( get_option('default_area_maptype') ); ?>" />
            <option value="Google Maps" <?php echo (get_option('default_area_maptype') == "Google maps" ? "selected" : ""); ?> >Google Maps</option>
            <option value="Open Cycle Map" <?php echo (get_option('default_area_maptype') == "Open Cycle Map" ? "selected" : ""); ?> >Open Cycle Map</option>
            <option value="Open Street Map" <?php echo (get_option('default_area_maptype') == "Open Street Map" ? "selected" : ""); ?> >Open Street Map</option>
            <option value="Thunderforest Landscape" <?php echo (get_option('default_area_maptype') == "Thunderforest Landscape" ? "selected" : ""); ?> >Thunderforest Landscape</option>
            </select>
        </td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Default Walk Map type</th>
        <td><select name="default_walk_maptype" value="<?php echo esc_attr( get_option('default_walk_maptype') ); ?>" />
            <option value="Google Maps" <?php echo (get_option('default_walk_maptype') == "Google maps" ? "selected" : ""); ?> >Google Maps</option>
            <option value="Open Cycle Map" <?php echo (get_option('default_walk_maptype') == "Open Cycle Map" ? "selected" : ""); ?> >Open Cycle Map</option>
            <option value="Open Street Map" <?php echo (get_option('default_walk_maptype') == "Open Street Map" ? "selected" : ""); ?> >Open Street Map</option>
            <option value="Thunderforest Landscape" <?php echo (get_option('default_walk_maptype') == "Thunderforest Landscape" ? "selected" : ""); ?> >Thunderforest Landscape</option>
            </select>
        </td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Google Maps API key</th>
        <td><input class="wis-maps-api-key" id="wis-maps-gmap-api-key" type="text" name="google_maps_API_key" value="<?php echo esc_attr( get_option('google_maps_API_key') ); ?>" /></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Thunderforest API key</th>
        <td><input class="wis-maps-api-key" id="wis-maps-tf-api-key" type="text" name="thunderforest_API_key" value="<?php echo esc_attr( get_option('thunderforest_API_key') ); ?>" /></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">IGN Copyright Text</th>
        <td><input class="wis-maps-textarea" rows="2" id="wis-maps-ign-copyright-text" type="text" name="ign-copyright-text" value="<?php echo esc_attr( get_option('ign-copyright-text') ); ?>" /></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Area map explanatory text</th>
        <td><textarea class="wis-maps-textarea" rows="8" id="wis-maps-explanation" name="map_explanation" ><?php echo get_option('map_explanation'); ?></textarea></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Area list explanatory text</th>
        <td><textarea class="wis-maps-textarea" rows="8" id="wis-list-explanation" name="list_explanation" ><?php echo get_option('list_explanation'); ?></textarea></td>
        </tr>
        
    </table>
    
    <?php submit_button(); ?>

</form>
</div>
<br>
<div id="export-walk-pod">
    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
        <?php wp_nonce_field( 'export-walk', 'robbie-nonce' ); ?>
        <input type="hidden" name="action" value="export_walk_pod">
        <input type="submit" name="save" value="Export Walks">
    </form>
</div>
<br>
<div id="export-area-pod">
    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
        <?php wp_nonce_field( 'export-area', 'robbie-nonce' ); ?>
        <input type="hidden" name="action" value="export_area_pod">
        <input type="submit" name="save" value="Export Areas">
    </form>
</div>
<br>
<div id="export-grade-pod">
    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
        <?php wp_nonce_field( 'export-grade', 'robbie-nonce' ); ?>
        <input type="hidden" name="action" value="export_grade_pod">
        <input type="submit" name="save" value="Export Grades">
    </form>
</div>
<br>
<div id="export-place-pod">
    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
        <?php wp_nonce_field( 'export-place', 'robbie-nonce' ); ?>
        <input type="hidden" name="action" value="export_place_pod">
        <input type="submit" name="save" value="Export Start Places">
    </form>
</div>
<div id="existing-exports">
    <?php 
        $files = scandir ( plugin_dir_path( __FILE__ ) . 'exports' );
        echo '<h3>' . (count($files) - 2) . ' Existing Export' . ( count($files) == 3 ? '' : 's' ) . '</h3>';
        foreach ($files as $fname) {   // allow for filenames .. and . in the directory
            if ( ($fname !== '.') && ($fname !== '..') )
                {   ?>
                <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                    <?php wp_nonce_field( 'delete-export', 'robbie-nonce' ); ?>
                    <span>
                    <input type="hidden" name="action" value="delete_export">
                    <input type="hidden" name="fname" value="<?php echo $fname; ?>">
                    <a href="<?php echo plugins_url( 'exports/' . $fname, __FILE__ ); ?>" class="Download-link"> <?php echo $fname; ?> </a>
                    <input type="submit" name="Delete" value="Delete">
                    </span>
                </form>
            <?php 
            }
        }
    ?>
</div>
<br>

<?php

}
require_once( plugin_dir_path( __FILE__ ) . 'export.php' );

/**
 *  The code below is used when inserting or updating a Walk record; it's a 
 *  pre-save trigger used to update the Walk record prior to saving it to the database
 *  See http://pods.io/docs/code/filter-reference/pods_api_pre_save_pod_item_podname/
 *  If the administrator chooses a Place record as the starting point, then the code copies
 *  the nearest town field from the Place record to the Walk record.
 *  This isn't the best approach from a database perspective, but it's done so that the
 *  walk search facility has all the information that's being searched on within the Walk record itself.
 *  Otherwise we'd have to do a join across Walk and Place to find records where the text matched the nearest town,
 *  and I don't know how to do that in Pods. 
 */

add_filter('pods_api_pre_save_pod_item_walk', 'add_town', 10, 2);
function add_town($pieces, $is_new_item) {

    $place_id = $pieces[ 'fields' ][ 'walk_place' ][ 'value' ];
    if ($place_id != '') {
        $place_record = pods( 'place', $place_id );
        if ( $place_record->exists() ) {
            $pieces[ 'fields' ][ 'town' ][ 'value' ] = $place_record->field('nearest_town');
        }
    }

    return $pieces;
} 

/**  
 *  Plugin code
 *  The plugin works through inclusion of the shortcode [wismap] on a page.
 *  The main handler for the shortcode is handle_wis_map_shortcode below, but it
 *  calls subsidiary functions for each of the attributes of the shortcode. These are:
 *    maptype="<map type>"   The map type must be either "google maps" or "open cycle map" (case doesn't matter)
 *       If not specified, then the default map type (set within admin settings) is used
 *    maparea="<area>"    The "area" value must be one of the following, except that case doesn't matter:
 *          "Peninsular Spain" - for the map of peninsular Spain, showing the walking areas
 *          "Canary Islands" - for the map of the Canary Islands, showing the walking areas
 *              one of the walking areas, eg "costa blanca" - for the map of that area, showing walks in that area
 *       The output of the maparea attribute is a map of the preferred type, showing either the walking areas, or the walks 
 *    listarea="<area>"   The "area" value must be one of the walking areas
 *       The output of the listarea attribute is a paginated list of the walks in that area 
 *    import="migration.csv"     value is the filename of the csv file to be imported.
 *                               This should be used only during migration to the new website
 *       The output is the number of walk records imported. Check wp-content/debug.log for any errors
 *
 *    If the shortcode attributes specify that a map be shown, then the php code collates all the information to pass to
 *    the js code (via wp_localize_script calls). Note that there can be 2 maps on the same page, through having
 *    the wismap shortcode specified twice on the one page. (3 or more maps on the same page isn't supported).
 *    As it's the same js code handling a single map or 2 maps, we need to pass to js the number of maps on the page, and
 *    have difference variables for the 2 maps. This is handled by having the $wismap_number_of_maps global variable to 
 *    keep track of the number of maps, and adding this number (1 or 2) to the names of the js variables and to the wismap
 *    id of the html <div> element. 
 */
$wismap_number_of_maps = 0;   // this is to keep track of how many maps are on the page, to convey to the js code
 
add_shortcode("wismap", 'handle_wis_map_shortcode');
function handle_wis_map_shortcode($atts) {
    _log("Shortcode attributes:");
    _log($atts);
    $return_string = '';
    if (array_key_exists('maparea', $atts)) {
        $area_pod = pods('area');
        $params = array( 
            'where' => "name = '" . $atts['maparea'] . "'"
            );
        $area_pod->find($params);
        if ($area_pod->fetch()) {
            $area_id = $area_pod->field('term_id');
            $parent_id = $area_pod->field('parent');
            //_log('parent id is ' + $parent_id);
            $map_params = array(
                'latitude' => $area_pod->field('map_centre_latitude'),
                'longitude' => $area_pod->field('map_centre_longitude'),
                'zoom' => $area_pod->field('map_zoom')
                );
            if ($parent_id == 0) {
                $maptype = get_maptype($atts, get_option('default_country_maptype'));
                $return_string .= display_country_map($area_id, $maptype, $map_params);
            } else {
                $maptype = get_maptype($atts, get_option('default_area_maptype'));
                $return_string .= display_area_map($atts['maparea'], $maptype, $map_params);
                pass_places_to_js($atts['maparea']);
            }
        } else {  // can't find the area
            _log("Unrecognised value for maparea in wismap shortcode: " . $atts['maparea']);
            $return_string .= '<br><br>Unrecognised value "' . $atts['maparea'] . '" for maparea in wismap shortcode!<br><br>Please contact website administrator';
        }
    }
    if (array_key_exists('listarea', $atts)) {
        if (array_key_exists('pagelength', $atts)) {
            $pagelength = $atts['pagelength'];
        } else {
            $pagelength = "25";
        }
        $area_pod = pods('area');
        $params = array( 
            'where' => "name = '" . $atts['listarea'] . "'"
            );
        $area_pod->find($params);
        if ($area_pod->fetch()) {
            $area_id = $area_pod->field('term_id');
            $parent_id = $area_pod->field('parent');
            if ($parent_id == 0) {
                _log("Unrecognised value for listarea in wismap shortcode: " . $atts['listarea']);
                $return_string .= '<br><br>Unrecognised value "' . $atts['listarea'] . '" for listarea in wismap shortcode!<br><br>Please contact website administrator';
            } else {
                $return_string .= display_area_list($atts['listarea'], $pagelength);
            }
        } else {  // can't find the area
            _log("Unrecognised value for listarea in wismap shortcode: " . $atts['listarea']);
            $return_string .= '<br><br>Unrecognised value "' . $atts['listarea'] . '" for listarea in wismap shortcode!<br><br>Please contact website administrator';
        }
    }
/*  Comment out the import functionality - just in case!
    if (array_key_exists('importwalks', $atts)) {
        $walks_imported = import_walks($atts['importwalks']);
        $return_string .= '<p>' . $walks_imported . ' walks imported</p>';
    }
    if (array_key_exists('importareas', $atts)) {
        $areas_imported = import_areas($atts['importareas']);
        $return_string .= '<p>' . $areas_imported . ' areas imported</p>';
    }
    if (array_key_exists('importgrades', $atts)) {
        $grades_imported = import_grades($atts['importgrades']);
        $return_string .= '<p>' . $grades_imported . ' grades imported</p>';
    }
    if (array_key_exists('importplaces', $atts)) {
        $places_imported = import_places($atts['importplaces']);
        $return_string .= '<p>' . $places_imported . ' places imported</p>';
    }
*/
    if (array_key_exists('search', $atts)) {
        $maptype = get_maptype($atts, get_option('default_area_maptype'));
        if (array_key_exists('pagelength', $atts)) {
            $pagelength = $atts['pagelength'];
        } else {
            $pagelength = "25";
        }
        $return_string .= display_wis_search($maptype, $pagelength);
    }
    return $return_string;
}

require_once( plugin_dir_path( __FILE__ ) . 'maps.php' );

// this is a utility function for picking the maptype out of the shortcode attributes.
// if it's not specified explicitly in the shortcode then the $default will be returned (expected to be set to the appropriate get_option value)

function get_maptype($atts, $default) {
    if (array_key_exists('maptype', $atts)) {
        $maptype_att = strtolower($atts['maptype']);
        if ($maptype_att == "google maps") {
            $maptype = "google maps"; 
        } else if ($maptype_att == "open cycle map"){
            $maptype = "open cycle map";
        } else if ($maptype_att == "open street map"){
            $maptype = "open street map";
        } else if ($maptype_att == "thunderforest landscape"){
            $maptype = "thunderforest landscape";
        } else {
            _log("Unrecognised value for maptype in wismap shortcode: " . $atts['maptype']);
            return $default; 
        }
    } else {
        return $default;
    }
}

require_once( plugin_dir_path( __FILE__ ) . 'import.php' );


/** 
 *  Ajax 
 *  When a user clicks on a walk marker on the map, and that walk has a gpx track,
 *  then the js makes an ajax call which gets routed to get_gpx_track() below.
 *  This function uses the php simplexml_load() function to read and parse the xml in the file 
 *  It then extracts the lat/long pairs of the routes, and selects every nth one (defined by $sample variable)
 *  which it puts in an array to be returned to the javascript. 
 */

add_action("wp_ajax_nopriv_get_track", "get_gpx_track");
add_action("wp_ajax_get_track", "get_gpx_track");
function get_gpx_track() {
    _log("in get_gpx_track with fname " . $_REQUEST['fname']);
    $gpxfile = $_REQUEST['fname'];
    if ($xml=simplexml_load_file($gpxfile)) {
        $track = $xml->trk->trkseg->trkpt;
        //$trk_count = count ($track);
        $coords = array();
        $i = 0;
        $sample = 5; // takes every $sample pairs of lat/long coords
        foreach ($xml->trk->trkseg->trkpt as $value) {
            $lat = (array) $value->attributes()->lat; 
            $lon = (array) $value->attributes()->lon;
            $i += 1;
            if ($i % $sample == 0) {
                array_push($coords, array ('v' => $lat[0], 'h' => $lon[0]));
            }
        }
        $result = array(
            'type' => 'success',
            'track' => $coords
            );
    } else {
        $result = array(
            'type' => 'fail',
            'track' => ''
            );
    }
    //_log($result);
    $result = json_encode($result);
    echo $result;
    die();
}

/** 
 *  The following is a utility function to enable the caption to be displayed on the template for a single walk.
 *  However, this is no longer needed, as the display function was rewritten to use a single-walk.php file
 *
 *  There doesn't seem to be a magic tag to get the caption associated with an image. However, you can specify a function to call
 *  in conjunction with a magic tag, so pod_caption gets called with the image (ie the full <img> tag and attributes) as data.
 *  Pods sets the image caption as the img alt attribute, so this function extracts that attribute (if it's present) and passes
 *  the text string back as the caption. 
 *  See http://pods.io/docs/build/using-magic-tags/
 *  See also https://wordpress.org/support/topic/display-image-caption-from-within-pods-template/#post-8174907 for a more future-proof approach
 */
/*
function wis_extract_caption($imgtag) {
    //_log('in pod caption');
    //_log($imgtag);
    $alt_start = strpos($imgtag, 'alt="');
    if ($alt_start) {  // there is a caption
        $alt_finish = strpos($imgtag, '"', $alt_start + 5);
        
        $caption = substr($imgtag, $alt_start+5, $alt_finish - $alt_start - 5);
        if ("image0" == substr(strtolower($caption), 0, 6)) {  // wordpress has just put the filename in for the caption - remove this
            $caption = "";
        }
    } else {
        $caption = "";
    }
    return $caption;
    /* more future-proof approach
    $src_start = strpos($imgtag, 'src="');
    $src_finish = strpos($imgtag, '"', $src_start + 5);
    $guid = substr($imgtag, $src_start+5, $src_finish - $src_start - 5);
    global $wpdb;
    $attachment_id = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->prefix}posts WHERE guid = %s", $guid) );
    $attachment_details = wp_prepare_attachment_for_js( $attachment_id[0] );
    return $attachment_details['caption']; 
}
*/