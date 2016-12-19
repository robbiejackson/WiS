<?php    

/**
 *  This file has functionality associated with presenting the maps and lists of walks, including the advanced search 
 */

/**
 *  For the country maps (Spain / Canaries) we want to get the details of each walking area which is to be shown on each map
 *  The details are:
 *   - the name of the area (to be shown in the info box)
 *   - the number of walks in that area (to be shown in the info box)
 *   - the web page of that area (to be shown in the info box
 *   - the coordinates of the vertices of the polygon representing that walking area on the map
 * 
 *  We collate this information from the database and store it as an array $javascript_areainfo, 
 *    with one element for each walking area.
 *  Each element of the area is an associated array $javascript_areainfo_element with
 *    - name - the name of the area
 *    - nwalks - number of walks in that area
 *    - webpage - url of the web page for that area
 *    - polygon_coords - array of the polygon coordinates
 * 
 *  We then JSON encode all this to pass to the javascript routine
 *  As there are 2 country maps on the 1 web page the info goes to 2 different sets of js variables, suffixed by the map number (1 or 2)
 */ 

function localize_common_mapinfo($maptype, $params) { // utility function to localize info common to both country and area maps
    global $wismap_number_of_maps;
    wp_localize_script('wismap', 'latitude' . (string)$wismap_number_of_maps, $params['latitude']);
    wp_localize_script('wismap', 'longitude' . (string)$wismap_number_of_maps, $params['longitude']);
    wp_localize_script('wismap', 'zoom' . (string)$wismap_number_of_maps, $params['zoom']);
    wp_localize_script('wismap', 'maptype' . (string)$wismap_number_of_maps, strtolower($maptype));
    wp_localize_script('wismap', 'thunderforest_API_key', get_option('thunderforest_API_key'));
    wp_localize_script('wismap', 'iconfolder', trailingslashit( plugins_url( '', __FILE__)) . 'icons/');
}

function pass_places_to_js($area) {
    _log('passing places to js');
    $javascript_placeinfo = array();
    $place = pods('place');
    $params = array( 
                'where' => "place_area.name = '$area'",
                'limit' => -1 );
    $place->find($params);
    while ($place->fetch()) {
        $javascript_placeinfo[$place->field('term_id')] = array ( 
            'lat' => $place->field('latitude'),
            'lng' => $place->field('longitude'),
            'name' => $place->field('name'),
            );
    }
    _log($javascript_placeinfo);
    wp_localize_script('wismap', 'placeinfo', json_encode($javascript_placeinfo));
}

function display_country_map($area_id, $maptype, $params){
    wismap_add_js_scripts();   // we're going to need the javascript loaded
    global $wismap_number_of_maps;
    $wismap_number_of_maps += 1;
    wp_localize_script('wismap', 'nmaps', (string)$wismap_number_of_maps);
    wp_localize_script('wismap', 'map_country_or_area' . (string)$wismap_number_of_maps, 'country');
    localize_common_mapinfo($maptype, $params);
    $javascript_areainfo = array();
    //
    // first find the number of walks in each area
    // and set the result in an associative array $walks_per_area
    //
    $walks_per_area = array ();
    $walks = pods('walk');
    $params = array(
        'select' => 'walk_area.name as areaname, count(*) as total',
        'groupby' => "walk_area.name",
        'expiry' => 10);   // keep in cache for 10 seconds because it will be used for both Spain & Canaries maps (shown on same page)
    $walks->find($params);
    while ($walks->fetch()) {
        //_log('fetching a row with ' . $walks->field('areaname') . ' and ' . $walks->field('total'));
        $walks_per_area[$walks->field('areaname')] = $walks->field('total');
    }
    //_log($walks_per_area);
    //
    // Now get the details of the areas which are in this country map (ie parent id is the id of the 'country')
    //
    $polygon_coords = array();
    $area = pods('area');
    $params = array( 
        'where' => "parent = " . $area_id
        );
    $area->find($params);
    while ($area->fetch()) {
        $javascript_areainfo_element = array();
        $area_name = $area->field('name');
        $area_webpage = $area->field('webpage');
        //
        // add the number of walks for that area (from the previous query)
        //
        if (array_key_exists($area_name, $walks_per_area)) {
            $area_nwalks = $walks_per_area[$area_name];
        } else {
            $area_nwalks = 0;
        }
        //
        // change the polygon list of coordinates into an array of coordinates, so it's easier for the js code
        //
        $area_polygon = array();
        $polygon_string = $area->field('polygon');
        $coord_pairs = explode(';', $polygon_string);
        foreach ($coord_pairs as $coord_pair) {
            array_push($area_polygon, explode(',', $coord_pair));
        }
        //
        // now pull all the info together into the structure to be passed to js (once it's JSON encoded)
        //
        $javascript_areainfo_element = array(
            'name' => $area_name,
            'nwalks' => $area_nwalks,
            'webpage' => $area_webpage,
            'polygon_coords' => $area_polygon );
        //_log($javascript_areainfo_element);
        array_push($javascript_areainfo,$javascript_areainfo_element);
    }
    //_log($polygon_coords);
    wp_localize_script('wismap', 'areainfo' . (string)$wismap_number_of_maps, json_encode($javascript_areainfo));
    return "<div class='wismap-country' id='wismap" . (string)$wismap_number_of_maps . "'><br>Map loading ...<br>Note that this requires javascript to be switched on<br></div>";
}

/**
 *  For the area maps we want to get the details of each walk in that area
 *  The details are:
 *   - the name of the walk (to be shown in the info box)
 *   - the latitude and longitude (for placing the marker pin on the map)
 *   - the nearest town, grade, length (ie distance), ascent, walk time (to be shown in the info box)
 *   - the difficulty (used to decide what colour marker to show)
 *   - permalink of the walk (shown in the info box, and enables a user to click on this to go straight to the walk)
 *   - the guid of the gpx file associated with the walk - passed back in an ajax call to get the coords
 * 
 *  We collate this information from the database and store it as an array $javascript_info, 
 *    with one element for each walk in the area.
 *  Each element of the area is an associated array $javascript_info_element with
 *    - name - the name of the walk
 *    - lat, lng - the latitude and longitude
 *    - town, grade, distance, ascent, walk_time - info associated with the walk
 *    - difficulty - the difficulty 
 *    - url - the url of that walk
 *    - gpx - the url of the gpx file for that walk
 *    - placeid - the id of the standard starting place (if appropriate)
 * 
 *  We then JSON encode all this to pass to the javascript routine
 *  There can be only 1 area map on each web page, so the info is passed to a single set of js variables (ie not suffixed with map number)
 *  You can have on the same web page a map of an area and a list of the walks for that area.
 *  To facilitate sharing this info between the area map and area list of walks, the info is sent down just once to the js, and the global
 *  variable $wismaps_walkinfo keeps track of this.
 */ 

$wismaps_walkinfo = array(); 
 
function prepare_area_walkinfo($area) {
    global $wismaps_walkinfo;
    if (!$wismaps_walkinfo) {
        $walk = pods('walk');
        $params = array( 
            'where' => "walk_area.name = '$area'",
            'orderby' => 'difficulty.meta_value,distance.meta_value', 
            'limit' => -1
            );
        $walk->find($params);
        while ($walk->fetch()) {
            $javascript_walkinfo_element = array(
                'name' => $walk->field('post_title'),
                'lat' => $walk->field('start_point_latitude'),
                'lng' => $walk->field('start_point_longitude'),
                'place' => $walk->field('walk_place.name'),
                'placeid' => $walk->field('walk_place.term_id'),
                'town' => $walk->field('town'),
                'grade' => $walk->field('walk_grade.name'),
                'distance' => $walk->field('distance'),
                'ascent' => $walk->field('ascent'),
                'walk_time' => $walk->field('walking_time'),
                'difficulty' => $walk->field('difficulty'),
                'url' => get_permalink($walk->field('id')),
                'gpx' => $walk->field('gps_track_file')['guid']
                );
            array_push($wismaps_walkinfo,$javascript_walkinfo_element);
        }
        //_log($wismaps_walkinfo);
        wp_localize_script('wismap', 'walkinfo', json_encode($wismaps_walkinfo));
    }
}


function display_area_map($area, $maptype, $params) {
    wismap_add_js_scripts();   // we're going to need the javascript loaded
    global $wismap_number_of_maps;
    $wismap_number_of_maps += 1;
    wp_localize_script('wismap', 'nmaps', (string)$wismap_number_of_maps);
    wp_localize_script('wismap', 'map_country_or_area' . (string)$wismap_number_of_maps, 'area');
    localize_common_mapinfo($maptype, $params);
    prepare_area_walkinfo($area);
    return get_option('map_explanation') . "<div class='wismap-area' id='wismap" . (string)$wismap_number_of_maps . "'><br>Map loading ...<br>Note that this requires javascript to be switched on<br></div>";
}

function display_area_list($area, $pagelength) {
    wismap_add_js_scripts();
    prepare_area_walkinfo($area);
    wp_localize_script('wismap', 'list', 'true');
    wp_localize_script('wismap', 'pagelength', $pagelength);
    return get_option('list_explanation') . "<div class='wismap-list' id='wismap-list'><br>Div for List of Walks<br></div>";
}

/** 
  * Advanced Search Page
  * 
  *
  */ 

function display_wis_search($maptype, $pagelength) {
    $return = display_search_form();
    // If it's a POST request then we need to do the search, otherwise just return
    if (isset($_POST) && empty($_POST)) {
        return $return;
    }
    // first confirm that there's a valid area 
    //_log($_POST);
    $area_found = false; 
    if ( isset($_POST["area"]) && strlen($_POST["area"]) > 0) {
        $area = pods('area');
        $params = array( 
            'where' => "name = '" . $_POST["area"] . "' AND parent != 0",
            );
        $area->find($params);
        if ($area->fetch()) {
            $area_id = $area->field('term_id');
            $area_name = $area->field('name');
            $map_params = array(
                'latitude' => $area->field('map_centre_latitude'),
                'longitude' => $area->field('map_centre_longitude'),
                'zoom' => $area->field('map_zoom')
                );
            $area_found = true;
            // $return .= "found area " . $area_name . ", id: " . $area_id;
        }
    }
    if (! $area_found ) {
        $return .= "Error: Please select a valid area";
        return $return;
    }
    // we've got a valid area, now do a search for the walks
    // set up the query based on the POST parameters sent
    // You can get POST parameters with empty strings, so check for that 
    $walk = pods('walk');
    $params = array( 
        'where' => "walk_area.term_id = '$area_id' " . 
            (( isset($_POST["difficulty"] ) && esc_attr( $_POST["difficulty"] ) != 0) ? " AND difficulty.meta_value = '" . esc_attr( $_POST["difficulty"] ) . "'" : "") . 
            ( isset($_POST["min-length"]) && strlen($_POST["min-length"]) > 0 ? " AND distance.meta_value >= " . esc_attr( $_POST["min-length"] ) : "") .
            ( isset($_POST["max-length"]) && strlen($_POST["max-length"]) > 0 ? " AND distance.meta_value <= " . esc_attr( $_POST["max-length"] ) : "") .
            ( isset($_POST["min-ascent"]) && strlen($_POST["min-ascent"]) > 0 ? " AND ascent.meta_value >= " . esc_attr( $_POST["min-ascent"] ) : "") . 
            ( isset($_POST["max-ascent"]) && strlen($_POST["max-ascent"]) ? " AND ascent.meta_value <= " . esc_attr( $_POST["max-ascent"] ) : "") .
            ( isset($_POST["min-walktime"]) && strlen($_POST["min-walktime"]) ? " AND walking_time.meta_value >= " . esc_attr( $_POST["min-walktime"] ) : "") .  
            ( isset($_POST["max-walktime"]) && strlen($_POST["max-walktime"]) ? " AND walking_time.meta_value <= " . esc_attr( $_POST["max-walktime"] ) : "") .
            ( isset($_POST["search-text"]) ? " AND (town.meta_value like '%" . esc_attr($_POST["search-text"]) . "%' OR post_title like '%" . esc_attr($_POST["search-text"]) . "%' OR post_content like '%" . esc_attr($_POST["search-text"]) . "%')" : "")
            ,
        'limit' => -1
        );
    $walk->find($params);
    $nwalks = 0;
    // cycle through the rows returned, and add each to the $wismaps_walkinfo array which will be sent down to the javascript
    global $wismaps_walkinfo;
    while ($walk->fetch()) {
        $nwalks++;
        $javascript_walkinfo_element = array(
            'name' => $walk->field('post_title'),
            'lat' => $walk->field('start_point_latitude'),
            'lng' => $walk->field('start_point_longitude'),
            'place' => $walk->field('walk_place.name'),
            'placeid' => $walk->field('walk_place.term_id'),
            'town' => $walk->field('town'),
            'grade' => $walk->field('walk_grade.name'),
            'distance' => $walk->field('distance'),
            'ascent' => $walk->field('ascent'),
            'walk_time' => $walk->field('walking_time'),
            'difficulty' => $walk->field('difficulty'),
            'url' => get_permalink($walk->field('id')),
            'gpx' => $walk->field('gps_track_file')['guid']
            );
        array_push($wismaps_walkinfo,$javascript_walkinfo_element);
    }
    if ($nwalks == 0) {
        $return .= "Sorry, no walks matched your search criteria. Please try again.";
        return $return;
    }
    $return .= "$nwalks walk" . ($nwalks == 1 ? "" : "s") . " found<br>";
    // we've found some walks. Now prepare to send them down and display the map and list
    global $wismap_number_of_maps;
    $wismap_number_of_maps = 1;
    wismap_add_js_scripts();
    localize_common_mapinfo($maptype, $map_params);
    pass_places_to_js($area_name);
    wp_localize_script('wismap', 'nmaps', (string)$wismap_number_of_maps);
    wp_localize_script('wismap', 'map_country_or_area' . (string)$wismap_number_of_maps, 'area');
    wp_localize_script('wismap', 'walkinfo', json_encode($wismaps_walkinfo));
    wp_localize_script('wismap', 'list', 'true');
    wp_localize_script('wismap', 'pagelength', $pagelength);
    $return .= get_option('map_explanation') . "<div class='wismap-area' id='wismap" . (string)$wismap_number_of_maps . "'><br>Map loading ...<br>Note that this requires javascript to be switched on<br></div>";
    $return .= get_option('list_explanation') . "<div class='wismap-list' id='wismap-list'><br>Div for List of Walks<br></div>";
    return $return;
}

function display_search_form() {
    $return = '<div class="wis-search-form">';
    $return .= '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="post">';
    $return .= '<table>';
    $return .= '<tr><td><label for="area">Area</label></td>';
    // Select box for the area
    $return .= '<td><select name="area">';
    $return .= '<option value="">Please select one</option>';
    // find those areas which have walks, and set as input options just those which have walks
    $walkareas = pods('walk');
    $params = array(
        'select' => 'walk_area.name as areaname, count(*) as total',
        'groupby' => "walk_area.name",
        'having' => 'count(*) > 0',
        'expiry' => 300);   // keep in cache for 5 minutes in case the user makes several searches
    $walkareas->find($params);
    while ($walkareas->fetch()) {
        $area_name = $walkareas->field('areaname');
        if ( isset( $_POST["area"] ) && esc_attr( $_POST["area"] ) == $area_name) {
            $selected = 'selected';
        } else {
            $selected = '';
        }
        $return .= '<option value="' . $area_name . '" ' . $selected . '>' . $area_name . '</option>';
    }
    $return .= '</select></td></tr>';
    // Select box for difficulty
    $return .= '<tr><td><label for="difficulty">Difficulty</label></td>';
    $return .= '<td><select name="difficulty">';
    $return .= '<option value="0" ' . (( isset( $_POST["difficulty"] ) && esc_attr( $_POST["difficulty"] ) == "0") ? "selected" : "") . '>Any difficulty</option>';
    $return .= '<option value="1" ' . (( isset( $_POST["difficulty"] ) && esc_attr( $_POST["difficulty"] ) == "1") ? "selected" : "") . '>1 - Easy</option>';
    $return .= '<option value="2" ' . (( isset( $_POST["difficulty"] ) && esc_attr( $_POST["difficulty"] ) == "2") ? "selected" : "") . '>2 - Moderate</option>';
    $return .= '<option value="3" ' . (( isset( $_POST["difficulty"] ) && esc_attr( $_POST["difficulty"] ) == "3") ? "selected" : "") . '>3 - Moderately Strenuous</option>';
    $return .= '<option value="4" ' . (( isset( $_POST["difficulty"] ) && esc_attr( $_POST["difficulty"] ) == "4") ? "selected" : "") . '>4 - Strenuous</option>';
    $return .= '<option value="5" ' . (( isset( $_POST["difficulty"] ) && esc_attr( $_POST["difficulty"] ) == "5") ? "selected" : "") . '>5 - Very Strenuous</option>';
    $return .= '</select></td></tr>';
    // Walk length
    $return .= '<tr><td><label for="min-length">Length (km) between</label></td>';
    $return .= '<td><input name="min-length" type="number" min="0" max="50" size="5" step="1" value="' . ( isset( $_POST["min-length"] ) ? esc_attr( $_POST["min-length"] ) : "") . '"></td>';
    $return .= '<td><label for="max-length">and</label></td>';
    $return .= '<td><input name="max-length" type="number" min="0" max="50" size="5" step="1" value="' . ( isset( $_POST["max-length"] ) ? esc_attr( $_POST["max-length"] ) : "") . '"></td></tr>';
    // Total ascent
    $return .= '<tr><td><label for="min-ascent">Ascent (metres) between</label></td>';
    $return .= '<td><input name="min-ascent" type="number" min="0" max="5000" size="5" step="100" value="' . ( isset( $_POST["min-ascent"] ) ? esc_attr( $_POST["min-ascent"] ) : "") . '"></td>';
    $return .= '<td><label for="max-ascent">and</label></td>';
    $return .= '<td><input name="max-ascent" type="number" min="0" max="5000" size="5" step="100" value="' . ( isset( $_POST["max-ascent"] ) ? esc_attr( $_POST["max-ascent"] ) : "") . '"></td></tr>';
    // Total walking time
    $return .= '<tr><td><label for="min-walktime">Walking time (hours) between</label></td>';
    $return .= '<td><input name="min-walktime" type="number" min="0" max="20" size="5" step="1" value="' . ( isset( $_POST["min-walktime"] ) ? esc_attr( $_POST["min-walktime"] ) : "") . '"></td>';
    $return .= '<td><label for="max-walktime">and</label></td>';
    $return .= '<td><input name="max-walktime" type="number" min="0" max="20" size="5" step="1" value="' . ( isset( $_POST["max-walktime"] ) ? esc_attr( $_POST["max-walktime"] ) : "") . '"></td></tr>';
    // Search text string
    $return .= '<tr><td><label for="search-text">Search text (eg town)</label></td>';
    $return .= '<td><input type="text" name="search-text" pattern="[a-zA-Z0-9 ]+" value="' . ( isset( $_POST["search-text"] ) ? esc_attr( $_POST["search-text"] ) : '' ) . '"  /></td></tr>';
    // Submit button
    $return .= '<td><td><input type="submit" name="search-submit" value="Search"/></td></tr></table>';
    $return .= '</form></div>';
    // 
    return $return;
}

/**
 *  For the walk map we want to show a single walk on a map
 *  We use the same details as for walks on area maps, namely:
 *   - the name of the walk (to be shown in the info box)
 *   - the latitude and longitude (for placing the marker pin on the map)
 *   - the nearest town, grade, length (ie distance), ascent, walk time (to be shown in the info box)
 *   - the difficulty (used to decide what colour marker to show)
 *   - permalink of the walk (shown in the info box, and enables a user to click on this to go straight to the walk)
 *   - the guid of the gpx file associated with the walk - passed back in an ajax call to get the coords
 * 
 *  As with the area maps, this info is stored in a $walkinfo record which is passed down to the javascript
 *  We also need to pass the placeinfo information but because this map is for just a single walk, we just
 *    check if this walk starts a standard start place, and if so, get the details of that place.
 *    with one element for each walk in the area.
 *  Each element of the area is an associated array $javascript_info_element with
 *    - name - the name of the walk
 *    - lat, lng - the latitude and longitude
 *    - town, grade, distance, ascent, walk_time - info associated with the walk
 *    - difficulty - the difficulty 
 *    - url - the url of that walk
 *    - gpx - the url of the gpx file for that walk
 *    - placeid - the id of the standard starting place (if appropriate)
 * 
 *  We then JSON encode all this to pass to the javascript routine
 *  There can be only 1 area map on each web page, so the info is passed to a single set of js variables (ie not suffixed with map number)
 *  You can have on the same web page a map of an area and a list of the walks for that area.
 *  To facilitate sharing this info between the area map and area list of walks, the info is sent down just once to the js, and the global
 *  variable $wismaps_walkinfo keeps track of this.
 */ 

function display_walk_map($walk) {
    // Function which prepares the data for the display on the map of a single walk
    wismap_add_js_scripts();   // we're going to need the javascript loaded
    // First we add all the general map information
    global $wismap_number_of_maps;
    $wismap_number_of_maps += 1;
    wp_localize_script('wismap', 'nmaps', (string)$wismap_number_of_maps);
    wp_localize_script('wismap', 'map_country_or_area' . (string)$wismap_number_of_maps, 'walk');
    $params = array (
                'latitude' => $walk->field('start_point_latitude'),
                'longitude' => $walk->field('start_point_longitude'),
                'zoom' => "14",
                );
    localize_common_mapinfo(get_option('default_walk_maptype'), $params);
    //
    // Prepare the information about the walk, and send it json-encoded to the js
    //
    global $wismaps_walkinfo;
    // if the $walk record has a standard starting place then get the lat / long from it instead of the $walk record
    // (rather than sending down the placeinfo information)
    $place = $walk->field('walk_place');
    if ($place) {
        $walk_place = pods ('place', $place['term_id']);
        $lat = $walk_place->field('latitude');
        $lng = $walk_place->field('longitude');
    } else {
        $lat = $walk->field('start_point_latitude');
        $lng = $walk->field('start_point_longitude');
    }
    $javascript_walkinfo_element = array(
        'name' => $walk->field('post_title'),
        'lat' => $lat,
        'lng' => $lng,
        'place' => $walk->field('walk_place.name'),
        'placeid' => $walk->field('walk_place.term_id'),
        'town' => $walk->field('town'),
        'grade' => $walk->field('walk_grade.name'),
        'distance' => $walk->field('distance'),
        'ascent' => $walk->field('ascent'),
        'walk_time' => $walk->field('walking_time'),
        'difficulty' => $walk->field('difficulty'),
        'url' => get_permalink($walk->field('id')),
        'gpx' => $walk->field('gps_track_file')['guid']
        );
    array_push($wismaps_walkinfo,$javascript_walkinfo_element);
    wp_localize_script('wismap', 'walkinfo', json_encode($wismaps_walkinfo));
    //
    // Return the div for the map
    // Note that there's no class on the div, because that would cause a big space to appear on the page
    // The class is set on within the javascript
    //
    return "<div id='wismap" . (string)$wismap_number_of_maps . "'><br>Click " . "<a href='#' onclick='displaySingleWalk(event)'>here</a> to see this walk on a map</div><br>";
}
