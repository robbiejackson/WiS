<?php    


/** 
 *  Importing data from csv file into Pods
 *  The format of the csv files is the same as is produced by the Export functionality. Just remember to remove the header row!
 *
 *  If starting from scratch, the Areas have to be imported first, because import of Places and Walks are dependent upon the Areas being there.
 *
 *  Importing the walks assumes that the custom taxonomies Area, Places and Grades have already been completed/imported as regards data entry
 *  and that the csv file, file description, gpx and photo files (already with captions) have been uploaded to wordpress
 *  
 *  This function is triggered by the inclusion of a shortcode eg [wismap importwalks="xxx.csv"]
 *
 *  The function original_import_walks() is the original version which was used for migration from the previous database. 
 *  The format of the original walks csv file is what would be output by the following sql operating on the previous database structure:
 *  select walk_id,replace(title,"\\",""),replace(town_name,"\\",""),area_name,original_author,last_updated_date,grade,
 *    length,ascent,time_for_walk,completion_time,start_point_latitude,start_point_longitude,directions_to_start,
 *    replace(short_walk_description,"\\",""),replace(recommendations_or_restrictions,"\\","")
 *  from walks,town,area
 *  where walks.town_id = town.town_id and town.area_id = area.area_id and area_name = 'Els Ports'
 *  into outfile 'migration-els-ports.csv' fields terminated by '|'
 */

 /* no longer needed
function original_import_walks($fname) {
    // some file operations in this function will throw an exception if an error occurs
    // however this is ok, because it's just used in the migration process
    $csv_fname = WP_PLUGIN_URL .'/wis-maps/' . $fname; 
    $csv_file = fopen($csv_fname, "r"); 
    $csv_values = fgetcsv($csv_file, 0, '|');
    $pod = pods('walk');
    $walks_imported = 0;
    global $wpdb;   // needed for doing the queries into the wp database
    while ($csv_values) {
        _log($csv_values);
        // Most fields will go straight into the pod, but we need to specify the ids for:
        //    - custom taxonomies - grade and area
        //    - files - walk description file, gpx file (if it exists) and photos (if they exist)
        // first get the area id
        $area = pods('area');
        $params = array('where' => "name = '$csv_values[3]'");
        $area->find($params);
        $result = $area->fetch();
        $area_id = $result['term_id'];
        _log("Area id is " . $area_id);
        // now get the grade id
        $grade = pods('grade');
        $params = array('where' => "name = '$csv_values[6]'");
        $grade->find($params);
        $result = $grade->fetch();
        $grade_id = $result['term_id'];
        _log("Grade id is " . $grade_id);
        // Map the CBMW grade to the difficulty (1-5, 1 being easy)
        $cbmw_grade = strtoupper($csv_values[6]);
        if ($cbmw_grade[0] == "E") {
            $difficulty = 1;
        } else if (substr($cbmw_grade,0,2) == "M/") {
            $difficulty = 2;
        } else if (substr($cbmw_grade,0,2) == "MS") {
            $difficulty = 3;
            } else if ($cbmw_grade[0] == "S") {
            $difficulty = 4;
            } else if ($cbmw_grade[0] == "V") {
            $difficulty = 5;
        } else {
            $difficulty = 0;
        }
        // now get the walk descriptions file
        $walk_description_filename = "wd0" . $csv_values[0];
        $walk_description_attachment_id = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s and post_type='attachment'", $walk_description_filename));
        if (!$walk_description_attachment_id) {
            _log("Can't get walk description attachment id\n");
            return "Can't get walk description attachment id\n";
        }
        _log($walk_description_attachment_id);
        // now the gpx file (if it exists)
        $gpx_filename = "gps0" . $csv_values[0];    
        $gpx_attachment_id = 0;  // default value
        $gpx_attachment_id = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s and post_type='attachment'", $gpx_filename));
        _log($gpx_attachment_id);
        // now find any photos
        $photo_filename = "image0" . $csv_values[0] . "-%";
        $photo_attachment_ids = 0;
        $photo_attachment_ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title like %s and post_type='attachment'", $photo_filename));
        _log($photo_attachment_ids);
        if (count($photo_attachment_ids) > 6) {
            _log("too many photos!!");
            }
        // now prepare the array of data to be inserted into the pod
        $data = array(
            'post_title' => $csv_values[1],
            'post_content' => $csv_values[14],
            'town' => $csv_values[2],
            'walk_area' => $area_id,
            'walk_author' => $csv_values[4],
            'last_updated' => $csv_values[5],
            'distance' => $csv_values[7],
            'ascent' => $csv_values[8],
            'walking_time' => $csv_values[9],
            'total_time' => $csv_values[10],
            'walk_grade' => $grade_id,
            'difficulty' => $difficulty,
            'start_point_latitude' => $csv_values[11],
            'start_point_longitude' => $csv_values[12],
            'directions_to_start' => $csv_values[13],
            'restrictions' => $csv_values[15],
            'walk_description_file' => $walk_description_attachment_id,
            'gps_track_file' => $gpx_attachment_id,
            'photo' => $photo_attachment_ids,
            'post_status' => 'publish'
        );
        _log($data);
        $data_id = $pod->add($data);
        _log('Pod id after add: ' . $data_id);
        $walks_imported += 1;
        $csv_values = fgetcsv($csv_file, 0, '|');
    }
    fclose($csv_file);
    return $walks_imported;
}
*/

function import_walks($fname) {
    // some file operations in this function will throw an exception if an error occurs
    // however this is ok, because it's just used in the migration process
    $csv_fname = WP_PLUGIN_URL .'/wis-maps/' . $fname; 
    $csv_file = fopen($csv_fname, "r"); 
    $csv_values = fgetcsv($csv_file, 0, '|');
    $pod = pods('walk');
    $walks_imported = 0;
    global $wpdb;   // needed for doing the queries into the wp database
    while ($csv_values) {
        _log($csv_values);
        // Most fields will go straight into the pod, but we need to specify the ids for:
        //    - custom taxonomies - grade and area
        //    - files - walk description file, gpx file (if it exists) and photos (if they exist)
        // first get the area id
        $area = pods('area');
        $params = array('where' => "name = '$csv_values[3]'");
        $area->find($params);
        $result = $area->fetch();
        $area_id = $result['term_id'];
        _log("Area id is " . $area_id);
        // now get the grade id
        $grade = pods('grade');
        $params = array('where' => "name = '$csv_values[10]'");
        $grade->find($params);
        $result = $grade->fetch();
        $grade_id = $result['term_id'];
        _log("Grade id is " . $grade_id);
        // now get the place id, if starting place is set
        if ($csv_values[14] != '') {
            $place = pods('place');
            $place_name = addslashes($csv_values[14]);    // to cater for the apostrophe in Val d'Ebo
            $params = array('where' => "name = '$place_name'");
            $place->find($params);
            $result = $place->fetch();
            $place_id = $result['term_id'];
        } else {
            $place_id = 0;
        }
        // now get the walk descriptions file
        $walk_description_name = $csv_values[17];
        $pdf_pattern = '%.pdf';   // can't put % into the sql statement of a prepare function, ie can't put "... and guid like '%.pdf'"
        $wpdb->flush();
        $walk_description_attachment_id = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s and post_type='attachment' and guid like %s", $walk_description_name, $pdf_pattern));
        if (!$walk_description_attachment_id) {
            _log("Can't get walk description attachment id\n");
            return "Can't get walk description attachment id<br>";
        }
        _log($walk_description_attachment_id);
        // now the gpx file (if it exists)
        $gpx_file_name = $csv_values[18];   
        $gpx_pattern = '%.gpx';
        $gpx_attachment_id = 0;  // default value
        $wpdb->flush();
        $gpx_attachment_id = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s and post_type='attachment' and guid like %s", $gpx_file_name, $gpx_pattern));
        _log($gpx_attachment_id);
        // now find any photos
        $photo_attachment_ids = array();
        for ($i = 0; $i < 6; $i++) {
            $photo_file_name = $csv_values[19 + 2 * $i];
            if (strlen($photo_file_name) > 0) {
                $wpdb->flush();
                $photo_attachment_id = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid like %s and post_type='attachment'", '%/' . $photo_file_name));
                if (!$photo_attachment_id) {
                    $wpdb->print_error();
                }
                $photo_attachment_ids[$i] = $photo_attachment_id[0];
            }
        }
        _log($photo_attachment_ids);
        // now prepare the array of data to be inserted into the pod
        $data = array(
            'post_title' => $csv_values[0],
            'post_content' => $csv_values[1],
            'town' => $csv_values[2],
            'walk_area' => $area_id,
            'walk_author' => $csv_values[4],
            'last_updated' => $csv_values[5],
            'distance' => $csv_values[6],
            'ascent' => $csv_values[7],
            'walking_time' => $csv_values[8],
            'total_time' => $csv_values[9],
            'walk_grade' => $grade_id,
            'difficulty' => $csv_values[11],
            'start_point_latitude' => $csv_values[12],
            'start_point_longitude' => $csv_values[13],
            'walk_place' => $place_id,
            'directions_to_start' => $csv_values[15],
            'restrictions' => $csv_values[16],
            'walk_description_file' => $walk_description_attachment_id,
            'gps_track_file' => $gpx_attachment_id,
            'photo' => $photo_attachment_ids,
            'post_status' => 'publish'
        );
        _log($data);
        $data_id = $pod->add($data);
        _log('Pod id after add: ' . $data_id);
        $walks_imported += 1;
        $csv_values = fgetcsv($csv_file, 0, '|');
    }
    fclose($csv_file);
    return $walks_imported;
}

/* 
 * This functionality is no longer needed
 * It was written when the approach of using a Pods Advanced Content Type was considered, in order to improve performance.
 *

function import_hikes($fname) {
    // some file operations in this function will throw an exception if an error occurs
    // however this is ok, because it's just used in the migration process
    $csv_fname = WP_PLUGIN_URL .'/wis-maps/' . $fname; 
    $csv_file = fopen($csv_fname, "r"); 
    $csv_values = fgetcsv($csv_file, 0, '|');
    $pod = pods('hike');
    $hikes_imported = 0;
    global $wpdb;   // needed for doing the queries into the wp database
    while ($csv_values) {
        _log($csv_values);
        // Most fields will go straight into the pod, but we need to specify the ids for:
        //    - custom taxonomies - grade and area
        //    - files - walk description file, gpx file (if it exists) and photos (if they exist)
        // first get the area id
        $area = pods('area');
        $params = array('where' => "name = '$csv_values[3]'");
        $area->find($params);
        $result = $area->fetch();
        $area_id = $result['term_id'];
        _log("Area id is " . $area_id);
        // now get the grade id
        $grade = pods('grade');
        $params = array('where' => "name = '$csv_values[6]'");
        $grade->find($params);
        $result = $grade->fetch();
        $grade_id = $result['term_id'];
        _log("Grade id is " . $grade_id);
        // Map the CBMW grade to the difficulty (1-5, 1 being easy)
        $cbmw_grade = strtoupper($csv_values[6]);
        if ($cbmw_grade[0] == "E") {
            $difficulty = 1;
        } else if (substr($cbmw_grade,0,2) == "M/") {
            $difficulty = 2;
        } else if (substr($cbmw_grade,0,2) == "MS") {
            $difficulty = 3;
            } else if ($cbmw_grade[0] == "S") {
            $difficulty = 4;
            } else if ($cbmw_grade[0] == "V") {
            $difficulty = 5;
        } else {
            $difficulty = 0;
        }
        // now get the walk descriptions file
        $walk_description_filename = "wd0" . $csv_values[0];
        $walk_description_attachment_id = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s and post_type='attachment'", $walk_description_filename));
        if (!$walk_description_attachment_id) {
            _log("Can't get walk description attachment id\n");
            return "Can't get walk description attachment id\n";
        }
        _log($walk_description_attachment_id);
        // now the gpx file (if it exists)
        $gpx_filename = "gps0" . $csv_values[0];    
        $gpx_attachment_id = 0;  // default value
        $gpx_attachment_id = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s and post_type='attachment'", $gpx_filename));
        _log($gpx_attachment_id);
        // now find any photos
        $photo_filename = "image0" . $csv_values[0] . "-%";
        $photo_attachment_ids = 0;
        $photo_attachment_ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title like %s and post_type='attachment'", $photo_filename));
        _log($photo_attachment_ids);
        if (count($photo_attachment_ids) > 6) {
            _log("too many photos!!");
            }
        // now prepare the array of data to be inserted into the pod
        $data = array(
            'name' => $csv_values[1],
            'description' => $csv_values[14],
            'town' => $csv_values[2],
            'walk_area' => $area_id,
            'walk_author' => $csv_values[4],
            'last_updated' => $csv_values[5],
            'distance' => $csv_values[7],
            'ascent' => $csv_values[8],
            'walking_time' => $csv_values[9],
            'total_time' => $csv_values[10],
            'walk_grade' => $grade_id,
            'difficulty' => $difficulty,
            'start_point_latitude' => $csv_values[11],
            'start_point_longitude' => $csv_values[12],
            'directions_to_start' => $csv_values[13],
            'restrictions' => $csv_values[15],
            'walk_description_file' => $walk_description_attachment_id,
            'gps_track_file' => $gpx_attachment_id,
            'photo' => $photo_attachment_ids,
            'post_status' => 'publish'
        );
        _log($data);
        $data_id = $pod->add($data);
        _log('Pod id after add: ' . $data_id);
        $hikes_imported += 1;
        $csv_values = fgetcsv($csv_file, 0, '|');
    }
    fclose($csv_file);
    return $hikes_imported;
}
*/

function import_areas($fname) {
    $csv_fname = WP_PLUGIN_URL .'/wis-maps/' . $fname; 
    $csv_file = fopen($csv_fname, "r"); 
    $csv_values = fgetcsv($csv_file, 0, '|');
    $pod = pods('area');
    $areas_imported = 0;
    while ($csv_values) {
        _log($csv_values);
        // if the parent field isn't empty, then get the id of that parent area
        if ($csv_values[1] != '') {
            $parents = pods ('area');
            $params = array('where' => "name = '$csv_values[1]'");
            $parents->find($params);
            $result = $parents->fetch();
            $parent_id = $result['term_id'];
        } else {
            $parent_id = 0;
        }
        // now set up the data for the Pod
        $data = array(
            'name' => $csv_values[0],
            'parent' => $parent_id, 
            'map_centre_latitude' => $csv_values[2],
            'map_centre_longitude' => $csv_values[3],
            'map_zoom' => $csv_values[4],
            'polygon' => $csv_values[5],
        );
        _log($data);
        $data_id = $pod->add($data);
        _log('Pod id after add: ' . $data_id);
        $areas_imported += 1;
        $csv_values = fgetcsv($csv_file, 0, '|');
    }
    fclose($csv_file);
    return $areas_imported;
}

function import_grades($fname) { 
    $csv_fname = WP_PLUGIN_URL .'/wis-maps/' . $fname; 
    $csv_file = fopen($csv_fname, "r"); 
    $csv_values = fgetcsv($csv_file, 0, '|');
    $pod = pods('grade');
    $grades_imported = 0;
    while ($csv_values) {
        _log($csv_values);
        $data = array(
            'name' => $csv_values[0],
            'description' => $csv_values[1],
        );
        _log($data);
        $data_id = $pod->add($data);
        _log('Pod id after add: ' . $data_id);
        $grades_imported += 1;
        $csv_values = fgetcsv($csv_file, 0, '|');
    }
    fclose($csv_file);
    return $grades_imported; 
}

function import_places($fname) {
    $csv_fname = WP_PLUGIN_URL .'/wis-maps/' . $fname; 
    $csv_file = fopen($csv_fname, "r"); 
    $csv_values = fgetcsv($csv_file, 0, '|');
    $pod = pods('place');
    $places_imported = 0;
    while ($csv_values) {
        _log($csv_values);
        $area = pods('area');
        $params = array('where' => "name = '$csv_values[2]'");
        $area->find($params);
        $result = $area->fetch();
        $area_id = $result['term_id'];
        _log("Area id is " . $area_id);
        $data = array(
            'name' => $csv_values[0],
            'description' => $csv_values[1],
            'place_area' => $area_id,
            'latitude' => $csv_values[3],
            'longitude' => $csv_values[4],
        );
        _log($data);
        $data_id = $pod->add($data);
        _log('Pod id after add: ' . $data_id);
        $places_imported += 1;
        $csv_values = fgetcsv($csv_file, 0, '|');
    }
    fclose($csv_file);
    return $places_imported;
}

/** 
 *  The following was just used during initial migration from the previous site, to set the title of the file to the filename, rather than the windows image file "title" metadata
 *  Comment out the add_action except when migrating the images, because this might have nasty side effects of changing the title of other images being uploaded
 *  This functionality isn't needed for images which are downloaded from the wordpress instance and uploaded again. 
 */

//add_action('added_post_meta', 'fixup_image_metadata', 10, 4);
/*
function fixup_image_metadata($meta_id, $post_id, $meta_key, $meta_value) {

    // _wp_attachment_metadata added
    //_log("In fixup_image_metadata with key ");
    //_log($meta_key);
    //_log(" and value ");
    //_log($meta_value);
    global $wpdb;
    
    if($meta_key === '_wp_attachment_metadata') {
        $image_metadata = $meta_value['image_meta'];
        //_log("image metadata");
        //_log($image_metadata);
        $image_title = $image_metadata['title'];
        //_log("image_meta title is " . $image_title);
        //_log("image file is " . $meta_value['file']);
        $fname = $meta_value['file'];
        $last_slash = strrpos($fname,"/");
        if ($last_slash !== false) {
            $fname = substr($fname, $last_slash + 1);
            //_log("filename is " . $fname);
            }
        _log("Processing filename " . $fname . " with title " . $image_title);
        if ($image_title == '') return;
        $post_id = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_title = %s", $image_title));
        _log($post_id);
        $post_id_len = count($post_id);
        if ($post_id_len != 1) {  // more than 1 photo can have the same caption ! Amend this one by hand
            _log("$post_id has length " . count($post_id_len) . "Need to change this title manually");
            return;
        }
        if (is_wp_error($post_id)) {
            _log("Got an error with $wpdb get_col");
            $errors = $post_id->get_error_messages();
            foreach ($errors as $error) {
                _log($error);
            }
        } else {
            _log("updating the record");
            $updated_post_record = array('ID' => $post_id[0], 'post_title' => $fname);
            $post_id = wp_update_post($updated_post_record);
            if (is_wp_error($post_id)) {
                $errors = $post_id->get_error_messages();
                foreach ($errors as $error) {
                    _log($error);
                }
            }
        }

    }
}
*/