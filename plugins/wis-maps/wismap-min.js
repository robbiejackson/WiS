google.maps.event.addDomListener(window,"load",displayMapsAndLists);var infoWindow,maps=[],markers=[],polylines=[],walksWithPlaces=[],singleWalkRoute="";function displayMapsAndLists(){"undefined"!=typeof nmaps&&("country"==map_country_or_area1?displayCountryMap("wismap1",latitude1,longitude1,zoom1,maptype1,JSON.parse(areainfo1)):"area"==map_country_or_area1?displayAreaMap("wismap1",latitude1,longitude1,zoom1,maptype1,JSON.parse(walkinfo),JSON.parse(placeinfo)):"walk"==map_country_or_area1&&getRoute(JSON.parse(walkinfo)[0].gpx),2==nmaps&&("country"==map_country_or_area2?displayCountryMap("wismap2",latitude2,longitude2,zoom2,maptype2,JSON.parse(areainfo2)):"area"==map_country_or_area2&&displayAreaMap("wismap2",latitude2,longitude2,zoom2,maptype2,JSON.parse(walkinfo),JSON.parse(placeinfo)))),"undefined"!=typeof list&&buildTable(JSON.parse(walkinfo))}function getRoute(e){jQuery.ajax({type:"get",dataType:"json",url:wisAjax.ajaxurl,data:{action:"get_track",fname:e},success:function(e){"success"==e.type?singleWalkRoute=e.track:console.log("ajax response.type wasn't success")}})}function map_selected(e){switch(e){case"google maps":return google.maps.MapTypeId.ROADMAP;case"open cycle map":return"OCM";case"open street map":return"OSM";case"thunderforest landscape":default:return"Land"}}function displayMap(e,a,t,i,l){var o=[];for(var n in google.maps.MapTypeId)o.push(google.maps.MapTypeId[n]);o.push("OCM"),o.push("OSM"),o.push("Land");var s=new google.maps.Map(document.getElementById(e),{center:{lat:Number(a),lng:Number(t)},zoom:Number(i),mapTypeId:map_selected(l),mapTypeControlOptions:{mapTypeIds:o},streetViewControl:!1,scrollwheel:!1,gestureHandling:"cooperative"});return maps.push(s),s.mapTypes.set("OSM",new google.maps.ImageMapType({getTileUrl:function(e,a){return"https://tile.openstreetmap.org/"+a+"/"+e.x+"/"+e.y+".png"},tileSize:new google.maps.Size(256,256),name:"OSM",alt:"Show OpenStreetMap",maxZoom:18})),s.mapTypes.set("OCM",new google.maps.ImageMapType({getTileUrl:function(e,a){return"https://tile.thunderforest.com/cycle/"+a+"/"+e.x+"/"+e.y+".png?apikey="+thunderforest_API_key},tileSize:new google.maps.Size(256,256),name:"OCM",alt:"Show OpenCycleMap",maxZoom:18})),s.mapTypes.set("Land",new google.maps.ImageMapType({getTileUrl:function(e,a){return"https://tile.thunderforest.com/landscape/"+a+"/"+e.x+"/"+e.y+".png?apikey="+thunderforest_API_key},tileSize:new google.maps.Size(256,256),name:"Land",alt:"Show Thunderforest Landscape",maxZoom:18})),s}function displayCountryMap(e,a,t,i,l,o){for(var n=displayMap(e,a,t,i,l),s=new google.maps.InfoWindow,r=0;r<o.length;r++){o[0];for(var p,d=o[r].polygon_coords,c=[],u=0;u<d.length;u++)p={lat:Number(d[u][0]),lng:Number(d[u][1])},c.push(p);var g=new google.maps.Polygon({paths:c,strokeColor:0==o[r].nwalks?"#FF0000":"#00FF00",strokeOpacity:.8,strokeWeight:3});g.setMap(n),addPolygonListener(g,s,n,o[r])}}function addPolygonListener(e,t,i,l){e.addListener("click",function(e){var a=l.name+" ("+l.nwalks+" walk"+(1==l.nwalks?")":"s)");0<l.nwalks?a+='<br><a href="'+l.webpage+'">Walks in '+l.name+"</a>":0<l.webpage.length&&(a+='<br><a href="'+l.webpage+'">Walking in '+l.name+"</a>"),t.setContent(a),t.setPosition(e.latLng),t.open(i)})}function displayAreaMap(e,a,t,i,l,o,n){var s=displayMap(e,a,t,i,l);infoWindow=new google.maps.InfoWindow;for(var r=0;r<o.length;r++)o[r].id=r,o[r].placeid?addWalksWithPlace(o[r]):addMarker(s,o[r]);processWalksWithPlace(s,n)}function addMarker(e,a){var t=difficultyColour(a.difficulty,!1),i=new google.maps.Marker({position:{lat:Number(a.lat),lng:Number(a.lng)},title:a.name,map:e,icon:pinSymbol(t),id:a.id,optimized:!1});addMarkerListener(markers[a.id]=i,infoWindow,e,a)}function addWalksWithPlace(e){void 0===walksWithPlaces[e.placeid]&&(walksWithPlaces[e.placeid]=[]),walksWithPlaces[e.placeid].push(e)}function processWalksWithPlace(e,a){for(var t in walksWithPlaces){if(1==walksWithPlaces[t].length)walksWithPlaces[t][0].lat=a[t].lat,walksWithPlaces[t][0].lng=a[t].lng,addMarker(e,walksWithPlaces[t][0]);else addNumberIconListener(e,new google.maps.Marker({position:{lat:Number(a[t].lat),lng:Number(a[t].lng)},title:" "==a[t].name[2]?a[t].name.substring(3)+" Click to expand / hide walks here":a[t].name+" Click to expand / hide walks here",map:e,icon:iconfolder+"number_"+walksWithPlaces[t].length+".png",id:walkinfo.id,optimized:!1,zIndex:google.maps.Marker.MAX_ZINDEX+walksWithPlaces[t].length,visibilityState:0}),t,a[t])}}function addOffsetMarker(e,a,t,i,l){difficultyColour(t.difficulty,!1);var o=new google.maps.Marker({position:a,title:t.name,map:e,icon:offsetPinSymbol(i,difficultyColour(t.difficulty,!1)),id:t.id,optimized:!1,zIndex:google.maps.Marker.MAX_ZINDEX+l});addMarkerListener(markers[t.id]=o,infoWindow,e,t)}function addMarkerListener(i,l,o,n){i.addListener("click",function(e){var a=!1,t="<strong>"+n.name+"</strong>";t+=" near "+n.town+"<br>",t+="Grade: "+n.grade+", Length: "+n.distance+", Ascent: "+n.ascent+", Walk Time: "+n.walk_time+"hrs",t+='<br><strong><a href="'+n.url+'" target="_blank">Go to walk details</a></strong>',i.hasOwnProperty("track")?drawRoute(o,i.id,i.track,difficultyColour(n.difficulty),!0):n.gpx?(t+="<br>Retrieving walk GPS data ...",a=!0):t+="<br>No GPS data available",l.setContent(t),l.setPosition(e.latLng),l.open(o),a&&jQuery.ajax({type:"get",dataType:"json",url:wisAjax.ajaxurl,data:{action:"get_track",fname:n.gpx},success:function(e){"success"==e.type?(l.setContent(t+"Done! Click (when cursor changes) on the route to hide it"),i.track=e.track,drawRoute(o,i.id,e.track,difficultyColour(n.difficulty),!0)):console.log("ajax response.type wasn't success")}})}),i.addListener("mouseover",function(){var e=this.getIcon();e.fillColor=difficultyColour(n.difficulty,!0),this.setIcon(e)}),i.addListener("mouseout",function(){var e=this.getIcon();e.fillColor=difficultyColour(n.difficulty,!1),this.setIcon(e)})}function addNumberIconListener(o,e,n,s){e.addListener("click",function(e){var a=walksWithPlaces[n].length;if(0==this.visibilityState){this.visibilityState=1;for(var t=0;t<a;t++){var i=2*t*Math.PI/a-.5*Math.PI,l=.5*a<t?a-t+1:t+1;addOffsetMarker(o,{lat:Number(s.lat),lng:Number(s.lng)},walksWithPlaces[n][t],i,l)}}else if(1==this.visibilityState){this.visibilityState=2;for(t=0;t<a;t++)markers[walksWithPlaces[n][t].id].setVisible(!1)}else if(2==this.visibilityState){this.visibilityState=1;for(t=0;t<a;t++)markers[walksWithPlaces[n][t].id].setVisible(!0)}})}function displaySingleWalk(e){jQuery("#wismap1").addClass("wismap-area");var a=JSON.parse(walkinfo)[0],t=displayMap("wismap1",latitude1,longitude1,zoom1,maptype1),i=difficultyColour(a.difficulty,!1);new google.maps.Marker({position:{lat:Number(a.lat),lng:Number(a.lng)},title:a.name,map:t,icon:pinSymbol(i),optimized:!1});if(singleWalkRoute)drawRoute(t,0,singleWalkRoute,i,!1);else var l=1,o=setInterval(function(){singleWalkRoute?(drawRoute(t,0,singleWalkRoute,i,!1),clearInterval(o)):(20<l&&clearInterval(o),l+=1)},1e3);return e.preventDefault(),!1}function pinSymbol(e){return{path:"M 0,0 c -2,-20 -10,-22 -10,-30 a 10,10 0 1,1 20,0 c 0,8 -8,10 -10,30 z",fillColor:e,fillOpacity:1,strokeColor:"#000",strokeWeight:2,scale:1}}function offsetPinSymbol(e,a){var t=Math.round(50*Math.cos(e)),i=Math.round(50*Math.sin(e));if(Math.abs(e-Math.PI/2)>Math.atan(1/3))return{path:"M 0,0 L "+t+","+i+" c -2,-20 -10,-22 -10,-30 a 10,10 0 1,1 20,0 c 0,8 -8,10 -10,30 z",fillColor:a,fillOpacity:1,strokeColor:"#000",strokeWeight:2,scale:1};var l=Math.cos(Math.PI/2-e),o=Math.sin(Math.PI/2-e),n=Math.round(10*o),s=Math.round(10*l);return{path:"M 0,0 L "+n+","+s+" m "+(t-n)+","+(i-s)+" c -2,-20 -10,-22 -10,-30 a 10,10 0 1,1 20,0 c 0,8 -8,10 -10,30 z",fillColor:a,fillOpacity:1,strokeColor:"#000",strokeWeight:2,scale:1}}function difficultyColour(e,a){switch(Number(e)){case 1:var t=a?"#00FF00":"#32CD32";break;case 2:t=a?"#FFFF00":"#FFD700";break;case 3:t=a?"#FF8000":"#FF6000";break;case 4:t=a?"#FF0000":"#DC2020";break;case 5:t=a?"#696969":"#000000";break;default:t="#0000FF"}return t}function drawRoute(e,a,t,i,l){if(void 0===polylines[a]){var o;route=[];for(var n={path:google.maps.SymbolPath.FORWARD_OPEN_ARROW,strokeColor:"black",strokeWeight:1,scale:2},s=0;s<t.length;s++)o={lat:parseFloat(t[s].v),lng:parseFloat(t[s].h)},route.push(o);polyline=new google.maps.Polyline({path:route,geodesic:!0,icons:[{icon:n,repeat:"50px"}],strokeColor:i,strokeOpacity:.5,strokeWeight:5,visible:!0}),l&&polyline.addListener("click",function(e){this.setVisible(!1)}),polylines[a]=polyline,polyline.setMap(e)}else polylines[a].setVisible(!0)}function buildTable(e){var a='<table id="wismap-table" class="display" width="100%" cellspacing="0"><thead>';a+="<tr><th>Title</th><th>Nearest Town</th><th>Grade</th><th>Length (km)</th><th>Ascent (m)</th><th>Duration (hours)</th></tr>",a+="<tbody>";for(var t=0;t<e.length;t++)a+='<tr><td><a href="'+e[t].url+'" target="_blank">'+e[t].name+"</a></td><td>"+e[t].town+'</td><td class="cbmw-'+e[t].difficulty+'">'+e[t].grade+"</td><td>"+e[t].distance+"</td><td>"+e[t].ascent+"</td><td>"+e[t].walk_time+"</td></tr>";a+="</tbody></table>",document.getElementById("wismap-list").innerHTML=a,pagelen=parseInt(pagelength)||10;var i=jQuery("#wismap-table").DataTable({pageLength:pagelen,order:[],responsive:!0});jQuery("#wismap-table tbody").on("click","tr",function(){jQuery(this).hasClass("selected")?jQuery(this).removeClass("selected"):(i.jQuery("tr.selected").removeClass("selected"),jQuery(this).addClass("selected"))})}