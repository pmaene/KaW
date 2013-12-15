<?php
// Classes
include 'flickr.php';
include 'zimmo.php';

// Parameters
$postalCode = 9000;
$cityName = "Gent";
$province = "Oost-Vlaanderen";

$minAreaLongitude = 51.052872;
$maxAreaLongitude = 51.058872;
$minAreaLatitude  = 3.7185141;
$maxAreaLatitude  = 3.7285141;

$nbSquares = 100;

// Main
$flickr = new Flickr('e3a5ebef0de67a460ad53fa4b84b83c2');
$zimmo = new Zimmo();

$longitudeResolution = round(($maxAreaLongitude - $minAreaLongitude)/sqrt($nbSquares), 6);
$latitudeResolution = round(($maxAreaLatitude - $minAreaLatitude)/sqrt($nbSquares), 6);

if (!file_exists('/tmp/city')) {
    echo 'Fetching data for ' . $nbSquares . ' ' . ($nbSquares != 1 ? 'squares' : 'square') . PHP_EOL;
    echo PHP_EOL;
    echo 'Resolution' . PHP_EOL;
    echo '  Longitude: ' . $longitudeResolution . PHP_EOL;
    echo '  Latitude: ' . $latitudeResolution . PHP_EOL;
    echo PHP_EOL;

    if (!file_exists('/tmp/houses')) {
        $houses = $zimmo->scrape($postalCode, $cityName, $province, Zimmo::FOR_SALE, Zimmo::HOUSES_AND_FLATS);

        echo PHP_EOL;
        echo 'Writing houses to cache file' . PHP_EOL;
        file_put_contents('/tmp/houses', serialize($houses));
    } else {
        echo 'Reading houses from cache file' . PHP_EOL;
        $houses = unserialize(file_get_contents('/tmp/houses'));
    }

    echo PHP_EOL;

    $squares = array();
    for ($i = 0; $i < sqrt($nbSquares); $i++) {
        for ($j = 0; $j < sqrt($nbSquares); $j++) {
            $minLongitude = $minAreaLongitude + $i*$longitudeResolution;
            $minLatitude = $minAreaLatitude + $j*$latitudeResolution;
            $maxLongitude = $minAreaLongitude + ($i+1)*$longitudeResolution;
            $maxLatitude = $minAreaLatitude + ($j+1)*$latitudeResolution;

            echo 'Square ' . (count($squares)+1) . PHP_EOL;
            echo '  Bounding Box' . PHP_EOL;
            echo '    minLongitude: ' . $minLongitude . PHP_EOL;
            echo '    minLatitude: ' . $minLatitude . PHP_EOL;
            echo '    maxLongitude: ' . $maxLongitude . PHP_EOL;
            echo '    maxLatitude: ' . $maxLatitude . PHP_EOL;
            echo PHP_EOL;

            $squareHouses = array();
            foreach ($houses as $house) {
                if (
                    null !== $house['coordinates']['longitude'] && null !== $house['coordinates']['latitude']
                    && $minLongitude >= $house['coordinates']['longitude'] && $house['coordinates']['longitude'] <= $maxLongitude
                    && $minLatitude >= $house['coordinates']['latitude'] && $house['coordinates']['latitude'] <= $maxLatitude
                ) {
                    $squareHouses[] = $house;
                }
            }

            echo '  Assigned ' . count($squareHouses) . ' ' . (count($squareHouses) != 1 ? 'houses' : 'house') . ' to square' . PHP_EOL;
            echo PHP_EOL;

            $squares[] = array(
                'square' => array(
                    'minLongitude' => $minLongitude,
                    'minLatitude'  => $minLatitude,
                    'maxLongitude' => $maxLongitude,
                    'maxLatitude'  => $maxLatitude
                ),
                'photos' => $flickr->findPhotos($minLongitude, $minLatitude, $maxLongitude, $maxLatitude),
                'houses' => $squareHouses,
            );
        }
    }

    $data = array(
        'cameraModels' => $flickr->cameraModels,
        'photgraphers' => $flickr->photographers,
        'squares' => $squares
    );

    echo 'Writing data to cache file' . PHP_EOL;
    file_put_contents('/tmp/city', serialize($data));
} else {
    echo 'Reading data from cache file' . PHP_EOL;
    $data = unserialize(file_get_contents('/tmp/city'));
}
