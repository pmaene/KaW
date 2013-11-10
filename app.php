<?php

// Constants
const API_KEY = 'e3a5ebef0de67a460ad53fa4b84b83c2';

// Globals
$cameraModels = array();

// Parameters
$minAreaLongitude = 51.052872;
$maxAreaLongitude = 51.058872;
$minAreaLatitude  = 3.7185141;
$maxAreaLatitude  = 3.7285141;

$nbSquares = 100;

// Main
$longitudeResolution = round(($maxAreaLongitude - $minAreaLongitude)/sqrt($nbSquares), 6);
$latitudeResolution = round(($maxAreaLatitude - $minAreaLatitude)/sqrt($nbSquares), 6);

if (!file_exists('/tmp/city.flickr')) {
    echo 'Fetching data for ' . $nbSquares . ' ' . ($nbSquares != 1 ? 'squares' : 'square') . PHP_EOL;
    echo PHP_EOL;
    echo 'Resolution' . PHP_EOL;
    echo '  Longitude: ' . $longitudeResolution . PHP_EOL;
    echo '  Latitude: ' . $latitudeResolution . PHP_EOL;
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

            $squares[] = array_merge(
                array(
                    'square' => array(
                        'minLongitude' => $minLongitude,
                        'minLatitude'  => $minLatitude,
                        'maxLongitude' => $maxLongitude,
                        'maxLatitude'  => $maxLatitude
                    ),
                ),
                findPhotos($minLongitude, $minLatitude, $maxLongitude, $maxLatitude)
            );
        }
    }

    $data = array(
        'cameraModels' => $cameraModels,
        'squares' => $squares
    );

    echo 'Writing data to cache file' . PHP_EOL;
    file_put_contents('/tmp/city.flickr', serialize($data));
} else {
    echo 'Reading data from cache file' . PHP_EOL;
    $data = unserialize(file_get_contents('/tmp/city.flickr'));
}

echo PHP_EOL;

echo 'Writing XML data' . PHP_EOL;
file_put_contents('/tmp/city.xml', convertDataToXml($data));

// Functions
function findPhotos($minLongitude, $minLatitude, $maxLongitude, $maxLatitude) {
    global $cameraModels;

    $photographers = array();
    $photos = array();

    $result = doRequest(
        'flickr.photos.search',
        array(
            'bbox' => $minLongitude . ',' . $minLatitude . ',' . $maxLongitude  . ',' . $maxLatitude
        )
    );

    $nbPages = $result['photos']['pages'];
    echo '  Found ' . $result['photos']['total'] . ' ' . ($result['photos']['total'] != 1 ? 'photos' : 'photo') . ' in ' . $nbPages . ' ' . ($nbPages != 1 ? 'pages' : 'page') . PHP_EOL;

    for ($i = 0; $i < $nbPages; $i++) {
        echo '    Processing page ' . ($i + 1) . PHP_EOL;

        $result = doRequest(
            'flickr.photos.search',
            array(
                'bbox' => $minLongitude . ',' . $minLatitude . ',' . $maxLongitude  . ',' . $maxLatitude,
                'page' => $i+1
            )
        );

        foreach ($result['photos']['photo'] as $photo) {
            echo '      Processing photo ' . $photo['id'] . PHP_EOL;

            $exifInfo = getPhotoExif($photo['id']);
            if (isset($exifInfo['photo'])) {
                $cameraModel = $exifInfo['photo']['camera'];
                if (false === ($cameraModelKey = array_search($cameraModel, $cameraModels))) {
                    $cameraModels[] = $cameraModel;
                    $cameraModelKey = array_search($cameraModel, $cameraModels);
                }

                if (isset($photographers[$photo['owner']])) {
                    $photographers[$photo['owner']]['photos'][] = $photo['id'];
                    if (false !== $cameraModelKey && false === array_search($cameraModelKey, $photographers[$photo['owner']]['cameraModels']))
                        $photographers[$photo['owner']]['cameraModels'][] = $cameraModelKey;
                } else {
                    $photographers[$photo['owner']] = array(
                        'cameraModels' => array(
                            $cameraModelKey
                        ),
                        'photos' => array(
                            $photo['id']
                        ),
                    );
                }

                $photos[$photo['id']] = array(
                    'cameraModel'  => $cameraModelKey,
                    'photographer' => $photo['owner'],
                    'time'         => strtotime(getPhotoInfo($photo['id'])['photo']['dates']['taken']),
                    'views'        => getPhotoInfo($photo['id'])['photo']['views']
                );
            }
        }
    }

    echo PHP_EOL;

    return array(
        'photographers' => $photographers,
        'photos'        => $photos
    );
}

function getPhotoInfo($id) {
    return doRequest(
        'flickr.photos.getInfo',
        array(
            'photo_id' => $id
        )
    );
}

function getPhotoExif($id) {
    return doRequest(
        'flickr.photos.getExif',
        array(
            'photo_id' => $id
        )
    );
}

function doRequest($method, $arguments) {
    $parameters = array_merge(
        array(
            'api_key' => API_KEY,
            'method'  => $method,
            'format'  => 'php_serial'
        ),
        $arguments
    );

    return unserialize(file_get_contents(
        'http://api.flickr.com/services/rest/?' . http_build_query($parameters)
    ));
}

function convertDataToXml($data) {
    $xmlData = '';
    $photographers = array();

    echo '  Camera Models' . PHP_EOL;
        foreach ($data['cameraModels'] as $nbCameraModel => $cameraModel)
            $xmlData .= convertCameraModelToXml($nbCameraModel, $cameraModel);

    foreach ($data['squares'] as $nbSquare => $square) {
        echo '  Square ' . ($nbSquare+1) . PHP_EOL;
        $xmlData .= convertSquareToXml($nbSquare, $square['square']);

        echo '    Photographers' . PHP_EOL;
        foreach ($square['photographers'] as $idPhotographer => $photographer) {
            if (false === array_search($idPhotographer, $photographers)) {
                $photographers[] = $idPhotographer;
                $xmlData .= convertPhotographerToXml($idPhotographer, $photographer);
            }
        }

        echo '    Photos' . PHP_EOL;
        foreach ($square['photos'] as $idPhoto => $photo)
            $xmlData .= convertPhotoToXml($idPhoto, $photo, $nbSquare);
    }

    return $xmlData;
}

function convertSquareToXml($nbSquare, $square) {
    $object = new XmlObject(
        'owl:NamedIndividual',
        array(
            'rdf:about' => '&city;Square_' . $nbSquare
        ),
        array(
            new XmlObject(
                'city:hasMinLongitudeValue',
                array(
                    'rdf:datatype' => '&xsd;float'
                ),
                (string) $square['minLongitude']
            ),
            new XmlObject(
                'city:hasMinLatitudeValue',
                array(
                    'rdf:datatype' => '&xsd;float'
                ),
                (string) $square['minLatitude']
            ),
            new XmlObject(
                'city:hasMaxLongitudeValue',
                array(
                    'rdf:datatype' => '&xsd;float'
                ),
                (string) $square['maxLongitude']
            ),
            new XmlObject(
                'city:hasMaxLatitudeValue',
                array(
                    'rdf:datatype' => '&xsd;float'
                ),
                (string) $square['maxLatitude']
            ),
        )
    );

    return (string) $object;
}

function convertCameraModelToXml($nbCameraModel, $cameraModel) {
    $object = new XmlObject(
        'owl:NamedIndividual',
        array(
            'rdf:about' => '&city;CameraModel_' . $nbCameraModel
        ),
        array(
            new XmlObject(
                'city:hasPriceValue',
                array(
                    'rdf:datatype' => '&xsd;float'
                ),
                (string) 0.0
            ),
            new XmlObject(
                'city:hasNameValue',
                array(
                    'rdf:datatype' => '&xsd;string'
                ),
                $cameraModel
            ),
        )
    );

    return (string) $object;
}

function convertPhotographerToXml($idPhotographer, $photographer) {
    $cameraModels = array();
    foreach ($photographer['cameraModels'] as $cameraModel) {
        $cameraModels[] = new XmlObject(
            'city:hasCameraModel',
            array(
                'rdf:resource' => '&city;CameraModel_' . $cameraModel
            )
        );
    }

    $photos = array();
    foreach ($photographer['photos'] as $photo) {
        $cameraModels[] = new XmlObject(
            'city:isPhotographerOf',
            array(
                'rdf:resource' => '&city;Photo_' . $photo
            )
        );
    }

    $object = new XmlObject(
        'owl:NamedIndividual',
        array(
            'rdf:about' => '&city;Photographer_' . $idPhotographer
        ),
        array_merge(
            $cameraModels,
            $photos
        )
    );

    return (string) $object;
}

function convertPhotoToXml($idPhoto, $photo, $nbSquare) {
    $object = new XmlObject(
        'owl:NamedIndividual',
        array(
            'rdf:about' => '&city;Photo_' . $idPhoto
        ),
        array(
            new XmlObject(
                'city:hasLocation',
                array(
                    'rdf:resource' => '&city;Square_' . $nbSquare
                )
            ),
            new XmlObject(
                'city:hasCameraModel',
                array(
                    'rdf:resource' => '&city;CameraModel_' . $photo['cameraModel']
                )
            ),
            new XmlObject(
                'city:hasPhotographer',
                array(
                    'rdf:resource' => '&city;Photographer_' . $photo['photographer']
                )
            ),
            new XmlObject(
                'city:hasTimeValue',
                array(
                    'rdf:datatype' => '&xsd;positiveInteger'
                ),
                (string) $photo['time']
            ),
            new XmlObject(
                'city:hasViewsValue',
                array(
                    'rdf:datatype' => '&xsd;positiveInteger'
                ),
                (string) $photo['views']
            )
        )
    );

    return (string) $object;
}

// Classes
class XmlObject
{
    /**
     * @var string The object's content
     */
    private $_content;

    /**
     * @param string $tag The object's tag
     * @param array $params The object's paramters
     * @param mixed $content The object's content
     * @throws \CommonBundle\Component\Util\Xml\Exception\InvalidArugmentException The given content was invalid
     */
    public function __construct($tag, array $params = null, $content = null)
    {
        if (($tag === null) || !is_string($tag))
            throw new InvalidArgumentException('Invalid tag');

        if ($content === null) {
            if ($params === null) {
                $this->_content = '<' . $tag . '/>';
            } else {
                $this->_content .= '<' . $tag;
                foreach ($params as $key => $value)
                    $this->_content .= ' ' . $key . '="' . $this->_escape($value) . '"';
                $this->_content .= '/>';
            }
        } else {
            if ($params === null) {
                $this->_content = '<' . $tag . '>';
            } else {
                $this->_content .= '<' . $tag;
                foreach ($params as $key => $value)
                    $this->_content .= ' ' . $key . '="' . $this->_escape($value) . '"';
                $this->_content .= '>';
            }

            if (is_string($content)) {
                $this->_content .= $this->_escape($content);
            } else if ($content instanceof XmlObject) {
                $this->_content .= $content->__toString();
            } else if (is_array($content)) {
                foreach ($content as $part) {
                    if (is_string($part)) {
                        $this->_content .= $this->_escape($part);
                    } else if ($part instanceof XmlObject) {
                        $this->_content .= $part->__toString();
                    } else {
                        throw new InvalidArgumentException('The given content was invalid');
                    }
                }
            } else {
                throw new InvalidArgumentException('The given content was invalid');
            }

            $this->_content .= '</' . $tag . '>';
        }
    }

    /**
     * Converts an UTF-8 content value to HTML.
     *
     * @param string $value The value that should be converted
     * @return string
     */
    private function _escape($value)
    {
        return UTF8::utf8ToHtml($value, true);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->_content;
    }
}

class Utf8
{
    /**
     * Convert a UTF-8 string to HTML.
     *
     * @static
     * @param string $utf8 The string in UTF-8 charset
     * @param boolean $encodeTags True will convert "<" to "&lt;", default value is false
     * @return string
     * @throws \CommonBundle\Component\Util\Exception\InvalidArgumentException The given first parameter was not a string
     */
    public static function utf8ToHtml($utf8, $encodeTags = false)
    {
        if ($utf8 === null)
            return null;

        if (!is_string($utf8)) {
            throw new InvalidArgumentException(
                'Expected a string as first parameter, not ' . gettype($utf8)
            );
        }

        $result = '';
        for ($i = 0; $i < strlen($utf8); $i++) {
            $char = $utf8[$i];
            $ascii = ord($char);
            if ($ascii < 128) {
                // One-byte character
                $result .= ($encodeTags) ? htmlentities($char) : $char;
            } else if ($ascii < 192) {
                // Non-utf8 character or not a start byte
            } else if ($ascii < 224) {
                // Two-byte character
                $ascii1 = ord($utf8[$i+1]);
                $unicode = (15 & $ascii) * 64 + (63 & $ascii1);
                $result .= '&#x' . dechex($unicode) . ';';
                $i++;
            } else if ($ascii < 240) {
                // Three-byte character
                $ascii1 = ord($utf8[$i+1]);
                $ascii2 = ord($utf8[$i+2]);
                $unicode = (15 & $ascii) * 4096 + (63 & $ascii1) * 64 + (63 & $ascii2);
                $result .= '&#x' . dechex($unicode) .';';
                $i += 2;
            } else if ($ascii < 248) {
                // Four-byte character
                $ascii1 = ord($utf8[$i+1]);
                $ascii2 = ord($utf8[$i+2]);
                $ascii3 = ord($utf8[$i+3]);
                $unicode = (15 & $ascii) * 262144 + (63 & $ascii1) * 4096 + (63 & $ascii2) * 64 + (63 & $ascii3);
                $result .= '&#x' . dechex($unicode) . ';';
                $i += 3;
            }
        }

        $result = str_replace('&amp;city;', '&city;', $result);
        $result = str_replace('&amp;xsd;', '&xsd;', $result);

        return $result;
    }
}
