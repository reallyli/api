<?php

namespace App\Nova\Filters;

use App\Models\Category;
use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;
use Elasticsearch\ClientBuilder;

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
            ->join('categories', 'categories.id', '=', 'business_category.category_id')
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

        if ($categoryIds = cache('categoryIds'.auth()->id())) {
            // a
            // a-a
            // a-a-a
            $result = ClientBuilder::create()
                ->setHosts(config('scout_elastic.client.hosts'))
                ->build()->search($this->getParams(json_decode($categoryIds, true)));

            collect($result['hits']['hits'])->pluck('_source.name')
                ->filter(function ($category) use (&$categories) {
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
        }

        return $categories;
    }

    public function getParams($categoryIds)
    {
        return [
            'index' => 'category',
            'type' => 'categories',
            'body' => [
                'size' => Category::LIMIT,
                'sort'=> [
                    [
                        'name.raw'=> [
                            'order'=> 'asc'
                        ]
                    ]
                ],
                'query' => [
                    'bool' => [
                        'filter' => [
                            'terms' => [
                                'id' => $categoryIds,
                            ],
                        ]
                    ]
                ]
            ],
        ];
    }
}
