<?php

namespace App\Nova\Filters;

use App\Models\Category;
use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;

class BusinessCategory extends Filter
{
    /**
     * Apply the filter to the given query.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  mixed $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Request $request, $query, $value)
    {
        return $query
            ->select('businesses.*')
            ->join('business_category', 'businesses.id', '=', 'business_category.business_id')
            ->join('categories', 'business_category.category_id', '=', 'categories.id')
            ->where('categories.name', $value);
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function options(Request $request)
    {
        $categories = [];

        // a
        // a-a
        // a-a-a
        Category::pluck('name')->map(function ($category) {
            return trim($category, '/');
        })->sort()->filter(function ($category) use (&$categories) {
            return strpos($category, '-') !== false ? true : ($categories[$category] = $category) && false;
        })->filter(function ($category) use (&$categories) {
            return substr_count($category, '-') > 1 ? true : ($categories[$category] = $category) && false;
        })->filter(function ($category) use (&$categories) {
            return substr_count($category, '-') > 2 ? true : ($categories[$category] = $category) && false;
        })->filter(function ($category) use (&$categories) {
            return substr_count($category, '-') > 3 ? true : ($categories[$category] = $category) && false;
        })->filter(function ($category) use (&$categories) {
            return substr_count($category, '-') > 4 ? true : ($categories[$category] = $category) && false;
        });

        return $categories;
    }
}
