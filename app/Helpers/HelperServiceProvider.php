<?php

use Illuminate\Support\ServiceProvider;

class HelperServiceProvider extends ServiceProvider
{
    public static function generatePIN($digits = 4)
    {
        $i = 0; //counter
        $pin = ""; //our default pin is blank.
        while ($i < $digits) {
            //generate a random number between 0 and 9.
            $pin .= mt_rand(1, 9);
            $i++;
        }
        return $pin;
    }

    public static function inBounds($coordinates, $west, $south, $east, $north)
    {
        return ($coordinates[0] - $east) * ($coordinates[0] - $west) < 0 &&
                ($coordinates[1] - $north) * ($coordinates[1] - $south) < 0;
    }
}
