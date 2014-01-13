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
@ATTRIBUTE normSalePrice		NUMERIC
@ATTRIBUTE normRentPrice		NUMERIC

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

foreach ($data['squares'] as $square) {
   	$normPrices = [];

    $normPrices['normFlatSalePrice'] = getNormalisedPrice(FLAT, SALE, $square);
    $normPrices['normHouseSalePrice'] = getNormalisedPrice(HOUSE, SALE, $square);
    $normPrices['normBusinessSalePrice'] = getNormalisedPrice(BUSINESS, SALE, $square);
    $normPrices['normFlatRentPrice'] = getNormalisedPrice(FLAT, RENT, $square);
    $normPrices['normHouseRentPrice'] = getNormalisedPrice(HOUSE, RENT, $square);
    $normPrices['normBusinessRentPrice'] = getNormalisedPrice(BUSINESS, RENT, $square);

    $normPrices['nbOfPhotos'] = count($square['photos']);
    $normPrices['nbOfCafes'] = count($square['cafes']);
    $normPrices['nbOfRestaurants'] = count($square['restaurants']);
    $normPrices['nbOfHotels'] = count($square['hotels']);
    $normPrices['nbOfShops'] = count($square['shops']);

    if ($normPrices['normFlatSalePrice'] > $maxNormFlatSalePrice)
    	$maxNormFlatSalePrice = $normPrices['normFlatSalePrice'];

    if ($normPrices['normHouseSalePrice'] > $maxNormHouseSalePrice)
    	$maxNormHouseSalePrice = $normPrices['normHouseSalePrice'];

    if ($normPrices['normBusinessSalePrice'] > $maxNormBusinessSalePrice)
    	$maxNormBusinessSalePrice = $normPrices['normBusinessSalePrice'];

    if ($normPrices['normFlatRentPrice'] > $maxNormFlatRentPrice)
    	$maxNormFlatRentPrice = $normPrices['normFlatRentPrice'];

    if ($normPrices['normHouseRentPrice'] > $maxNormHouseRentPrice)
    	$maxNormHouseRentPrice = $normPrices['normHouseRentPrice'];

    if ($normPrices['normBusinessRentPrice'] > $maxNormBusinessRentPrice)
    	$maxNormBusinessRentPrice = $normPrices['normBusinessRentPrice'];

    $result[] = $normPrices;
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
	$line = $line = $square['nbOfPhotos'] . ',' . $square['nbOfHotels'] . ',' . $square['nbOfCafes'] . ',' . $square['nbOfRestaurants'] . ',' . $square['nbOfShops'] . ',' . $square['normSalePrice'] . ',' . $square['normRentPrice'] . PHP_EOL;
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
