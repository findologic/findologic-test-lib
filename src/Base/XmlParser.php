<?php

namespace Findologic\Base;

class XmlParser
{

    /** @var XmlParser */
    private static $instance = null;

    private function __construct()
    {
    }

    /**
     * @return XmlParser
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new XmlParser();
        }

        return self::$instance;
    }

    /**
     * Parses xml string to array
     *
     * @param $url
     * @return array|string
     * @throws \Exception
     */
    public function parse($url)
    {
        if ($url === false) {
            throw new \Exception('CURL call failed!');
        }

        $simpleXml = simplexml_load_file($url);

        return $this->simpleXML2Array($simpleXml);
    }

    /**
     * Creates array from SimpleXMLElement with all attributes and fields
     *
     * @param \SimpleXMLElement $xml
     * @return array|string
     */
    private function simpleXML2Array($xml)
    {
        if (is_string($xml) || is_numeric($xml)) {
            return $xml;
        }

        $array = (array)$xml;
        if (count($array) == 0) {
            $array = (string)$xml;
        }

        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_object($value)) {
                    if ($value instanceof \SimpleXMLElement) {
                        $array[$key] = $this->simpleXML2Array($value);
                    }
                } else {
                    $array[$key] = $this->simpleXML2Array($value);
                }
            }
        }

        return $array;
    }

}