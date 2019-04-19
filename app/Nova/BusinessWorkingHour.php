<?php

namespace App\Nova;

use App\Models\BusinessWorkingHours;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Select;
use Michielfb\Time\Time;

class BusinessWorkingHour extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = BusinessWorkingHours::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
    ];

    public static $displayInNavigation = false;

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [

            Select::make('Day')->options(
                BusinessWorkingHours::DAYS
            )
                ->displayUsingLabels()
            ->rules('required','between:0,6'),

            Time::make('Start time')
                ->help(
                    'Example: 8:30AM'
                )
                ->rules('required')
                ->format('hh:mm A'),

            Time::make('End time')
                ->help(
                    'Example: 6:30PM'
                )
                ->rules('required')
                ->format('hh:mm A'),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }


}
