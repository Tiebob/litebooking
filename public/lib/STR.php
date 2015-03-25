<?php

/**
 * Created by PhpStorm.
 * User: bob
 * Date: 2014/11/28
 * Time: 下午 04:12
 */
class STR
{

    function __construct()
    {
    }


    /**
     * 在字串 $string 前後加上字元 $symbol
     * @param $string
     * @param $symbol
     * @return string
     */
    public static function concatlr($string, $symbol)
    {
        return ($symbol . $string . $symbol);
    }

    /**
     * 在字串 $string 前加上字元 $symbol
     * @param $string
     * @param $symbol
     * @return string
     */
    public static function concatl($string, $symbol)
    {
        return ($symbol . $string);
    }

    /**
     * 在字串 $string 後加上字元 $symbol
     * @param $string
     * @param $symbol
     * @return string
     */
    public static function concatr($string, $symbol)
    {
        return ($string . $symbol);
    }

}