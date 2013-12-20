
<html>

<?php 
  const RENT = "rent";
  const SALE = "sale";
  const FLAT = "flat";
  const HOUSE = "house";
  const BUSINESS = "business";
  /* array has following structure:
  square
      realEstate
          business
              rent
                  {HOUSES}
              sale
                  {HOUSES}
          flat
          house
  */
  $data = unserialize(file_get_contents('/tmp/city'));


  function getNormalisedPrices($typeOfBuilding, $rentOrSale) {
    global $data;
    $data_js = [];
    foreach ($data['squares'] as $square) {
        $totalNormPrice = 0;
        foreach ($square['realEstate'][$typeOfBuilding][$rentOrSale] as $houseForSale) {
            $totalNormPrice = $totalNormPrice + $houseForSale['normalisedPrice'];
        }
        $nbOfHousesForSale = count($square['realEstate'][$typeOfBuilding][$rentOrSale]);
        if( $nbOfHousesForSale != 0) {
            $house['lat'] = $square['coordinates']['minLatitude'];
            $house['lon'] = $square['coordinates']['minLongitude'];
            $house['count'] = $totalNormPrice/$nbOfHousesForSale;
            $data_js[] = $house;
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

  function normalise($arr, $maxValue) {
    foreach ($arr as $value) {
      $value->count = $value->count/$maxValue;
    }
    return $arr;
  }
  
?>

<head>
  <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
  <title>heatmap.js OpenLayers Heatmap Layer</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body, html {
      margin:0;
      padding:0;
      font-family:Arial;
    }
    h1 {
      margin-bottom:10px;
    }
    #main {
      position:relative;
      width:1020px;
      padding:20px;
      margin:auto;
    }
    .map {
      position:relative;
      width:450px;
      height:450px;
      border:1px dashed black;
    }
    .rightMap {
      right: -100px;
    }
    .mapContainer {
      position:relative;
      float: left;
    }
    #configArea {
      position:relative;
      float:left;
      width:200px;
      padding:15px;
      padding-top:0;
      padding-right:0;
    }
    .btn {
      margin-top:25px;
      padding:10px 20px 10px 20px;
      -moz-border-radius:15px;
      -o-border-radius:15px;
      -webkit-border-radius:15px;
      border-radius:15px;
      border:2px solid black;
      cursor:pointer;
      color:white;
      background-color:black;
    }
    #gen:hover{
      background-color:grey;
      color:black;
    }
    textarea{
      width:260px;
      padding:10px;
      height:200px;
    }
    
  </style>
  <link rel="shortcut icon" type="image/png" href="http://www.patrick-wied.at/img/favicon.png" />


</head>
<body>

  <div id="main">
    <h1>Maps of Antwerp</h1>
    <div class="mapContainer">
      <h4>Normalised Prices Houses for Sale</h4>
      <div id="normalisedHouseSale" class="map">
      </div>
    </div>
    <div class="mapContainer rightMap">
      <h4>Normalised Prices Houses for Rent</h4>
      <div id="normalisedHouseRent" class="map">
      </div>
    </div>
    <div class="mapContainer">
      <h4>Normalised Prices Appartments for Sale</h4>
      <div id="normalisedFlatSale" class="map">
      </div>
    </div>
    <div class="mapContainer rightMap">
      <h4>Normalised Prices Appartments for Rent</h4>
      <div id="normalisedFlatRent" class="map">
      </div>
    </div>
    <div class="mapContainer">
      <h4>Normalised Prices Businesses for Sale</h4>
      <div id="normalisedBusinessSale" class="map">
      </div>
    </div>
    <div class="mapContainer rightMap">
      <h4>Normalised Prices Businesses for Rent</h4>
      <div id="normalisedBusinessRent" class="map">
      </div>
    </div>
    <div class="mapContainer">
      <h4>Normalised Sale Prices</h4>
      These prices are all sale prices from businesses, houses and</br> flats divided by their maximum value and plotted on one map.
      <div id="normalisedSale" class="map">
      </div>
    </div>
  </div>
  <script src="http://openlayers.org/api/OpenLayers.js"></script>
  <script type="text/javascript" src="heatmap.js"></script>
  <script type="text/javascript" src="heatmap-openlayers.js"></script>
  <script type="text/javascript">
  //var map1, layer1, heatmap1;
    function createMap(testData, divName){
        var transformedTestData = { max: testData.max , data: [] },
        
        data = testData.data,
        datalen = data.length,
        nudata = [];

        // in order to use the OpenLayers Heatmap Layer we have to transform our data into 
        // { max: <max>, data: [{lonlat: <OpenLayers.LonLat>, count: <count>},...]}

        while(datalen--){
            nudata.push({
            lonlat: new OpenLayers.LonLat(data[datalen].lon, data[datalen].lat),
            count: data[datalen].count });
        }

        transformedTestData.data = nudata;

        map = new OpenLayers.Map( divName );
        layer = new OpenLayers.Layer.OSM();


        // create our heatmap layer
        heatmap = new OpenLayers.Layer.Heatmap( "Heatmap Layer", map, layer, {visible: true, radius:13}, {isBaseLayer: false, opacity: 0.3, projection: new OpenLayers.Projection("EPSG:4326")});
        map.addLayers([layer, heatmap]);

        var center = new OpenLayers.LonLat(4.4186, 51.21412);
        center.transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());
        map.setCenter(center,13);
        //map.zoomIn();
        //var bounds = new OpenLayers.Bounds(4.45, 51.3, 4.42, 51.1);
   
   
        //map.zoomToExtent(bounds);
        heatmap.setDataSet(transformedTestData);
        return([map, layer, heatmap]);
    }

    <?php 
      $normHouseSalePrices = getNormalisedPrices(HOUSE,SALE);
      $maxHouseSalePrice = getMaxCountOf($normHouseSalePrices);
    ?>

    function init() {
      var testData={
        max: <?php echo $maxHouseSalePrice;?>,
        data: <?php echo $normHouseSalePrices; ?>
      };
      var divName = "normalisedHouseSale";
      createMap(testData, divName);
      
      <?php 
        $normHouseRentPrices = getNormalisedPrices(HOUSE,RENT);
        $maxHouseRentPrice = getMaxCountOf($normHouseRentPrices);
      ?>

      testData={
        max: <?php echo $maxHouseRentPrice; ?>,
        data: <?php echo $normHouseRentPrices; ?>
      };
      divName = "normalisedHouseRent";
      createMap(testData, divName);
    

      <?php 
        $normFlatSalePrices = getNormalisedPrices(FLAT,SALE);
        $maxFlatSalePrice = getMaxCountOf($normFlatSalePrices);
      ?>

      testData={
        max: <?php echo $maxFlatSalePrice; ?>,
        data: <?php echo $normFlatSalePrices; ?>
      };
      divName = "normalisedFlatSale";
      createMap(testData, divName);
    
      <?php 
        $normFlatRentPrices = getNormalisedPrices(FLAT,RENT);
        $maxFlatRentPrice = getMaxCountOf($normFlatRentPrices);
      ?>

      testData={
        max: <?php echo $maxFlatRentPrice; ?>,
        data: <?php echo $normFlatRentPrices; ?>
      };
      divName = "normalisedFlatRent";
      createMap(testData, divName);  

      <?php 
        $normBusinessSalePrices = getNormalisedPrices(BUSINESS,SALE);
        $maxBusinessSalePrice = getMaxCountOf($normBusinessSalePrices);
      ?>

      testData={
        max: <?php echo $maxBusinessSalePrice; ?>,
        data: <?php echo $normBusinessSalePrices; ?>
      };
      divName = "normalisedBusinessSale";
      createMap(testData, divName);
    
      <?php 
        $normBusinessRentPrices = getNormalisedPrices(BUSINESS,RENT);
        $maxBusinessRentPrice = getMaxCountOf($normBusinessRentPrices);
      ?>

      testData={
        max: <?php echo $maxBusinessRentPrice; ?>,
        data: <?php echo $normBusinessRentPrices; ?>
      };
      divName = "normalisedBusinessRent";
      createMap(testData, divName);  

      <?php 
        $normHouseSalePrices = normalise(json_decode($normHouseSalePrices), $maxHouseSalePrice);
        $normFlatSalePrices = normalise(json_decode($normFlatSalePrices), $maxFlatSalePrice);
        $normBusinessSalePrices = normalise(json_decode($normBusinessSalePrices), $maxBusinessSalePrice);
        $normSalePrices = array_merge($normHouseSalePrices, $normFlatSalePrices, $normBusinessSalePrices);
      ?>

      testData={
        max: 1,
        data: <?php echo json_encode($normSalePrices); ?>
      };
      divName = "normalisedSale";
      createMap(testData, divName);

    }
   

    window.onload = function(){ 
        init();
    };

</script>

</html>
