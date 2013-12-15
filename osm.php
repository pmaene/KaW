<?php
/**
 * OpenStreetMap
 * @author Pieter Maene <pieter.maene@student.kuleuven.be>
 */

class OpenStreetMap
{
    private $_minLongitude;
    private $_minLatitude;
    private $_maxLongitude;
    private $_maxLatitude;

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

    public function findCafes()
    {
        $elements = $this->_doRequest('amenity="cafe"');
        echo '  Found ' . count($elements) . ' ' . (count($elements) != 1 ? 'cafes' : 'cafe') . PHP_EOL;
        return $elements;
    }

    public function findHotels()
    {
        $elements = $this->_doRequest('tourism="hotel"');
        echo '  Found ' . count($elements) . ' ' . (count($elements) != 1 ? 'hotels' : 'hotel') . PHP_EOL;
        return $elements;
    }

    public function findRestaurants()
    {
        $elements = $this->_doRequest('cuisine');
        echo '  Found ' . count($elements) . ' ' . (count($elements) != 1 ? 'restaurants' : 'restaurant') . PHP_EOL;
        return $elements;
    }

    public function findShops()
    {
        $elements = $this->_doRequest('shop');
        echo '  Found ' . count($elements) . ' ' . (count($elements) != 1 ? 'shops' : 'shop') . PHP_EOL;
        return $elements;
    }

    private function _doRequest($type)
    {
        $data = json_decode(file_get_contents(
            'http://overpass-api.de/api/interpreter?' . http_build_query(array(
                'data' => '[out:json];node(' . $this->_minLatitude . ',' . $this->_minLongitude . ',' . $this->_maxLatitude  . ',' . $this->_maxLongitude . ')[' . $type . '];out body;'
            ))
        ));

        $elements = array();
        foreach ($data->elements as $element) {
            $elements[] = array(
                'id' => $element->id,
                'coordinates' => array(
                    'latitude' => $element->lat,
                    'longitude' => $element->lon
                )
            );
        }

        return $elements;
    }
}
