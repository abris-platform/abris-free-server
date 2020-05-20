<?php
declare( strict_types = 1 );

use PHPUnit\Framework\TestCase;
require_once dirname( __FILE__ ).'/../Server/methods_base.php';

function rrmdir($dir) { 
    if (is_dir($dir)) { 
      $objects = scandir($dir);
      foreach ($objects as $object) { 
        if ($object != "." && $object != "..") { 
          if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
            rrmdir($dir. DIRECTORY_SEPARATOR .$object);
          else
            unlink($dir. DIRECTORY_SEPARATOR .$object); 
        } 
      }
      rmdir($dir); 
    } 
  }

$params_common =  [
    'entityName' => 'airports_data',
    'schemaName' => 'bookings',
    'predicate' => [
        'strict' => true,
        'operands' => [
        ]
    ],
    'aggregate' => [
    ],
    'limit' => 0,
    'offset' => 0,
    'primaryKey' => 'airport_code',
    'currentKey' => null,
    'fields' => [
        'airport_code' => [
            'table_alias' => 't',
            'subfields' => null,
            'hidden' => false
        ],
        'coordinates' => [
            'table_alias' => 't'
        ],
        'timezone' => [
            'table_alias' => 't'
        ]
    ],
    'join' => [
    ],
    'sample' => null,
    'order' => [
    ],
    'process' => [
        'title' => 'Airports (internal data)',
        'captions' => [
            'airport_code' => 'Airport code',
            'coordinates' => 'Airport coordinates (longitude and latitude)',
            'timezone' => 'Airport time zone'
        ],
        'types' => [
            'airport_code' => 'string',
            'coordinates' => 'string',
            'timezone' => 'string'
        ]
    ],
    'functions' => [
    ],
    'format' => 'array',
    'desc' => 'Загрузка таблицы \'Airports ( internal data )\''

];

final class methodsTestExport extends TestCase
 {

    public function test_getTableDataPredicate_xlsx_simple() {
        global $params_common;
        $params = $params_common;
        $params["process"]["type"] = 'xls';
        $params["process"]["save_to_file"] = 1;
        $params["process"]["name_file"] = 'test_getTableDataPredicate_xlsx_simple.xlsx';
        $res = methodsBase::getTableDataPredicate( $params );
        //file_put_contents ( 'test_getTableDataPredicate_xlsx_simple.xlsx', ob_get_contents() );

        if (!file_exists('Server/files/tmp'))        
          mkdir('Server/files/tmp');
        $zip = new ZipArchive;
        if ($zip->open('Server/files/test_getTableDataPredicate_xlsx_simple.xlsx') === TRUE) {
            $zip->extractTo('Server/files/tmp');
            $this->assertEquals( sha1_file( 'tests/html/test_getTableDataPredicate_xlsx_simple.xml' ), sha1_file( 'Server/files/tmp/xl/worksheets/sheet1.xml' ) );
            $zip->close();
        } else {
            throw new Exception('zip error');
        }
        rrmdir('Server/files/tmp');
    }
    
    public function test_getTableDataPredicate_xlsx_groups() {
        global $params_common;
        $params = $params_common;
        $params["process"]["type"] = 'xls';
        $params["process"]["save_to_file"] = 1;
        $params["process"]["name_file"] = 'test_getTableDataPredicate_xlsx_groups.xlsx';
        $params['group'] = [['field'=>'timezone','desc'=>false]];
        $res = methodsBase::getTableDataPredicate( $params );
        $zip = new ZipArchive;
        if ($zip->open('Server/files/test_getTableDataPredicate_xlsx_groups.xlsx') === TRUE) {
            $zip->extractTo('Server/files/');
            $this->assertEquals( sha1_file( 'tests/html/test_getTableDataPredicate_xlsx_groups.xml' ), sha1_file( 'Server/files/xl/worksheets/sheet1.xml' ) );
            $zip->close();
        } else {
            throw new Exception('zip error');
        }

    }

    public function test_getTableDataPredicate_pdf_simple() {
        global $params_common;
        $params = $params_common;
        $params["process"]["type"] = 'pdf';
        $params["process"]["save_to_file"] = 1;
        $params["process"]["name_file"] = 'test_getTableDataPredicate_pdf_simple.pdf';
        $params["process"]["orient"]="P";
        $params["process"]["paper"]="A4";
        $params["process"]["font"]="12";
        $params["process"]["widths"]=[70,267,131];
        $res = methodsBase::getTableDataPredicate( $params );

        $assertedImagick = new \Imagick();
        $assertedImagick->readImageBlob( file_get_contents( 'Server/files/test_getTableDataPredicate_pdf_simple.pdf' ));
        $assertedImagick->resetIterator();
        $assertedImagick = $assertedImagick->appendImages(true);
        $testImagick = new \Imagick();
        $testImagick->readImageBlob(file_get_contents( 'tests/html/test_getTableDataPredicate_pdf_simple.pdf' ));
        $testImagick->resetIterator();
        $testImagick = $testImagick->appendImages(true);
    
        $diff = $assertedImagick->compareImages($testImagick, 1);
        $this->assertSame(0.0, $diff[1]);
    }

    public function test_getTableDataPredicate_pdf_group() {
        global $params_common;
        $params = $params_common;
        $params["process"]["type"] = 'pdf';
        $params["process"]["save_to_file"] = 1;
        $params["process"]["name_file"] = 'test_getTableDataPredicate_pdf_group.pdf';
        $params["process"]["orient"]="P";
        $params["process"]["paper"]="A4";
        $params["process"]["font"]="12";
        $params["process"]["widths"]=[70,267,131];
        $params['group'] = [['field'=>'timezone','desc'=>false]];

        $res = methodsBase::getTableDataPredicate( $params );

        $assertedImagick = new \Imagick();
        $assertedImagick->readImageBlob( file_get_contents( 'Server/files/test_getTableDataPredicate_pdf_group.pdf' ));
        $assertedImagick->resetIterator();
        $assertedImagick = $assertedImagick->appendImages(true);
        $testImagick = new \Imagick();
        $testImagick->readImageBlob(file_get_contents( 'tests/html/test_getTableDataPredicate_pdf_group.pdf' ));
        $testImagick->resetIterator();
        $testImagick = $testImagick->appendImages(true);
    
        $diff = $assertedImagick->compareImages($testImagick, 1);
        $this->assertSame(0.0, $diff[1]);
    }


}