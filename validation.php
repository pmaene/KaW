<?php

const RENT = 'rent';
const SALE = 'sale';
const FLAT = 'flat';
const HOUSE = 'house';
const BUSINESS = 'business';
const RESTAURANT = 'restaurants';
const HOTEL = 'hotels';
const SHOP = 'shops';
const CAFE = 'cafes';

$data = unserialize(file_get_contents('/tmp/city'));
$targetFile = 'realEstate.arff';

unlink($targetFile);
$handle = fopen('./' . $targetFile, 'w');

// Bounding boxes of certain areas in Antwerp
// [minLat, minLon, maxLat, maxLon]
$eilandje = [
    'minLat' => 51.228,
    'minLon' => 4.4,
    'maxLat' => 51.24,
    'maxLon' => 4.42,
    'score'  => 600
];
$oldCityCenter = [
    'minLat' => 51.217,
    'minLon' => 4.395,
    'maxLat' => 51.222,
    'maxLon' => 4.405,
    'score'  => 1000
];
$seefHoek = [
    'minLat' => 51.221,
    'minLon' => 4.420,
    'maxLat' => 51.228,
    'maxLon' => 4.435,
    'score' => 300
];
$provincieHuis = [
    'minLat' => 51.216,
    'minLon' => 4.433,
    'maxLat' => 51.221,
    'maxLon' => 4.445,
    'score'  => 100
];
$tZuid = [
    'minLat' => 51.212,
    'minLon' => 4.394,
    'maxLat' => 51.217,
    'maxLon' => 4.406,
    'score'  => 800
];

$regions['eilandje'] = $eilandje;
$regions['oldCityCenter'] = $oldCityCenter;
$regions['seefHoek'] = $seefHoek;
$regions['provincieHuis'] = $provincieHuis;
$regions['tZuid'] = $tZuid;

foreach ($regions as $key => $region) {
    $squares = [];
    foreach ($data['squares'] as $square) {
        if( $square['coordinates']['minLatitude'] >= $region['minLat'] && $square['coordinates']['maxLatitude'] <= $region['maxLat'] && $square['coordinates']['minLongitude'] >= $region['minLon'] && $square['coordinates']['maxLongitude'] <= $region['maxLon'] ) {
            $squares[] = $square;
        }
    }

    $regions[$key]['squares'] = $squares;
}

foreach ($regions as $key => $region) {
    $regions[$key]['normFlatSalePrice'] = 0;
    $regions[$key]['normHouseSalePrice'] = 0;
    $regions[$key]['normBusinessSalePrice'] = 0;
    $regions[$key]['normFlatRentPrice'] = 0;
    $regions[$key]['normHouseRentPrice'] = 0;
    $regions[$key]['normBusinessRentPrice'] = 0;

    $regions[$key]['nbOfPhotos'] = 0;
    $regions[$key]['nbOfCafes'] = 0;
    $regions[$key]['nbOfRestaurants'] = 0;
    $regions[$key]['nbOfHotels'] = 0;
    $regions[$key]['nbOfShops'] = 0;
    $regions[$key]['nbOfSquares'] = 0;

    foreach ($region['squares'] as $square) {
        $regions[$key]['normFlatSalePrice'] = getNormalisedPrice(FLAT, SALE, $square);
        $regions[$key]['normHouseSalePrice'] = getNormalisedPrice(HOUSE, SALE, $square);
        $regions[$key]['normBusinessSalePrice'] = getNormalisedPrice(BUSINESS, SALE, $square);
        $regions[$key]['normFlatRentPrice'] = getNormalisedPrice(FLAT, RENT, $square);
        $regions[$key]['normHouseRentPrice'] = getNormalisedPrice(HOUSE, RENT, $square);
        $regions[$key]['normBusinessRentPrice'] = getNormalisedPrice(BUSINESS, RENT, $square);

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

$maxNormFlatSalePrice = 0;
$maxNormHouseSalePrice = 0;
$maxNormBusinessSalePrice = 0;
$maxNormFlatRentPrice = 0;
$maxNormHouseRentPrice = 0;
$maxNormBusinessRentPrice = 0;

$maxNbOfPhotos = 0;
$maxNbOfCafes = 0;
$maxNbOfRestaurants = 0;
$maxNbOfHotels = 0;
$maxNbOfShops = 0;

foreach ($regions as $key => $region) {
    if ($region['normFlatSalePrice'] > $maxNormFlatSalePrice)
        $maxNormFlatSalePrice = $region['normFlatSalePrice'];

    if ($region['normHouseSalePrice'] > $maxNormHouseSalePrice)
        $maxNormHouseSalePrice = $region['normHouseSalePrice'];

    if ($region['normBusinessSalePrice'] > $maxNormBusinessSalePrice)
        $maxNormBusinessSalePrice = $region['normBusinessSalePrice'];

    if ($region['normFlatRentPrice'] > $maxNormFlatRentPrice)
        $maxNormFlatRentPrice = $region['normFlatRentPrice'];

    if ($region['normHouseRentPrice'] > $maxNormHouseRentPrice)
        $maxNormHouseRentPrice = $region['normHouseRentPrice'];

    if ($region['normBusinessRentPrice'] > $maxNormBusinessRentPrice)
        $maxNormBusinessRentPrice = $region['normBusinessRentPrice'];

    if ($region['nbOfPhotos'] > $maxNbOfPhotos)
        $maxNbOfPhotos = $region['nbOfPhotos'];

    if ($region['nbOfCafes'] > $maxNbOfCafes)
        $maxNbOfCafes = $region['nbOfCafes'];

    if ($region['nbOfRestaurants'] > $maxNbOfRestaurants)
        $maxNbOfRestaurants = $region['nbOfRestaurants'];

    if ($region['nbOfHotels'] > $maxNbOfHotels)
        $maxNbOfHotels = $region['nbOfHotels'];

    if ($region['nbOfShops'] > $maxNbOfShops)
        $maxNbOfShops = $region['nbOfShops'];
}

foreach ($regions as $key => $region) {
    $regions[$key]['normFlatSalePrice'] = $region['normFlatSalePrice']/$maxNormFlatSalePrice;
    $regions[$key]['normHouseSalePrice'] = $region['normHouseSalePrice']/$maxNormHouseSalePrice;
    //$regions[$key]['normBusinessSalePrice'] = $region['normBusinessSalePrice']/$maxNormBusinessSalePrice;
    $regions[$key]['normFlatRentPrice'] = $region['normFlatRentPrice']/$maxNormFlatRentPrice;
    //$regions[$key]['normHouseRentPrice'] = $region['normHouseRentPrice']/$maxNormHouseRentPrice;
    //$regions[$key]['normBusinessRentPrice'] = $region['normBusinessRentPrice']/$maxNormBusinessRentPrice;

    $regions[$key]['normSalePrice'] = $regions[$key]['normFlatSalePrice'] + $regions[$key]['normHouseSalePrice'] + $regions[$key]['normBusinessSalePrice'];
    $regions[$key]['normRentPrice'] = $regions[$key]['normFlatRentPrice'] + $regions[$key]['normHouseRentPrice'] + $regions[$key]['normBusinessRentPrice'];

    $regions[$key]['nbOfPhotos'] = $region['nbOfPhotos']/$maxNbOfPhotos;
    $regions[$key]['nbOfCafes'] = $region['nbOfCafes']/$maxNbOfCafes;
    $regions[$key]['nbOfRestaurants'] = $region['nbOfRestaurants']/$maxNbOfRestaurants;
    $regions[$key]['nbOfHotels'] = $region['nbOfHotels']/$maxNbOfHotels;
    $regions[$key]['nbOfShops'] = $region['nbOfShops']/$maxNbOfShops;

    $regions[$key]['popularity'] = 1/2*($regions[$key]['nbOfPhotos']) + 1/8*($regions[$key]['nbOfHotels'] + $regions[$key]['nbOfCafes'] + $regions[$key]['nbOfRestaurants'] + $regions[$key]['nbOfShops']);
}

$line = <<<EOF
% 1. Title: Real estate database of Antwerp
%
@RELATION realEstateAntwerp

@ATTRIBUTE popularity           NUMERIC
@ATTRIBUTE normSalePrice        NUMERIC
@ATTRIBUTE normRentPrice        NUMERIC

@DATA

EOF;

fwrite($handle, $line);

foreach ($regions as $key => $region) {
    $line = $regions[$key]['popularity'] . ',' . $regions[$key]['normSalePrice'] . ',' . $regions[$key]['normRentPrice'] . PHP_EOL;
    fwrite($handle, $line);
}

function getNormalisedPrice($typeOfBuilding, $rentOrSale, $square) {
    $normPrice = 0;
    foreach ($square['realEstate'][$typeOfBuilding][$rentOrSale] as $realEstate)
        $normPrice = $normPrice + $realEstate['normalisedPrice'];

    if (count($square['realEstate'][$typeOfBuilding][$rentOrSale]) != 0)
        return $normPrice/count($square['realEstate'][$typeOfBuilding][$rentOrSale]);

    return 0;
}
