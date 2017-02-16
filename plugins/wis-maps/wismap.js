google.maps.event.addDomListener( window, 'load', displayMapsAndLists );

/* This is the js code which handles display of the maps and list on the website.
 * The maps functionality uses the Google maps api, and just pulls in the map tiles for maps (eg openstreetmap) other than Google maps
 * The list functionality uses the datatables.net library.
 *
 * The functionality supports only up to 2 maps on the page (needed for the front page where you have a Spain map and a Canary Islands map),
 * but these must be "country" maps - not 2 area maps on the same page.
 * In addition you can have 1 list section for the same area - the area map and list uses the same walk information passed from the php code.
 *
 * The maps and list are inserted into <div> sections which are output by the server php code.
 * 
 * The js relies on information which is prepared in the php code in the server, and then passed to the browser (via the wordpress wp_localize_script function).
 * There is some defensive code below which checks that some of these variables are set, rather than causing a js crash.
 *
 * The variables passed from the php are:
 *
 * nmaps - set to either 1 or 2, and specifies how many maps will be shown on the page. 
 * map_country_or_area1 - text string set to 'country' if first map is a country map, or 'area' if it's an area map, or 'walk' if it's a single walk
 * map_country_or_area2 - similarly for second map
 * latitude1, longitude1, zoom1, maptype1 - details for first map - centre lat/long, zoom level, and map type (eg google map / open street map / open cycle map etc)
 * latitude2, longitude2, zoom2, maptype2 - similarly for the second map
 *
 * areainfo1 - a JSON-encoded array of information for the first country map, with details of the various areas to be shown on it. Each entry in the array comprises:
 *   - the name of the area (to be shown in the info box)
 *   - the number of walks in that area (to be shown in the info box)
 *   - the web page of that area (to be shown in the info box, and providing a link to that page)
 *   - the coordinates of the vertices of the polygon representing that walking area on the map. If number of walks > 0 the area is shown in green, otherwise red
 * areainfo2 - an identically structured array of area information for the second country map
 *
 * walkinfo - a JSON-encoded array of information for walks in the selected area. This isn't suffixed with 1 or 2, so there's only 1 area map per page. 
 *   Each array element comprises:
 *    - name - the name of the walk
 *    - lat, lng - the latitude and longitude of the start place for that walk
 *    - town, grade, distance, ascent, walk_time - info associated with the walk
 *    - difficulty - the difficulty - used for selecting the colour of the marker and gpx route traced on the map
 *    - url - the url of that walk
 *    - gpx - the url of the gpx file for that walk (won't be set if there's no gpx file for this walk)
 *    - placeid - the id of the starting place (won't be set if a standard starting place isn't set for this walk)
 * The walkinfo is also set for a map of a single walk.
 *
 * placeinfo - a JSON-encoded array of information relating to standard starting places in the selected area. These result in numbered icons being shown on the map, where
 *    there are a number of walks starting at that place. If there is only 1 walk starting there (which may often happen if the search facility returns only 1 of those walks) 
 *    then an ordinary marker pin is shown instead. 
 *    Each array element comprises:
 *    - lat, lng - for placing the marker pin on the map
 *    - name - shown when a mouse hovers over the marker pin
 * If it's a single walk, then placeinfo isn't set.
 *
 * If the map is for a single walk then we use ajax to get the GPS coords immediately and store them in case the user wants to see the map
 *
 * For an area map we get the GPS coords only when a user clicks on a walk marker pin
 *
 * list - set if a list is required, undefined if not.
 * pagelength - for a list, the number of walks to show on one page (used with pagination)
*/
    
var maps = [];
var infoWindow;   // this is just for the area map - for country map infoWindow is passed through as parameter
var markers = [];
var polylines = [];  
var walksWithPlaces = [];   // an array to hold walks which have the 'place' field set
var singleWalkRoute = "";   // an array to hold the GPS coordinates for a single route. 

function displayMapsAndLists() {
    //console.log(typeof list);
    //console.log(typeof nmaps);
    //console.log(iconfolder);
    //if (typeof placeinfo !== 'undefined') console.log(placeinfo);
    //if (typeof walkinfo !== 'undefined') console.log(walkinfo);
    if (typeof nmaps !== 'undefined') {
        if (map_country_or_area1 == 'country') {
            displayCountryMap('wismap1', latitude1, longitude1, zoom1, maptype1, JSON.parse(areainfo1));
        } else if (map_country_or_area1 == 'area') {
            displayAreaMap('wismap1', latitude1, longitude1, zoom1, maptype1, JSON.parse(walkinfo), JSON.parse(placeinfo));
        } else if (map_country_or_area1 == 'walk') {
            getRoute(JSON.parse(walkinfo)[0].gpx);
        }
        if (nmaps == 2) {
            if (map_country_or_area2 == 'country') {
                displayCountryMap('wismap2', latitude2, longitude2, zoom2, maptype2, JSON.parse(areainfo2));
            } else if (map_country_or_area2 == 'area') {
                displayAreaMap('wismap2', latitude2, longitude2, zoom2, maptype2, JSON.parse(walkinfo), JSON.parse(placeinfo));
            }
        }
    }
    if (typeof list !== 'undefined') {
        buildTable(JSON.parse(walkinfo));
    }
}

function getRoute(gpx) {
    jQuery.ajax({
        type: "get",
        dataType: "json",
        url: wisAjax.ajaxurl,
        data: {action: "get_track", fname: gpx},
        success: function(response) {
            if (response.type == "success") {
                //console.log(response.track);
                singleWalkRoute = response.track;   // store in case the user wants this track again
            } else {
                console.log("ajax response.type wasn't success");
            }
        }
    });
}

function map_selected(maptype) {
// utility function for returning the map type to pass to the google maps constructor, based on the value of the maptype variable sent down from php.
    //console.log('maptype is ' + maptype);
    switch(maptype) {
    case "google maps":
        return google.maps.MapTypeId.ROADMAP;
    case "open cycle map":
        return "OCM";
    case "open street map":
        return "OSM";
    case "thunderforest landscape":
        return "Land";
    default:
        return "Land";
    } 
}

function displayMap(map_element, latitude, longitude, zoom, maptype) {
// draws the map, of type maptype, centred on lat/long with zoom factor, and including the various types of maps supported in top left corner   
    //console.log('displaying country map centred on ' + latitude + ', ' + longitude + ', with zoom ' + zoom);
    var mapTypeIds = [];
        for(var type in google.maps.MapTypeId) {
            //console.log('map type includes ' + type);
            mapTypeIds.push(google.maps.MapTypeId[type]);
        }
    mapTypeIds.push("OCM");
    mapTypeIds.push("OSM");
    mapTypeIds.push("Land");
    var map = new google.maps.Map(document.getElementById(map_element), {
        center: {lat: Number(latitude), lng: Number(longitude)}, zoom: Number(zoom), mapTypeId: map_selected(maptype),
    mapTypeControlOptions: { mapTypeIds: mapTypeIds }, streetViewControl: false, scrollwheel: false, gestureHandling: 'cooperative' }
    );
    maps.push(map);
    map.mapTypes.set("OSM", new google.maps.ImageMapType({
            getTileUrl: function(coord, zoom) {
                //console.log ('in OSM getTileUrl with coord ' + coord.x + ', ' + coord.y + ' and zoom ' + zoom);
                return "http://tile.openstreetmap.org/" + zoom + "/" + coord.x + "/" + coord.y + ".png";
            },
            tileSize: new google.maps.Size(256, 256),
            name: "OSM",
            alt: "Show OpenStreetMap",
            maxZoom: 18
        }));
    map.mapTypes.set("OCM", new google.maps.ImageMapType({
        getTileUrl: function(coord, zoom) {
                //console.log ('in OCM getTileUrl with coord ' + coord.x + ', ' + coord.y + ' and zoom ' + zoom);
                return "https://tile.thunderforest.com/cycle/" + zoom + "/" + coord.x + "/" + coord.y + ".png?apikey=" + thunderforest_API_key;
            },
            tileSize: new google.maps.Size(256, 256),
            name: "OCM",
            alt: "Show OpenCycleMap",
            maxZoom: 18
        }));    
    map.mapTypes.set("Land", new google.maps.ImageMapType({
        getTileUrl: function(coord, zoom) {
                //console.log ('in TF Land getTileUrl with coord ' + coord.x + ', ' + coord.y + ' and zoom ' + zoom);
                return "https://tile.thunderforest.com/landscape/" + zoom + "/" + coord.x + "/" + coord.y + ".png?apikey=" + thunderforest_API_key;
            },
            tileSize: new google.maps.Size(256, 256),
            name: "Land",
            alt: "Show Thunderforest Landscape",
            maxZoom: 18
        }));      
    return map;
}

/**
 *
 * Country maps with polygons indicating walking areas
 *
 */


function displayCountryMap(map_element, latitude, longitude, zoom, maptype, areainfo) {
// draws the map of Peninsular Spain / Canary Islands, adding the walking areas
    //console.log('Areainfo: ' + areainfo);
    var map = displayMap(map_element, latitude, longitude, zoom, maptype);
    var infoWindow = new google.maps.InfoWindow;
    for (var i=0; i<areainfo.length; i++) {
        var a1 = areainfo[0];
        var polygon = areainfo[i].polygon_coords;
        var polygonCoords = [];
        var coordPair;
        for (var j=0; j< polygon.length; j++) {
            coordPair = {lat: Number(polygon[j][0]), lng: Number(polygon[j][1])}; 
            polygonCoords.push(coordPair);
        }
        var area = new google.maps.Polygon({
            paths: polygonCoords,
            strokeColor: (areainfo[i]['nwalks'] == 0 ? '#FF0000' : '#00FF00'),
            strokeOpacity: 0.8,
            strokeWeight: 3,
            });
        area.setMap(map);
        addPolygonListener (area, infoWindow, map, areainfo[i]);  // use a closure to get the right areainfo record
    }
}

function addPolygonListener (area, infoWindow, map, areainfoElement) {
// adds the polygon representing the walking area
    area.addListener('click', function (e) {
        var contentString = areainfoElement.name + ' (' + areainfoElement.nwalks + " walk" + (areainfoElement.nwalks == 1 ? ')' : 's)');
        if (areainfoElement.nwalks > 0) {
            contentString += '<br><a href="' + areainfoElement.webpage + '">Walks in ' + areainfoElement.name + '</a>';
        } else if (areainfoElement.webpage.length > 0) {
            contentString += '<br><a href="' + areainfoElement.webpage + '">Walking in ' + areainfoElement.name + '</a>';
        }
        infoWindow.setContent(contentString);
        infoWindow.setPosition(e.latLng);
        infoWindow.open(map);
        });
}

/**
 *
 * Area maps with markers indicating walks
 *
 */


function displayAreaMap(map_element, latitude, longitude, zoom, maptype, walkinfos, placeinfos) {
// displays an area map, with marker pins for the walks in that area.
    var map = displayMap(map_element, latitude, longitude, zoom, maptype);
    infoWindow = new google.maps.InfoWindow;
    //console.log(walkinfo);
    //console.log(placeinfo);
    for (var i = 0; i < walkinfos.length; i++) {
        walkinfos[i].id = i;   // give each walk a unique id, and store it with the other walk info
        if (walkinfos[i].placeid) {
            addWalksWithPlace(walkinfos[i]);
        } else {
            addMarker(map, walkinfos[i]);
        }
    }
    processWalksWithPlace(map, placeinfos);
}

function addMarker(map, walkinfo) {
// add a marker pin on the map, indicating the start of a walk
// this function isn't used for walks (with offset markers) which are expanded from a numbered icon
    var colour = difficultyColour(walkinfo.difficulty, false);
    var marker = new google.maps.Marker({
        position: {lat: Number(walkinfo.lat), lng: Number(walkinfo.lng)},
        title: walkinfo.name,
        map: map,
        icon: pinSymbol(colour),
        id: walkinfo.id,
        optimized: false,  // needed for zIndex to work
    });
    markers[walkinfo.id] = marker;
    addMarkerListener(marker, infoWindow, map, walkinfo);
}

function addWalksWithPlace (walkinfo) {
// this function is called if a walk has got the 'place' field set
// It builds up an associative array called walksWithPlaces where:
//    - the key is the name of the place
//    - the value is an array of walks which have that place set 
    //console.log('in addWalksWithPlace with place ' + walkinfo.place);
    if (typeof walksWithPlaces[walkinfo.placeid] === 'undefined' ) {   // haven't got any walks stored for this place yet - initialise the array
        walksWithPlaces[walkinfo.placeid] = [];
    }
    walksWithPlaces[walkinfo.placeid].push(walkinfo);
}

function processWalksWithPlace(map, placeinfos) {
// This function processes the walksWithPlaces associative array
// For each entry in the associative array it adds a numbered marker icon to the map, indicating the number of walks starting there
// (If there's just 1 walk it just puts a normal marker pin there)
// Then it sets up the listeners to show/hide the individual walks and their (offset) marker pins
    //console.log(walksWithPlaces);
    //console.log(Object.keys(walksWithPlaces));
    for (var placeid in walksWithPlaces) {
        //console.log('number of walks in place id ' + placeid + ' is ' + walksWithPlaces[placeid].length);
        if (walksWithPlaces[placeid].length == 1) {
            // just put a normal marker pin, but get the lat/long off the place record (rather than the walk record)
            walksWithPlaces[placeid][0].lat = placeinfos[placeid].lat;
            walksWithPlaces[placeid][0].lng = placeinfos[placeid].lng;
            addMarker(map, walksWithPlaces[placeid][0]);
        } else {
            // console.log("adding marker for place id " + placeid + " at " + placeinfos[placeid].lat + ", " + placeinfos[placeid].lat);
            var marker = new google.maps.Marker({
                position: {lat: Number(placeinfos[placeid].lat), lng: Number(placeinfos[placeid].lng)},
                // if the place starts with "XX " (area plus space) then skip over that for the marker title (shown on hover)
                title: (placeinfos[placeid].name[2] == " ") ? (placeinfos[placeid].name).substring(3) + ' Click to expand / hide walks here' : 
                                                                placeinfos[placeid].name + ' Click to expand / hide walks here',
                map: map,
                icon: iconfolder + 'number_' + walksWithPlaces[placeid].length + '.png',
                id: walkinfo.id,
                optimized: false,  // needed for zIndex to work
                zIndex: google.maps.Marker.MAX_ZINDEX + walksWithPlaces[placeid].length,
                visibilityState: 0
            });
            addNumberIconListener(map, marker, placeid, placeinfos[placeid]);
        }
    }
}

function addOffsetMarker(map, position, walkinfo, angle, zIndex) {
// adds a marker pin for the case where a numbered icon is expanded to show the n walks starting there.
    //console.log('Adding offset marker for ' + walkinfo.name + ' id: ' + walkinfo.id + ' place id: ' + walkinfo.placeid + ' at ' + position.lat + ', ' + position.lng + ' and angle ' + angle + ' and zindex ' + zIndex);
    var colour = difficultyColour(walkinfo.difficulty, false);
    var marker = new google.maps.Marker({
        position: position,
        title: walkinfo.name,
        map: map,
        icon: offsetPinSymbol(angle, difficultyColour (walkinfo.difficulty, false)),
        id: walkinfo.id,
        optimized: false,
        zIndex: google.maps.Marker.MAX_ZINDEX + zIndex
    });
    markers[walkinfo.id] = marker; 
    addMarkerListener(marker, infoWindow, map, walkinfo);
}

function addMarkerListener (marker, infoWindow, map, walkinfoElement) {
// listener for a marker pin being clicked.
// In response this function sets the info window (based on info passed down from the server)
// and if there's a gpx file it requests that via ajax, and draws the trail on the map
    marker.addListener('click', function (e) {
        var needToDownloadGPX = false;   // whether we need to download the gpx track file or not
        var contentString = '<strong>' + walkinfoElement.name + '</strong>';
        contentString += " near " + walkinfoElement.town + '<br>';
        contentString += "Grade: " + walkinfoElement.grade + ", Length: " + walkinfoElement.distance + ", Ascent: " + 
                walkinfoElement.ascent + ", Walk Time: " + walkinfoElement.walk_time + "hrs";
        contentString += '<br><strong><a href="' + walkinfoElement.url + '" target="_blank">Go to walk details</a></strong>';
        if (marker.hasOwnProperty("track")) {   // the web user has already downloaded the gpx track for this walk
            drawRoute(map, marker.id, marker.track, difficultyColour(walkinfoElement.difficulty), true);
        } else {
            if (walkinfoElement.gpx) {  // ie if there's a gpx file for this walk
                contentString += "<br>Retrieving walk GPS data ...";
                needToDownloadGPX = true; 
            } else {
                contentString += "<br>No GPS data available";
            }
        }
        infoWindow.setContent(contentString);
        infoWindow.setPosition(e.latLng);
        infoWindow.open(map);
        //console.log(walkinfoElement.gpx);
        if (needToDownloadGPX) {  
            jQuery.ajax({
                type: "get",
                dataType: "json",
                url: wisAjax.ajaxurl,
                data: {action: "get_track", fname: walkinfoElement.gpx},
                success: function(response) {
                    if (response.type == "success") {
                        //console.log("marker id is " + marker.id);
                        //console.log(response.track);
                        infoWindow.setContent(contentString + "Done! Click (when cursor changes) on the route to hide it");
                        marker.track = response.track;   // store in case the user wants this track again
                        drawRoute(map, marker.id, response.track, difficultyColour(walkinfoElement.difficulty), true);
                    } else {
                        console.log("ajax response.type wasn't success");
                    }
                }
            });
        }
    });
    marker.addListener('mouseover', function() {
            var symbol = this.getIcon(); 
            symbol.fillColor = difficultyColour (walkinfoElement.difficulty, true);
            this.setIcon(symbol);
        });
    marker.addListener('mouseout', function() {
            var symbol = this.getIcon(); 
            symbol.fillColor = difficultyColour (walkinfoElement.difficulty, false);
            this.setIcon(symbol);
        });
}

function addNumberIconListener(map, marker, placeid, placeinfo) {
    marker.addListener('click', function (e) { 
        var numWalksAtThisPlace = walksWithPlaces[placeid].length;
        // If this is the first time that this marker has been clicked, then create all the subsidiary offset markers for the walks starting here
        // On subsequent clicks of this marker just hide and reveal these marker pins (via setVisible call), rather than removing and recreating them
        // To manage this use a variable visibilityState.
        if (this.visibilityState == 0) {
            this.visibilityState = 1;
            for (var i = 0; i < numWalksAtThisPlace; i++) {  
                var angle = 2.0*i*Math.PI / numWalksAtThisPlace - 0.5 * Math.PI;  
                var zIndex = (i > 0.5 * numWalksAtThisPlace) ? (numWalksAtThisPlace - i + 1) : i + 1;
                addOffsetMarker(map, {lat: Number(placeinfo.lat), lng: Number(placeinfo.lng)}, walksWithPlaces[placeid][i], angle, zIndex);
            }
        } else if (this.visibilityState == 1) {
            this.visibilityState = 2;
            for (var i = 0; i < numWalksAtThisPlace; i++) {
                markers[walksWithPlaces[placeid][i].id].setVisible(false);
            }
        } else if (this.visibilityState == 2) {
            this.visibilityState = 1;
            for (var i = 0; i < numWalksAtThisPlace; i++) {
                markers[walksWithPlaces[placeid][i].id].setVisible(true);
            }
        }
    });
}

function displaySingleWalk(event) {
    //console.log('in displaySingleWalk');
    //console.log(walkinfo);
    jQuery("#wismap1").addClass("wismap-area");  // make a big space now on the page for the map
    var thisWalkinfo = JSON.parse(walkinfo)[0];
    var map = displayMap('wismap1', latitude1, longitude1, zoom1, maptype1);
    var colour = difficultyColour(thisWalkinfo.difficulty, false);
    var marker = new google.maps.Marker({
        position: {lat: Number(thisWalkinfo.lat), lng: Number(thisWalkinfo.lng)},
        title: thisWalkinfo.name,
        map: map,
        icon: pinSymbol(colour),
        optimized: false,  // needed for zIndex to work
    });
    if (singleWalkRoute) {
        //console.log('found the route');
        drawRoute(map, 0, singleWalkRoute, colour, false);
    } else {
        //console.log('route not downloaded yet');
        var attempts = 1;
        var waitForAjax = setInterval(function() {
            //console.log('waiting for ajax');
            if (singleWalkRoute) {
                drawRoute(map, 0, singleWalkRoute, colour, false);
                clearInterval(waitForAjax);
            } else {
                if (attempts > 20) {  // give up!
                    clearInterval(waitForAjax);
                    }
                attempts += 1;
            }
        }, 1000);
    }
    event.preventDefault();   // stop the browser going to the url of the link that's just been clicked
    return false; 
}

function pinSymbol(colour) { // see http://stackoverflow.com/questions/7095574/google-maps-api-3-custom-marker-color-for-default-dot-marker 
// SVG for a marker pin, with the colour being passed in as a parameter
    return {
        // path: 'M 0,0 C -2,-20 -10,-22 -10,-30 A 10,10 0 1,1 10,-30 C 10,-22 2,-20 0,0 z M -2,-30 a 2,2 0 1,1 4,0 2,2 0 1,1 -4,0',
        path: 'M 0,0 c -2,-20 -10,-22 -10,-30 a 10,10 0 1,1 20,0 c 0,8 -8,10 -10,30 z',  // leaving out little circle in the middle
        fillColor: colour,
        fillOpacity: 1,
        strokeColor: '#000',
        strokeWeight: 2,
        scale: 1,
   };
}

function offsetPinSymbol(angle, colour) {
// SVG for a marker pin when a number of walks are expanded, with (x,y) being the position of the bottom of the pin relative to the bottom of the number icon
    var x = Math.round(50.0 * Math.cos(angle));
    var y = Math.round(50.0 * Math.sin(angle));
    if (Math.abs(angle - Math.PI/2.0) > Math.atan(1.0/3.0)) {
        //console.log('normal processing for angle ' + angle);
        return {
            // path: 'M 0,0 C -2,-20 -10,-22 -10,-30 A 10,10 0 1,1 10,-30 C 10,-22 2,-20 0,0 z M -2,-30 a 2,2 0 1,1 4,0 2,2 0 1,1 -4,0',
            // path: 'M 0,0 c -2,-20 -10,-22 -10,-30 a 10,10 0 1,1 20,10 c 0,8 -8,10 0,0 z',
            path: 'M 0,0 L ' + x + ',' + y + ' c -2,-20 -10,-22 -10,-30 a 10,10 0 1,1 20,0 c 0,8 -8,10 -10,30 z',
            fillColor: colour,
            fillOpacity: 1,
            strokeColor: '#000',
            strokeWeight: 2,
            scale: 1,
        };
    } else {
        // Put a gap in the line from the numbered icon to the base of the marker pin - otherwise you get a black line over the pin
        //console.log('complex processing for angle ' + angle);
        var c = Math.cos(Math.PI/2.0 - angle);
        var s = Math.sin(Math.PI/2.0 - angle);
        var l1 = Math.round(10.0 * s);
        var l2 = Math.round(10.0 * c);
        var m1 = x - l1;
        var m2 = y - l2;
        //console.log ('vars l1=' + l1 + ', l2=' + l2 + ', m1=' + m1 + ', m2=' + m2);
        return {
            // path: 'M 0,0 C -2,-20 -10,-22 -10,-30 A 10,10 0 1,1 10,-30 C 10,-22 2,-20 0,0 z M -2,-30 a 2,2 0 1,1 4,0 2,2 0 1,1 -4,0',
            // path: 'M 0,0 c -2,-20 -10,-22 -10,-30 a 10,10 0 1,1 20,10 c 0,8 -8,10 0,0 z',
            path: 'M 0,0 L ' + l1 + ',' + l2 + ' m ' + m1 + ',' + m2 + ' c -2,-20 -10,-22 -10,-30 a 10,10 0 1,1 20,0 c 0,8 -8,10 -10,30 z',
            fillColor: colour,
            fillOpacity: 1,
            strokeColor: '#000',
            strokeWeight: 2,
            scale: 1,
        };
    }
}

function difficultyColour (difficulty, highlighted) {
// returns a colour based on difficulty of a walk.
// the highlighted shade (for when the mouse hovers over a pin) is slightly lighter in colour
    switch(Number(difficulty)) {
        case 1:
            var colour = (highlighted ? "#00FF00" : "#32CD32");
            break;
        case 2:
            var colour = (highlighted ? "#FFFF00" : "#FFD700");
            break;
        case 3:
            var colour = (highlighted ? "#FF8000" : "#FF6000");
            break;
        case 4:
            var colour = (highlighted ? "#FF0000" : "#DC2020");
            break;
        case 5:
            var colour = (highlighted ? "#696969" : "#000000");
            break;
        default:
            var colour = "#0000FF"
    }     
    return colour; 

}

function drawRoute(map, id, coords, pathColour, allowRouteClick) {
// draws the walking trail on the map
// create the polyline the first time the walk marker pin is clicked
// on subsequent clicks on the walking route polyline just hide / show the route. 
    if (typeof polylines[id] === 'undefined') {
        // this route doesn't exist yet on the map
        //console.log('creating new polyline');
        var coord;
        route = [];
        var arrow = { 
            path: google.maps.SymbolPath.FORWARD_OPEN_ARROW,  // this is for showing direction of travel
            strokeColor: 'black',
            strokeWeight: 1,
            scale: 2,
            };

        for (var i = 0; i <coords.length; i++) { 
            coord = { lat: parseFloat(coords[i].v), lng: parseFloat(coords[i].h) };
            route.push(coord);
        }
        polyline = new google.maps.Polyline({
            path: route,
            geodesic: true,
            icons: [ { icon: arrow, repeat: '50px'} ],
            strokeColor: pathColour,
            strokeOpacity: 0.5,
            strokeWeight: 5,
            visible: true    
        });
        if (allowRouteClick) {
            polyline.addListener('click', function(e) {
                //console.log('hiding polyline ');
                this.setVisible(false);
            });
        }
        polylines[id] = polyline; 
        polyline.setMap(map);
    } else {
        // this polyline is already defined - set it visible if it's not visible
        //console.log('reusing existing polyline');
        polylines[id].setVisible(true);
    }

}

function buildTable(walks) {
// for displaying a table of walks for that area
// build up the table of walks and pass it all to Datatables for handling all the dynamic filter / sort aspects.
    var table = '<table id="wismap-table" class="display" width="100%" cellspacing="0"><thead>';
    table += '<tr><th>Title</th><th>Nearest Town</th><th>Grade</th><th>Length (km)</th><th>Ascent (m)</th><th>Duration (hours)</th></tr>';
    table += '<tbody>';
    for (var i = 0; i < walks.length; i++) {
        table += '<tr><td><a href="' + walks[i].url + '" target="_blank">' + walks[i].name + '</a></td><td>' + walks[i].town + '</td><td class="cbmw-' + walks[i].difficulty + '">' +
            walks[i].grade + '</td><td>' + walks[i].distance +
            '</td><td>' + walks[i].ascent + '</td><td>' + walks[i].walk_time + '</td></tr>';
    }
    table += '</tbody></table>';
    //console.log(table);
    document.getElementById('wismap-list').innerHTML = table;

    pagelen = parseInt(pagelength) || 10;
    var tab = jQuery('#wismap-table').DataTable({"pageLength" : pagelen, "order": [], responsive: true});
 
    jQuery('#wismap-table tbody').on( 'click', 'tr', function () {
        if ( jQuery(this).hasClass('selected') ) {
            jQuery(this).removeClass('selected');
        }
        else {
            tab.jQuery('tr.selected').removeClass('selected');
            jQuery(this).addClass('selected');
        }
    } );
}
