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
    public function buildQueryPayload() {
        $keyword = $this->builder->query;

	session_start();
	$bounds = false;
	$filter = [];
	if(isset($_SESSION['last_biz_map_query'])) {
		$bounds = $_SESSION['last_biz_map_query'];
		$filter = [
			'bool' => [
			    'must' => [
				'geo_bounding_box' => [
				    'location' => $bounds,
				],
			    ],
			],
		    ];
	}


	$fuzz = 0;
        if(strlen($keyword) >= 4) {
            $fuzz = 1;
        }
        if(strlen($keyword) >= 6) {
            $fuzz = 2;
        }

        $queryMatch = [
            'must' => [
                'fuzzy' =>
                    [
                        'name' =>
                            [
                                'value' => $keyword,
                                'boost' => 1,
                                'fuzziness' => $fuzz,
                                'prefix_length' => 0,
                                'max_expansions' => 100,
                            ],
                    ],
            ],
        ];
	if(!empty($filter)) {
            $queryMatch['filter'] = $filter;
	}

	return $queryMatch;
    }
}
