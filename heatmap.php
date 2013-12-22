<?php
const RENT = "rent";
const SALE = "sale";
const FLAT = "flat";
const HOUSE = "house";
const BUSINESS = "business";
const RESTAURANT = "restaurants";
const HOTEL = "hotels";
const SHOP = "shops";
const CAFE = "cafes";

$data = unserialize(file_get_contents('/tmp/city'));

function getNormalisedPrices($typeOfBuilding, $rentOrSale) {
    global $data;
    $data_js = [];
    foreach ($data['squares'] as $square) {
        $totalNormPrice = 0;
        foreach ($square['realEstate'][$typeOfBuilding][$rentOrSale] as $houseForSale)
            $totalNormPrice = $totalNormPrice + $houseForSale['normalisedPrice'];

        $nbOfHousesForSale = count($square['realEstate'][$typeOfBuilding][$rentOrSale]);
        $house['lat'] = $square['coordinates']['minLatitude'];
        $house['lon'] = $square['coordinates']['minLongitude'];
        if(0 != $nbOfHousesForSale) {
            $house['count'] = $totalNormPrice/$nbOfHousesForSale;
            $data_js[] = $house;   
        }
    }

    return json_encode($data_js);
}

function getNbOfPhotos() {
    global $data;
    $data_js = [];
    foreach ($data['squares'] as $square) {
        $photos['lat'] = $square['coordinates']['minLatitude'];
        $photos['lon'] = $square['coordinates']['minLongitude'];
        $photos['count'] = count($square['photos']);
        $data_js[] = $photos;
    }

    return json_encode($data_js);
}

function getNbOfOSMData($osmType) {
    global $data;
    $data_js = [];
    foreach ($data['squares'] as $square) {
        $nbOfOSMData = $osm['count'] = count($square[$osmType]);
        if($nbOfOSMData != 0) {
            $osm['lat'] = $square['coordinates']['minLatitude'];
            $osm['lon'] = $square['coordinates']['minLongitude'];
            $data_js[] = $osm;
        }
    }

    return json_encode($data_js);
}

function getMaxCountOf($jsonString) {
    $max = 0;
    foreach (json_decode($jsonString) as $value) {
        if($value->count > $max)
            $max = $value->count;
    }
    return $max;
}

function normalise($array, $maxValue) {
    foreach ($array as $value)
        if($value->count!=0)
            $value->count = $value->count/$maxValue;
    return $array;
}
?>

<html>
    <head>
        <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
        <title>heatmap.js OpenLayers Heatmap Layer</title>

        <style type="text/css">
            body, html {
                margin: 0;
                padding: 0;
                font-family: Arial;
            }

            h1 {
                margin-bottom: 10px;
            }

            #main {
                position: relative;
                width: 1020px;
                padding: 20px;
                margin: auto;
            }

            .map {
                position: relative;
                width: 450px;
                height: 450px;
                border: 1px dashed black;
            }

            .rightMap {
                right: -100px;
            }

            .mapContainer {
                position: relative;
                float: left;
            }

            #configArea {
                position: relative;
                float: left;
                width: 200px;
                padding: 15px;
                padding-top: 0;
                padding-right: 0;
            }

            .btn {
                margin-top: 25px;
                padding: 10px 20px 10px 20px;
                -moz-border-radius: 15px;
                -o-border-radius: 15px;
                -webkit-border-radius: 15px;
                border-radius: 15px;
                border: 2px solid black;
                cursor: pointer;
                color: white;
                background-color: black;
            }

            #gen:hover{
                background-color:grey;
                color:black;
            }
        </style>
    </head>

    <body>
        <div id="main">
            <h1>Maps of Antwerp</h1>
            <div class="mapContainer">
                <h4>Normalised Prices Houses for Sale</h4>
                <div id="normalisedHouseSale" class="map"></div>
            </div>
            <div class="mapContainer rightMap">
                <h4>Normalised Prices Houses for Rent</h4>
                <div id="normalisedHouseRent" class="map"></div>
            </div>
            <div class="mapContainer">
                <h4>Normalised Prices Appartments for Sale</h4>
                <div id="normalisedFlatSale" class="map"></div>
            </div>
            <div class="mapContainer rightMap">
                <h4>Normalised Prices Appartments for Rent</h4>
                <div id="normalisedFlatRent" class="map"></div>
            </div>
            <div class="mapContainer">
                <h4>Normalised Prices Businesses for Sale</h4>
                <div id="normalisedBusinessSale" class="map"></div>
            </div>
            <div class="mapContainer rightMap">
                <h4>Normalised Prices Businesses for Rent</h4>
                <div id="normalisedBusinessRent" class="map"></div>
            </div>
            <div class="mapContainer">
                <h4>Normalised Sale Prices</h4>
                <p>These prices are all sale prices from businesses, houses and<br /> flats divided by their maximum value and plotted on one map.</p>
                <div id="normalisedSale" class="map"></div>
            </div>
            <div class="mapContainer rightMap">
                <h4>Normalised Rent Prices</h4>
                <p>These prices are all rent prices from businesses, houses and<br /> flats divided by their maximum value and plotted on one map.</p>
                <div id="normalisedRent" class="map"></div>
            </div>
            <div class="mapContainer">
                <h4>Number of Photos</h4>
                <div id="nbOfPhotos" class="map"></div>
            </div>
            <div class="mapContainer rightMap">
                <h4>Number of Restaurants</h4>
                <div id="nbOfRestaurants" class="map"></div>
            </div>
            <div class="mapContainer">
                <h4>Number of Hotels</h4>
                <div id="nbOfHotels" class="map"></div>
            </div>
            <div class="mapContainer rightMap">
                <h4>Number of Cafes</h4>
                <div id="nbOfCafes" class="map"></div>
            </div>
            <div class="mapContainer">
                <h4>Number of Shops</h4>
                <div id="nbOfShops" class="map"></div>
            </div>
            <div class="mapContainer rightMap">
                <h4>Normalised OSM Data</h4>
                <div id="normalisedOSM" class="map"></div>
            </div>
            <div class="mapContainer">
                <h4>Normalised Sale and Rent Data</h4>
                <div id="normSaleAndRent" class="map"></div>
            </div>
            <div class="mapContainer rightMap">
                <?php  
                    // Bounding boxes of certain areas in Antwerp
                    // [minLat, minLon, maxLat, maxLon]
                    $eilandje =         [   
                                            'minLat' => 51.228,
                                            'minLon' => 4.4,
                                            'maxLat' => 51.24, 
                                            'maxLon' => 4.42,
                                            'score' => 600
                                        ]; // Eilandje is populair
                    $oldCityCenter =    [
                                            'minLat' => 51.217, 
                                            'minLon' => 4.395,
                                            'maxLat' => 51.222, 
                                            'maxLon' => 4.405,
                                            'score' => 1000
                                        ]; // Kathedraal en Groenplaats zijn populair
                    $seefHoek =         [
                                            'minLat' => 51.221, 
                                            'minLon' => 4.420,
                                            'maxLat' => 51.228, 
                                            'maxLon' => 4.435,
                                            'score' => 300
                                        ]; // Seefhoek is onpopulair
                    $provincieHuis =    [
                                            'minLat' => 51.216, 
                                            'minLon' => 4.433,
                                            'maxLat' => 51.221,
                                            'maxLon' => 4.445,
                                            'score' => 100
                                        ]; // Regio rond provincie-huis is onpopulair
                    $tZuid =            [
                                            'minLat' => 51.212,
                                            'minLon' => 4.394,
                                            'maxLat' => 51.217,
                                            'maxLon' => 4.406,
                                            'score' => 800
                                        ]; // Region rond 't Zuid is populair
                    $meir =             [
                                            'minLat' => ,
                                            'minLon' => ,
                                            'maxLat' => ,
                                            'maxLon' => ,
                                            'score' =>
                                        ]
                    $regions['eilandje'] = $eilandje;
                    $regions['oldCityCenter'] = $oldCityCenter;
                    $regions['seefHoek'] = $seefHoek;
                    $regions['provincieHuis'] = $provincieHuis;
                    $regions['tZuid'] = $tZuid;
                    
                    // Add all corresponding squares to a region
                    foreach ($regions as $key => $region) {
                        $squares = [];
                        foreach ($data['squares'] as $square) {
                            if( $square['coordinates']['minLatitude'] >= $region['minLat'] && $square['coordinates']['maxLatitude'] <= $region['maxLat'] && $square['coordinates']['minLongitude'] >= $region['minLon'] && $square['coordinates']['maxLongitude'] <= $region['maxLon'] ) {
                                $squares[] = $square;
                            }
                        }
                        $regions[$key]['squares'] = $squares;
                    }

                    
                    //echo "Calculating regions ...<br>";
                    foreach ($regions as $key => $region) {
                        //echo $key . "<br>";
                        $regions[$key]['nbOfPhotos'] = 0;
                        $regions[$key]['nbOfCafes'] = 0;
                        $regions[$key]['nbOfRestaurants'] = 0;
                        $regions[$key]['nbOfHotels'] = 0;
                        $regions[$key]['nbOfShops'] = 0;
                        $regions[$key]['nbOfSquares'] = 0;
                        foreach ($region['squares'] as $square) {
                            //echo $regions[$key]['nbOfPhotos'] . " number of photos<br>";
                            $regions[$key]['nbOfPhotos'] += count($square['photos']);
                            $regions[$key]['nbOfCafes'] += count($square['cafes']);
                            $regions[$key]['nbOfRestaurants'] += count($square['restaurants']);
                            $regions[$key]['nbOfHotels'] += count($square['hotels']);
                            $regions[$key]['nbOfShops'] += count($square['shops']);
                            $regions[$key]['nbOfSquares'] += 1;
                        }
                    }

                    foreach ($regions as $key => $region) {
                        $regions[$key]['nbOfPhotos'] = $regions[$key]['nbOfPhotos']/$regions[$key]['nbOfSquares'];
                        $regions[$key]['nbOfCafes'] = $regions[$key]['nbOfCafes']/$regions[$key]['nbOfSquares'];
                        $regions[$key]['nbOfRestaurants'] = $regions[$key]['nbOfRestaurants']/$regions[$key]['nbOfSquares'];
                        $regions[$key]['nbOfHotels'] = $regions[$key]['nbOfHotels']/$regions[$key]['nbOfSquares'];
                        $regions[$key]['nbOfShops'] = $regions[$key]['nbOfShops']/$regions[$key]['nbOfSquares'];
                    }
                    
                    echo "<br>";
                    foreach ($regions as $key => $region) {
                        echo "<b>" . $key . "</b> contains: <br>";
                        echo $region['nbOfPhotos'] . " photos per square <br>";
                        echo $region['nbOfCafes'] . " cafes per square <br>";
                        echo $region['nbOfRestaurants'] . " restaurants per square <br>";
                        echo $region['nbOfHotels'] . " hotels per square <br>";
                        echo $region['nbOfShops'] . " shops per square <br>";
                        echo "on " . $region['nbOfSquares'] . " squares<br>";
                        echo "Assigned popularity score of " . $region['score'] . "<br><br><br>";
                    }


                    $maxNbOfPhotos = 0;
                    $maxNbOfCafes = 0;
                    $maxNbOfRestaurants = 0;
                    $maxNbOfHotels = 0;
                    $maxNbOfShops = 0;
                    foreach ($regions as $key => $region) {
                        if($region['nbOfPhotos'] > $maxNbOfPhotos)
                            $maxNbOfPhotos = $region['nbOfPhotos'];
                        if($region['nbOfCafes'] > $maxNbOfCafes)
                            $maxNbOfCafes = $region['nbOfCafes'];
                        if($region['nbOfRestaurants'] > $maxNbOfRestaurants)
                            $maxNbOfRestaurants = $region['nbOfRestaurants'];
                        if($region['nbOfHotels'] > $maxNbOfHotels)
                            $maxNbOfHotels = $region['nbOfHotels'];
                        if($region['nbOfShops'] > $maxNbOfShops)
                            $maxNbOfShops = $region['nbOfShops'];
                        
                    }

                    /*
                    echo "<br><br> <b>Matlab variables:</b>";
                    
                    echo "<br>d=[";
                    foreach ($regions as $key => $region) {
                        echo " " . $region['score'] . ";";
                    }
                    echo " ];";

                    echo "C=[";*/
                    foreach ($regions as $key => $region) {
                        //echo " " . $region['nbOfPhotos']/$maxNbOfPhotos . " " . $region['nbOfCafes']/$maxNbOfCafes . " " . $region['nbOfRestaurants']/$maxNbOfRestaurants . " " . $region['nbOfHotels']/$maxNbOfHotels . " " . $region['nbOfShops']/$maxNbOfShops . ";";
                        //echo " " . $region['nbOfPhotos']/$maxNbOfPhotos . " " . $region['nbOfCafes']/$maxNbOfCafes . " " . $region['nbOfRestaurants']/$maxNbOfRestaurants . " " . $region['nbOfShops']/$maxNbOfShops . ";";
                        $regions[$key]['calculatedScore'] = ($region['nbOfPhotos']/$maxNbOfPhotos+$region['nbOfCafes']/$maxNbOfCafes*2/5+$region['nbOfRestaurants']/$maxNbOfRestaurants*3/5+$region['nbOfHotels']/$maxNbOfHotels*1/5+$region['nbOfShops']/$maxNbOfShops*4/5)*33.33333;
                    }
                    //echo "];<br><br>";

                    foreach ($regions as $key => $region) {
                        echo "<b>" . $key . "</b> has a calculated score of " . $region['calculatedScore'] . "<br>";
                        echo "while the assigned score was " . $region['score'] . "<br><br>";                        
                    }
                ?>
            </div>
        </div>

        <script src="http://openlayers.org/api/OpenLayers.js"></script>
        <script type="text/javascript" src="heatmap.js"></script>
        <script type="text/javascript" src="heatmap-openlayers.js"></script>
        <script type="text/javascript">
            function createMap(testData, divName, radiusVar) {
                var transformedTestData = { max: testData.max , data: [] },

                data = testData.data,
                datalen = data.length,
                nudata = [];

                while(datalen--) {
                    nudata.push({
                        lonlat: new OpenLayers.LonLat(data[datalen].lon, data[datalen].lat),
                        count: data[datalen].count
                    });
                }

                transformedTestData.data = nudata;

                map = new OpenLayers.Map( divName );
                layer = new OpenLayers.Layer.OSM();

                heatmap = new OpenLayers.Layer.Heatmap(
                    "Heatmap Layer",
                    map,
                    layer,
                    {
                        visible: true,
                        radius: radiusVar
                    },
                    {
                        isBaseLayer: false,
                        opacity: 0.3,
                        projection: new OpenLayers.Projection("EPSG:4326")
                    }
                );
                map.addLayers([layer, heatmap]);

                var center = new OpenLayers.LonLat(4.4186, 51.21412);
                center.transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());
                map.setCenter(center,13);

                heatmap.setDataSet(transformedTestData);
                return([map, layer, heatmap]);
            }

            function init() {
                <?php
                    $normHouseSalePrices = getNormalisedPrices(HOUSE,SALE);
                    $maxHouseSalePrice = getMaxCountOf($normHouseSalePrices);
                ?>

                var testData = {
                    max: <?php echo $maxHouseSalePrice;?>,
                    data: <?php echo $normHouseSalePrices; ?>
                };
                var divName = "normalisedHouseSale";
                createMap(testData, divName, 13);

                <?php
                    $normHouseRentPrices = getNormalisedPrices(HOUSE,RENT);
                    $maxHouseRentPrice = getMaxCountOf($normHouseRentPrices);
                ?>

                testData = {
                    max: <?php echo $maxHouseRentPrice; ?>,
                    data: <?php echo $normHouseRentPrices; ?>
                };
                divName = "normalisedHouseRent";
                createMap(testData, divName, 13);

                <?php
                    $normFlatSalePrices = getNormalisedPrices(FLAT,SALE);
                    $maxFlatSalePrice = getMaxCountOf($normFlatSalePrices);
                ?>

                testData = {
                    max: <?php echo $maxFlatSalePrice; ?>,
                    data: <?php echo $normFlatSalePrices; ?>
                };
                divName = "normalisedFlatSale";
                createMap(testData, divName, 13);

                <?php
                    $normFlatRentPrices = getNormalisedPrices(FLAT,RENT);
                    $maxFlatRentPrice = getMaxCountOf($normFlatRentPrices);
                ?>

                testData = {
                    max: <?php echo $maxFlatRentPrice; ?>,
                    data: <?php echo $normFlatRentPrices; ?>
                };
                divName = "normalisedFlatRent";
                createMap(testData, divName, 13);

                <?php
                    $normBusinessSalePrices = getNormalisedPrices(BUSINESS,SALE);
                    $maxBusinessSalePrice = getMaxCountOf($normBusinessSalePrices);
                ?>

                testData = {
                    max: <?php echo $maxBusinessSalePrice; ?>,
                    data: <?php echo $normBusinessSalePrices; ?>
                };
                divName = "normalisedBusinessSale";
                createMap(testData, divName, 13);

                <?php
                    $normBusinessRentPrices = getNormalisedPrices(BUSINESS,RENT);
                    $maxBusinessRentPrice = getMaxCountOf($normBusinessRentPrices);
                ?>

                testData = {
                    max: <?php echo $maxBusinessRentPrice; ?>,
                    data: <?php echo $normBusinessRentPrices; ?>
                };
                divName = "normalisedBusinessRent";
                createMap(testData, divName, 13);

                <?php
                    $normHouseSalePrices = normalise(json_decode($normHouseSalePrices), $maxHouseSalePrice);
                    $normFlatSalePrices = normalise(json_decode($normFlatSalePrices), $maxFlatSalePrice);
                    $normBusinessSalePrices = normalise(json_decode($normBusinessSalePrices), $maxBusinessSalePrice);
                    $normSalePrices = array_merge($normHouseSalePrices, $normFlatSalePrices, $normBusinessSalePrices);
                ?>

                testData = {
                    max: 1,
                    data: <?php echo json_encode($normSalePrices); ?>
                };
                divName = "normalisedSale";
                createMap(testData, divName, 13);

                <?php
                    $normHouseRentPrices = normalise(json_decode($normHouseRentPrices), $maxHouseRentPrice);
                    $normFlatRentPrices = normalise(json_decode($normFlatRentPrices), $maxFlatRentPrice);
                    $normBusinessRentPrices = normalise(json_decode($normBusinessRentPrices), $maxBusinessRentPrice);
                    $normRentPrices = array_merge($normHouseRentPrices, $normFlatRentPrices, $normBusinessRentPrices);
                ?>

                testData = {
                    max: 1,
                    data: <?php echo json_encode($normRentPrices); ?>
                };
                divName = "normalisedRent";
                createMap(testData, divName, 13);

                <?php
                    $nbOfPhotos = getNbOfPhotos();
                    $maxNbOfPhotos = getMaxCountOf($nbOfPhotos);
                ?>

                testData = {
                    max: <?php echo $maxNbOfPhotos; ?>,
                    data: <?php echo $nbOfPhotos; ?>
                };
                divName = "nbOfPhotos";
                createMap(testData, divName, 11);

                <?php
                    $nbOfRestaurants = getNbOfOSMData(RESTAURANT);
                    $maxNbOfRestaurants = getMaxCountOf($nbOfRestaurants);
                ?>

                testData = {
                    max: <?php echo $maxNbOfRestaurants; ?>,
                    data: <?php echo $nbOfRestaurants; ?>
                };
                divName = "nbOfRestaurants";
                createMap(testData, divName, 13);

                <?php
                    $nbOfHotels = getNbOfOSMData(HOTEL);
                    $maxNbOfHotels = getMaxCountOf($nbOfHotels);
                ?>

                testData = {
                    max: <?php echo $maxNbOfHotels; ?>,
                    data: <?php echo $nbOfHotels; ?>
                };
                divName = "nbOfHotels";
                createMap(testData, divName, 13);

                <?php
                    $nbOfCafes = getNbOfOSMData(CAFE);
                    $maxNbOfCafes = getMaxCountOf($nbOfCafes);
                ?>

                testData = {
                    max: <?php echo $maxNbOfCafes; ?>,
                    data: <?php echo $nbOfCafes; ?>
                };
                divName = "nbOfCafes";
                createMap(testData, divName, 13);

                <?php
                    $nbOfShops = getNbOfOSMData(SHOP);
                    $maxNbOfShops = getMaxCountOf($nbOfShops);
                ?>

                testData = {
                    max: <?php echo $maxNbOfShops; ?>,
                    data: <?php echo $nbOfShops; ?>
                };
                divName = "nbOfShops";
                createMap(testData, divName, 13);

                <?php
                    $nbOfShops = normalise(json_decode($nbOfShops), $maxNbOfShops);
                    $nbOfCafes = normalise(json_decode($nbOfCafes), $maxNbOfCafes);
                    $nbOfRestaurants = normalise(json_decode($nbOfRestaurants), $maxNbOfRestaurants);
                    $nbOfHotels = normalise(json_decode($nbOfHotels), $maxNbOfHotels);
                    $normOSMData = array_merge($nbOfShops, $nbOfCafes, $nbOfRestaurants, $nbOfHotels);
                ?>

                testData = {
                    max: 1,
                    data: <?php echo json_encode($normOSMData); ?>
                };
                divName = "normalisedOSM";
                createMap(testData, divName, 11);

                <?php
                    $normSaleAndRent = array_merge($normSalePrices, $normRentPrices);
                ?>

                testData = {
                    max: 1,
                    data: <?php echo json_encode($normSaleAndRent); ?>
                };
                divName = "normSaleAndRent";
                createMap(testData, divName, 11);
            }

            window.onload = function(){
                init();
            };
        </script>
    </body>
</html>
