<?php
/**
 * MATLAB
 * @author Pieter Maene <pieter.maene@student.kuleuven.be>
 */

const RENT = "rent";
const SALE = "sale";
const FLAT = "flat";
const HOUSE = "house";
const BUSINESS = "business";
const RESTAURANT = "restaurants";
const HOTEL = "hotels";
const SHOP = "shops";
const CAFE = "cafes";

mt_srand(getSeed());

$data = unserialize(file_get_contents('/tmp/city'));
$nbSquares = sqrt(count($data['squares']));

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
    $resultSquare = [];

    $resultSquare['normFlatSalePrice'] = getNormalisedPrice(FLAT, SALE, $square);
    $resultSquare['normHouseSalePrice'] = getNormalisedPrice(HOUSE, SALE, $square);
    $resultSquare['normBusinessSalePrice'] = getNormalisedPrice(BUSINESS, SALE, $square);
    $resultSquare['normFlatRentPrice'] = getNormalisedPrice(FLAT, RENT, $square);
    $resultSquare['normHouseRentPrice'] = getNormalisedPrice(HOUSE, RENT, $square);
    $resultSquare['normBusinessRentPrice'] = getNormalisedPrice(BUSINESS, RENT, $square);

    $resultSquare['nbOfPhotos'] = count($square['photos']);
    $resultSquare['nbOfCafes'] = count($square['cafes']);
    $resultSquare['nbOfRestaurants'] = count($square['restaurants']);
    $resultSquare['nbOfHotels'] = count($square['hotels']);
    $resultSquare['nbOfShops'] = count($square['shops']);

    if ($resultSquare['normFlatSalePrice'] > $maxNormFlatSalePrice)
        $maxNormFlatSalePrice = $resultSquare['normFlatSalePrice'];

    if ($resultSquare['normHouseSalePrice'] > $maxNormHouseSalePrice)
        $maxNormHouseSalePrice = $resultSquare['normHouseSalePrice'];

    if ($resultSquare['normBusinessSalePrice'] > $maxNormBusinessSalePrice)
        $maxNormBusinessSalePrice = $resultSquare['normBusinessSalePrice'];

    if ($resultSquare['normFlatRentPrice'] > $maxNormFlatRentPrice)
        $maxNormFlatRentPrice = $resultSquare['normFlatRentPrice'];

    if ($resultSquare['normHouseRentPrice'] > $maxNormHouseRentPrice)
        $maxNormHouseRentPrice = $resultSquare['normHouseRentPrice'];

    if ($resultSquare['normBusinessRentPrice'] > $maxNormBusinessRentPrice)
        $maxNormBusinessRentPrice = $resultSquare['normBusinessRentPrice'];

    if ($resultSquare['nbOfPhotos'] > $maxNbOfPhotos)
        $maxNbOfPhotos = $resultSquare['nbOfPhotos'];

    if ($resultSquare['nbOfCafes'] > $maxNbOfCafes)
        $maxNbOfCafes = $resultSquare['nbOfPhotos'];

    if ($resultSquare['nbOfRestaurants'] > $maxNbOfRestaurants)
        $maxNbOfRestaurants = $resultSquare['nbOfRestaurants'];

    if ($resultSquare['nbOfHotels'] > $maxNbOfHotels)
        $maxNbOfHotels = $resultSquare['nbOfHotels'];

    if ($resultSquare['nbOfShops'] > $maxNbOfShops)
        $maxNbOfShops = $resultSquare['nbOfShops'];

    $result[] = $resultSquare;
}

foreach ($result as $key => $square) {
    $result[$key]['normFlatSalePrice'] = $square['normFlatSalePrice']/$maxNormFlatSalePrice;
    $result[$key]['normHouseSalePrice'] = $square['normHouseSalePrice']/$maxNormHouseSalePrice;
    $result[$key]['normBusinessSalePrice'] = $square['normBusinessSalePrice']/$maxNormBusinessSalePrice;
    $result[$key]['normFlatRentPrice'] = $square['normFlatRentPrice']/$maxNormFlatRentPrice;
    //$result[$key]['normHouseRentPrice'] = $square['normHouseRentPrice']/$maxNormHouseRentPrice;
    $result[$key]['normBusinessRentPrice'] = $square['normBusinessRentPrice']/$maxNormBusinessRentPrice;

    $nbOfEachSaleCategory = 0;
    if ($result[$key]['normFlatSalePrice'] > 0)
        $nbOfEachSaleCategory++;
    if ($result[$key]['normHouseSalePrice'] > 0)
        $nbOfEachSaleCategory++;
    if ($result[$key]['normBusinessSalePrice'] > 0)
        $nbOfEachSaleCategory++;

    $nbOfEachRentCategory = 0;
    if($result[$key]['normFlatRentPrice'] != 0)
        $nbOfEachRentCategory++;
    if($result[$key]['normHouseRentPrice'] != 0)
        $nbOfEachRentCategory++;
    if($result[$key]['normBusinessRentPrice'] != 0)
        $nbOfEachRentCategory++;

    $result[$key]['normSalePrice'] = $result[$key]['normFlatSalePrice'] + $result[$key]['normHouseSalePrice'] + $result[$key]['normBusinessSalePrice'];
    $result[$key]['normRentPrice'] = $result[$key]['normFlatRentPrice'] + $result[$key]['normHouseRentPrice'] + $result[$key]['normBusinessRentPrice'];

    if($nbOfEachSaleCategory != 0)
        $result[$key]['normSalePrice'] = $result[$key]['normSalePrice']/$nbOfEachSaleCategory;
    if($nbOfEachRentCategory != 0)
        $result[$key]['normRentPrice'] = $result[$key]['normRentPrice']/$nbOfEachRentCategory;

    $result[$key]['nbOfPhotos'] = $square['nbOfPhotos']/$maxNbOfPhotos;
    $result[$key]['nbOfHotels'] = $square['nbOfHotels']/$maxNbOfHotels;
    $result[$key]['nbOfCafes'] = $square['nbOfCafes']/$maxNbOfCafes;
    $result[$key]['nbOfRestaurants'] = $square['nbOfRestaurants']/$maxNbOfRestaurants;
    $result[$key]['nbOfShops'] = $square['nbOfShops']/$maxNbOfShops;

    $result[$key]['popularity'] = 1/5*($result[$key]['nbOfPhotos'] + $result[$key]['nbOfHotels'] + $result[$key]['nbOfCafes'] + $result[$key]['nbOfRestaurants'] + $result[$key]['nbOfShops']);
}

$out = 'realEstate = [';

$row = 0;
$column = 0;
foreach ($result as $square) {
    $out .= 1/2*($square['normSalePrice'] + $square['normRentPrice']);

    if (($nbSquares-1) == $column%$nbSquares) {
        $out .= '; ';

        $column = 0;
        $row++;
    } else {
        $out .= ' ';
        $column++;
    }
}

$out .= '];';
echo $out . PHP_EOL;

$out = 'popularity = [';

$row = 0;
$column = 0;
foreach ($result as $square) {
    $out .= $square['popularity'];

    if (($nbSquares-1) == $column%$nbSquares) {
        $out .= '; ';

        $column = 0;
        $row++;
    } else {
        $out .= ' ';
        $column++;
    }
}

$out .= '];';
echo $out . PHP_EOL;

$randomResult = array();
for ($i = 0; $i < count($result); $i++) {
    $randomResult[$i]['normSalePrice'] = mt_rand()/mt_getrandmax();
    $randomResult[$i]['normRentPrice'] = mt_rand()/mt_getrandmax();

    $randomResult[$i]['nbOfPhotos'] = mt_rand(0, $maxNbOfPhotos);
    $randomResult[$i]['nbOfHotels'] = mt_rand(0, $maxNbOfHotels);
    $randomResult[$i]['nbOfCafes'] = mt_rand(0, $maxNbOfCafes);
    $randomResult[$i]['nbOfRestaurants'] = mt_rand(0, $maxNbOfRestaurants);
    $randomResult[$i]['nbOfShops'] = mt_rand(0, $maxNbOfShops);
}

$maxNormSalePrice = 0;
$maxNormRentPrice = 0;

$maxNbOfPhotos = 0;
$maxNbOfCafes = 0;
$maxNbOfRestaurants = 0;
$maxNbOfHotels = 0;
$maxNbOfShops = 0;

foreach ($randomResult as $square) {
    if ($square['normSalePrice'] > $maxNormSalePrice)
        $maxNormSalePrice = $square['normSalePrice'];

    if ($square['normRentPrice'] > $maxNormRentPrice)
        $maxNormRentPrice = $square['normRentPrice'];

    if ($square['nbOfPhotos'] > $maxNbOfPhotos)
        $maxNbOfPhotos = $square['nbOfPhotos'];

    if ($square['nbOfCafes'] > $maxNbOfCafes)
        $maxNbOfCafes = $square['nbOfPhotos'];

    if ($square['nbOfRestaurants'] > $maxNbOfRestaurants)
        $maxNbOfRestaurants = $square['nbOfRestaurants'];

    if ($square['nbOfHotels'] > $maxNbOfHotels)
        $maxNbOfHotels = $square['nbOfHotels'];

    if ($square['nbOfShops'] > $maxNbOfShops)
        $maxNbOfShops = $square['nbOfShops'];
}

foreach ($randomResult as $key => $square) {
    $result[$key]['normSalePrice'] = $square['normSalePrice']/$maxNormSalePrice;
    $result[$key]['normRentPrice'] = $square['normRentPrice']/$maxNormRentPrice;

    $result[$key]['nbOfPhotos'] = $square['nbOfPhotos']/$maxNbOfPhotos;
    $result[$key]['nbOfHotels'] = $square['nbOfPhotos']/$maxNbOfHotels;
    $result[$key]['nbOfCafes'] = $square['nbOfPhotos']/$maxNbOfCafes;
    $result[$key]['nbOfRestaurants'] = $square['nbOfPhotos']/$maxNbOfRestaurants;
    $result[$key]['nbOfShops'] = $square['nbOfPhotos']/$maxNbOfShops;

    $result[$key]['popularity'] = 1/5*($square['nbOfPhotos']/$maxNbOfPhotos + $square['nbOfHotels']/$maxNbOfHotels + $square['nbOfCafes']/$maxNbOfCafes + $square['nbOfRestaurants']/$maxNbOfRestaurants + $square['nbOfShops']/$maxNbOfShops);
}

$out = 'randomRealEstate = [';

$row = 0;
$column = 0;
foreach ($result as $square) {
    $out .= 1/2*($square['normSalePrice'] + $square['normRentPrice']);

    if (($nbSquares-1) == $column%$nbSquares) {
        $out .= '; ';

        $column = 0;
        $row++;
    } else {
        $out .= ' ';
        $column++;
    }
}

$out .= '];';
echo $out . PHP_EOL;

$out = 'randomPopularity = [';

$row = 0;
$column = 0;
foreach ($result as $square) {
    $out .= $square['popularity'];

    if (($nbSquares-1) == $column%$nbSquares) {
        $out .= '; ';

        $column = 0;
        $row++;
    } else {
        $out .= ' ';
        $column++;
    }
}

$out .= '];';
echo $out . PHP_EOL;

function getNormalisedPrice($typeOfBuilding, $rentOrSale, $square) {
    $normPrice = 0;
    foreach ($square['realEstate'][$typeOfBuilding][$rentOrSale] as $realEstate)
        $normPrice = $normPrice + $realEstate['normalisedPrice'];

    if (count($square['realEstate'][$typeOfBuilding][$rentOrSale]) != 0)
        return $normPrice/count($square['realEstate'][$typeOfBuilding][$rentOrSale]);

    return 0;
}

function getSeed() {
    list($usec, $sec) = explode(' ', microtime());
    return (float) $sec + ((float) $usec * 100000);
}
