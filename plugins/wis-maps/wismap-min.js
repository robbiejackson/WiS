function displayMapsAndLists(){"undefined"!=typeof nmaps&&("country"==map_country_or_area1?displayCountryMap("wismap1",latitude1,longitude1,zoom1,maptype1,JSON.parse(areainfo1)):"area"==map_country_or_area1?displayAreaMap("wismap1",latitude1,longitude1,zoom1,maptype1,JSON.parse(walkinfo),JSON.parse(placeinfo)):"walk"==map_country_or_area1&&getRoute(JSON.parse(walkinfo)[0].gpx),2==nmaps&&("country"==map_country_or_area2?displayCountryMap("wismap2",latitude2,longitude2,zoom2,maptype2,JSON.parse(areainfo2)):"area"==map_country_or_area2&&displayAreaMap("wismap2",latitude2,longitude2,zoom2,maptype2,JSON.parse(walkinfo),JSON.parse(placeinfo)))),"undefined"!=typeof list&&buildTable(JSON.parse(walkinfo))}function getRoute(a){jQuery.ajax({type:"get",dataType:"json",url:wisAjax.ajaxurl,data:{action:"get_track",fname:a},success:function(a){"success"==a.type?singleWalkRoute=a.track:console.log("ajax response.type wasn't success")}})}function map_selected(a){switch(a){case"google maps":return google.maps.MapTypeId.ROADMAP;case"open cycle map":return"OCM";case"open street map":return"OSM";case"thunderforest landscape":return"Land";default:return"Land"}}function displayMap(a,b,c,d,e){var f=[];for(var g in google.maps.MapTypeId)f.push(google.maps.MapTypeId[g]);f.push("OCM"),f.push("OSM"),f.push("Land");var h=new google.maps.Map(document.getElementById(a),{center:{lat:Number(b),lng:Number(c)},zoom:Number(d),mapTypeId:map_selected(e),mapTypeControlOptions:{mapTypeIds:f},streetViewControl:!1,scrollwheel:!1,gestureHandling:"cooperative"});return maps.push(h),h.mapTypes.set("OSM",new google.maps.ImageMapType({getTileUrl:function(a,b){return"http://tile.openstreetmap.org/"+b+"/"+a.x+"/"+a.y+".png"},tileSize:new google.maps.Size(256,256),name:"OSM",alt:"Show OpenStreetMap",maxZoom:18})),h.mapTypes.set("OCM",new google.maps.ImageMapType({getTileUrl:function(a,b){return"http://tile.opencyclemap.org/cycle/"+b+"/"+a.x+"/"+a.y+".png"},tileSize:new google.maps.Size(256,256),name:"OCM",alt:"Show OpenCycleMap",maxZoom:18})),h.mapTypes.set("Land",new google.maps.ImageMapType({getTileUrl:function(a,b){return"https://tile.thunderforest.com/landscape/"+b+"/"+a.x+"/"+a.y+".png?apikey="+thunderforest_API_key},tileSize:new google.maps.Size(256,256),name:"Land",alt:"Show Thunderforest Landscape",maxZoom:18})),h}function displayCountryMap(a,b,c,d,e,f){for(var g=displayMap(a,b,c,d,e),h=new google.maps.InfoWindow,i=0;i<f.length;i++){for(var m,k=(f[0],f[i].polygon_coords),l=[],n=0;n<k.length;n++)m={lat:Number(k[n][0]),lng:Number(k[n][1])},l.push(m);var o=new google.maps.Polygon({paths:l,strokeColor:0==f[i].nwalks?"#FF0000":"#00FF00",strokeOpacity:.8,strokeWeight:3});o.setMap(g),addPolygonListener(o,h,g,f[i])}}function addPolygonListener(a,b,c,d){a.addListener("click",function(a){var e=d.name+" ("+d.nwalks+" walk"+(1==d.nwalks?")":"s)");d.nwalks>0?e+='<br><a href="'+d.webpage+'">Walks in '+d.name+"</a>":d.webpage.length>0&&(e+='<br><a href="'+d.webpage+'">Walking in '+d.name+"</a>"),b.setContent(e),b.setPosition(a.latLng),b.open(c)})}function displayAreaMap(a,b,c,d,e,f,g){var h=displayMap(a,b,c,d,e);infoWindow=new google.maps.InfoWindow;for(var i=0;i<f.length;i++)f[i].id=i,f[i].placeid?addWalksWithPlace(f[i]):addMarker(h,f[i]);processWalksWithPlace(h,g)}function addMarker(a,b){var c=difficultyColour(b.difficulty,!1),d=new google.maps.Marker({position:{lat:Number(b.lat),lng:Number(b.lng)},title:b.name,map:a,icon:pinSymbol(c),id:b.id,optimized:!1});markers[b.id]=d,addMarkerListener(d,infoWindow,a,b)}function addWalksWithPlace(a){"undefined"==typeof walksWithPlaces[a.placeid]&&(walksWithPlaces[a.placeid]=[]),walksWithPlaces[a.placeid].push(a)}function processWalksWithPlace(a,b){for(var c in walksWithPlaces)if(1==walksWithPlaces[c].length)walksWithPlaces[c][0].lat=b[c].lat,walksWithPlaces[c][0].lng=b[c].lng,addMarker(a,walksWithPlaces[c][0]);else{var d=new google.maps.Marker({position:{lat:Number(b[c].lat),lng:Number(b[c].lng)},title:" "==b[c].name[2]?b[c].name.substring(3)+" Click to expand / hide walks here":b[c].name+" Click to expand / hide walks here",map:a,icon:iconfolder+"number_"+walksWithPlaces[c].length+".png",id:walkinfo.id,optimized:!1,zIndex:google.maps.Marker.MAX_ZINDEX+walksWithPlaces[c].length,visibilityState:0});addNumberIconListener(a,d,c,b[c])}}function addOffsetMarker(a,b,c,d,e){var g=(difficultyColour(c.difficulty,!1),new google.maps.Marker({position:b,title:c.name,map:a,icon:offsetPinSymbol(d,difficultyColour(c.difficulty,!1)),id:c.id,optimized:!1,zIndex:google.maps.Marker.MAX_ZINDEX+e}));markers[c.id]=g,addMarkerListener(g,infoWindow,a,c)}function addMarkerListener(a,b,c,d){a.addListener("click",function(e){var f=!1,g="<strong>"+d.name+"</strong>";g+=" near "+d.town+"<br>",g+="Grade: "+d.grade+", Length: "+d.distance+", Ascent: "+d.ascent+", Walk Time: "+d.walk_time+"hrs",g+='<br><strong><a href="'+d.url+'" target="_blank">Go to walk details</a></strong>',a.hasOwnProperty("track")?drawRoute(c,a.id,a.track,difficultyColour(d.difficulty),!0):d.gpx?(g+="<br>Retrieving walk GPS data ...",f=!0):g+="<br>No GPS data available",b.setContent(g),b.setPosition(e.latLng),b.open(c),f&&jQuery.ajax({type:"get",dataType:"json",url:wisAjax.ajaxurl,data:{action:"get_track",fname:d.gpx},success:function(e){"success"==e.type?(b.setContent(g+"Done! Click (when cursor changes) on the route to hide it"),a.track=e.track,drawRoute(c,a.id,e.track,difficultyColour(d.difficulty),!0)):console.log("ajax response.type wasn't success")}})}),a.addListener("mouseover",function(){var a=this.getIcon();a.fillColor=difficultyColour(d.difficulty,!0),this.setIcon(a)}),a.addListener("mouseout",function(){var a=this.getIcon();a.fillColor=difficultyColour(d.difficulty,!1),this.setIcon(a)})}function addNumberIconListener(a,b,c,d){b.addListener("click",function(b){var e=walksWithPlaces[c].length;if(0==this.visibilityState){this.visibilityState=1;for(var f=0;f<e;f++){var g=2*f*Math.PI/e-.5*Math.PI,h=f>.5*e?e-f+1:f+1;addOffsetMarker(a,{lat:Number(d.lat),lng:Number(d.lng)},walksWithPlaces[c][f],g,h)}}else if(1==this.visibilityState){this.visibilityState=2;for(var f=0;f<e;f++)markers[walksWithPlaces[c][f].id].setVisible(!1)}else if(2==this.visibilityState){this.visibilityState=1;for(var f=0;f<e;f++)markers[walksWithPlaces[c][f].id].setVisible(!0)}})}function displaySingleWalk(a){jQuery("#wismap1").addClass("wismap-area");var b=JSON.parse(walkinfo)[0],c=displayMap("wismap1",latitude1,longitude1,zoom1,maptype1),d=difficultyColour(b.difficulty,!1);new google.maps.Marker({position:{lat:Number(b.lat),lng:Number(b.lng)},title:b.name,map:c,icon:pinSymbol(d),optimized:!1});if(singleWalkRoute)drawRoute(c,0,singleWalkRoute,d,!1);else var f=1,g=setInterval(function(){singleWalkRoute?(drawRoute(c,0,singleWalkRoute,d,!1),clearInterval(g)):(f>20&&clearInterval(g),f+=1)},1e3);return a.preventDefault(),!1}function pinSymbol(a){return{path:"M 0,0 c -2,-20 -10,-22 -10,-30 a 10,10 0 1,1 20,0 c 0,8 -8,10 -10,30 z",fillColor:a,fillOpacity:1,strokeColor:"#000",strokeWeight:2,scale:1}}function offsetPinSymbol(a,b){var c=Math.round(50*Math.cos(a)),d=Math.round(50*Math.sin(a));if(Math.abs(a-Math.PI/2)>Math.atan(1/3))return{path:"M 0,0 L "+c+","+d+" c -2,-20 -10,-22 -10,-30 a 10,10 0 1,1 20,0 c 0,8 -8,10 -10,30 z",fillColor:b,fillOpacity:1,strokeColor:"#000",strokeWeight:2,scale:1};var e=Math.cos(Math.PI/2-a),f=Math.sin(Math.PI/2-a),g=Math.round(10*f),h=Math.round(10*e),i=c-g,j=d-h;return{path:"M 0,0 L "+g+","+h+" m "+i+","+j+" c -2,-20 -10,-22 -10,-30 a 10,10 0 1,1 20,0 c 0,8 -8,10 -10,30 z",fillColor:b,fillOpacity:1,strokeColor:"#000",strokeWeight:2,scale:1}}function difficultyColour(a,b){switch(Number(a)){case 1:var c=b?"#00FF00":"#32CD32";break;case 2:var c=b?"#FFFF00":"#FFD700";break;case 3:var c=b?"#FF8000":"#FF6000";break;case 4:var c=b?"#FF0000":"#DC2020";break;case 5:var c=b?"#696969":"#000000";break;default:var c="#0000FF"}return c}function drawRoute(a,b,c,d,e){if("undefined"==typeof polylines[b]){var f;route=[];for(var g={path:google.maps.SymbolPath.FORWARD_OPEN_ARROW,strokeColor:"black",strokeWeight:1,scale:2},h=0;h<c.length;h++)f={lat:parseFloat(c[h].v),lng:parseFloat(c[h].h)},route.push(f);polyline=new google.maps.Polyline({path:route,geodesic:!0,icons:[{icon:g,repeat:"50px"}],strokeColor:d,strokeOpacity:.5,strokeWeight:5,visible:!0}),e&&polyline.addListener("click",function(a){this.setVisible(!1)}),polylines[b]=polyline,polyline.setMap(a)}else polylines[b].setVisible(!0)}function buildTable(a){var b='<table id="wismap-table" class="display" width="100%" cellspacing="0"><thead>';b+="<tr><th>Title</th><th>Nearest Town</th><th>Grade</th><th>Length (km)</th><th>Ascent (m)</th><th>Duration (hours)</th></tr>",b+="<tbody>";for(var c=0;c<a.length;c++)b+='<tr><td><a href="'+a[c].url+'" target="_blank">'+a[c].name+"</a></td><td>"+a[c].town+'</td><td class="cbmw-'+a[c].difficulty+'">'+a[c].grade+"</td><td>"+a[c].distance+"</td><td>"+a[c].ascent+"</td><td>"+a[c].walk_time+"</td></tr>";b+="</tbody></table>",document.getElementById("wismap-list").innerHTML=b,pagelen=parseInt(pagelength)||10;var d=jQuery("#wismap-table").DataTable({pageLength:pagelen,order:[],responsive:!0});jQuery("#wismap-table tbody").on("click","tr",function(){jQuery(this).hasClass("selected")?jQuery(this).removeClass("selected"):(d.jQuery("tr.selected").removeClass("selected"),jQuery(this).addClass("selected"))})}google.maps.event.addDomListener(window,"load",displayMapsAndLists);var maps=[],infoWindow,markers=[],polylines=[],walksWithPlaces=[],singleWalkRoute="";