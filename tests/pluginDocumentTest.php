<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
require_once dirname(__FILE__)."/../Server/methods.php";

final class pluginDocumentTest extends TestCase
{
    public function test_get_hint_t(): void
    {
      $res = methods::specialMethod(
        ["methodName"=>"get_hint", 
         "params"=>[["term_name"=>"отопительный прибор","term_kind"=>"T"]]]);

      $res[0]["st_term_key"] = "";
      $res[0]["st_term_definition"] = substr($res[0]["st_term_definition"], 0, 20);
      $this->assertEquals(
        [[
        "st_term_key" => "", 
        "st_term_name" => "отопительный прибор", 
        "st_term_definition" => 'Устройство' 
        ]],
        $res 
      );
    }

    public function test_get_hint_t_array(): void
    {
      $res = methods::specialMethod(
        ["methodName"=>"get_hint", 
         "params"=>[["term_name"=>["отопительный прибор","внутренняя поверхность нагрева отопительного прибора"],"term_kind"=>"T"]]]);

      $res[0]["st_term_key"] = "";
      $res[0]["st_term_definition"] = substr($res[0]["st_term_definition"], 0, 20);

      $res[1]["st_term_key"] = "";
      $res[1]["st_term_definition"] = substr($res[1]["st_term_definition"], 0, 10);

      $this->assertEquals(
        [[
        "st_term_key" => "", 
        "st_term_name" => "отопительный прибор", 
        "st_term_definition" => 'Устройство' 
        ],
        [
          "st_term_key" => "", 
          "st_term_name" => "внутренняя поверхность нагрева отопительного прибора", 
          "st_term_definition" => 'Часть' 
          ]
      ],
        $res 
      );
    }

    public function test_get_hint_s(): void
    {
      $res = methods::specialMethod(
        ["methodName"=>"get_hint", 
         "params"=>[["term_name"=>"ГОСТ 380-2005","term_kind"=>"S"]]]);

      $this->assertEquals(
        [
          [
                "st_key" => "6988cc41-85a2-4834-9ede-eae1c75dbac3", 
                "name" => "Сталь углеродистая обыкновенного качества. Марки", 
                "des_full" => "ГОСТ 380-2005" 
             ] 
        ],
        $res 
      );
    }

    public function test_get_hint_g(): void
    {
      $res = methods::specialMethod(
        ["methodName"=>"get_hint", 
         "params"=>[["term_name"=>"ГОСТ 380","term_kind"=>"G"]]]);
         print(json_encode($res));

      $this->assertEquals(
        [
          [
                "st_key" => "6988cc41-85a2-4834-9ede-eae1c75dbac3", 
                "name" => "Сталь углеродистая обыкновенного качества. Марки", 
                "des_full" => "ГОСТ 380-2005" 
             ] 
        ],
        $res 
      );
    }

    public function test_load_articles_simple(): void
    {
      $res = methods::specialMethod(
         [
          "methodName"=>"load_articles",
          "params"=>[[
                     "limit"=>"1",
                     "language"=>"russian",
                     "articleTitle"=>"title",
                     "articleText"=>"content",
                     "articleKey"=>"aircraft_doc_key",
                     "searchQuery"=>"пассажирский",
                     "entity"=>"aircraft_doc",
                     "schema"=>"test_modules"]]
          ]);

          unset($res['sql']);
          $res['data'][0]["content"] = substr($res['data'][0]["content"], 0, 40);
          $this->assertEquals(
            ['data'=>array (
              0 => 
              array (
                'aircraft_doc_key' => '12ed5979-0f6a-4aaa-a401-0c25912a183a',
                            'title' => 'Boeing 777',
                            'content' => '<b>пассажирских</b> само'
              )
            ), 'records'=>[['count'=>4]]]
            ,
            $res );
    }
    public function test_load_articles_vec(): void
    {
      $res = methods::specialMethod(
         [
          "methodName"=>"load_articles",
          "params"=>[[
                     "limit"=>"1",
                     "language"=>"russian",
                     "articleTitle"=>"title",
                     "articleText"=>"content",
                     "articleKey"=>"aircraft_doc_key",
                     "articleVec"=>"content_vec",
                     "searchQuery"=>"пассажирский",
                     "entity"=>"aircraft_doc",
                     "schema"=>"test_modules"]]
          ]);
      unset($res['sql']);
      $res['data'][0]["content"] = substr($res['data'][0]["content"], 0, 40);
      $this->assertEquals(
        ['data'=>array (
          0 => 
          array (
            'aircraft_doc_key' => '12ed5979-0f6a-4aaa-a401-0c25912a183a',
                        'title' => 'Boeing 777',
                        'content' => '<b>пассажирских</b> само'
          )
        ), 'records'=>[['count'=>4]]]
        ,
        $res );
    }
    
    public function test_load_articles_order(): void
    {
      $res = methods::specialMethod(
         [
          "methodName"=>"load_articles",
          "params"=>[[
                     "limit"=>"1",
                     "language"=>"russian",
                     "articleTitle"=>"title",
                     "articleText"=>"content",
                     "articleKey"=>"aircraft_doc_key",
                     "articleVec"=>"content_vec",
                     "orderBeforeRank"=>[["field"=>"title","desc"=>true]],
                     "orderAfterRank"=>[["field"=>"aircraft_doc_key","desc"=>true]],
                     "searchQuery"=>"пассажирский",
                     "entity"=>"aircraft_doc",
                     "schema"=>"test_modules"]]
          ]);
      $this->assertEquals(
        "SELECT aircraft_doc_key, ts_headline('russian', title, phraseto_tsquery('russian', 'пассажирский')::tsquery) as title,ts_headline('russian', content, phraseto_tsquery('russian', 'пассажирский')::tsquery) as content,count(*) over () as _selected_data_count from \"test_modules\".\"aircraft_doc\" where plainto_tsquery('russian','пассажирский') @@ content_vec order by \"title\" DESC,  ts_rank(content_vec, plainto_tsquery('russian','пассажирский')) desc , \"aircraft_doc_key\" DESC limit 1 offset 0",
        $res['sql']);
    }
    
    public function test_load_articles_labels(): void
    {
      $res = methods::specialMethod(
         [
          "methodName"=>"load_articles",
          "params"=>[[
                     "limit"=>"1",
                     "language"=>"russian",
                     "articleTitle"=>"title",
                     "articleText"=>"content",
                     "articleLabel"=>["label"],
                     "articleKey"=>"aircraft_doc_key",
                     "searchQuery"=>"пассажирский",
                     "entity"=>"aircraft_doc",
                     "schema"=>"test_modules"]]
          ]);

          unset($res['sql']);
          $res['data'][0]["content"] = substr($res['data'][0]["content"], 0, 40);
          $this->assertEquals(
            ['data'=>array (
              0 => 
              array (
                'aircraft_doc_key' => '12ed5979-0f6a-4aaa-a401-0c25912a183a',
                            'title' => 'Boeing 777',
                            'content' => '<b>пассажирских</b> само',
                            'label' => 'Boing'
              )
            ), 'records'=>[['count'=>4]]]
            ,
            $res );
    }

}