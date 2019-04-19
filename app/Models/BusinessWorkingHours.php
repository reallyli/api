<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class BusinessWorkingHours extends Model
{
    public const DAYS = [
        'Sun',
        'Mon',
        'Tues',
        'Wed',
        'Thurs',
        'Fri',
        'Sat',
        /*
          * Days:
          * 0 = Sun
          * 1 = Mon
          * 2 = Tues
          * 3 = Wed
          * 4 = Thurs
          * 5 = Fri
          * 6 = Sat
          * */

    ];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    protected function getFormattedTime($value)
    {
        return Carbon::createFromFormat('H:i:s', $value)->format('h:i A');
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESORS
    |--------------------------------------------------------------------------
    */

    public function getStartTimeFormattedAttribute()
    {
        return $this->getFormattedTime($this->start_time);
    }

    public function getEndTimeFormattedAttribute()
    {
        return $this->getFormattedTime($this->end_time);
    }


    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */


}
