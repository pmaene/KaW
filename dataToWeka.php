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

$line = <<<EOF
% 1. Title: Real estate database of Antwerp
%
@RELATION realEstateAntwerp

@ATTRIBUTE nbOfPhotos  			NUMERIC
@ATTRIBUTE nbOfHotels   		NUMERIC
@ATTRIBUTE nbOfCafes  			NUMERIC
@ATTRIBUTE nbOfRestaurants		NUMERIC
@ATTRIBUTE nbOfShops			NUMERIC
@ATTRIBUTE popularity           NUMERIC
@ATTRIBUTE normSalePrice		NUMERIC
@ATTRIBUTE normRentPrice		NUMERIC
@ATTRIBUTE normPrice            NUMERIC

@DATA


EOF;

fwrite($handle, $line);

$result = [];

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

foreach ($data['squares'] as $square) {
   	$squareResult = [];

    $squareResult['normFlatSalePrice'] = getNormalisedPrice(FLAT, SALE, $square);
    $squareResult['normHouseSalePrice'] = getNormalisedPrice(HOUSE, SALE, $square);
    $squareResult['normBusinessSalePrice'] = getNormalisedPrice(BUSINESS, SALE, $square);
    $squareResult['normFlatRentPrice'] = getNormalisedPrice(FLAT, RENT, $square);
    $squareResult['normHouseRentPrice'] = getNormalisedPrice(HOUSE, RENT, $square);
    $squareResult['normBusinessRentPrice'] = getNormalisedPrice(BUSINESS, RENT, $square);

    $squareResult['nbOfPhotos'] = count($square['photos']);
    $squareResult['nbOfCafes'] = count($square['cafes']);
    $squareResult['nbOfRestaurants'] = count($square['restaurants']);
    $squareResult['nbOfHotels'] = count($square['hotels']);
    $squareResult['nbOfShops'] = count($square['shops']);

    if ($squareResult['normFlatSalePrice'] > $maxNormFlatSalePrice)
    	$maxNormFlatSalePrice = $squareResult['normFlatSalePrice'];

    if ($squareResult['normHouseSalePrice'] > $maxNormHouseSalePrice)
    	$maxNormHouseSalePrice = $squareResult['normHouseSalePrice'];

    if ($squareResult['normBusinessSalePrice'] > $maxNormBusinessSalePrice)
    	$maxNormBusinessSalePrice = $squareResult['normBusinessSalePrice'];

    if ($squareResult['normFlatRentPrice'] > $maxNormFlatRentPrice)
    	$maxNormFlatRentPrice = $squareResult['normFlatRentPrice'];

    if ($squareResult['normHouseRentPrice'] > $maxNormHouseRentPrice)
    	$maxNormHouseRentPrice = $squareResult['normHouseRentPrice'];

    if ($squareResult['normBusinessRentPrice'] > $maxNormBusinessRentPrice)
    	$maxNormBusinessRentPrice = $squareResult['normBusinessRentPrice'];

    if ($squareResult['nbOfPhotos'] > $maxNbOfPhotos)
        $maxNbOfPhotos = $squareResult['nbOfPhotos'];

    if ($squareResult['nbOfCafes'] > $maxNbOfCafes)
        $maxNbOfCafes = $squareResult['nbOfCafes'];

    if ($squareResult['nbOfRestaurants'] > $maxNbOfRestaurants)
        $maxNbOfRestaurants = $squareResult['nbOfRestaurants'];

    if ($squareResult['nbOfHotels'] > $maxNbOfHotels)
        $maxNbOfHotels = $squareResult['nbOfHotels'];

    if ($squareResult['nbOfShops'] > $maxNbOfShops)
        $maxNbOfShops = $squareResult['nbOfShops'];

    $result[] = $squareResult;
}

foreach ($result as $key => $square) {
	$result[$key]['normFlatSalePrice'] = $square['normFlatSalePrice']/$maxNormFlatSalePrice;
	$result[$key]['normHouseSalePrice'] = $square['normHouseSalePrice']/$maxNormHouseSalePrice;
	$result[$key]['normBusinessSalePrice'] = $square['normBusinessSalePrice']/$maxNormBusinessSalePrice;
	$result[$key]['normFlatRentPrice'] = $square['normFlatRentPrice']/$maxNormFlatRentPrice;
	//$square[$key]['normHouseRentPrice'] = $square['normHouseRentPrice']/$maxNormHouseRentPrice;
	$result[$key]['normBusinessRentPrice'] = $square['normBusinessRentPrice']/$maxNormBusinessRentPrice;

	$result[$key]['normSalePrice'] = $result[$key]['normFlatSalePrice'] + $result[$key]['normHouseSalePrice'] + $result[$key]['normBusinessSalePrice'];
	$result[$key]['normRentPrice'] = $result[$key]['normFlatRentPrice'] + $result[$key]['normHouseRentPrice'] + $result[$key]['normBusinessRentPrice'];
}

foreach ($result as $square) {
    $popularity = $square['nbOfPhotos']/$maxNbOfPhotos + $square['nbOfHotels']/$maxNbOfHotels + $square['nbOfCafes']/$maxNbOfCafes + $square['nbOfRestaurants']/$maxNbOfRestaurants + $square['nbOfShops']/$maxNbOfShops;
	$line = $line = $square['nbOfPhotos'] . ',' . $square['nbOfHotels'] . ',' . $square['nbOfCafes'] . ',' . $square['nbOfRestaurants'] . ',' . $square['nbOfShops'] . ',' . $popularity . ',' . $square['normSalePrice'] . ',' . $square['normRentPrice'] . ',' . ($square['normSalePrice'] + $square['normRentPrice']) . PHP_EOL;
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

function getMax($keyName, $arr) {
	$maxValue = 0;
	foreach ($arr as $value) {
		if($maxValue < $value[$keyName])
			$maxValue = $value[$keyName];
	}
	return $maxValue;
}
