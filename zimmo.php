<?php
/**
 * Zimmo
 * @author Stijn Meul <stijn.meul@student.kuleuven.be>
 */

class Zimmo
{
    // Parameters to define whether real estate that is for sale or real estate that is for rent has to be scraped
    const FOR_SALE = 1;
    const FOR_RENT = 2;

    // Parameters to define which type of real estate that has to be scraped
    const HOUSES_AND_FLATS = 'Woning%3BAppartement';
    const HOUSES = 'Woning';
    const FLATS = 'Appartement';
    const BUSINESS = 'Handel-Kantoor';

    private $_cookieFile = '';

    public function __construct()
    {
        $this->_cookieFile = tempnam('/tmp', 'curl_cookie');
    }

    public function scrape($postalCode, $cityName, $province, $typeOfRealEstate, $buyRealEstate) {
        $nbPagesScraped = 0;

        $urlOfFirstPage = $this->_getResultPageUrl($postalCode, $cityName, $province, $typeOfRealEstate, $buyRealEstate, 4, false);
        $xpathOfFirstPage = $this->_getDataOfPage($urlOfFirstPage, 0);
        $nbResults = $this->_getNbResults($xpathOfFirstPage);
        $nbPages = ceil($nbResults/20);

        echo 'Found ' . $nbResults . ' ' . ($nbResults != 1 ? 'houses' : 'house') . ' in ' . $nbPages . ' ' . ($nbPages != 1 ? 'pages' : 'page') . PHP_EOL;

        $houses = [];
        for ($i = 1; $i <= $nbPages; $i++) {
            $houses = array_merge(
                $this->_getHouseInformationOf(
                    $this->_getDataOfPage(
                        $this->_getResultPageUrl($postalCode, $cityName, $province, $typeOfRealEstate, $buyRealEstate, $i),
                        $this->_cookieFile,
                        1
                    ),
                    $this->_cookieFile
                ),
                $houses
            );
        }

        return $houses;
    }

    private function _getResultPageUrl($postalCode, $cityName, $province, $buyRealEstate, $typeOfRealEstate, $page, $display = true) {
        if ($display)
            echo '  Processing page ' . $page . PHP_EOL;

        return 'http://www.zimmo.be/nl/?gemeente='
            . $postalCode
            . '+'
            . $cityName
            . '%2C+'
            . $province
            . '&status='
            . $buyRealEstate
            . '&types='
            . $typeOfRealEstate
            . '&slpk_max=maximum&prijs_max=maximum&sort=score&sort_order=DESC&search_type=city&layout=list&searchText='
            . $postalCode
            . '+'
            . $cityName
            . '%2C+'
            . $province
            . '&pagina='
            . $page;
    }

    private function _getNbResults($xpathOfFirstPage) {
        $queryStatus = $xpathOfFirstPage->query("//span[@class='results']")->item(0)->nodeValue;
        preg_match_all('/\d+/', $queryStatus, $matches);
        return $matches[0][2];
    }

    private function _getHouseInformationOf($xpathOfResultPage) {
        $houses = [];

        // Get all house prices
        $pricesOnPage = $xpathOfResultPage->query("//span[@class='price padding']");
        for ($i=0; $i < $pricesOnPage->length; $i++) {
            $price = $pricesOnPage->item($i)->nodeValue;
            preg_match_all('/\d+/', $price, $matches);
            $price = '';
            foreach ($matches[0] as $key => $value) {
                $price = $price . $value;
            }
            $houses[$i]['price'] = $price;
        }

        // Get all ID's of the houses on this page
        $idsOnPage = $xpathOfResultPage->query("//div[@class='item']/@id");
        // New projects get another class apparently
        $idsProjectOnPage = $xpathOfResultPage->query("//div[@class='item project']/@id");
        $j = 0;
        foreach ($idsOnPage as $key => $value) {
            $houses[$value->nodeValue] = $houses[$j];
            unset($houses[$j]);
            $j++;
        }
        foreach ($idsProjectOnPage as $key => $value) {
            $houses[$value->nodeValue] = $houses[$j];
            unset($houses[$j]);
            $j++;
        }

        // Get all areas of the houses on this page
        foreach ($houses as $key => $value) {
            $areasOnPage = $xpathOfResultPage->query("//div[@id='" . $key . "']/div[@class='clearfix']/p[@class='col second']/span");

            $houses[$key]['area']['living'] = null;
            if(false !== strpos($areasOnPage->item(0)->nodeValue, 'Woonopp.')) {
                preg_match_all("/\d+/", $areasOnPage->item(1)->nodeValue, $matches);
                if(!empty($matches[0])) {
                    $area = '';
                    foreach ($matches[0] as $key2 => $value2) {
                        $area = $area . $value2;
                    }
                    $houses[$key]['area']['living'] = $area;
                }
            } else {
                // This is again a new project exception
                $areasOnPage = $xpathOfResultPage->query("//div[@id='" . $key . "']/div[@class='project-details']/table/tbody/tr/td");
                preg_match_all("/\d+/", $areasOnPage->item(1)->nodeValue, $matches);
                if(!empty($matches[0])) {
                    $area = '';
                    foreach ($matches[0] as $key2 => $value2) {
                        $area = $area . $value2;
                    }
                    $houses[$key]['area']['living'] = $area;
                } else {
                    $houses[$key]['area']['living'] = null;
                }

            }
            if(false !== strpos($areasOnPage->item(2)->nodeValue, 'Grondopp.')) {
                preg_match_all("/\d+/", $areasOnPage->item(3)->nodeValue, $matches);
                if(count($matches[0]) == 0) {
                    $houses[$key]['area']['terrain'] = null;
                } else {
                    $area = "";
                    foreach ($matches[0] as $key2 => $value2) {
                        $area = $area . "" . $value2;
                    }
                    $houses[$key]['area']['terrain'] = $area;
                }
            } else {
                $houses[$key]['area']['terrain'] = null;
            }
        }

        // Get all coordinates of the houses on this page
        foreach ($houses as $key => $value) {
            $coordinatesOnPage = $xpathOfResultPage->query("//div[@id='" . $key . "']/div[@class='clearfix']/a/@class");
            // Check for each house whether it has an address
            if(false !== strstr($coordinatesOnPage->item(0)->nodeValue, 'noMap')) {
                // Current house with ID $key does not have an address set
                $houses[$key]['coordinates']['latitude'] = null;
                $houses[$key]['coordinates']['longitude'] = null;
            } else {
                // Current house with ID $key does have an address set
                $coordinatesOnPage = $xpathOfResultPage->query("//div[@id='" . $key . "']/div[@class='clearfix']/a/@onclick");
                preg_match_all('/\d+/', $coordinatesOnPage->item(0)->nodeValue, $matches);
                $houses[$key]['coordinates']['latitude'] = $matches[0][0] . '.' . $matches[0][1];
                $houses[$key]['coordinates']['longitude'] = $matches[0][2] . '.' . $matches[0][3];
            }
        }

        // Calculate and add the normalised price per square meter for each house
        foreach ($houses as $key => $value) {
            if(!is_null($value['area']['terrain']) && !is_null($value['area']['living']) && !is_null($value['price'])) {
                $maxArea = max($value['area']['terrain'],$value['area']['living']);
                $houses[$key]['normalisedPrice'] = $value['price']/$maxArea;
            } else {
                if(!is_null($value['price']) && !is_null($value['area']['terrain']))
                    $houses[$key]['normalisedPrice'] = $value['price']/$value['area']['terrain'];
                elseif(!is_null($value['price']) && !is_null($value['area']['living']))
                    $houses[$key]['normalisedPrice'] = $value['price']/$value['area']['living'];
                else
                    $houses[$key]['normalisedPrice'] = null;
            }
        }

        return $houses;
    }

    private function _getDataOfPage($url, $nbPagesScraped) {
        $domPage = new DOMDocument();
        $agents = array(
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534.24 (KHTML, like Gecko) Chrome/11.0.696.60 Safari/534.24',
            'Opera/9.63 (Windows NT 6.0; U; ru) Presto/2.1.1',
            'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.5) Gecko/2008120122 Firefox/3.0.5',
        );

        // Download the search results
        $curlHandle = curl_init($url);

        curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 100);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 100);
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_USERAGENT, $agents[rand(0, count($agents) - 1)]);
        if ($nbPagesScraped == 0) {
            curl_setopt($curlHandle, CURLOPT_COOKIEJAR, $this->_cookieFile);
        } else {
            curl_setopt($curlHandle, CURLOPT_COOKIEFILE, $this->_cookieFile);
        }
        $data = curl_exec($curlHandle);
        curl_close($curlHandle);
        $nbPagesScraped++;

        @$domPage->loadHTML($data);
        // Clear yukky HTML errors
        libxml_clear_errors();

    	return $xpathPage = new DOMXPath($domPage);
    }
}
