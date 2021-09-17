<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class methodsTest extends TestCase
{
    public function test_getPIDs() {
        global $_STORAGE;
        $_STORAGE['pids'] = array('test' => '123456');

        $resDb = methodsBase::getPIDs([]);
        $this->assertEquals(array('pids' => array()), $resDb);
    }

    public function test_killPID() {
        $params = [
            'pid' => '13008',
        ];
        $res = methodsBase::killPID($params);
        $this->assertEquals($res, array(
            array('pg_terminate_backend' => 'f')
        ));
    }

    public function test_addEntities() {
        $params = array(
            'entityName' => 'bookings',
            'schemaName' => 'bookings',
            'key' => 'book_ref',
            'value' => array('44444', '123456')
        );

        $res = methodsBase::deleteEntitiesByKey($params);

        $params = array(
            'entityName' => 'bookings',
            'schemaName' => 'bookings',
            'fields' => array(
                array(
                    'book_ref' => '44444',
                    'book_date' => '2020-03-12 12:01:00+03',
                    'total_amount' => '44444'
                ),
            ),
            'files' => array(),
            'key' => 'book_ref',
            'types' => array(
                'book_ref' => 'text'
            )
        );

        $res = methodsBase::addEntities($params);
        $this->assertEquals($res, array(
            array(
                'book_ref' => '44444'
            ),
        ));

        $params = array(
            'entityName' => 'bookings',
            'schemaName' => 'bookings',
            'fields' => array(
                array(
                    'book_ref' => '123456',
                    'book_date' => '2020-03-12 12:04:00+03',
                    'total_amount' => '654321'
                ),
            ),
            'files' => array(),
            'key' => 'book_ref',
            'types' => NULL
        );

        $res = methodsBase::addEntities($params);
        $this->assertEquals($res, array(
            array(
                'book_ref' => '123456'
            )
        ));

        $params = array(
            'entityName' => 'text_types',
            'schemaName' => 'test_schema',
            'fields' => array(
                array(
                    'text_types_key' => '',
                    'meta_plain' => 'test12',
                    'meta_text' => 'test2',
                    'detail_plain' => 'test3',
                    'detail_text' => '<p>test4</p>'
                )
            ),
            'files' => array(),
            'key' => 'text_types_key',
            'types' => NULL
        );

        $res = methodsBase::addEntities($params);
        $this->assertNotNull($res[0]['text_types_key']);
    }

    public function test_updateEntity() {
        $params = [
            'entityName' => 'bookings',
            'schemaName' => 'bookings',
            'key' => ['book_ref', "total_amount"],
            'value' => [
                0 => ['000068'],
                1 => ['18100'],
            ],
            'fields' => [
                "book_date" => "2020-03-12 18:18:00+03",
            ],
            'files' => [],
            'types' => NULL,
        ];

        $res = methodsBase::updateEntity($params);
        $this->assertEquals($res, [
            "sql" => "UPDATE \"bookings\".\"bookings\" SET \"book_date\" = '2020-03-12 18:18:00+03' WHERE \"book_ref\" = '000068' AND \"total_amount\" = '18100';"
        ]);


        $params = [
            'entityName' => 'bookings',
            'schemaName' => 'bookings',
            'key' => ['book_ref', "total_amount"],
            'value' => [
                0 => ['000068', '000181', '000012'],
                1 => ['18100', '131800', '37900'],
            ],
            'fields' => [
                "book_date" => ["2020-03-12 18:18:00+03", "2020-03-12 18:18:00+03", "2020-03-12 18:18:00+03"],
            ],
            'files' => [],
            'types' => NULL,
        ];

        $res = methodsBase::updateEntity($params);
        $this->assertEquals($res, [
            "sql" => "UPDATE \"bookings\".\"bookings\" SET \"book_date\" = '2020-03-12 18:18:00+03' WHERE \"book_ref\" = '000068' AND \"total_amount\" = '18100';UPDATE \"bookings\".\"bookings\" SET \"book_date\" = '2020-03-12 18:18:00+03' WHERE \"book_ref\" = '000181' AND \"total_amount\" = '131800';UPDATE \"bookings\".\"bookings\" SET \"book_date\" = '2020-03-12 18:18:00+03' WHERE \"book_ref\" = '000012' AND \"total_amount\" = '37900';"
        ]);


        $params = [
            'entityName' => 'bookings',
            'schemaName' => 'bookings',
            'key' => 'book_ref',
            'value' => [
                '123456',
                '44444',
            ],
            'fields' => [
                "total_amount" => ['6666', '9999',],
                "book_date" => ["2020-03-12 18:44:00+03", "2020-03-12 18:55:00+03",],
            ],
            'files' => [],
            'types' => NULL,
        ];

        $res = methodsBase::updateEntity($params);
        $this->assertEquals($res, [
            "sql" => "UPDATE \"bookings\".\"bookings\" SET \"total_amount\" = '6666', \"book_date\" = '2020-03-12 18:44:00+03' WHERE \"book_ref\" = '123456';UPDATE \"bookings\".\"bookings\" SET \"total_amount\" = '9999', \"book_date\" = '2020-03-12 18:55:00+03' WHERE \"book_ref\" = '44444';"
        ]);


        $params = [
            "entityName" => "text_types",
            "schemaName" => "test_schema",
            "key" => "text_types_key",
            "value" => "bb8b8a74-e4ec-4ce9-8b58-8d3de11d8137",
            "fields" => [
                "meta_plain" => NULL,
                "meta_text" => NULL,
                "detail_plain" => NULL,
            ],
            "files" => [
            ],
            "types" => NULL,
        ];

        $res = methodsBase::updateEntity($params);
        $this->assertEquals($res, [
            "sql" => "UPDATE \"test_schema\".\"text_types\" SET \"meta_plain\" = NULL, \"meta_text\" = NULL, \"detail_plain\" = NULL WHERE \"text_types_key\" = 'bb8b8a74-e4ec-4ce9-8b58-8d3de11d8137';"
        ]);


        $params = [
            'entityName' => 'bookings',
            'schemaName' => 'bookings',
            'key' => 'book_ref',
            'value' => '123456',
            'fields' => [
                'book_ref' => '22222',
            ],
            'files' => [],
            'types' => [
                'book_ref' => 'text',
            ],
        ];

        $res = methodsBase::updateEntity($params);
        $this->assertEquals($res, [
            "sql" => "UPDATE \"bookings\".\"bookings\" SET \"book_ref\" = '22222'::text WHERE \"book_ref\"::text = '123456'::text;"
        ]);
    }

    public function test_deleteEntitiesByKey() {

        $params = [
            "entityName" => "bookings",
            "schemaName" => "bookings",
            'key' => 'book_ref',
            'value' => ['000015', '000016'],
        ];

        $res = methodsBase::deleteEntitiesByKey($params);
        $this->assertEquals($res, [
            "sql" => "DELETE FROM \"bookings\".\"bookings\" WHERE \"book_ref\" = '000015';DELETE FROM \"bookings\".\"bookings\" WHERE \"book_ref\" = '000016';"
        ]);

        $params = [
            "entityName" => "bookings",
            "schemaName" => "bookings",
            'key' => ['book_ref', "total_amount"],
            'value' => [
                0 => ['000013', '000014'],
                1 => ['000045', '000055'],
            ],
        ];

        $res = methodsBase::deleteEntitiesByKey($params);
        $this->assertEquals($res, [
            "sql" => "DELETE FROM \"bookings\".\"bookings\" WHERE \"book_ref\" = '000013' AND \"total_amount\" = '000045';DELETE FROM \"bookings\".\"bookings\" WHERE \"book_ref\" = '000014' AND \"total_amount\" = '000055';"
        ]);

        $params = [
            "entityName" => "bookings",
            "schemaName" => "bookings",
            "key" => "book_ref",
            "value" => "22222",
            "types" => [
                "book_ref" => "text",
            ],
        ];

        $res = methodsBase::deleteEntitiesByKey($params);
        $this->assertEquals($res, [
            "sql" => "DELETE FROM \"bookings\".\"bookings\" WHERE \"book_ref\" = '22222'::text;"
        ]);
    }

    public function test_getCurrentUser() {
        global $_STORAGE, $flag_astra;
        $_STORAGE['login'] = '';
        $res = methodsBase::getCurrentUser();
        $this->assertEquals($res, "guest");
        $_SERVER['REMOTE_USER'] = "abris.site\\postgres";
        $res = methodsBase::getCurrentUser();
        $this->assertEquals($res, "postgres");
        $res = methodsBase::getCurrentUser();
        $this->assertEquals($res, "postgres");

        $flag_astra = true;
        $_SERVER['KRB5CCNAME'] = "";
        $_SERVER['PHP_AUTH_USER'] = "postgres";
        $res = methodsBase::getCurrentUser();
        $this->assertEquals($res, "postgres");
        $flag_astra = false;


    }

    public function test_isGuest() {
        global $_STORAGE, $flag_astra;
        $_SESSION['login'] = 'postgres';
        $res = methodsBase::isGuest();
        $this->assertEquals($res, 1);

        $flag_astra = true;
        $_SERVER['PHP_AUTH_USER'] = "postgres";
        $res = methodsBase::isGuest();
        $this->assertEquals($res, true);
        $flag_astra = false;
    }

    public function test_quote() {
        $res = methodsBase::quote("test");
        $this->assertEquals($res, "'test'");
    }

    public function test_test() {
        $res = methodsBase::test("test");
        $this->assertEquals($res, "test");
    }

    public function test_getTableDataPredicate_array() {
        $params =
            [
                "format" => "array",
                "entityName" => "airports",
                "schemaName" => "bookings",
                "predicate" => [
                    "strict" => true,
                    "operands" => [
                        [
                            "levelup" => false,
                            "operand" => [
                                "field" => "timezone",
                                "path" => [
                                    "timezone"
                                ],
                                "op" => "EQ",
                                "value" => "Asia/Novokuznetsk",
                                "search_in_key" => false,
                                "table_alias" => "t"
                            ]
                        ]
                    ]
                ],
                "aggregate" => [],
                "limit" => 10,
                "offset" => 0,
                "primaryKey" => "airport_code",
                "currentKey" => "",
                "fields" => [
                    "airport_code" => [
                        "table_alias" => "t",
                        "subfields" => null,
                        "hidden" => false
                    ],
                    "airport_name" => [
                        "table_alias" => "t"
                    ],
                    "timezone" => [
                        "table_alias" => "t"
                    ]
                ],
                "join" => [],
                "order" => [],
                "process" => null,
                "functions" => []

            ];
        $res = methodsBase::getTableDataPredicate($params);

        $this->assertEquals($res['fields'],
            [
                "airport_code",
                "airport_name",
                "timezone"
            ]
        );

        $this->assertEquals($res['data'],
            [
                [
                    "KEJ",
                    "Кемерово",
                    "Asia/Novokuznetsk"
                ],
                [
                    "NOZ",
                    "Спиченково",
                    "Asia/Novokuznetsk"
                ]
            ]
        );

    }

    public function test_getTableDataPredicate_empty_groups() {
        $params =
            [
                "entityName" => "airports",
                "schemaName" => "bookings",
                "predicate" => [
                    "strict" => true,
                    "operands" => [
                        [
                            "levelup" => false,
                            "operand" => [
                                "field" => "timezone",
                                "path" => [
                                    "timezone"
                                ],
                                "op" => "EQ",
                                "value" => "Asia/Novokuznetsk",
                                "search_in_key" => false,
                                "table_alias" => "t"
                            ]
                        ]
                    ]
                ],
                "aggregate" => [],
                "limit" => 10,
                "offset" => 0,
                "primaryKey" => "airport_code",
                "currentKey" => "",
                "fields" => [
                    "airport_code" => [
                        "table_alias" => "t",
                        "subfields" => null,
                        "hidden" => false
                    ],
                    "airport_name" => [
                        "table_alias" => "t"
                    ],
                    "timezone" => [
                        "table_alias" => "t"
                    ]
                ],
                "group" => [],
                "join" => [],
                "order" => [],
                "process" => null,
                "functions" => []

            ];
        $res = methodsBase::getTableDataPredicate($params);

        $this->assertEquals($res['data'],
            [
                [
                    "airport_code" => "KEJ",
                    "airport_name" => "Кемерово",
                    "timezone" => "Asia/Novokuznetsk"
                ],
                [
                    "airport_code" => "NOZ",
                    "airport_name" => "Спиченково",
                    "timezone" => "Asia/Novokuznetsk"
                ]
            ]
        );
    }

    public function test_getTableDataPredicate_object() {
        $params =
            [
                "entityName" => "airports",
                "schemaName" => "bookings",
                "predicate" => [
                    "strict" => true,
                    "operands" => [
                        [
                            "levelup" => false,
                            "operand" => [
                                "field" => "timezone",
                                "path" => [
                                    "timezone"
                                ],
                                "op" => "EQ",
                                "value" => "Asia/Novokuznetsk",
                                "search_in_key" => false,
                                "table_alias" => "t"
                            ]
                        ]
                    ]
                ],
                "aggregate" => [],
                "limit" => 10,
                "offset" => 0,
                "primaryKey" => "airport_code",
                "currentKey" => "",
                "fields" => [
                    "airport_code" => [
                        "table_alias" => "t",
                        "subfields" => null,
                        "hidden" => false
                    ],
                    "airport_name" => [
                        "table_alias" => "t"
                    ],
                    "timezone" => [
                        "table_alias" => "t"
                    ]
                ],
                "join" => [],
                "order" => [],
                "process" => null,
                "functions" => []

            ];
        $res = methodsBase::getTableDataPredicate($params);

        $this->assertEquals($res['data'],
            [
                [
                    "airport_code" => "KEJ",
                    "airport_name" => "Кемерово",
                    "timezone" => "Asia/Novokuznetsk"
                ],
                [
                    "airport_code" => "NOZ",
                    "airport_name" => "Спиченково",
                    "timezone" => "Asia/Novokuznetsk"
                ]
            ]
        );
    }

    public function test_getTableDataPredicate_aggregate() {
        $params =
            [
                "entityName" => "airports",
                "schemaName" => "bookings",
                "predicate" => [
                    "strict" => true,
                    "operands" => [
                        [
                            "levelup" => false,
                            "operand" => [
                                "field" => "timezone",
                                "path" => [
                                    "timezone"
                                ],
                                "op" => "EQ",
                                "value" => "Asia/Novokuznetsk",
                                "search_in_key" => false,
                                "table_alias" => "t"
                            ]
                        ]
                    ]
                ],
                "aggregate" => [["func" => "count", "field" => "airport_code"]],
                "limit" => 10,
                "offset" => 0,
                "primaryKey" => "airport_code",
                "currentKey" => "",
                "fields" => [
                    "airport_code" => [
                        "table_alias" => "t",
                        "subfields" => null,
                        "hidden" => false
                    ],
                    "airport_name" => [
                        "table_alias" => "t"
                    ],
                    "timezone" => [
                        "table_alias" => "t"
                    ]
                ],
                "join" => [],
                "order" => [],
                "process" => null,
                "functions" => []

            ];
        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res,
            [
                "data" => [
                    [
                        "airport_code" => "KEJ",
                        "airport_name" => "Кемерово",
                        "timezone" => "Asia/Novokuznetsk"
                    ],
                    [
                        "airport_code" => "NOZ",
                        "airport_name" => "Спиченково",
                        "timezone" => "Asia/Novokuznetsk"
                    ]
                ],
                "records" => [
                    [
                        "count" => "2"
                    ]
                ],
                "offset" => 0,
                "fields" => [
                    "airport_code",
                    "airport_name",
                    "timezone"
                ],
                "sql" => "SELECT  \"t\".\"airport_code\", \"t\".\"airport_name\", \"t\".\"timezone\" FROM \"bookings\".\"airports\" as t  WHERE (\"t\".\"timezone\" = 'Asia/Novokuznetsk')  ORDER BY airport_code LIMIT 10 OFFSET 0",
                "count(airport_code)" => [
                    [
                        "count" => "2"
                    ]
                ]
            ]
        );

        $params = [
            "entityName" => "airports",
            "schemaName" => "bookings",
            "predicate" => [
                "strict" => true,
                "operands" => []
            ],

            "aggregate" => [
                0 => [
                    "func" => "count",
                    "field" => "airport_name",
                ],
                1 => [
                    "func" => "count",
                    "field" => "city",
                ],
                2 => [
                    "func" => "count",
                    "field" => "coordinates",
                ],
                3 => [
                    "func" => "count",
                    "field" => "timezone",
                ],
            ],

            "limit" => 1,
            "offset" => 0,
            "primaryKey" => "airport_code",
            "fields" => [
                "airport_code" => [
                    "table_alias" => "t",
                    "subfields" => NULL,
                    "hidden" => false,
                ],
                "airport_name" => [
                    "table_alias" => "t",
                ],
                "city" => [
                    "table_alias" => "t",
                ],
                "coordinates" => [
                    "table_alias" => "t",
                ],
                "timezone" => [
                    "table_alias" => "t",
                ],
            ],

            "join" => [],
            "sample" => NULL,
            "order" => [],
            "group" => [],
            "process" => null,
            "functions" => [],
            "format" => 'array',
            "desc" => 'Загрузка таблицы "Airports"',

        ];

        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res,
            [

                "data" => [
                    [
                        0 => 11,
                        1 => 11,
                        2 => 11,
                        3 => 11,

                    ],

                ],

                "records" => [
                    0 => [
                        "count" => 1,
                    ],
                ],

                "offset" => 0,
                "fields" => [
                    0 => "airport_name",
                    1 => "city",
                    2 => "coordinates",
                    3 => "timezone"
                ],

                "sql" => "SELECT  count(\"t\".\"airport_name\") as airport_name, count(\"t\".\"city\") as city, count(\"t\".\"coordinates\") as coordinates, count(\"t\".\"timezone\") as timezone FROM \"bookings\".\"airports\" as t  LIMIT 1 OFFSET 0",
                "count(airport_name)" => [
                    0 => [
                        "count" => 11
                    ]
                ],

                "count(city)" => [
                    0 => [
                        "count" => 11
                    ]
                ],

                "count(coordinates)" => [
                    0 => [
                        "count" => 11
                    ]
                ],

                "count(timezone)" => [
                    0 => [
                        "count" => 11
                    ]
                ],
            ]
        );

    }

    public function test_getTableDataPredicate_currentKey() {
        $params =
            [
                "format" => "array",
                "entityName" => "airports",
                "schemaName" => "bookings",
                "predicate" => [
                    "strict" => true,
                    "operands" => []
                ],
                "aggregate" => [],
                "limit" => 10,
                "offset" => 0,
                "primaryKey" => "airport_code",
                "currentKey" => "MMK",
                "fields" => [
                    "airport_code" => [
                        "table_alias" => "t",
                        "subfields" => null,
                        "hidden" => false
                    ],
                    "airport_name" => [
                        "table_alias" => "t"
                    ],
                    "timezone" => [
                        "table_alias" => "t"
                    ]
                ],
                "join" => [],
                "order" => [["field" => "airport_code", "desc" => "1"]],
                "process" => null,
                "functions" => [],
                "max_cost" => 10,

            ];
        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals(
            [
                "data" => [
                    [
                        "YKS",
                        "Якутск",
                        "Asia/Yakutsk"
                    ],
                    [
                        "VVO",
                        "Владивосток",
                        "Asia/Vladivostok"
                    ],
                    [
                        "UUS",
                        "Хомутово",
                        "Asia/Sakhalin"
                    ],
                    [
                        "PKC",
                        "Елизово",
                        "Asia/Kamchatka"
                    ],
                    [
                        "NOZ",
                        "Спиченково",
                        "Asia/Novokuznetsk"
                    ],
                    [
                        "MJZ",
                        "Мирный",
                        "Asia/Yakutsk"
                    ],
                    [
                        "LED",
                        "Пулково",
                        "Europe/Moscow"
                    ],
                    [
                        "KHV",
                        "Хабаровск-Новый",
                        "Asia/Vladivostok"
                    ],
                    [
                        "KGD",
                        "Храброво",
                        "Europe/Kaliningrad"
                    ],
                    [
                        "KEJ",
                        "Кемерово",
                        "Asia/Novokuznetsk"
                    ]
                ],
                "records" => [
                    [
                        "count" => 11
                    ]
                ],
                "offset" => '0',
                "fields" => [
                    "airport_code",
                    "airport_name",
                    "timezone"
                ],
                "sql" => "SELECT  \"t\".\"airport_code\", \"t\".\"airport_name\", \"t\".\"timezone\" FROM \"bookings\".\"airports\" as t   ORDER BY \"t\".\"airport_code\" DESC, airport_code LIMIT 10 OFFSET 0"
            ], $res
        );

        $params["middleRow"] = true;
        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals(
            [
                "data" =>
                    [
                        [
                            'YKS',
                            'Якутск',
                            'Asia/Yakutsk'
                        ],
                        [
                            'VVO',
                            'Владивосток',
                            'Asia/Vladivostok'
                        ], [
                        'UUS',
                        'Хомутово',
                        'Asia/Sakhalin'
                    ], [
                        'PKC',
                        'Елизово',
                        'Asia/Kamchatka'
                    ], [
                        'NOZ',
                        'Спиченково',
                        'Asia/Novokuznetsk'
                    ], [
                        'MJZ',
                        'Мирный',
                        'Asia/Yakutsk'
                    ], [
                        'LED',
                        'Пулково',
                        'Europe/Moscow'
                    ], [
                        'KHV',
                        'Хабаровск-Новый',
                        'Asia/Vladivostok'
                    ],
                        [
                            'KGD',
                            'Храброво',
                            'Europe/Kaliningrad'
                        ], [
                        'KEJ',
                        'Кемерово',
                        'Asia/Novokuznetsk'
                    ]
                    ],
                "records" => [
                    [
                        "count" => 11
                    ]
                ],
                "offset" => '0',
                "fields" => [
                    "airport_code",
                    "airport_name",
                    "timezone"
                ],
                "sql" => "SELECT  \"t\".\"airport_code\", \"t\".\"airport_name\", \"t\".\"timezone\" FROM \"bookings\".\"airports\" as t   ORDER BY \"t\".\"airport_code\" DESC, airport_code LIMIT 10 OFFSET 0",
            ], $res);
    }

    public function test_getTableDataPredicate_currentKey_costructedField_order() {
        $params =
            [
                "format" => "array",
                "entityName" => "airports",
                "schemaName" => "bookings",
                "predicate" => [
                    "strict" => true,
                    "operands" => []
                ],
                "aggregate" => [],
                "limit" => 2,
                "offset" => 0,
                "primaryKey" => "airport_code",
                "currentKey" => "MMK",
                "fields" => [
                    "airport_code" => [
                        "table_alias" => "t",
                        "subfields" => null,
                        "hidden" => false
                    ],
                    "airport_name" => [
                        "table_alias" => "t"
                    ],
                    "timezone" => [
                        "table_alias" => "t"
                    ],
                    "flight_no" => [
                        "table_alias" => "t0",
                        "title" => "Flight number"
                    ]
                ],
                "join" => [[
                    "key" => "airport_code",
                    "schema" => "bookings",
                    "entity" => "flights",
                    "table_alias" => "t0",
                    "parent_table_alias" => "t",
                    "entityKey" => "departure_airport",
                    "array_mode" => true
                ]],
                "order" => [["field" => "flight_no", "desc" => "1"]],
                "process" => null,
                "functions" => []

            ];
        $res = methodsBase::getTableDataPredicate($params);


        $this->assertEquals(
            [
                "offset" => "0",
                "fields" => [
                    "airport_code",
                    "airport_name",
                    "timezone",
                    "flight_no"
                ],
                "sql" => "SELECT  \"t\".\"airport_code\", \"t\".\"airport_name\", \"t\".\"timezone\", \"t0\".\"flight_no\" FROM \"bookings\".\"airports\" as t  left join \"bookings\".\"flights\" as \"t0\" on \"t\".\"airport_code\" = \"t0\".\"departure_airport\"  ORDER BY \"t0\".\"flight_no\" DESC, airport_code LIMIT 2 OFFSET 0",
                "data" => [
                    [
                        "KEJ",
                        "Кемерово",
                        "Asia/Novokuznetsk",
                        ""
                    ],
                    [
                        "KGD",
                        "Храброво",
                        "Europe/Kaliningrad",
                        ""
                    ]
                ],
                "records" => [
                    [
                        "count" => "20"
                    ]
                ]
            ], $res
        );
    }

    public function test_getTableDataPredicate_groups() {
        $params = [
            "entityName" => "flights",
            "schemaName" => "bookings",
            "predicate" => [
                "strict" => true,
                "operands" => [
                ]
            ],
            "aggregate" => [
            ],
            "limit" => "5",
            "offset" => 0,
            "primaryKey" => "flight_id",
            "fields" => [
                "flight_id" => [
                    "table_alias" => "t",
                    "subfields" => null,
                    "hidden" => false
                ],
                "flight_no" => [
                    "table_alias" => "t"
                ],
                "scheduled_departure" => [
                    "table_alias" => "t"
                ],
                "scheduled_arrival" => [
                    "table_alias" => "t"
                ],
                "departure_airport" => [
                    "table_alias" => "t",
                    "subfields" => [
                        "airport_code",
                        "airport_name",
                        "city",
                        "coordinates",
                        "timezone"
                    ],
                    "subfields_navigate_alias" => "t0",
                    "subfields_table_alias" => [
                        "t0",
                        "t0",
                        "t0",
                        "t0",
                        "t0"
                    ],
                    "subfields_key" => "airport_code"
                ],
                "arrival_airport" => [
                    "table_alias" => "t",
                    "subfields" => [
                        "airport_code",
                        "airport_name",
                        "city",
                        "coordinates",
                        "timezone"
                    ],
                    "subfields_navigate_alias" => "t1",
                    "subfields_table_alias" => [
                        "t1",
                        "t1",
                        "t1",
                        "t1",
                        "t1"
                    ],
                    "subfields_key" => "airport_code"
                ],
                "status" => [
                    "table_alias" => "t"
                ],
                "aircraft_code" => [
                    "table_alias" => "t",
                    "subfields" => [
                        "aircraft_code",
                        "model",
                        "range"
                    ],
                    "subfields_navigate_alias" => "t2",
                    "subfields_table_alias" => [
                        "t2",
                        "t2",
                        "t2"
                    ],
                    "subfields_key" => "aircraft_code"
                ],
                "actual_departure" => [
                    "table_alias" => "t"
                ],
                "actual_arrival" => [
                    "table_alias" => "t"
                ]
            ],
            "join" => [
                [
                    "key" => "departure_airport",
                    "virtual" => false,
                    "schema" => "bookings",
                    "entity" => "airports",
                    "table_alias" => "t0",
                    "parent_table_alias" => "t",
                    "entityKey" => "airport_code"
                ],
                [
                    "key" => "arrival_airport",
                    "virtual" => false,
                    "schema" => "bookings",
                    "entity" => "airports",
                    "table_alias" => "t1",
                    "parent_table_alias" => "t",
                    "entityKey" => "airport_code"
                ],
                [
                    "key" => "aircraft_code",
                    "virtual" => false,
                    "schema" => "bookings",
                    "entity" => "aircrafts",
                    "table_alias" => "t2",
                    "parent_table_alias" => "t",
                    "entityKey" => "aircraft_code"
                ]
            ],
            "sample" => null,
            "order" => [
                [
                    "field" => "flight_no",
                    "desc" => false,
                    "columnIndex" => 1
                ],
                [
                    "field" => "status",
                    "desc" => false,
                    "columnIndex" => 3
                ],
                [
                    "field" => "departure_airport",
                    "desc" => true
                ]
            ],
            "group" => [
                [
                    "field" => "flight_no",
                    "desc" => false,
                    "columnIndex" => 1
                ],
                [
                    "field" => "status",
                    "desc" => false,
                    "columnIndex" => 3
                ]
            ],
            "process" => null,
            "functions" => [
            ],
            "format" => "array",
            "desc" => "Загрузка таблицы \"Flights\""
        ];

        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res["data"],
            array(
                array(
                    '2',
                    'PG0404',
                    '2017-08-05 16:05:00+00',
                    '2017-08-05 17:00:00+00',
                    '{"f1":"DME Домодедово Москва (37.90629959106445,55.40879821777344) Europe/Moscow","f2":"DME"}',
                    '{"f1":"LED Пулково Санкт-Петербург (30.262500762939453,59.80030059814453) Europe/Moscow","f2":"LED"}',
                    'Arrived',
                    '{"f1":"321 Аэробус A321-200 5600","f2":"321"}',
                    '2017-08-05 16:06:00+00',
                    '2017-08-05 17:01:00+00',
                ),
                array(
                    '17',
                    'PG0404',
                    '2017-08-06 16:05:00+00',
                    '2017-08-06 17:00:00+00',
                    '{"f1":"DME Домодедово Москва (37.90629959106445,55.40879821777344) Europe/Moscow","f2":"DME"}',
                    '{"f1":"LED Пулково Санкт-Петербург (30.262500762939453,59.80030059814453) Europe/Moscow","f2":"LED"}',
                    'Arrived',
                    '{"f1":"321 Аэробус A321-200 5600","f2":"321"}',
                    '2017-08-06 16:05:00+00',
                    '2017-08-06 17:00:00+00'
                ),
                array(
                    '6',
                    'PG0404',
                    '2017-08-16 16:05:00+00',
                    '2017-08-16 17:00:00+00',
                    '{"f1":"DME Домодедово Москва (37.90629959106445,55.40879821777344) Europe/Moscow","f2":"DME"}',
                    '{"f1":"LED Пулково Санкт-Петербург (30.262500762939453,59.80030059814453) Europe/Moscow","f2":"LED"}',
                    'Scheduled',
                    '{"f1":"321 Аэробус A321-200 5600","f2":"321"}',
                    '',
                    ''
                ),
                array(
                    '12',
                    'PG0404',
                    '2017-08-23 16:05:00+00',
                    '2017-08-23 17:00:00+00',
                    '{"f1":"DME Домодедово Москва (37.90629959106445,55.40879821777344) Europe/Moscow","f2":"DME"}',
                    '{"f1":"LED Пулково Санкт-Петербург (30.262500762939453,59.80030059814453) Europe/Moscow","f2":"LED"}',
                    'Scheduled',
                    '{"f1":"321 Аэробус A321-200 5600","f2":"321"}',
                    '',
                    ''
                ),
                array(
                    '1',
                    'PG0405',
                    '2017-07-16 06:35:00+00',
                    '2017-07-16 07:30:00+00',
                    '{"f1":"DME Домодедово Москва (37.90629959106445,55.40879821777344) Europe/Moscow","f2":"DME"}',
                    '{"f1":"LED Пулково Санкт-Петербург (30.262500762939453,59.80030059814453) Europe/Moscow","f2":"LED"}',
                    'Arrived',
                    '{"f1":"321 Аэробус A321-200 5600","f2":"321"}',
                    '2017-07-16 06:44:00+00',
                    '2017-07-16 07:39:00+00'
                )
            )
        );
    }

    public function test_getTableData() {
        $params = [
            "entityName" => "bookings",
            "schemaName" => "bookings",
            "fields" => [
                "book_ref",
                "total_amount"

            ],
            "limit" => 15,
            "offset" => 0,
            "key" => "book_ref",
            "value" => "00000F",
            "predicate" => "00000F",
            "exclude" => [
                0 => ["book_ref",
                    "total_amount"],

            ],
            "desc" => 'Загрузка списка "Bookings"'
        ];


        $res = methodsBase::getTableData($params);
        $this->assertEquals($res, [
            "data" => [
                0 => ["book_ref" => '00000F', "total_amount" => "265700.00"],
            ],
            "records" => [
                0 => ["count" => '1'],
            ]

        ]);


        $params = [
            "entityName" => "tickets",
            "schemaName" => "bookings",
            "fields" => [
                "passenger_id"
            ],
            "predicate" => "21312",
            "limit" => 10,
            "offset" => 0,
            "key" => null,
            "value" => null,
            "distinct" => true,
            "exclude" => [],
            "desc" => 'Загрузка списка "Tickets"'
        ];
        $res = methodsBase::getTableData($params);
        $this->assertEquals($res, [
            "data" => [
                0 => ["passenger_id" => '1405 221312'],
                1 => ["passenger_id" => '2846 021312'],
                2 => ["passenger_id" => '3960 621312'],
                3 => ["passenger_id" => '4152 521312'],
                4 => ["passenger_id" => '5838 621312'],
                5 => ["passenger_id" => '6704 621312'],
                6 => ["passenger_id" => '6991 021312'],
                7 => ["passenger_id" => '7437 921312']
            ],
            "records" => [
                0 => ["count" => '8'],
            ]
        ]);


        $params = [
            "fields" => "",
            "entityName" => "bookings",
            "schemaName" => "bookings",
            "key" => "book_ref",
            "value" => "00000F",
            "limit" => 15,
            "offset" => 0,
        ];
        $res = methodsBase::getTableData($params);
        $this->assertEquals($res, [
            "data" => [
                0 => ["book_ref" => "00000F",
                    "book_date" => "2017-07-05 00:12:00+00",
                    "total_amount" => "265700.00",
                ],
            ],
            "records" => [
                0 => ["count" => '1'],
            ],

        ]);
    }

    public function test_getUserDescription() {
        global $_STORAGE;

        $_STORAGE['login'] = 'admins';
        $res = methodsBase::getUserDescription();
        $this->assertEquals(
            $res,
            array(
                'user' => 'admins',
                'comment' => 'Администратор'
            )
        );
    }

    public function test_getTableDataPredicate_duration() {
        $params =
            [
                "format" => "array",
                "entityName" => "flights",
                "schemaName" => "bookings",
                "predicate" => [
                    "strict" => true,
                    "operands" => [
                        [
                            "levelup" => false,
                            "operand" => [
                                "field" => "scheduled_departure",
                                "path" => [
                                    "scheduled_departure"
                                ],
                                "op" => "DUR",
                                "value" => "P3Y6M4D",
                                "table_alias" => "t",
                                "search_in_key" => false
                            ]
                        ]
                    ]
                ],
                "aggregate" => [],
                "limit" => 10,
                "offset" => 0,
                "primaryKey" => "flight_id",
                "currentKey" => "",
                "fields" => [
                    "flight_no" => [
                        "table_alias" => "t"
                    ],
                    "scheduled_departure" => [
                        "table_alias" => "t"
                    ],
                ],
                "join" => [],
                "order" => [],
                "process" => null,
                "functions" => []

            ];

        $res = methodsBase::getTableDataPredicate($params);

        $this->assertEquals(
            "SELECT  \"t\".\"flight_no\", \"t\".\"scheduled_departure\" FROM \"bookings\".\"flights\" as t  WHERE (\"t\".\"scheduled_departure\" <= now() and \"t\".\"scheduled_departure\" > now() - 'P3Y6M4D'::interval)  ORDER BY flight_id LIMIT 10 OFFSET 0",
            $res['sql']
        );


    }

    public function test_getTableDataPredicate_match_order() {
        $params =
            [
                "entityName" => "airports",
                "schemaName" => "bookings",
                "predicate" => [
                    "strict" => true,
                    "operands" => [
                        [
                            "levelup" => false,
                            "operand" => [
                                "path" => [],
                                "op" => "C",
                                "value" => "N",
                                "m_order" => true
                            ]
                        ]
                    ]
                ],
                "aggregate" => [],
                "limit" => 15,
                "offset" => 0,
                "primaryKey" => "airport_code",
                "currentKey" => "",
                "fields" => [
                    "airport_code" => [
                        "table_alias" => "t",
                        "subfields" => null,
                        "hidden" => false
                    ],
                    "timezone" => [
                        "table_alias" => "t"
                    ]
                ],
                "join" => [],
                "order" => [],
                "process" => null,
                "functions" => []

            ];
        $res = methodsBase::getTableDataPredicate($params);

        $this->assertEquals('[{"airport_code":"KEJ","timezone":"Asia\/Novokuznetsk"},{"airport_code":"KGD","timezone":"Europe\/Kaliningrad"},{"airport_code":"NOZ","timezone":"Asia\/Novokuznetsk"},{"airport_code":"UUS","timezone":"Asia\/Sakhalin"}]',
            json_encode($res['data']));
    }

    public function test_getTableDataPredicate_space_search() {
        $params =
            [
                "format" => "array",
                "entityName" => "airports",
                "schemaName" => "bookings",
                "predicate" => [
                    "strict" => true,
                    "operands" => [
                        [
                            "levelup" => false,
                            "operand" => [
                                "field" => "timezone",
                                "path" => [
                                    "timezone"
                                ],
                                "op" => "C",
                                "value" => "As vo",
                                "search_in_key" => false,
                                "table_alias" => "t",
                                "m_order" => "t"
                            ]
                        ]
                    ]
                ],
                "aggregate" => [],
                "limit" => 10,
                "offset" => 0,
                "primaryKey" => "airport_code",
                "currentKey" => "",
                "fields" => [
                    "airport_code" => [
                        "table_alias" => "t",
                        "subfields" => null,
                        "hidden" => false
                    ],
                    "airport_name" => [
                        "table_alias" => "t"
                    ],
                    "timezone" => [
                        "table_alias" => "t"
                    ]
                ],
                "join" => [],
                "order" => [],
                "process" => null,
                "functions" => []

            ];
        $res = methodsBase::getTableDataPredicate($params);


        $this->assertEquals(
            'SELECT  "t"."airport_code", "t"."airport_name", "t"."timezone" FROM "bookings"."airports" as t  WHERE ("t"."timezone"::text ILIKE \'%As%\'::text and "t"."timezone"::text ILIKE \'%vo%\'::text)  ORDER BY airport_code LIMIT 10 OFFSET 0',
            $res['sql']
        );
    }

    public function test_getTableDataPredicate_operand() {
        $params = [
            "entityName" => "bookings",
            "schemaName" => "bookings",
            "predicate" => [
                "strict" => true,
                "operands" => [
                    [
                        "levelup" => false,
                        "operand" => [
                            "field" => "total_amount",
                            "path" => [
                                "total_amount"],
                            "op" => "G",
                            "value" => "6000",
                            "m_order" => true,
                            "table_alias" => "t"
                        ],
                    ],
                    [
                        "levelup" => false,
                        "operand" => [
                            "field" => "book_ref",
                            "path" => [
                                "book_ref"],
                            "op" => "ISNN",
                            'search_in_key' => true,
                            "table_alias" => "t"
                        ],
                    ],
                ],
            ],

            "aggregate" => [],
            "limit" => 3,
            "offset" => 0,
            "primaryKey" => "book_ref",
            "currentKey" => null,
            "fields" => [
                "book_ref" => ["table_alias" => "t"],
                "book_date" => ["table_alias" => "t"],
                "total_amount" => ["table_alias" => "t"]
            ],
            "join" => [],
            "sample" => null,
            "order" => [
                [
                    "field" => "total_amount",
                    "distinct" => true
                ],
            ],
            "group" => [],
            "process" => null,
            "functions" => [],
            "format" => "array",
            "desc" => "Загрузка таблицы \"Bookings\""

        ];

        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res, [
            'offset' => 0,
            'fields' => ['book_ref', 'book_date', 'total_amount'],
            'sql' => 'SELECT distinct on ("t"."total_amount") "t"."book_ref", "t"."book_date", "t"."total_amount" FROM "bookings"."bookings" as t  WHERE ("t"."total_amount" > \'6000\') AND ("t"."book_ref" IS NOT NULL )  ORDER BY "t"."total_amount", book_ref LIMIT 3 OFFSET 0',
            'data' => [
                ['1DC435', '2017-07-20 02:36:00+00', '6700.00'],
                ['7F5D7B', '2017-08-04 18:31:00+00', '7300.00'],
                ['44444', '2020-03-12 15:55:00+00', '9999.00']
            ],
            'records' => [
                ['count' => $res['records'][0]['count']]
            ],
        ]);

        $params["predicate"]["operands"][0]["operand"]["op"] = "L";
        $params["predicate"]["operands"][1]["operand"]["op"] = "ISN";
        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res, [
            'offset' => 0,
            'fields' => ['book_ref', 'book_date', 'total_amount'],
            'sql' => 'SELECT distinct on ("t"."total_amount") "t"."book_ref", "t"."book_date", "t"."total_amount" FROM "bookings"."bookings" as t  WHERE ("t"."total_amount" < \'6000\') AND ("t"."book_ref" IS NULL )  ORDER BY "t"."total_amount", book_ref LIMIT 3 OFFSET 0',
            'data' => [],
            'records' => [
                ['count' => 0]
            ],
        ]);

        $params["predicate"]["operands"][0]["operand"]["op"] = "GEQ";
        $params["predicate"]["operands"][1]["operand"]["op"] = "LEQ";
        $params["predicate"]["operands"][1]["operand"]["value"] = "0002D8";

        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res, [
            'offset' => 0,
            'fields' => ['book_ref', 'book_date', 'total_amount'],
            'sql' => 'SELECT distinct on ("t"."total_amount") "t"."book_ref", "t"."book_date", "t"."total_amount" FROM "bookings"."bookings" as t  WHERE ("t"."total_amount" >= \'6000\') AND ("t"."book_ref" <= \'0002D8\')  ORDER BY "t"."total_amount", book_ref LIMIT 3 OFFSET 0',
            'data' => [
                ['000068', '2020-03-12 15:18:00+00', '18100.00'],
                ['0002D8', '2017-08-07 18:40:00+00', '23600.00'],
                ['000012', '2020-03-12 15:18:00+00', '37900.00']
            ],
            'records' => [
                ['count' => 4]
            ],
        ]);


        $params = [
            "entityName" => "aircrafts",
            "schemaName" => "bookings",
            "predicate" => [
                "strict" => true,
                "operands" => [
                    [
                        "levelup" => false,
                        "operand" => [
                            "field" => "range",
                            "path" => ["range"],
                            "op" => "F",
                            "value" => "testint",
                            "m_order" => true,
                            "table_alias" => "t"],
                    ],
                ],
            ],

            "aggregate" => [],
            "limit" => 3,
            "offset" => 0,
            "primaryKey" => "aircraft_code",
            "currentKey" => null,
            "fields" => [
                "aircraft_code" => ["table_alias" => "t"],
                "model" => ["table_alias" => "t"],
                "range" => ["table_alias" => "t"]
            ],
            "join" => [],
            "sample" => null,
            "order" => [
                [
                    "field" => "range",
                    "distinct" => true
                ],
            ],
            "group" => [],
            "process" => null,
            "functions" => [],
            "format" => "array",
            "desc" => "Загрузка таблицы \"Aircrafts\""
        ];

        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res, [
            'offset' => 0,
            'fields' => ['aircraft_code', 'model', 'range'],
            'sql' => 'SELECT distinct on ("t"."range") "t"."aircraft_code", "t"."model", "t"."range" FROM "bookings"."aircrafts" as t  WHERE ("testint"("t"."range"))  ORDER BY "t"."range", aircraft_code LIMIT 3 OFFSET 0',
            'data' => [
                ['CN1', 'Сессна 208 Караван', '1200'],
                ['CR2', 'Бомбардье CRJ-200', '2700'],
                ['SU9', 'Сухой Суперджет-100', '3000']
            ],
            'records' => [
                ['count' => 9]
            ],
        ]);

        $params["predicate"]["operands"][0]["operand"]["op"] = "FC";
        $params["predicate"]["operands"][0]["operand"]["value"] = "testint2";
        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res, [
            'offset' => 0,
            'fields' => ['aircraft_code', 'model', 'range'],
            'sql' => 'SELECT distinct on ("t"."range") "t"."aircraft_code", "t"."model", "t"."range" FROM "bookings"."aircrafts" as t  WHERE ("testint2"(\'"bookings"."aircrafts"\', "t"."range"))  ORDER BY "t"."range", aircraft_code LIMIT 3 OFFSET 0',
            'data' => [
                ['CN1', 'Сессна 208 Караван', '1200'],
                ['CR2', 'Бомбардье CRJ-200', '2700'],
                ['SU9', 'Сухой Суперджет-100', '3000']
            ],
            'records' => [
                ['count' => 9]
            ],
        ]);

        $params["predicate"]["operands"][0]["operand"]["op"] = "EQF";
        $params["predicate"]["operands"][0]["operand"]["value"] = "testint3";
        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res, [
            'offset' => 0,
            'fields' => ['aircraft_code', 'model', 'range'],
            'sql' => 'SELECT distinct on ("t"."range") "t"."aircraft_code", "t"."model", "t"."range" FROM "bookings"."aircrafts" as t  WHERE ("t"."range" =  "testint3"())  ORDER BY "t"."range", aircraft_code LIMIT 3 OFFSET 0',
            'data' => [
                ['CN1', 'Сессна 208 Караван', '1200']
            ],
            'records' => [
                ['count' => 1]
            ],
        ]);

        $params["predicate"]["operands"][0]["operand"]["op"] = "FEQ";
        $params["predicate"]["operands"][0]["operand"]["value"] = 1200;
        $params["predicate"]["operands"][0]["operand"]["func"] = "test4";
        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res, [
            'offset' => 0,
            'fields' => ['aircraft_code', 'model', 'range'],
            'sql' => 'SELECT distinct on ("t"."range") "t"."aircraft_code", "t"."model", "t"."range" FROM "bookings"."aircrafts" as t  WHERE ("test4"("t"."range") =  \'1200\')  ORDER BY "t"."range", aircraft_code LIMIT 3 OFFSET 0',
            'data' => [
                ['CN1', 'Сессна 208 Караван', '1200']
            ],
            'records' => [
                ['count' => 1]
            ],
        ]);
    }

    public function test_getTableDataPredicate_operand_EQ() {
        $params = [
            "format" => "array",
            "entityName" => "airports",
            "schemaName" => "bookings",
            "predicate" => [
                "strict" => true,
                "operands" => [
                    [
                        "levelup" => false,
                        "operand" => [
                            "field" => "timezone",
                            "path" => [
                                "timezone"
                            ],
                            "op" => "EQ",
                            "value" => [
                                "Asia/Novokuznetsk", "Asia/Yakutsk", ""
                            ],
                            "search_in_key" => false,
                            "table_alias" => "t"
                        ]
                    ]
                ]
            ],
            "aggregate" => [],
            "limit" => 10,
            "offset" => 0,
            "primaryKey" => "airport_code",
            "currentKey" => "",
            "fields" => [
                "airport_code" => [
                    "table_alias" => "t",
                    "subfields" => null,
                    "hidden" => false
                ],
                "airport_name" => [
                    "table_alias" => "t"
                ],
                "timezone" => [
                    "table_alias" => "t"
                ]
            ],
            "join" => [],
            "order" => [],
            "process" => null,
            "functions" => []

        ];

        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res, [
                'offset' => 0,
                'fields' => ['airport_code', 'airport_name', 'timezone'],
                'sql' => 'SELECT  "t"."airport_code", "t"."airport_name", "t"."timezone" FROM "bookings"."airports" as t  WHERE ("t"."timezone" IS NULL OR "t"."timezone"::text = \'\' OR "t"."timezone" IN (\'Asia/Novokuznetsk\',\'Asia/Yakutsk\'))  ORDER BY airport_code LIMIT 10 OFFSET 0',
                'data' => [
                    ['KEJ', 'Кемерово', 'Asia/Novokuznetsk'],
                    ['MJZ', 'Мирный', 'Asia/Yakutsk'],
                    ['NOZ', 'Спиченково', 'Asia/Novokuznetsk'],
                    ['YKS', 'Якутск', 'Asia/Yakutsk']
                ],
                'records' => [
                    ['count' => 4]
                ],
            ]
        );

        $params = [
            "entityName" => "bookings",
            "schemaName" => "bookings",
            "predicate" => [
                "strict" => true,
                "operands" => [
                    [
                        "levelup" => false,
                        "operand" => [
                            "field" => "book_ref",
                            "path" => ["book_ref"],
                            "op" => "EQ",
                            'search_in_key' => true,
                            "table_alias" => "t",
                            "value" => null
                        ],
                    ],
                ],
            ],

            "aggregate" => [],
            "limit" => 3,
            "offset" => 0,
            "primaryKey" => "book_ref",
            "currentKey" => null,
            "fields" => [
                "book_ref" => ["table_alias" => "t"],
                "book_date" => ["table_alias" => "t"],
                "total_amount" => ["table_alias" => "t"]
            ],
            "join" => [],
            "sample" => null,
            "order" => [
                [
                    "field" => "total_amount",
                    "distinct" => true
                ],
            ],
            "group" => [],
            "process" => null,
            "functions" => [],
            "format" => "array",
            "desc" => "Загрузка таблицы \"Bookings\""

        ];


        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res, [
            'offset' => 0,
            'fields' => ['book_ref', 'book_date', 'total_amount'],
            'sql' => 'SELECT distinct on ("t"."total_amount") "t"."book_ref", "t"."book_date", "t"."total_amount" FROM "bookings"."bookings" as t  WHERE ("t"."book_ref" is null)  ORDER BY "t"."total_amount", book_ref LIMIT 3 OFFSET 0',
            'data' => [],
            'records' => [
                ['count' => 0]
            ],
        ]);


        $params["predicate"]["operands"][0]["operand"]["value"] = [];
        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res, [
            'offset' => 0,
            'fields' => ['book_ref', 'book_date', 'total_amount'],
            'sql' => 'SELECT distinct on ("t"."total_amount") "t"."book_ref", "t"."book_date", "t"."total_amount" FROM "bookings"."bookings" as t  WHERE ("t"."book_ref" IS NULL OR trim("t"."book_ref"::text) = \'\')  ORDER BY "t"."total_amount", book_ref LIMIT 3 OFFSET 0',
            'data' => [],
            'records' => [
                ['count' => 0]
            ],
        ]);


        $params["predicate"]["operands"][0]["operand"]["value"][0] = "";
        $params["predicate"]["operands"][0]["operand"]["value"][1] = "";
        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res, [
            'offset' => 0,
            'fields' => ['book_ref', 'book_date', 'total_amount'],
            'sql' => 'SELECT distinct on ("t"."total_amount") "t"."book_ref", "t"."book_date", "t"."total_amount" FROM "bookings"."bookings" as t  WHERE ("t"."book_ref" IS NULL OR "t"."book_ref"::text = \'\')  ORDER BY "t"."total_amount", book_ref LIMIT 3 OFFSET 0',
            'data' => [],
            'records' => [
                ['count' => 0]
            ],
        ]);

        $params["predicate"]["operands"][0]["operand"]["value"][0] = "Asia/Novokuznetsk";
        $params["predicate"]["operands"][0]["operand"]["value"][1] = "Europe/Moscow";

        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res, [
            'offset' => 0,
            'fields' => ['book_ref', 'book_date', 'total_amount'],
            'sql' => 'SELECT distinct on ("t"."total_amount") "t"."book_ref", "t"."book_date", "t"."total_amount" FROM "bookings"."bookings" as t  WHERE ("t"."book_ref" IN (\'Asia/Novokuznetsk\',\'Europe/Moscow\'))  ORDER BY "t"."total_amount", book_ref LIMIT 3 OFFSET 0',
            'data' => [],
            'records' => [
                ['count' => 0]
            ],
        ]);
    }

    public function test_getTableDataPredicate_operand_NEQ() {
        $params = [
            "format" => "array",
            "entityName" => "airports",
            "schemaName" => "bookings",
            "predicate" => [
                "strict" => true,
                "operands" => [
                    [
                        "levelup" => false,
                        "operand" => [
                            "field" => "timezone",
                            "path" => [
                                "timezone"
                            ],
                            "op" => "NEQ",
                            "value" => [
                                "Asia/Novokuznetsk", "Asia/Yakutsk", ""
                            ],
                            "search_in_key" => false,
                            "table_alias" => "t"
                        ]
                    ]
                ]
            ],
            "aggregate" => [],
            "limit" => 10,
            "offset" => 0,
            "primaryKey" => "airport_code",
            "currentKey" => "",
            "fields" => [
                "airport_code" => [
                    "table_alias" => "t",
                    "subfields" => null,
                    "hidden" => false
                ],
                "airport_name" => [
                    "table_alias" => "t"
                ],
                "timezone" => [
                    "table_alias" => "t"
                ]
            ],
            "join" => [],
            "order" => [],
            "process" => null,
            "functions" => []

        ];

        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res, [
                'offset' => 0,
                'fields' => ['airport_code', 'airport_name', 'timezone'],
                'sql' => 'SELECT  "t"."airport_code", "t"."airport_name", "t"."timezone" FROM "bookings"."airports" as t  WHERE ("t"."timezone" IS NOT NULL AND trim("t"."timezone"::text) <> \'\' and "t"."timezone" NOT IN (\'Asia/Novokuznetsk\',\'Asia/Yakutsk\'))  ORDER BY airport_code LIMIT 10 OFFSET 0',
                'data' => [
                    ['DME', 'Домодедово', 'Europe/Moscow'],
                    ['KGD', 'Храброво', 'Europe/Kaliningrad'],
                    ['KHV', 'Хабаровск-Новый', 'Asia/Vladivostok'],
                    ['LED', 'Пулково', 'Europe/Moscow'],
                    ['PKC', 'Елизово', 'Asia/Kamchatka'],
                    ['UUS', 'Хомутово', 'Asia/Sakhalin'],
                    ['VVO', 'Владивосток', 'Asia/Vladivostok']
                ],
                'records' => [
                    ['count' => 7]
                ],
            ]
        );


        $params["predicate"]["operands"][0]["operand"]["value"] = "";
        $params["limit"] = 3;
        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res, [
                'offset' => 0,
                'fields' => ['airport_code', 'airport_name', 'timezone'],
                'sql' => 'SELECT  "t"."airport_code", "t"."airport_name", "t"."timezone" FROM "bookings"."airports" as t  WHERE ("t"."timezone" is null)  ORDER BY airport_code LIMIT 3 OFFSET 0',
                'data' => [],
                'records' => [
                    ['count' => 0]
                ],
            ]
        );

        $params["predicate"]["operands"][0]["operand"]["value"] = [];
        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res, [
                'offset' => 0,
                'fields' => ['airport_code', 'airport_name', 'timezone'],
                'sql' => 'SELECT  "t"."airport_code", "t"."airport_name", "t"."timezone" FROM "bookings"."airports" as t  WHERE ("t"."timezone" IS NOT NULL AND "t"."timezone"::text <> \'\')  ORDER BY airport_code LIMIT 3 OFFSET 0',
                'data' => [
                    ['DME', 'Домодедово', 'Europe/Moscow'],
                    ['KEJ', 'Кемерово', 'Asia/Novokuznetsk'],
                    ['KGD', 'Храброво', 'Europe/Kaliningrad']
                ],
                'records' => [
                    ['count' => 11]
                ],
            ]
        );

        $params["predicate"]["operands"][0]["operand"]["value"][0] = "";
        $params["predicate"]["operands"][0]["operand"]["value"][1] = "";
        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res, [
                'offset' => 0,
                'fields' => ['airport_code', 'airport_name', 'timezone'],
                'sql' => 'SELECT  "t"."airport_code", "t"."airport_name", "t"."timezone" FROM "bookings"."airports" as t  WHERE ("t"."timezone" IS NOT NULL AND trim("t"."timezone"::text) <> \'\')  ORDER BY airport_code LIMIT 3 OFFSET 0',
                'data' => [
                    ['DME', 'Домодедово', 'Europe/Moscow'],
                    ['KEJ', 'Кемерово', 'Asia/Novokuznetsk'],
                    ['KGD', 'Храброво', 'Europe/Kaliningrad']
                ],
                'records' => [
                    ['count' => 11]
                ],
            ]
        );

        $params["predicate"]["operands"][0]["operand"]["value"][0] = "Asia/Novokuznetsk";
        $params["predicate"]["operands"][0]["operand"]["value"][1] = "Asia/Yakutsk";

        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res, [
                'offset' => 0,
                'fields' => ['airport_code', 'airport_name', 'timezone'],
                'sql' => 'SELECT  "t"."airport_code", "t"."airport_name", "t"."timezone" FROM "bookings"."airports" as t  WHERE ("t"."timezone" NOT IN (\'Asia/Novokuznetsk\',\'Asia/Yakutsk\'))  ORDER BY airport_code LIMIT 3 OFFSET 0',
                'data' => [
                    ['DME', 'Домодедово', 'Europe/Moscow'],
                    ['KGD', 'Храброво', 'Europe/Kaliningrad'],
                    ['KHV', 'Хабаровск-Новый', 'Asia/Vladivostok']
                ],
                'records' => [
                    ['count' => 7]
                ],
            ]
        );

        $params["predicate"]["operands"][0]["operand"]["value"] = "Asia/Novokuznetsk";
        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res, [
                'offset' => 0,
                'fields' => ['airport_code', 'airport_name', 'timezone'],
                'sql' => 'SELECT  "t"."airport_code", "t"."airport_name", "t"."timezone" FROM "bookings"."airports" as t  WHERE ("t"."timezone" <> \'Asia/Novokuznetsk\')  ORDER BY airport_code LIMIT 3 OFFSET 0',
                'data' => [
                    ['DME', 'Домодедово', 'Europe/Moscow'],
                    ['KGD', 'Храброво', 'Europe/Kaliningrad'],
                    ['KHV', 'Хабаровск-Новый', 'Asia/Vladivostok']
                ],
                'records' => [
                    ['count' => 9]
                ],
            ]
        );
    }

    public function test_getTableDataPredicate_operand_С() {
        $params = [
            "entityName" => "tickets",
            "schemaName" => "bookings",
            "predicate" => [
                "strict" => true,
                "operands" => [
                    [
                        "levelup" => false,
                        "operand" => [
                            "field" => "book_ref",
                            "path" => ["book_ref", "book_ref"],
                            "op" => "C",
                            "value" => "F313DD",
                            "search_in_key" => null,
                            "table_alias" => "t0"
                        ]
                    ]
                ]
            ],
            "aggregate" => [],
            "limit" => 10,
            "offset" => 0,
            "primaryKey" => "ticket_no",
            "currentKey" => "0005432000991",
            "fields" => [
                "ticket_no" => ["table_alias" => "t"],
                "book_ref" => [
                    "table_alias" => "t",
                    "subfields" => [
                        "book_ref",
                        "book_date",
                        "total_amount"
                    ],
                    "sssssss" => [],
                    "subfields_navigate_alias" => "t0",
                    "subfields_table_alias" => ["t0", "t0", "t0"],
                    "subfields_key" => "book_ref"
                ],
                "passenger_id" => ["table_alias" => "t"],
                "passenger_name" => ["table_alias" => "t"],
                "contact_data" => ["table_alias" => "t"]
            ],
            "join" => [[
                "key" => "book_ref",
                "virtual" => false,
                "schema" => "bookings",
                "entity" => "bookings",
                "table_alias" => "t0",
                "parent_table_alias" => "t",
                "entityKey" => "book_ref",
                "array_mode" => false
            ]
            ],
            "sample" => null,
            "order" => [["field" => "ticket_no"]],
            "group" => [],
            "process" => null,
            "functions" => [],
            "format" => "array",
            "desc" => "Загрузка таблицы \"Tickets\""
        ];

        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res["data"][0], ['0005432000991',
            '{"f1":"F313DD 2017-07-03 01:37:00+00 30900.00","f2":"F313DD"}',
            '6615 976589',
            'MAKSIM ZHUKOV',
            '{"email": "m-zhukov061972@postgrespro.ru", "phone": "+70149562185"}']);

        $params['fields']['book_ref']['format'] = 'Booking %s (%s), cost: %s';
        $res = methodsBase::getTableDataPredicate($params);
        $this->assertEquals($res["data"][0], ['0005432000991',
            '{"f1":"Booking F313DD (2017-07-03 01:37:00+00), cost: 30900.00","f2":"F313DD"}',
            '6615 976589',
            'MAKSIM ZHUKOV',
            '{"email": "m-zhukov061972@postgrespro.ru", "phone": "+70149562185"}']);

        // $this->assertEquals("","");
    }

    public function test_getAllModelMetadata() {
        global $_STORAGE;
        $res = methodsBase::getAllModelMetadata();

        // check relations loaded
        $this->assertArrayHasKey(
            $_STORAGE['Controller']->Sql(
                "SELECT format('%s.aircraft_code.%s.aircraft_code', 'bookings.aircrafts_data'::regclass::oid, 'bookings.flights'::regclass::oid) AS f"
            )[0]['f'],
            $res['projections']['aircrafts_data']['relations']
        );
    }

    public function test_getTableDefaultValues() {
        $params = [];
        $res = methodsBase::getTableDefaultValues($params);
        $this->assertEquals([], $res);
    }

    public function test_authenticate() {
        global $_STORAGE;
        $_STORAGE['PHPSESSID'] = '';
        $params = [
            'usename' => 'postgres',
            'passwd' => '123456'
        ];

        $res = methodsBase::authenticate($params);
        $this->assertEquals($res, [
            0 => [
                'usename' => 'postgres'
            ],
        ]);

        $params = [
            'usename' => '',
            'passwd' => ''
        ];

        $res = methodsBase::authenticate($params);
        $this->assertEquals($res, null);

    }

    public function test_GetClientIP() {
        $res = GetClientIP();
        $this->assertEquals($res, "UNKNOWN");
    }
}