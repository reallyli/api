<?php

namespace App\Elastic\Rules;

use ScoutElastic\SearchRule;

class BusinessSearchRule extends SearchRule
{
    /**
     * @inheritdoc
     */
    public function buildHighlightPayload()
    {
        return [
            'fields' => [
                'name' => [
                    'type' => 'plain'
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function buildQueryPayload()
    {
        $query = $this->builder->query;
        return [
            'must' => [
                'fuzzy' =>
                    [
                        'name' =>
                            [
                                'value' => $query,
                                'boost' => 1,
                                'fuzziness' => 5,
                                'prefix_length' => 0,
                                'max_expansions' => 100,
                            ],
                    ],
            ],
        ];
    }
}
