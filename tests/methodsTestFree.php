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
    $res = methodsBase::getPIDS([]);

    $this->assertEquals($res, array(
      'pids' => 
     array (
     ),
    ));

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
   $this->assertEquals($res,[]);

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
   $this->assertEquals($res,[]);


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
   $this->assertEquals($res,[]);




 }

  public function test_deleteEntitiesByKey(){

     $params = [
            "entityName" => "bookings", 
            "schemaName" => "bookings", 
            "key" => "book_ref", 
            "value" => "22222" 
    ]; 

    $res = methodsBase::deleteEntitiesByKey($params);
    $this->assertEquals($res,[]);
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
   $_SESSION['login'] = '';
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


}