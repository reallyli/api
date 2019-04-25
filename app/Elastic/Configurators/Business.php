<?php

namespace App\Elastic\Configurators;

use ScoutElastic\IndexConfigurator;
use ScoutElastic\Migratable;

class Business extends IndexConfigurator
{
    use Migratable;

    // < elasticsearch 7.0
    // default index: model name
    // default type: table name
    // set up custom index name
    // protected $name = 'business_200000';

    /**
     * @var array
     */
    protected $settings = [
        'analysis' => [
            'filter'    => [
                'synonym_filter' => [
                    'type'          => 'synonym',
                    'synonyms' => 'analysis/synonym.txt'
//                    'synonyms' => [
//                        "grooming, cleaning"
//                    ]
                ]
            ],
            'tokenizer' => [
                'comma' => [
                    'type'    => 'pattern',
                    'pattern' => ','
                ]
            ],
            'analyzer'  => [
                'english'             => [
                    'type'      => 'standard',
                    'stopwords' => '_english_'
                ],
                'synonym_analyzer'    => [
                    'tokenizer' => 'standard',
                    'filter'    => ['lowercase', 'synonym_filter']
                ],
                'whitespace_analyzer' => [
                    'type'      => 'custom',
                    'tokenizer' => 'comma',
                    'filter'    => [
                        'trim', 'lowercase'
                    ]
                ],
                'substring_analyzer' => [
                    'tokenizer' => 'keyword',
                    'filter'    => ['lowercase']
                ]
            ]
        ]
    ];
}
