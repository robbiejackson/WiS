<?php    

/** 
  * Exports
  * This file contains the code for exporting the pods into CSV files and supporting the download and delete of these files
  *
  */ 

function export_walk_pod() {
    //_log('in ' . __FUNCTION__ . " line " . __LINE__);
    if (! isset( $_POST['robbie-nonce'] ) || ! wp_verify_nonce( $_POST['robbie-nonce'], 'export-walk' ) ) {
        echo "Bad nonce";
        return;
    }    
    $fname = "walk-" . date("Y-m-d-H-i-s") . ".csv";
    $pathname = plugin_dir_path( __FILE__ ) . 'exports/' . $fname;
    $csv = fopen($pathname, "w");
    $nbytes = fwrite($csv, "Title | Short Description | Town | Area | Author | Last Updated | Length | Ascent | Walking Time | Total Time | Grade |" . 
            "Difficulty | Start Lat | Start Long | Start Place | Directions to start | Restrictions | Walk Description | GPX file | " . 
            "Photo1 | Caption1 | Photo2 | Caption2 | Photo3 | Caption3 | Photo4 | Caption4 | Photo5 | Caption5 | Photo6 | Caption6 \n");
    $walk = pods( ' walk' );
    $params = array(
        'limit' => -1,
        'orderby' => 'post_title');
    $walk->find($params);
    $j = 0;
    while ($walk->fetch()) {
        $j += 1;
        $title = $walk->field('post_title');
        $description = '"' . $walk->field('post_content') . '"';
        $town = $walk->field('town');
        $area = $walk->field('walk_area.name');
        $author = $walk->field('walk_author');
        $last_updated = $walk->field('last_updated');
        $distance = $walk->field('distance');
        $ascent = $walk->field('ascent');
        $walking_time = $walk->field('walking_time');
        $total_time = $walk->field('total_time');
        $grade = $walk->field('walk_grade.name');
        $difficulty = $walk->field('difficulty');
        $start_lat = $walk->field('start_point_latitude');
        $start_long = $walk->field('start_point_longitude');
        $place = $walk->field('walk_place.name');
        $directions_to_start = '"' . $walk->field('directions_to_start') . '"';
        $restrictions = '"' . $walk->field('restrictions') . '"';
        $walk_desc = $walk->field('walk_description_file');
        $gps_file = $walk->field('gps_track_file');
        $photos = $walk->field('photo');
        $line = $title .'|'. $description .'|'. $town .'|'. $area .'|'. $author .'|'. $last_updated .'|'. $distance .'|'. $ascent .'|'. 
            $walking_time .'|'. $total_time .'|'. $grade .'|'. $difficulty .'|'. $start_lat .'|'. $start_long .'|'. $place .'|'. 
            $directions_to_start .'|'. $restrictions .'|'. $walk_desc['post_title']  .'|'. $gps_file['post_title'] . '|';
        for ($i=1; $i<=6; $i++) {
            if (count($photos) >= $i) {
                $line .= $photos[$i-1]['post_title'] .'|'. $photos[$i-1]['post_excerpt'] .'|';
            } else {
                $line .= '||';
            }
        }
        $nbytes = fwrite($csv, $line . "\n");
        }
    fclose($csv);
    echo "<br>Grades exported to file " . $pathname . "<br><br>";
    echo '<a href="' . plugins_url( 'exports/' . $fname, __FILE__ ) . '" class="Download-link">Download Walks Export</a><br><br>';
    echo '<a href="' . admin_url('admin.php?page=wis-maps-settings') . '" >Return to WIS settings</a><br><br>';
    echo '<a href="' . admin_url() . '" >Go to admin dashboard</a><br><br>';
}
add_action('admin_post_export_walk_pod', 'export_walk_pod');

function export_area_pod() {
    _log('in ' . __FUNCTION__ . " line " . __LINE__);
    if (! isset( $_POST['robbie-nonce'] ) || ! wp_verify_nonce( $_POST['robbie-nonce'], 'export-area' ) ) {
        echo "Bad nonce";
        return;
    }    
    $fname = "area-" . date("Y-m-d-H-i-s") . ".csv";
    $pathname = plugin_dir_path( __FILE__ ) . 'exports/' . $fname;
    $csv = fopen($pathname, "w");
    $nbytes = fwrite($csv, "Area Name | Parent | Latitude | Longitude | Zoom | Polygon Coordinates| Webpage \n");
    // first get the names of the parents and store them
    $parents = pods('area');
    $params = array( 
        'where' => "parent = 0",
        );
    $parents->find($params);
    $countries = array();
    while ($parents->fetch()) {
        $parent_id = $parents->field('term_id');
        $parent_name = $parents->field('name');
        $countries[$parent_id] = $parent_name;
    }
    // now get the areas themselves
    $area = pods( 'area' );
    $params = array( 
    //    'select' => "'name','map_centre_latitude','map_centre_longitude','map_zoom','polygon','webpage'",
    //    'where' => "'parent' != 0",
        'orderby' => 'tt.parent');
    $area->find($params);
    while ($area->fetch()) {
        $name = $area->field('name');
        $parent_id = $area->field('parent');
        if ($parent_id == 0) {  // ie it's a top-level area
            $parent = '';
        } else {
            $parent = $countries[$parent_id];
        }
        $lat = $area->field('map_centre_latitude');
        $long = $area->field('map_centre_longitude');
        $zoom = $area->field('map_zoom');
        $polygon = $area->field('polygon');
        $webpage = $area->field('webpage');
        $nbytes = fwrite($csv, $name . '|' . $parent . '|' . $lat . '|' . $long . '|' . $zoom . '|' . $polygon . '|' . $webpage . '|' . "\n");
//        $parent = $area->field('parent.name');
//        _log($parent);
    }
    fclose($csv);
    echo "<br>Areas exported to file " . $pathname . "<br><br>";
    echo '<a href="' . plugins_url( 'exports/' . $fname, __FILE__ ) . '" class="Download-link">Download Areas Export</a><br><br>';
    echo '<a href="' . admin_url('admin.php?page=wis-maps-settings') . '" >Return to WIS settings</a><br><br>';
    echo '<a href="' . admin_url() . '" >Go to admin dashboard</a><br><br>';    

}
add_action('admin_post_export_area_pod', 'export_area_pod');

function export_grade_pod() {
    _log('in ' . __FUNCTION__ . " line " . __LINE__);
    if (! isset( $_POST['robbie-nonce'] ) || ! wp_verify_nonce( $_POST['robbie-nonce'], 'export-grade' ) ) {
        echo "Bad nonce";
        return;
    }        
    $fname = "grade-" . date("Y-m-d-H-i-s") . ".csv";
    $pathname = plugin_dir_path( __FILE__ ) . 'exports/' . $fname;
    $csv = fopen($pathname, "w");
    $grade = pods( 'grade' );
    $params = array( 
        'limit' => -1,
        'orderby' => 'name');
    $grade->find($params);
    while ($grade->fetch()) {
        $name = $grade->field('name');
        //_log($name);
        $description = $grade->field('description');
        $nbytes = fwrite($csv, $name . '|"' . $description . '"' . "\n");
    }
    fclose($csv); 
    echo "<br>Grades exported to file " . $pathname . "<br><br>";
    echo '<a href="' . plugins_url( 'exports/' . $fname, __FILE__ ) . '" class="Download-link">Download Grades Export</a><br><br>';
    echo '<a href="' . admin_url('admin.php?page=wis-maps-settings') . '" >Return to WIS settings</a><br><br>';
    echo '<a href="' . admin_url() . '" >Go to admin dashboard</a><br><br>';    
}

add_action('admin_post_export_grade_pod', 'export_grade_pod');

function export_place_pod() {
    _log('in ' . __FUNCTION__ . " line " . __LINE__);
    if (! isset( $_POST['robbie-nonce'] ) || ! wp_verify_nonce( $_POST['robbie-nonce'], 'export-place' ) ) {
        echo "Bad nonce";
        return;
    }        
    $fname = "place-" . date("Y-m-d-H-i-s") . ".csv";
    $pathname = plugin_dir_path( __FILE__ ) . 'exports/' . $fname;
    $csv = fopen($pathname, "w");
    $nbytes = fwrite($csv, "Place | Description | Area | Latitude | Longitude \n");
    $place = pods( 'place' );
    $params = array( 
        'limit' => -1,
        'orderby' => 'name');
    $place->find($params);
    while ($place->fetch()) {
        $name = $place->field('name');
        $description = '"' . $place->field('description') . '"';
        $area = $place->field('place_area.name');
        $lat = $place->field('latitude');
        $long = $place->field('longitude');
        $nbytes = fwrite($csv, $name . '|' . $description . '|' .$area . '|' . $lat .'|' . $long . "\n");
    }
    fclose($csv); 
    echo "<br>Places exported to file " . $pathname . "<br><br>";
    echo '<a href="' . plugins_url( 'exports/' . $fname, __FILE__ ) . '" class="Download-link">Download Start Places Export</a><br><br>';
    echo '<a href="' . admin_url('admin.php?page=wis-maps-settings') . '" >Return to WIS settings</a><br><br>';
    echo '<a href="' . admin_url() . '" >Go to admin dashboard</a><br><br>';    
}

add_action('admin_post_export_place_pod', 'export_place_pod');

function delete_export() {
    _log('in ' . __FUNCTION__ . " line " . __LINE__);
    if (! isset( $_POST['robbie-nonce'] ) || ! wp_verify_nonce( $_POST['robbie-nonce'], 'delete-export' ) ) {
        echo "Bad nonce";
        return;
    }
    if (isset( $_POST['fname'] )) {
        $files = scandir ( plugin_dir_path( __FILE__ ) . 'exports' );
        foreach ($files as $fname) {
            if ( $fname == $_POST['fname']) {
                $pathname = plugin_dir_path( __FILE__ ) . 'exports/' . $fname;
                unlink ($pathname); 
                echo '<h3>' . $fname . ' deleted</h3>';
                }
            continue;
        }
    }
    echo '<a href="' . admin_url('admin.php?page=wis-maps-settings') . '" >Return to WIS settings</a><br><br>';
    echo '<a href="' . admin_url() . '" >Go to admin dashboard</a><br><br>';    
}
add_action('admin_post_delete_export', 'delete_export');

?>