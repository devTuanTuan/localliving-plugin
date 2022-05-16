<?php

namespace LocalLiving_Plugin\iTravelAPI\Helper;

class XMLSerializer
{
    // functions adopted from http://www.sean-barton.co.uk/2009/03/turning-an-array-or-object-into-xml-using-php/

    public static function generateValidXmlFromObj(stdClass $obj, $node_block = 'nodes', $node_name = 'node')
    {
        $arr = get_object_vars($obj);
        return self::generateValidXmlFromArray($arr, $node_block, $node_name);
    }

    public static function generateValidXmlFromArray($array, $node_block = 'nodes', $node_name = 'node')
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>';

        $xml .= '<' . $node_block . '>';
        $xml .= self::generateXmlFromArray($array, $node_name);
        $xml .= '</' . $node_block . '>';

        return $xml;
    }

    public static function generateXmlFromArray($array, $node_name)
    {
        $xml = '';

        if (is_array($array) || is_object($array))
        {
            foreach ($array as $key => $value)
            {
                if (is_numeric($key))
                {
                    $key = $node_name;
                }
                if (is_array($value))
                {
                    // Ako je ARRAY, onda je numeri�ki. To zna�i da mi iz objekta [0] => ne�to moramo izgenerirati tag <$node_name>ne�to</node_name>
                    $xml .= self::generateXmlFromArray($value, $key);
                }
                else if (is_object($value))
                {
                    $xml .= '<' . $key . '>' . self::generateXmlFromArray($value, $key) . '</' . $key . '>';
                }
                else
                {
                    $xml .= '<' . $key . '>' . self::generateXmlFromArray($value, $key) . '</' . $key . '>';
                }
            }
        }
        else
        {
            $xml = htmlspecialchars($array, ENT_QUOTES);
        }

        return $xml;
    }
}
