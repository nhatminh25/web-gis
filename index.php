<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>OpenStreetMap &amp; OpenLayers - Marker Example</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <link rel="stylesheet" href="https://cdn.rawgit.com/openlayers/openlayers.github.io/master/en/v5.3.0/css/ol.css" type="text/css">
        <link rel="stylesheet" href="https://openlayers.org/en/v4.6.5/css/ol.css" type="text/css" />
        <link rel="stylesheet" href="./css/style.css">
        <script src="https://cdn.rawgit.com/openlayers/openlayers.github.io/master/en/v5.3.0/build/ol.js"></script>
    </head>
    <body onload="initialize_map();">
        <div class="wrapper">
            <div class="search-section">
                <span class="search-title">Tìm kiếm</span>
                <input 
                    type="text" 
                    class="search-inp"
                    id="search-location"
                    placeholder="Nhập từ khóa để tìm kiếm"
                >
                <div class="search-result" id="search-result"></div>
            </div>
            <div id="map"></div>
        </div>
        <?php include 'CMR_pgsqlAPI.php' ?>
        <?php
            // $myPDO = initDB();
            // $mySRID = '4326';
            
            // echo $myPDO;
            //$pointFormat = 'POINT(12,5)';

            //example1($myPDO);
            //example2($myPDO);
            //example3($myPDO,'4326','POINT(12,5)');
            //$result = getResult($myPDO,$mySRID,$pointFormat);

            //closeDB($myPDO);
        ?>

        <script src="https://cdn.jsdelivr.net/npm/jquery@3.2.1/dist/jquery.min.js"></script>
        <script>
            const searchBox = document.getElementById("search-location")
            searchBox.addEventListener("change", (e) => {
                const searchValue = e.target.value
                $.ajax({
                    type: "POST",
                    url: "CMR_pgsqlAPI.php",
                    data: { inSearchMode: true, searchValue: searchValue},
                    success : function (result, status, erro) {
                        const dataToRender = ["Từ khóa tìm kiếm không hợp lệ"].includes(result) ? result : JSON.parse(result || "[]" ); 
                        renderSearchResult(dataToRender);
                    },
                    error: function (req, status, error) {
                        console.log('error :>> ', error);
                    }
                });
            })

            function renderSearchResult(data) {
                let htmlElement = '';
                const searchResultDiv = document.getElementById('search-result')
                if(["Từ khóa tìm kiếm không hợp lệ"].includes(data)) {
                    htmlElement = '<div class="please-enter-search-value"><span>Vui lòng nhập từ khóa tìm kiếm</span></div>';  
                    searchResultDiv.innerHTML  = htmlElement;
                    return;
                }
                if(data.length == 0) {
                   htmlElement = '<div class="no-data"><span>Không tìm thấy dữ liệu</span></div>';     
                } else {
                    for(let i = 0; i < data.length; i++) {
                       htmlElement += `
                            <div class="search-result-item" onclick={moveToLocation(${data[i].geo})}>${data[i].name}</div>
                        `;
                    }
                }
                
                searchResultDiv.innerHTML  = htmlElement;
            }

            function moveToLocation(listPoint) {
                console.log('listPoint :>> ', listPoint);
            }
            var format = 'image/png';
            var map;
            var mapLat = 15.917;
            var mapLng = 107.331;
            var mapDefaultZoom = 5;
            function initialize_map() {
                layerBG = new ol.layer.Tile({
                    source: new ol.source.OSM({})
                });
                var layerCMR_adm1 = new ol.layer.Image({
                    source: new ol.source.ImageWMS({
                        ratio: 1,
                        url: 'http://localhost:8080/geoserver/example/wms?',
                        params: {
                            'FORMAT': format,
                            'VERSION': '1.1.1',
                            STYLES: '',
                            LAYERS: 'gadm41_vnm_1',
                        }
                    })
                });
                var viewMap = new ol.View({
                    center: ol.proj.fromLonLat([mapLng, mapLat]),
                    zoom: mapDefaultZoom
                    //projection: projection
                });
                map = new ol.Map({
                    target: "map",
                    // layers: [layerBG, layerCMR_adm1],
                    layers: [layerBG],
                    view: viewMap
                });
                // map.getView().fit(bounds, map.getSize());
                
                var styles = {
                    'MultiPolygon': new ol.style.Style({
                        fill: new ol.style.Fill({
                            color: 'orange'
                        }),
                        stroke: new ol.style.Stroke({
                            color: 'yellow', 
                            width: 2
                        })
                    })
                };
                var styleFunction = function (feature) {
                    return styles[feature.getGeometry().getType()];
                };
                var vectorLayer = new ol.layer.Vector({
                    //source: vectorSource,
                    style: styleFunction
                });
                map.addLayer(vectorLayer);

                function createJsonObj(result) {     
                    var geojsonObject = '{'
                            + '"type": "FeatureCollection",'
                            + '"crs": {'
                                + '"type": "name",'
                                + '"properties": {'
                                    + '"name": "EPSG:4326"'
                                + '}'
                            + '},'
                            + '"features": [{'
                                + '"type": "Feature",'
                                + '"geometry": ' + result
                            + '}]'
                        + '}';
                    return geojsonObject;
                }
                function drawGeoJsonObj(paObjJson) {
                    var vectorSource = new ol.source.Vector({
                        features: (new ol.format.GeoJSON()).readFeatures(paObjJson, {
                            dataProjection: 'EPSG:4326',
                            featureProjection: 'EPSG:3857'
                        })
                    });
                    var vectorLayer = new ol.layer.Vector({
                        source: vectorSource
                    });
                    map.addLayer(vectorLayer);
                }
                function highLightGeoJsonObj(paObjJson) {
                    var vectorSource = new ol.source.Vector({
                        features: (new ol.format.GeoJSON()).readFeatures(paObjJson, {
                            dataProjection: 'EPSG:4326',
                            featureProjection: 'EPSG:3857'
                        })
                    });
					vectorLayer.setSource(vectorSource);
                    /*
                    var vectorLayer = new ol.layer.Vector({
                        source: vectorSource
                    });
                    map.addLayer(vectorLayer);
                    */
                }
                function highLightObj(result) {
                    var strObjJson = createJsonObj(result);
                    var objJson = JSON.parse(strObjJson);
                    //drawGeoJsonObj(objJson);
                    highLightGeoJsonObj(objJson);
                }
                map.on('singleclick', function (evt) {
                    //alert("coordinate: " + evt.coordinate);
                    //var myPoint = 'POINT(12,5)';
                    var lonlat = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
                    var lon = lonlat[0];
                    var lat = lonlat[1];
                    var myPoint = 'POINT(' + lon + ' ' + lat + ')';
                    //alert("myPoint: " + myPoint);
                    //*
                    $.ajax({
                        type: "POST",
                        url: "CMR_pgsqlAPI.php",
                        //dataType: 'json',
                        data: {functionname: 'getGeoCMRToAjax', paPoint: myPoint},
                        success : function (result, status, erro) {
                            highLightObj(result);
                        },
                        error: function (req, status, error) {
                            alert(req + " " + status + " " + error);
                        }
                    });
                    //*/
                });
            };
        </script>
    </body>
</html>