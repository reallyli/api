<?php

namespace App\Elastic\Configurators;

use ScoutElastic\IndexConfigurator;
use ScoutElastic\Migratable;

class BusinessPost extends IndexConfigurator
{
    use Migratable;

    // protected $name = 'post_200000';
    
    /**
     * @var array
     */
    protected $settings = [
        'analysis' => [
            'analyzer' => [
                'substring_analyzer' => [
                    'tokenizer' => 'keyword',
                    'filter'    => ['lowercase']
                ]
            ]
        ]
    ];

    protected $defaultMapping = [
        'properties' => [
            'id'          => [
                'type' => 'text'
            ],
            'business_id' => [
                'type' => 'integer'
            ],
            'user_id'     => [
                'type' => 'integer'
            ],
            'type'        => [
                'type' => 'text'
            ],
            'location'    => [
                'type'  => 'geo_point',
                'index' => 'true'
            ],
            'images'      => [
                'type'       => 'nested',
                'properties' => [
                    'path' => [
                        'type' => 'text'
                    ]
                ]
            ],
            'text'        => [
                'type' => 'text',
            ],
            'meta'        => [
                'type' => 'text'
            ],
            'expire_date' => [
                'type' => 'text'
            ],
            'hours'          => [
                'type'       => 'nested',
                'properties' => [
                    'day_of_week'       => [
                        'type'  => 'byte',
                        'index' => 'true'
                    ],
                    'open_period_mins'  => [
                        'type'  => 'short',
                        'index' => 'true'
                    ],
                    'close_period_mins' => [
                        'type'  => 'short',
                        'index' => 'true'
                    ]
                ]
            ],
        ]
    ];
}
