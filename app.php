<?php
// Classes
include 'flickr.php';
include 'osm.php';
include 'zimmo.php';

// Parameters
$postalCodes = array(
    2000, 2600, 2018, 2030, 2060, 2140,
);
$cityNames   = array(
    2000 => 'Antwerpen',
    2600 => 'Berchem',
    2018 => 'Antwerpen',
    2030 => 'Antwerpen',
    2060 => 'Antwerpen',
    2140 => 'Borgerhout',
);
$province   = 'Antwerpen';

$minAreaLatitude  = 51.193;
$maxAreaLatitude  = 51.24;
$minAreaLongitude = 4.39;
$maxAreaLongitude = 4.445;

$nbSquares = 2500;

// Main
$flickr = new Flickr('e3a5ebef0de67a460ad53fa4b84b83c2');
$osm = new OpenStreetMap();
$zimmo = new Zimmo();

$latitudeResolution = round(($maxAreaLatitude - $minAreaLatitude)/sqrt($nbSquares), 6);
$longitudeResolution = round(($maxAreaLongitude - $minAreaLongitude)/sqrt($nbSquares), 6);

echo 'Fetching data for ' . $nbSquares . ' ' . ($nbSquares != 1 ? 'squares' : 'square') . PHP_EOL;
echo PHP_EOL;
echo 'Resolution' . PHP_EOL;
echo '  Latitude: ' . $latitudeResolution . PHP_EOL;
echo '  Longitude: ' . $longitudeResolution . PHP_EOL;
echo PHP_EOL;

if (!file_exists('/tmp/real_estate')) {
    $realEstate = array(
        'business_sale' => array(),
        'business_rent' => array(),
        'flat_sale'     => array(),
        'flat_rent'     => array(),
        'house_sale'    => array(),
        'house_rent'    => array(),
    );

    foreach ($postalCodes as $postalCode) {
        echo 'Scraping Zimmo for ' . $postalCode . ', ' . $cityNames[$postalCode] . PHP_EOL;

        $realEstate = array(
            'business_sale' => array_merge(
                $realEstate['business_sale'],
                $zimmo->scrape($postalCode, $cityNames[$postalCode], $province, Zimmo::FOR_SALE, Zimmo::BUSINESS)
            ),
            'business_rent' => array_merge(
                $realEstate['business_rent'],
                $zimmo->scrape($postalCode, $cityNames[$postalCode], $province, Zimmo::FOR_RENT, Zimmo::BUSINESS)
            ),
            'flat_sale'     => array_merge(
                $realEstate['flat_sale'],
                $zimmo->scrape($postalCode, $cityNames[$postalCode], $province, Zimmo::FOR_SALE, Zimmo::FLATS)
            ),
            'flat_rent'     => array_merge(
                $realEstate['flat_sale'],
                $zimmo->scrape($postalCode, $cityNames[$postalCode], $province, Zimmo::FOR_RENT, Zimmo::FLATS)
            ),
            'house_sale'    => array_merge(
                $realEstate['house_sale'],
                $zimmo->scrape($postalCode, $cityNames[$postalCode], $province, Zimmo::FOR_SALE, Zimmo::HOUSES)
            ),
            'house_rent'    => array_merge(
                $realEstate['house_rent'],
                $zimmo->scrape($postalCode, $cityNames[$postalCode], $province, Zimmo::FOR_RENT, Zimmo::HOUSES)
            ),
        );
    }

    echo 'Writing real estate to cache file' . PHP_EOL;
    file_put_contents('/tmp/real_estate', serialize($realEstate));
} else {
    echo 'Reading real estate from cache file' . PHP_EOL;
    $realEstate = unserialize(file_get_contents('/tmp/real_estate'));
}

echo PHP_EOL;

$squares = array();
for ($i = 0; $i < sqrt($nbSquares); $i++) {
    for ($j = 0; $j < sqrt($nbSquares); $j++) {
        $minLatitude = $minAreaLatitude + $j*$latitudeResolution;
        $maxLatitude = $minAreaLatitude + ($j+1)*$latitudeResolution;
        $minLongitude = $minAreaLongitude + $i*$longitudeResolution;
        $maxLongitude = $minAreaLongitude + ($i+1)*$longitudeResolution;

        echo 'Square ' . (count($squares)+1) . PHP_EOL;
        echo '  Bounding Box' . PHP_EOL;
        echo '    minLatitude: ' . $minLatitude . PHP_EOL;
        echo '    maxLatitude: ' . $maxLatitude . PHP_EOL;
        echo '    minLongitude: ' . $minLongitude . PHP_EOL;
        echo '    maxLongitude: ' . $maxLongitude . PHP_EOL;
        echo PHP_EOL;

        $flickr->setMinLatitude($minLatitude)
            ->setMaxLatitude($maxLatitude)
            ->setMinLongitude($minLongitude)
            ->setMaxLongitude($maxLongitude);

        $osm->setMinLatitude($minLatitude)
            ->setMaxLatitude($maxLatitude)
            ->setMinLongitude($minLongitude)
            ->setMaxLongitude($maxLongitude);

        $assignedRealEstate = array();
        foreach ($realEstate as $type => $properties) {
            $type = explode('_', $type);
            $assignedRealEstate[$type[0]][$type[1]] = array();

            foreach ($properties as $property) {
                if (
                    null !== $property['normalisedPrice']
                    && null !== $property['coordinates']['latitude'] && null !== $property['coordinates']['longitude']
                    && $property['coordinates']['latitude'] >= $minLatitude && $property['coordinates']['latitude'] <= $maxLatitude
                    && $property['coordinates']['longitude'] >= $minLongitude && $property['coordinates']['longitude'] <= $maxLongitude
                ) {
                    $assignedRealEstate[$type[0]][$type[1]][] = $property;
                }
            }

            $nbAssigned = count($assignedRealEstate[$type[0]][$type[1]]);
            echo '  Assigned ' . $nbAssigned . ' ' . $type[0] . ' ' . ($nbAssigned != 1 ? 'properties' : 'property') . ' for ' . $type[1] . ' to square' . PHP_EOL;
        }

        echo PHP_EOL;

        $squares[] = array(
            'coordinates'      => array(
                'minLatitude'  => $minLatitude,
                'maxLatitude'  => $maxLatitude,
                'minLongitude' => $minLongitude,
                'maxLongitude' => $maxLongitude
            ),
            'photos'      => $flickr->findPhotos(),
            'realEstate'  => $assignedRealEstate,
            'cafes'       => $osm->findCafes(),
            'hotels'      => $osm->findHotels(),
            'restaurants' => $osm->findRestaurants(),
            'shops'       => $osm->findShops()
        );

        echo PHP_EOL;
    }
}

$data = array(
    'cameraModels' => $flickr->cameraModels,
    'photgraphers' => $flickr->photographers,
    'squares' => $squares
);

echo 'Writing data to cache file' . PHP_EOL;
file_put_contents('/tmp/city', serialize($data));
