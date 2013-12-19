<?php
/**
 * MATLAB
 * @author Pieter Maene <pieter.maene@student.kuleuven.be>
 */

$data = unserialize(file_get_contents('/tmp/city'));

$out = 'realEstate = [';

$row = 0;
$column = 0;
foreach ($data['squares'] as $square) {
    $nbHousesForSale = 0;
    foreach ($square['realEstate'] as $type => $properties) {
        if (isset($square['realEstate'][$type]))
            $nbHousesForSale += count($square['realEstate'][$type]['sale']);
    }

    $avgNormalisedPrice = 0;
    if (0 !== $nbHousesForSale) {
        foreach ($square['realEstate'] as $type => $properties) {
            if (isset($square['realEstate'][$type])) {
                foreach ($square['realEstate'][$type]['sale'] as $property)
                    $avgNormalisedPrice += $property['normalisedPrice']/$nbHousesForSale;
            }
        }
    }

    $out .= $avgNormalisedPrice;

    if (49 == $column%$nbSquares) {
        $out .= '; ';

        $column = 0;
        $row++;
    } else {
        $out .= ' ';
        $column++;
    }
}

$out .= '];';

echo $out . PHP_EOL . PHP_EOL;

$out = 'photos = [';

$row = 0;
$column = 0;
foreach ($data['squares'] as $square) {
    $out .= count($square['photos']);

    if (49 == $column%$nbSquares) {
        $out .= '; ';

        $column = 0;
        $row++;
    } else {
        $out .= ' ';
        $column++;
    }
}

$out .= '];';

echo $out . PHP_EOL . PHP_EOL;

$out = 'osm = [';

$row = 0;
$column = 0;
foreach ($data['squares'] as $square) {
    $out .= count($square['cafes']) + count($square['hotels']) + count($square['restaurants']) + count($square['shops']);

    if (49 == $column%$nbSquares) {
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