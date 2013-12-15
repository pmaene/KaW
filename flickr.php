<?php
/**
 * Flickr
 * @author Pieter Maene <pieter.maene@student.kuleuven.be>
 */

class Flickr
{
    private $_apiKey = '';

    private $_minLatitude;
    private $_maxLatitude;
    private $_minLongitude;
    private $_maxLongitude;

    public $cameraModels = array();
    public $photographers = array();

    public function __construct($apiKey)
    {
        $this->_apiKey = $apiKey;
    }

    public function setMinLatitude($minLatitude)
    {
        $this->_minLatitude = $minLatitude;
        return $this;
    }

    public function setMaxLatitude($maxLatitude)
    {
        $this->_maxLatitude = $maxLatitude;
        return $this;
    }

    public function setMinLongitude($minLongitude)
    {
        $this->_minLongitude = $minLongitude;
        return $this;
    }

    public function setMaxLongitude($maxLongitude)
    {
        $this->_maxLongitude = $maxLongitude;
        return $this;
    }

    public function findPhotos()
    {
        $result = $this->_doRequest(
            'flickr.photos.search',
            array(
                'bbox' => $this->_minLatitude . ',' . $this->_minLongitude . ',' . $this->_maxLatitude  . ',' . $this->_maxLongitude
            )
        );

        $nbPages = $result['photos']['pages'];
        echo '  Found ' . $result['photos']['total'] . ' ' . ($result['photos']['total'] != 1 ? 'photos' : 'photo') . ' in ' . $nbPages . ' ' . ($nbPages != 1 ? 'pages' : 'page') . PHP_EOL;

        $photos = array();
        for ($i = 0; $i < $nbPages; $i++) {
            echo '    Processing page ' . ($i + 1) . PHP_EOL;

            $result = $this->_doRequest(
                'flickr.photos.search',
                array(
                    'bbox' => $this->_minLatitude . ',' . $this->_minLongitude . ',' . $this->_maxLatitude  . ',' . $this->_maxLongitude,
                    'page' => $i+1
                )
            );

            foreach ($result['photos']['photo'] as $photo) {
                echo '      Processing photo ' . $photo['id'] . PHP_EOL;

                $exifInfo = $this->_getPhotoExif($photo['id']);
                if (isset($exifInfo['photo'])) {
                    $cameraModel = $exifInfo['photo']['camera'];
                    if (false === ($cameraModelKey = array_search($cameraModel, $this->cameraModels))) {
                        $this->cameraModels[] = $cameraModel;
                        $cameraModelKey = array_search($cameraModel, $this->cameraModels);
                    }

                    if (isset($this->photographers[$photo['owner']])) {
                        $this->photographers[$photo['owner']]['photos'][] = $photo['id'];
                        if (false !== $cameraModelKey && false === array_search($cameraModelKey, $this->photographers[$photo['owner']]['cameraModels']))
                            $this->photographers[$photo['owner']]['cameraModels'][] = $cameraModelKey;
                    } else {
                        $this->photographers[$photo['owner']] = array(
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
                        'time'         => strtotime($this->_getPhotoInfo($photo['id'])['photo']['dates']['taken']),
                        'views'        => $this->_getPhotoInfo($photo['id'])['photo']['views']
                    );
                }
            }
        }

        echo PHP_EOL;

        return $photos;
    }

    private function _getPhotoInfo($id) {
        return $this->_doRequest(
            'flickr.photos.getInfo',
            array(
                'photo_id' => $id
            )
        );
    }

    private function _getPhotoExif($id) {
        return $this->_doRequest(
            'flickr.photos.getExif',
            array(
                'photo_id' => $id
            )
        );
    }

    private function _doRequest($method, $arguments) {
        $parameters = array_merge(
            array(
                'api_key' => $this->_apiKey,
                'method'  => $method,
                'format'  => 'php_serial'
            ),
            $arguments
        );

        return unserialize(file_get_contents(
            'http://api.flickr.com/services/rest/?' . http_build_query($parameters)
        ));
    }
}
