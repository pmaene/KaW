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

$totalNbOfRealEstate = array(
    RENT => 0,
    SALE => 0
);
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

    getTotalNbOfRealestate($square, $totalNbOfRealEstate);

    $result[] = $resultSquare;
}

$totalNbOfPhotos = 0;
foreach ($result as $key => $square) {
    $result[$key]['normFlatSalePrice'] = $square['normFlatSalePrice']/$maxNormFlatSalePrice;
    $result[$key]['normHouseSalePrice'] = $square['normHouseSalePrice']/$maxNormHouseSalePrice;
    $result[$key]['normBusinessSalePrice'] = $square['normBusinessSalePrice']/$maxNormBusinessSalePrice;
    $result[$key]['normFlatRentPrice'] = $square['normFlatRentPrice']/$maxNormFlatRentPrice;
    //$result[$key]['normHouseRentPrice'] = $square['normHouseRentPrice']/$maxNormHouseRentPrice;
    $result[$key]['normBusinessRentPrice'] = $square['normBusinessRentPrice']/$maxNormBusinessRentPrice;

    $result[$key]['normSalePrice'] = $result[$key]['normFlatSalePrice'] + $result[$key]['normHouseSalePrice'] + $result[$key]['normBusinessSalePrice'];
    $result[$key]['normRentPrice'] = $result[$key]['normFlatRentPrice'] + $result[$key]['normHouseRentPrice'] + $result[$key]['normBusinessRentPrice'];

    $totalNbOfPhotos += $square['nbOfPhotos'];

    $result[$key]['nbOfPhotos'] = $square['nbOfPhotos']/$maxNbOfPhotos;
    $result[$key]['nbOfHotels'] = $square['nbOfHotels']/$maxNbOfHotels;
    $result[$key]['nbOfCafes'] = $square['nbOfCafes']/$maxNbOfCafes;
    $result[$key]['nbOfRestaurants'] = $square['nbOfRestaurants']/$maxNbOfRestaurants;
    $result[$key]['nbOfShops'] = $square['nbOfShops']/$maxNbOfShops;

    $result[$key]['popularity'] = 1/2*($result[$key]['nbOfPhotos']) + 1/8*($result[$key]['nbOfHotels'] + $result[$key]['nbOfCafes'] + $result[$key]['nbOfRestaurants'] + $result[$key]['nbOfShops']);
}

echo 'Photos' . PHP_EOL;
echo '  Total: ' . $totalNbOfPhotos . PHP_EOL;
echo '  Average: ' . $totalNbOfPhotos/count($data['squares']) . PHP_EOL;
echo '  Maximum: ' . $maxNbOfPhotos . PHP_EOL;

echo 'Real Estate' . PHP_EOL;
echo '  Total: ' . ($totalNbOfRealEstate[RENT] + $totalNbOfRealEstate[SALE]) . PHP_EOL;
echo '  Average: ' . ($totalNbOfRealEstate[RENT] + $totalNbOfRealEstate[SALE])/count($data['squares']) . PHP_EOL . PHP_EOL;

echo '  Rent: ' . $totalNbOfRealEstate[RENT] . PHP_EOL;
echo '  Sale: ' . $totalNbOfRealEstate[SALE] . PHP_EOL;

function getNormalisedPrice($typeOfBuilding, $rentOrSale, $square) {
    $normPrice = 0;
    foreach ($square['realEstate'][$typeOfBuilding][$rentOrSale] as $realEstate)
        $normPrice = $normPrice + $realEstate['normalisedPrice'];

    if (count($square['realEstate'][$typeOfBuilding][$rentOrSale]) != 0)
        return $normPrice/count($square['realEstate'][$typeOfBuilding][$rentOrSale]);

    return 0;
}

function getTotalNbOfRealestate($square, &$total) {
    foreach ($square['realEstate'] as $realEstate) {
        $total[RENT] += count($realEstate[RENT]);
        $total[SALE] += count($realEstate[SALE]);
    }
}
