<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
require_once dirname(__FILE__)."/../Server/methods_base.php";
require_once dirname(__FILE__)."/../tests/config.php";

$ipAddr = "";
$dbRecoveryFunction = 'password_recovery';
$dbChangeFunction = '';


final class methodsTest extends TestCase
{
   

 public function test_getPIDS_simple(){
    sql('select version()');
    /*$res = methodsBase::getPIDS([]);

    $this->assertEquals($res, array(
      'pids' => 
     array (
     ),
    ));*/

    $_SESSION['pids'] = ["test"=>"123456"];
    $res = methodsBase::getPIDS([]);
    $this->assertEquals($res, array(
      'pids' => 
     array (
     ),
    )); 



  }

  public function test_killPID(){
   $params = [
         "pid"=>"13008",
   ];
   $res = methodsBase::killPID($params);
   $this->assertEquals($res, [
      0 => [
   "pg_terminate_backend" => "f"
      ],
   ]);

  }

  public function test_getExtensionsVersion(){
   $res = methodsBase::getExtensionsVersion([]);

   $this->assertEquals($res[0]['name'], 'pg_abris');
 }

 public function test_addEntities(){
   $params = [
        'entityName' => 'bookings',
        'schemaName' => 'bookings',
        'fields' => [
          0 => [
            'book_ref' => '123456',
            'book_date' => '2020-03-12 12:04:00+03',
            'total_amount' => '654321',
          ],
         ],
        'files' => [],
        'key' => 'book_ref',
        'types' => NULL,
   ];

   $res = methodsBase::addEntities($params);
   $this->assertEquals($res, [
      0 => [
          'book_ref' => '123456',
      ],
    ]);  
    
 $params = [
      'entityName' => 'bookings',
      'schemaName' => 'bookings',
      'fields' => [
        0 => [
          'book_ref' => '44444',
          'book_date' => '2020-03-12 12:01:00+03',
          'total_amount' => '44444',
        ],
       ],
      'files' => [],
      'key' => 'book_ref',
      'types' => NULL,
   ];

  $res = methodsBase::addEntities($params);
  $this->assertEquals($res, [
    0 => [
        'book_ref' => '44444',
    ],
  ]); 

  $params = [
   "entityName" => "text_types", 
   "schemaName" => "test_schema", 
   "fields" => [
     0 => [
      "text_types_key" => "", 
      "meta_plain" => "test1", 
      "meta_text" => "test2", 
      "detail_plain" => "test3", 
      "detail_text" => "<p>test4</p>" 
     ],
    ],
   'files' => [],
   'key' =>  "text_types_key", 
   'types' => NULL,
];

$res = methodsBase::addEntities($params);   //    для теста ответ bb8b8a74-e4ec-4ce9-8b58-8d3de11d8137 !!!!!
$this->assertEquals($res, [
   0 => [
      "text_types_key" => 'bb8b8a74-e4ec-4ce9-8b58-8d3de11d8137' ,
   ],
 ]); 


 }

public function test_updateEntity(){
   
   
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
   $this->assertEquals($res,[
      "sql" => "UPDATE \"bookings\".\"bookings\" SET \"book_date\" = '2020-03-12 18:18:00+03'  WHERE \"book_ref\" = '000068' AND \"total_amount\" = '18100';"
   ]);  
   
    
   
   $params = [
      'entityName' => 'bookings', 
      'schemaName' => 'bookings', 
      'key' => ['book_ref', "total_amount"],
      'value' => [
         0 => ['000068','000181','000012'],
         1 => ['18100','131800','37900'],
      ],
      'fields' => [
         "book_date" => ["2020-03-12 18:18:00+03", "2020-03-12 18:18:00+03","2020-03-12 18:18:00+03"],
      ], 
      'files' => [], 
      'types' => NULL,
   ]; 

   $res = methodsBase::updateEntity($params);
   $this->assertEquals($res,[
      "sql" => "UPDATE \"bookings\".\"bookings\" SET \"book_date\" = '2020-03-12 18:18:00+03'  WHERE \"book_ref\" = '000068' AND \"total_amount\" = '18100';UPDATE \"bookings\".\"bookings\" SET \"book_date\" = '2020-03-12 18:18:00+03'  WHERE \"book_ref\" = '000181' AND \"total_amount\" = '131800';UPDATE \"bookings\".\"bookings\" SET \"book_date\" = '2020-03-12 18:18:00+03'  WHERE \"book_ref\" = '000012' AND \"total_amount\" = '37900';"
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
   $this->assertEquals($res,[
      "sql" => "UPDATE \"bookings\".\"bookings\" SET \"total_amount\" = '6666', \"book_date\" = '2020-03-12 18:44:00+03'  WHERE \"book_ref\" = '123456';UPDATE \"bookings\".\"bookings\" SET \"total_amount\" = '9999', \"book_date\" = '2020-03-12 18:55:00+03'  WHERE \"book_ref\" = '44444';"
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
   $this->assertEquals($res,[
      "sql" => "UPDATE \"test_schema\".\"text_types\" SET \"meta_plain\" = NULL, \"meta_text\" = NULL, \"detail_plain\" = NULL  WHERE \"text_types_key\" = 'bb8b8a74-e4ec-4ce9-8b58-8d3de11d8137';"
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
   $this->assertEquals($res,[
      "sql" => "UPDATE \"bookings\".\"bookings\" SET \"book_ref\" = '22222'::text  WHERE \"book_ref\"::text = '123456'::text;"
   ]);




 }

  public function test_deleteEntitiesByKey(){

   $params = [
      "entityName" => "bookings", 
      "schemaName" => "bookings", 
      'key' => 'book_ref',
      'value' => ['000015','000016'],
   ];
    
   $res = methodsBase::deleteEntitiesByKey($params);
   $this->assertEquals($res,[
      "sql" => "DELETE FROM \"bookings\".\"bookings\" WHERE \"book_ref\" = '000015';DELETE FROM \"bookings\".\"bookings\" WHERE \"book_ref\" = '000016';"
   ]);

   $params = [
      "entityName" => "bookings", 
      "schemaName" => "bookings", 
      'key' => ['book_ref', "total_amount"],
      'value' => [
         0 => ['000013','000014'],
         1 => ['000045','000055'],
      ],
   ];
    
   $res = methodsBase::deleteEntitiesByKey($params);
   $this->assertEquals($res,[
      "sql" => "DELETE FROM \"bookings\".\"bookings\" WHERE \"book_ref\" = '000013' AND \"total_amount\" = '000045';DELETE FROM \"bookings\".\"bookings\" WHERE \"book_ref\" = '000014' AND \"total_amount\" = '000055';"
   ]);

   $params = [
            "entityName" => "bookings", 
            "schemaName" => "bookings", 
            "key" => "book_ref", 
            "value" => "22222" 
    ]; 

   $res = methodsBase::deleteEntitiesByKey($params);
   $this->assertEquals($res,[
      "sql" => "DELETE FROM \"bookings\".\"bookings\" WHERE \"book_ref\" = '22222';"
   ]);


  }

  public function test_authenticate(){
    $_COOKIE['PHPSESSID'] = "";

    $params =  [
      "usename" => "postgres", 
      "passwd" => "123456" 
    ] ;

    $res = methodsBase::authenticate($params);
    $this->assertEquals($res,[
      0 => [
          "usename"=> "postgres"
      ],
    ]); 

    $params =  [
      "usename" => "", 
      "passwd" => "" 
    ] ;

    $res = methodsBase::authenticate($params);
    $this->assertEquals($res,null);
  } 

 
  public function test_getCurrentUser()
  {
   global $_STORAGE;
   $_STORAGE['login'] = '';
   $res = methodsBase::getCurrentUser();
   $this->assertEquals($res,"guest");
   $_SERVER['REMOTE_USER'] = "abris.site\\postgres";
   $res = methodsBase::getCurrentUser();
   $this->assertEquals($res,"postgres");
   $res = methodsBase::getCurrentUser();
   $this->assertEquals($res,"postgres");

  }

  public function test_isGuest()
  {
   $_SESSION['login'] = 'postgres'; 
   $res = methodsBase::isGuest();
   $this->assertEquals($res,1);
  }


  public function test_quote(){
     $res = methodsBase::quote("test");
     $this->assertEquals($res,"'test'");
  }

  public function test_test(){
   $res = methodsBase::test("test");
   $this->assertEquals($res,"test");
  }

  public function test_getTableDataPredicate_array(){
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

public function test_getTableDataPredicate_empty_groups(){
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

public function test_getTableDataPredicate_object(){
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

//[{"entityName":"aircrafts","schemaName":"bookings","predicate":{"strict":true,"operands":[]},"aggregate":[{"func":"count","field":"range"}],"limit":1,"offset":0,"primaryKey":"aircraft_code","fields":{"aircraft_code":{"table_alias":"t","subfields":null,"hidden":false},"model":{"table_alias":"t"},"range":{"table_alias":"t"}},"join":[],"order":[],"process":null,"functions":[],"format":"array"}]

public function test_getTableDataPredicate_aggregate(){
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
       "aggregate" => [["func"=>"count","field"=>"airport_code"]], 
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
"sql" => "SELECT  \"t\".\"airport_code\", \"t\".\"airport_name\", \"t\".\"timezone\" FROM \"bookings\".\"airports\" as t  where (\"t\".\"timezone\" = 'Asia/Novokuznetsk')   LIMIT 10 OFFSET 0", 
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
      0 => 104,
      1 => 104,
      2 => 104,
      3 => 104,

   ],

],

"records" => [
   0 => [
      "count" => 104,
   ],
],

"offset" => 0,
"fields" => [
   0 => "airport_name",
   1 => "city",
   2 => "coordinates",
   3 => "timezone"
],

"sql" => "SELECT  count(\"t\".\"airport_name\") as airport_name, count(\"t\".\"city\") as city, count(\"t\".\"coordinates\") as coordinates, count(\"t\".\"timezone\") as timezone FROM \"bookings\".\"airports\" as t    LIMIT 1 OFFSET 0",
"count(airport_name)" => [
   0 => [
      "count" => 104
   ]
],

"count(city)" => [
   0 => [
      "count" => 104
   ]
],

"count(coordinates)" => [
   0 => [
      "count" => 104
   ]
],

"count(timezone)" => [
   0 => [
      "count" => 104
   ]
],
]
);

}


public function test_getTableDataPredicate_currentKey(){
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
        "order" => [["field"=>"airport_code" ,"desc"=>"1"]], 
        "process" => null, 
        "functions" => [] 
      
];       
$res = methodsBase::getTableDataPredicate($params);
$this->assertEquals(
[
"data" => [
      [
         "NNM", 
         "Нарьян-Мар", 
         "Europe/Moscow" 
      ], 
      [
            "NJC", 
            "Нижневартовск", 
            "Asia/Yekaterinburg" 
         ], 
      [
               "NFG", 
               "Нефтеюганск", 
               "Asia/Yekaterinburg" 
            ], 
      [
                  "NBC", 
                  "Бегишево", 
                  "Europe/Moscow" 
               ], 
      [
                     "NAL", 
                     "Нальчик", 
                     "Europe/Moscow" 
                  ], 
      [
                        "MRV", 
                        "Минеральные Воды", 
                        "Europe/Moscow" 
                     ], 
      [
                           "MQF", 
                           "Магнитогорск", 
                           "Asia/Yekaterinburg" 
                        ], 
      [
                              "MMK", 
                              "Мурманск", 
                              "Europe/Moscow" 
                           ], 
      [
                                 "MJZ", 
                                 "Мирный", 
                                 "Asia/Yakutsk" 
                              ], 
      [
                                    "MCX", 
                                    "Уйташ", 
                                    "Europe/Moscow" 
                                 ] 
   ], 
"records" => [
                                       [
                                          "count" => 104
                                       ] 
                                    ], 
"offset" => '50', 
"fields" => [
                                             "airport_code", 
                                             "airport_name", 
                                             "timezone" 
                                          ], 
"sql" => "SELECT  \"t\".\"airport_code\", \"t\".\"airport_name\", \"t\".\"timezone\" FROM \"bookings\".\"airports\" as t   ORDER BY \"t\".\"airport_code\" DESC, airport_code LIMIT 10 OFFSET 50" 
                                       ],$res
);

$params["middleRow"] = true;
$res = methodsBase::getTableDataPredicate($params);
$this->assertEquals(
   [
   "data" => [
      [
          'NFG',
          'Нефтеюганск',
          'Asia/Yekaterinburg'
      ],

      [
          'NBC',
          'Бегишево',
          'Europe/Moscow'
      ],

      [
          'NAL',
          'Нальчик',
          'Europe/Moscow'
      ],

      [
          'MRV',
          'Минеральные Воды',
          'Europe/Moscow'
      ],

      [
          'MQF',
          'Магнитогорск',
          'Asia/Yekaterinburg'
      ],

      [
          'MMK',
          'Мурманск',
          'Europe/Moscow'
      ],

      [
          'MJZ',
          'Мирный',
          'Asia/Yakutsk'
      ],

      [
          'MCX',
          'Уйташ',
          'Europe/Moscow'
      ],

      [
          'LPK',
          'Липецк',
          'Europe/Moscow'
      ],

      [
          'LED',
          'Пулково',
          'Europe/Moscow'
      ],

      ], 
      "records" => [
                                          [
                                             "count" => 104
                                          ] 
                                       ], 
      "offset" => '52', 
      "fields" => [
                                                "airport_code", 
                                                "airport_name", 
                                                "timezone" 
                                             ], 
      "sql" => "SELECT  \"t\".\"airport_code\", \"t\".\"airport_name\", \"t\".\"timezone\" FROM \"bookings\".\"airports\" as t   ORDER BY \"t\".\"airport_code\" DESC, airport_code LIMIT 10 OFFSET 52" ,
   ],$res);

}






public function test_getTableDataPredicate_groups(){
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
$this->assertEquals($res["data"], [
   [
       '20834',
       'PG0001',
       '2017-08-12 12:15:00+00',
       '2017-08-12 14:35:00+00',
       '{"f1":"UIK Усть-Илимск Усть-Илимск (102.565002441406,58.136100769043) Asia/Irkutsk","f2":"UIK"}',
       '{"f1":"SGC Сургут Сургут (73.4018020629883,61.3437004089355) Asia/Yekaterinburg","f2":"SGC"}',
       'Arrived',
       '{"f1":"CR2 Бомбардье CRJ-200 2700","f2":"CR2"}',
       '2017-08-12 12:17:00+00',
       '2017-08-12 14:34:00+00'
   ],

[
       '20835',
       'PG0001',
       '2017-07-22 12:15:00+00',
       '2017-07-22 14:35:00+00',
       '{"f1":"UIK Усть-Илимск Усть-Илимск (102.565002441406,58.136100769043) Asia/Irkutsk","f2":"UIK"}',
       '{"f1":"SGC Сургут Сургут (73.4018020629883,61.3437004089355) Asia/Yekaterinburg","f2":"SGC"}',
       'Arrived',
       '{"f1":"CR2 Бомбардье CRJ-200 2700","f2":"CR2"}',
       '2017-07-22 12:17:00+00',
       '2017-07-22 14:35:00+00'
   ],
[
       '20839',
       'PG0001',
       '2017-08-05 12:15:00+00',
       '2017-08-05 14:35:00+00',
       '{"f1":"UIK Усть-Илимск Усть-Илимск (102.565002441406,58.136100769043) Asia/Irkutsk","f2":"UIK"}',
       '{"f1":"SGC Сургут Сургут (73.4018020629883,61.3437004089355) Asia/Yekaterinburg","f2":"SGC"}',
       'Arrived',
       '{"f1":"CR2 Бомбардье CRJ-200 2700","f2":"CR2"}',
       '2017-08-05 12:18:00+00',
       '2017-08-05 14:38:00+00'
],

[
       '20841',
       'PG0001',
       '2017-07-29 12:15:00+00',
       '2017-07-29 14:35:00+00',
       '{"f1":"UIK Усть-Илимск Усть-Илимск (102.565002441406,58.136100769043) Asia/Irkutsk","f2":"UIK"}',
       '{"f1":"SGC Сургут Сургут (73.4018020629883,61.3437004089355) Asia/Yekaterinburg","f2":"SGC"}',
       'Arrived',
       '{"f1":"CR2 Бомбардье CRJ-200 2700","f2":"CR2"}',
       '2017-07-29 12:19:00+00',
       '2017-07-29 14:40:00+00'
],
[   
       '20836',
       'PG0001',
       '2017-09-09 12:15:00+00',
       '2017-09-09 14:35:00+00',
       '{"f1":"UIK Усть-Илимск Усть-Илимск (102.565002441406,58.136100769043) Asia/Irkutsk","f2":"UIK"}',
       '{"f1":"SGC Сургут Сургут (73.4018020629883,61.3437004089355) Asia/Yekaterinburg","f2":"SGC"}',
       'Cancelled',
       '{"f1":"CR2 Бомбардье CRJ-200 2700","f2":"CR2"}',
       '',
       ''
]
]);
}

public function test_getTableData()
  {
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
   $this->assertEquals($res,[
         "data" =>[
            0 => ["book_ref" => '00000F', "total_amount" => "265700.00"],
         ],
         "records" =>[
            0 => ["count" => '1'],
         ]

   ]);



   $params =  [
      "entityName" => "tickets", 
      "schemaName" => "bookings", 
      "fields" => [
         "passenger_id" 
      ], 
      "predicate" => "21312", 
      "limit" => 15, 
      "offset" => 0, 
      "key" => null, 
      "value" => null, 
      "distinct" => true, 
      "exclude" => [
         ], 
      "desc" => 'Загрузка списка "Tickets"' 

      
   ];
   $res = methodsBase::getTableData($params);
   $this->assertEquals($res,[
      "data" =>[
         0 => ["passenger_id" => '1405 221312'],
         1 => ["passenger_id" => '2117 213123'],
         2 => ["passenger_id" => '2846 021312'],
         3 => ["passenger_id" => '3960 621312'],
         4 => ["passenger_id" => '4152 521312'],
         5 => ["passenger_id" => '5838 621312'],
         6 => ["passenger_id" => '6366 213128'],
         7 => ["passenger_id" => '6704 621312'],
         8 => ["passenger_id" => '6772 213122'],
         9 => ["passenger_id" => '6991 021312'],
         10 => ["passenger_id" => '7437 921312'],
         11 => ["passenger_id" => '9183 213124'],

      ],
      "records" =>[
         0 => ["count" => '12'],
      ]
   ]);


   $params =  [
      "fields" => "",
      "entityName" => "bookings", 
      "schemaName" => "bookings", 
      "key" => "book_ref", 
      "value" => "00000F", 
      "limit" => 15, 
      "offset" => 0, 
   ];
   $res = methodsBase::getTableData($params);
   $this->assertEquals($res,[
      "data" =>[
         0 => ["book_ref" => "00000F",
         "book_date" => "2017-07-05 00:12:00+00",
         "total_amount" => "265700.00",
         ],
      ],
      "records" =>[
         0 => ["count" => '1'],
      ],

   ]);
  }


  public function test_getEntityByKey_One(): void
    {
        $this->assertEquals(
          methodsBase::getEntitiesByKey(array("schemaName" => "meta",
            "entityName" => "view_projection_entity",
            "key" => "projection_name",
            "value" => "airports")),
            array(array( 
                "projection_name" => "airports",
                "title" => "Airports",
                "jump" => "airports",
                "primarykey" => "airport_code",
                "additional" => null, 
                "readonly" => "f",
                "hint" => null, 
                "table_schema" => "bookings",
                "table_name" => "airports"
            ) ) 
          );


          $this->assertEquals(
            count(methodsBase::getEntitiesByKey(array("schemaName" => "meta",
              "entityName" => "view_projection_relation",
              "key" => "projection_name",
              "value" => "aircrafts_data"))),
              3
            );
        
    }

    public function test_getEntityByKey_Many(): void
    {
        $res = methodsBase::getEntitiesByKey(array("schemaName" => "meta",
        "entityName" => "view_projection_entity",
        "key" => "projection_name",
        "value" => array("aircrafts", "airports")));

        $this->assertEquals(
          $res,
            array(
              array( 
                "projection_name" => "aircrafts",
                "title" => "Aircrafts",
                "jump" => "aircrafts",
                "primarykey" => "aircraft_code",
                "additional" => null, 
                "readonly" => "f",
                "hint" => null, 
                "table_schema" => "bookings",
                "table_name" => "aircrafts"
              ),
              array( 
                "projection_name" => "airports",
                "title" => "Airports",
                "jump" => "airports",
                "primarykey" => "airport_code",
                "additional" => null, 
                "readonly" => "f",
                "hint" => null, 
                "table_schema" => "bookings",
                "table_name" => "airports"
            ) ) 
          );
        
    }

    public function test_getUserDescription(){
      global $_STORAGE;
      $_STORAGE['login'] = "admins";
      $res = methodsBase::getUserDescription();
      $this->assertEquals(
         $res,
           array(
            "user" => "admins",
            "comment" => "Администратор"
           )
      );
        
    }
    public function test_getTableDataPredicate_duration(){
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
                                   "search_in_key"=>false
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
      "SELECT  \"t\".\"flight_no\", \"t\".\"scheduled_departure\" FROM \"bookings\".\"flights\" as t  where (\"t\".\"scheduled_departure\" <= now() and \"t\".\"scheduled_departure\" > now() - 'P3Y6M4D'::interval)   LIMIT 10 OFFSET 0",
      $res['sql']
    );
  
 
  
   
   } 

   public function test_getTableDataPredicate_match_order(){
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

      $this->assertEquals('[{"airport_code":"NAL","timezone":"Europe\/Moscow"},{"airport_code":"NBC","timezone":"Europe\/Moscow"},{"airport_code":"NFG","timezone":"Asia\/Yekaterinburg"},{"airport_code":"NJC","timezone":"Asia\/Yekaterinburg"},{"airport_code":"NNM","timezone":"Europe\/Moscow"},{"airport_code":"NOJ","timezone":"Asia\/Yekaterinburg"},{"airport_code":"NOZ","timezone":"Asia\/Novokuznetsk"},{"airport_code":"NSK","timezone":"Asia\/Krasnoyarsk"},{"airport_code":"NUX","timezone":"Asia\/Yekaterinburg"},{"airport_code":"NYA","timezone":"Asia\/Yekaterinburg"},{"airport_code":"NYM","timezone":"Asia\/Yekaterinburg"},{"airport_code":"ABA","timezone":"Asia\/Krasnoyarsk"},{"airport_code":"BAX","timezone":"Asia\/Krasnoyarsk"},{"airport_code":"CEK","timezone":"Asia\/Yekaterinburg"},{"airport_code":"CNN","timezone":"Asia\/Yakutsk"}]',
      json_encode($res['data']));
      }
      public function test_getTableDataPredicate_space_search(){
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
      
     
        $this->assertEquals(
         'SELECT  "t"."airport_code", "t"."airport_name", "t"."timezone" FROM "bookings"."airports" as t  where ("t"."timezone"::TEXT ilike \'%As%\'::TEXT and "t"."timezone"::TEXT ilike \'%vo%\'::TEXT)   LIMIT 10 OFFSET 0',  
         $res['sql']
       );
      
   }
      

}