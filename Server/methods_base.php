<?php
/**
 * Abris - Web Application RAD Framework
 * @version v2.0.1
 * @license (c) TRO MOO AIO, Commercial Product
 * @date Sat Sep 17 2016 09:45:15
 */
    //require_once dirname(__FILE__).'/vendor/autoload.php';
if(file_exists(dirname(__FILE__) . '/tcpdf/tcpdf.php'))
	include_once(dirname(__FILE__) . '/tcpdf/tcpdf.php');
if(file_exists(dirname(__FILE__) . '/xlsxwriter.class.php'))
	include_once(dirname(__FILE__) . '/xlsxwriter.class.php');

    //use Spipu\Html2Pdf\Html2Pdf;
    //use Spipu\Html2Pdf\Exception\Html2PdfException;
    //use Spipu\Html2Pdf\Exception\ExceptionFormatter;

    require_once "db.php";
if(file_exists(dirname(__FILE__) . '/plugins.php'))
    require_once "plugins.php";

    function relation($schema, $table){
        global $dbUnrollViews;
        $rel = id_quote($schema).".".id_quote($table);
        if(in_array($rel, $dbUnrollViews?:array()))
        {
            $r = sql("select pg_get_viewdef(to_regclass('$rel'));");
            return "(".trim($r[0]["pg_get_viewdef"],';').")";
        }
        else
        return $rel;
    }

class methodsBase
{
    protected static function postProcessing(&$data_result, &$params){
        return $data_result;
    }
    
    public static function authenticate($params)
    {
        if ($params["usename"] <> '' and $params["passwd"] <> '') {
            $_SESSION['login'] = $params["usename"];
            $_SESSION['password'] = $params["passwd"];
            checkSchemaAdmin();
            return sql("SELECT '" . $params["usename"] . "' as usename"); // выполнить запрос для проверки аутентификации
            //return array(array("usename" => $params["usename"]));
        }
        else {
            if ($_SESSION['login'] <> '' and $_SESSION['password'] <> '') {
                global $adminSchema;
                global $ipAddr;
                if ($_SESSION["enable_admin"] == "t")
                    sql("SELECT " .$adminSchema .".update_session('" .$_SESSION['login'] ."', '" .$ipAddr ."', '" .$_COOKIE['PHPSESSID'] ."');", true);
            }

            unset($_SESSION['login']);
            unset($_SESSION['password']);
            unset($_SESSION['enable_admin']);
        }
    }
    
    public static function getAllEntities($params)
    {
        return sql('SELECT * FROM ' . relation($params["schemaName"], $params["entityName"]).' t');
    }
    
    public static function getCurrentUser()  
    {
        global $domain, $user;
        if (isset($_SESSION['login']) && ($_SESSION['login']) <> '') {
            return $_SESSION['login'];
        } else 
		if(isset($_SERVER['REMOTE_USER']))
		{
             $cred = explode('\\',$_SERVER['REMOTE_USER']);
             list($domain, $user) = $cred;
             $_SESSION['login'] =  $user;
             return $user;
        }
		else            
			return 'guest';

    }


    public static function isGuest()
    {
        return isset($_SESSION['login']);
    }


    public static function getTableData($params)
    {

        if ($params["fields"]) {
            $field_list = "";
            foreach ($params["fields"] as $field_index => $field_name) {
                if ($field_list) {
                    $field_list .= ", ";
                }
              
                $field_list .= id_quote($field_name);
            }
        } else {
            $field_list = "*";
        }

        


        $distinctfields = '';
        $orderfields = '';
		if(isset($params["distinct"])) {
            if(is_array($params["distinct"])){

               /* foreach ($params["distinct"] as $i => $o) {
        
                    if( $params["fields"][$o["field"]]["subfields"])
                        $o_f = id_quote($o["field"]);
                    else
                        $o_f = id_quote($o["field"]);
                    
                    if( isset($o["func"])){
                        $o_f = id_quote($o["func"]).'('.$o_f.')';
                    }
                    if( isset($o["type"])){
                        $o_f = $o_f.'::'.id_quote($o["type"]);
                    }
                    if( isset($o["distinct"])){
                        if ($distinctfields) {
                            $distinctfields .= ', '.$o_f;
                        } else {
                            $distinctfields = $o_f;
                        }
                    }
        
                    if ($orderfields) {
                        $orderfields .= ', '.$o_f;
                    } else {
                        $orderfields = 'ORDER BY '.$o_f;
                    }
        
                    if ($o["desc"]) {
                        $orderfields .= " DESC";
                    }
                }
                $statement = "SELECT DISTINCT on ($distinctfields) $field_list FROM " . relation($params["schemaName"],$params["entityName"]). ' t';
                $count = "SELECT count(DISTINCT $distinctfields) FROM " . relation($params["schemaName"],$params["entityName"]). ' t';
            */
            }
            else{
                $statement = "SELECT DISTINCT $field_list FROM " . relation($params["schemaName"],$params["entityName"]). ' t';
                $count = "SELECT count(DISTINCT $field_list) FROM " . relation($params["schemaName"],$params["entityName"]). ' t';

            }
        }
        else {
            $statement = "SELECT $field_list FROM " . relation($params["schemaName"],$params["entityName"]). ' t';
            $count = "SELECT count(*) FROM " . relation($params["schemaName"],$params["entityName"]). ' t';

        }

        $where = "";

        if($params["fields"]){
            foreach ($params["fields"] as $k => $n) {
                if(!in_array($n, $params['exclude'])){
                    if ($where) {
                        $where .= " OR ";
                    }
                    $where .= id_quote($n) . "::TEXT ILIKE '" . pg_escape_string('%' . $params["predicate"] . '%') . "'::TEXT";
                }

            }
       }

        if ($params["key"] && $params["value"]) {
            if ($where) {
                $where = "(" . $where . ") AND ";
            }
            $where .= id_quote($params["key"]) . " = '" . pg_escape_string($params["value"]) . "'";
        }

        if ($where) {
            $count = $count . ' where ' . $where;
            $statement = $statement . ' where ' . $where;
        }
        $statement = $statement.' '.$orderfields;
        

        if ($params["limit"] != 0 or $params["offset"] != 0) {
            $statement = $statement . ' LIMIT ' . $params["limit"] . ' OFFSET ' . $params["offset"];
        }


        return array("data" => sql($statement), "records" => sql($count));
    }
     //---------------------------------------------------------------------------------------
    // Если что вернуть в функцию getTableDataPredicate
    public static function quote($n) {
        return "'" . pg_escape_string($n) . "'";
    }

    public static function makeSimplePredicate($operand, $replace_rules, $fields, $params)
    {
        $func = null;
        $type_desc = '';

        $field = $operand["field"];
        if(isset($operand["func"]))
          $func = $operand["func"];

        if(isset($fields[$field]['type']))
            $type_desc = '::'.$fields[$field]['type'];


        if (!$operand["search_in_key"] && isset($replace_rules[$field])) {
            $field = $replace_rules[$field];
        } else {
            if ($operand["table_alias"]) {
                $field = id_quote($operand["table_alias"]) . "." . id_quote($field);
            } else {
                $field = "t." . id_quote($field);
            }
        }

        if(isset($operand["type"]))
            $field .= '::'.id_quote($operand["type"]);

        $value = $operand["value"];
        switch ($operand["op"]) {
            case "EQ":
                if(is_array($value)) {
                    if(count($value) > 0)
                    {
                        $value_list = implode(",",array_map("methods::quote", $value));
                        return $field ." IN ($value_list)";
                    }
                    else
                        return $field . " is null or ". $field ." = ''";
                }
                if(is_null($value)) {
                    return $field . " is null";
                }

                return $field . " = '" . pg_escape_string($value) . "'".$type_desc;
            case "NEQ":
                if(is_array($value)) {
                    if(count($value) > 0)
                    {
                        $value_list = implode(",",array_map("methods::quote", $value));
                        return $field ." NOT IN ($value_list)";
                    }
                    else
                        return $field . " is not null and ". $field ." <> ''";
                }
                return $field . " <> '" . pg_escape_string($value) . "'";
            case "G":
                return $field . " > '" . pg_escape_string($value) . "'";
            case "F":
                return  id_quote($value) . "($field)";
            case "FC":
                return  id_quote($value) . "('".pg_escape_string($params["schemaName"].'.'.$params["entityName"])."', $field)";
            case "EQF":
                return $field . " =  ". id_quote($value) . "()";
            case "FEQ":
                return id_quote($func) . "($field) =  '" . pg_escape_string($value) . "'";
            case "L":
                return $field . " < '" . pg_escape_string($value) . "'";
            case "GEQ":
                return $field . " >= '" . pg_escape_string($value) . "'";
            case "LEQ":
                return $field . " <= '" . pg_escape_string($value) . "'";
            case "C":
                if($value){
                    if($field != "t.\"\"") {
                        return $field . "::TEXT ilike '%" . pg_escape_string($value) . "%'::TEXT";
                    }
                    else{
                        $where = "";

                        foreach ($fields as $k => $field_description) {
                            if(!$field_description["hidden"]){
                                if (isset($field_description["subfields"])) {
                                    foreach ($field_description["subfields"] as $m => $j_field) {
                                        if ($where) {
                                            $where .= " OR ";
                                        }
                                        $where .= $field_description["subfields_table_alias"][$m].".".id_quote($j_field)."::TEXT ILIKE '" . pg_escape_string('%' . $value . '%') . "'::TEXT";
                                    }

                                }
                                else{
                                    if ($where) {
                                        $where .= " OR ";
                                    }
                                    $where .= "t.".id_quote($k) . "::TEXT ILIKE '" . pg_escape_string('%' . $value . '%') . "'::TEXT";

                                }
                            }
                        }
                        return "($where)";
                    }

                }

                else
                    return "true";
            case "ISN":
                //return $field . " IS NULL or ". $field ." = ''";
                return $field . " IS NULL ";
            case "ISNN":
                return $field . " IS NOT NULL ";
            case "FTS":
                
                $fts = json_decode($value, true);
                $where = "";
                $ft_query = $fts["ft_query"];
                $lang = $fts['language'];

                foreach (array_merge($fts["oth_props"], $fts["ft_props"]) as $prop) {
                    $field = $fields[$prop];
                    if (!$field["hidden"]) {
                        if (isset($field["subfields"])) {
                            foreach ($field["subfields"] as $m => $j_field) {
                                if ($where) $where .= " OR ";
                                $where .= $field["subfields_table_alias"][$m]. ".".id_quote($j_field) .
                                 "::TEXT ILIKE '" . pg_escape_string('%' . $ft_query . '%') . "'::TEXT";;
                            }
                        }
                        else
                        {
                            if ($where) $where .= " OR ";
                            $where .= "t.".id_quote($prop) . "::TEXT ILIKE '" .
                             pg_escape_string('%' . $ft_query . '%') . "'::TEXT";
                        }
                    }
                }

                foreach ($fts["ft_props"] as $prop) {
                    $field = $fields[$prop];
                    if (!$field["hidden"]) {
                        if (isset($field["subfields"])) {
                            foreach ($field["subfields"] as $m => $j_field) {
                                if ($where) $where .= " OR ";
                                $where .= "to_tsvector('" . $lang . "', " . $field["subfields_table_alias"][$m]. ".".id_quote($j_field) .
                                 ") @@ plainto_tsquery('" . $lang . "', '". pg_escape_string($ft_query) . "'::TEXT";
                            }
                        }
                        else
                        {
                            if ($where) $where .= " OR ";
                            $where .= "to_tsvector('" . $lang . "', t.".id_quote($prop) . ") @@ plainto_tsquery('" . $lang . "', '" . pg_escape_string($ft_query) . "')";
                        }
                    }
                }

                return '(' . $where . ')';

        }
    }
    public static function makePredicateString($predicate_object, $replace_rules, $fields, $params)
    {

        $operator = '';
        $string = '';
        foreach ($predicate_object["operands"] as $op) {

            if (!$op["levelup"]) {
                $string .= $operator . '(' . self::makeSimplePredicate($op["operand"], $replace_rules, $fields, $params) . ')';
            } else {
                $string .= $operator . '(' . self::makePredicateString($op["operand"], $replace_rules, $fields, $params) . ')';
            }
            $operator = ($predicate_object["strict"]) ? " AND " : " OR ";
        }
        return $string;
    }
    //---------------------------------------------------------------------------------------
    public static function makeOrderAndDistinctString($order_object, $params){
        $orderfields = '';
        $distinctfields = '';
        foreach ($order_object as $i => $o) {
            $o_t_alias = $params["fields"][$o["field"]]["table_alias"];
            if(!$o_t_alias)
              $o_t_alias = 't';

            if( isset($params["fields"][$o["field"]]["subfields"]))
                $o_f = id_quote($o["field"]);
            else
                $o_f = id_quote($o_t_alias).'.' .id_quote($o["field"]);
            
            if( isset($o["func"])){
                $o_f = id_quote($o["func"]).'('.$o_f.')';
            }
            if( isset($o["type"])){
                $o_f = $o_f.'::'.id_quote($o["type"]);
            }
            if(isset($o["distinct"])){
                if ($distinctfields) {
                    $distinctfields .= ', '.$o_f;
                } else {
                    $distinctfields = $o_f;
                }
            }

            if ($orderfields) {
                $orderfields .= ', '.$o_f;
            } else {
                $orderfields = 'ORDER BY '.$o_f;
            }

            if (isset($o["desc"])) {
              if($o["desc"])
                 $orderfields .= " DESC";
            }
        }
        if ($orderfields && $params["primaryKey"]) {
            $orderfields .= ', '.$params["primaryKey"];
        }
        return array('orderfields'=>$orderfields, 'distinctfields'=>$distinctfields);

    }
    //---------------------------------------------------------------------------------------

    public static function getTableDataPredicate($params)
    {
        // plog();
        // plog(json_encode($params));
        $desc =  isset($params['desc'])? $params['desc']:'';
        $replace_rules = array();

        if (isset($params["fields"])) {
            $field_list = "";
        } else {
            $field_list = "*";
        }


        $orderfields = '';
        $distinctfields = '';

        if(isset($params["process"])){
          if(isset($params["process"]["aggregate"]))
            $params["aggregate"] = $params["process"]["aggregate"];
          if(isset($params["process"]["group"]))
            $params["group"] = $params["process"]["group"];
        }

        $field_array = array();


        if(isset($params["aggregate"]) && count($params["aggregate"]) && isset($params["group"])){
            foreach($params["group"] as $i => $field_obj){
                $field_name = $field_obj["field"];
                if ($field_list) {
                    $field_list .= ", ";
                }
                $field_description = $params["fields"][$field_name];
                $field_list .= id_quote($field_description["table_alias"]).".".id_quote($field_name);
                $field_array[] = $field_name;
            }
            foreach($params["aggregate"] as $i => $field_obj){
                if ($field_list) {
                    $field_list .= ", ";
                }
                $field_name = $field_obj["field"];
                $field_func = $field_obj["func"];
                $field_description = $params["fields"][$field_name];
                $field_list .= $field_func."(".id_quote($field_description["table_alias"]).".".id_quote($field_name).") as $field_name";
                $field_array[] = $field_name;
            }
        }
        else{

            foreach ($params["fields"] as $field_name => $field_description) {
                if ($field_list) {
                    $field_list .= ", ";
                }


                if (isset($field_description["subfields"])) {
                    $j_field_list = "";
                    foreach ($field_description["subfields"] as $m => $j_field) {
                        if ($j_field_list) {
                            $j_field_list .= "||' '|| ";
                        }
                        $j_field_list .= "COALESCE(" . $field_description["subfields_table_alias"][$m] . "." . id_quote($j_field) . "::text,'')";
                    }
                    $field_list .= "(row_to_json(row($j_field_list, " . $field_description["subfields_navigate_alias"] . "." . id_quote($field_description["subfields_key"]) . "::text))::text) collate \"C\" as " . id_quote($field_name);
                    $field_array[] = $field_name;

                    $replace_rules[$field_name] = $j_field_list;
                } else {
            
                if(isset($field_description["only_filled"]))    
                  $field_list .= id_quote($field_description["table_alias"]).".".id_quote($field_name)." is not null as ".id_quote($field_name);
                else
                  $field_list .= id_quote($field_description["table_alias"]).".".id_quote($field_name);
                $field_array[] = $field_name;

                //if($field_description["type"])
                //  $field_list .= "::".id_quote($field_description["type"]); // <- �� ������ ������
                
                //$field_list .= "::text"; // <- �� ������ ������
              }

            }
            foreach ($params["functions"] as $function_name => $function_description) {
                if ($field_list) {
                    $field_list .= ", ";
                }
                $param_list = null;
                foreach ($function_description["params"] as $i => $param) {
                    if ($param_list) {
                        $param_list .= ", ";
                    }
                    $param_list .= id_quote($param['field']);
                }

                $field_list .= id_quote($function_description["schema"]).".".id_quote($function_description["func"])."($param_list)";
                $field_array[] = $function_description["func"];
                //if($field_description["type"])
                //  $field_list .= "::".id_quote($field_description["type"]); // <- �� ������ ������
                
                //$field_list .= "::text"; // <- �� ������ ������
              }

        }



        $join = "";

        foreach ($params["join"] as $k => $j) {
            

            if(isset($j["distinct"])){
                $order_distinct = self::makeOrderAndDistinctString($j["distinct"], $params);
                $join .= " left join (select distinct on (".$order_distinct['distinctfields'].") * from ". relation($j["schema"],$j["entity"]) ." t ". $order_distinct['orderfields'].")as " . id_quote($j["table_alias"]) . " on " . id_quote($j["parent_table_alias"]) . "." . id_quote($j["key"]) . " = " . id_quote($j["table_alias"]) . "." . id_quote($j["entityKey"]);
            }
            else
                $join .= " left join " . relation($j["schema"],$j["entity"]) . " as " . id_quote($j["table_alias"]) . " on " . id_quote($j["parent_table_alias"]) . "." . id_quote($j["key"]) . " = " . id_quote($j["table_alias"]) . "." . id_quote($j["entityKey"]);
        }


        $order_distinct = self::makeOrderAndDistinctString($params['order'], $params);
        $orderfields = $order_distinct['orderfields'];
        $distinctfields = $order_distinct['distinctfields'];
        
        $predicate = self::makePredicateString($params["predicate"], $replace_rules, $params["fields"], $params);
        if($distinctfields)
        $count = 'SELECT count(distinct '.$distinctfields.') FROM ' . relation($params["schemaName"],$params["entityName"]) . ' as t ' . $join;
      else
        $count = 'SELECT count(*) FROM ' . relation($params["schemaName"],$params["entityName"]) . ' as t ' . $join;

        if($distinctfields){
            $distinctfields = 'distinct on ('.$distinctfields.')';
        }
        $statement = 'SELECT '.$distinctfields .' '. $field_list . ' FROM ' . relation($params["schemaName"],$params["entityName"]) . ' as t ' . $join;

        $sql_aggregates = "";
        foreach($params["aggregate"] as $aggregateDescription) {
            if($aggregateDescription == end($params["aggregate"])) {
                $sql_aggregates = $sql_aggregates . $aggregateDescription["func"] . '(t.' . $aggregateDescription["field"] . ') as "'. $aggregateDescription["func"] . '(' . $aggregateDescription["field"] . ')"';
            }
            else {
                $sql_aggregates = $sql_aggregates . $aggregateDescription["func"] . '(t.' . $aggregateDescription["field"] . ') as "'. $aggregateDescription["func"] . '(' . $aggregateDescription["field"] . ')", ';
            }
        }
        $sql_aggregates = 'SELECT ' . $sql_aggregates . ' FROM ' . relation($params["schemaName"],$params["entityName"]) . ' as t ' . $join;

        if (isset($params["sample"])) {
            $ratio = intval($params["sample"]);
            $statement .= 'tablesample bernoulli(' . $ratio . ')';
        }

        if ($predicate != '') {
            //throw new Exception($predicate);
            $statement = $statement . ' where ' . $predicate;
            $sql_aggregates = $sql_aggregates .  ' where ' . $predicate;
            $count = $count . ' where ' . $predicate;
        }
        else
          $predicate = 'true';




        $rollupfields = '';


/*
        if(isset($params["group"])){
            foreach ($params["group"] as $i => $f) {
                    $o_t_alias = $params["fields"][$f]["table_alias"];

                    if(!$o_t_alias)
                      $o_t_alias = 't';
        
                    //if( $params["fields"][$f]["subfields"])
                    //    $o_f = id_quote($f);
                    //else
                        $o_f = id_quote($o_t_alias).'.' .id_quote($f);
                    
                    if ($rollupfields!='') {
                        $rollupfields .= ', '.$o_f;
                    } else {
                        $rollupfields .= $o_f;
                    }
    
            }
            $rollupfields = 'GROUP BY ROLLUP('.$rollupfields.')';
            
        }
		$statement = $statement . " " . $rollupfields. " " . $orderfields;
*/
       $statement = $statement . "  " . $orderfields;
        $rowNumber = 0;
        if(isset($params["currentKey"])){
            if($params["currentKey"] && ($params["limit"] != 0 and $params["limit"] != -1)){
                /*
                $pageNumberStatement = 'SELECT k.row_number FROM (select row_number() over (' .$orderfields.'), t.'.id_quote($params["primaryKey"]).
                ' from '. relation($params["schemaName"],$params["entityName"]) . ' as t ' . $join.' where ('.$predicate.')) k where k.'.
                             $params["primaryKey"].'=\''.pg_escape_string($params["currentKey"]).'\'';
                */
                $pageNumberStatement = 'SELECT CASE WHEN k.row_number = 0 THEN 0 ELSE (trunc((k.row_number-1)/'.$params["limit"].')*'.$params["limit"].') END as row_number
                FROM (select row_number() over (' .$orderfields.'), t.'.id_quote($params["primaryKey"]).
                ' from '.relation($params["schemaName"],$params["entityName"]). ' as t ' . $join.' ) k where k.'.$params["primaryKey"].'=\''.pg_escape_string($params["currentKey"]).'\'';

                $rowNumberRes = sql($pageNumberStatement);
                $params["offset"] = $rowNumberRes[0]["row_number"];

            }
        }
        if (($params["limit"] != 0 and $params["limit"] != -1) or ($params["offset"] != 0  &&  $params["offset"] >= 0)) {
            $statement = $statement . ' LIMIT ' . $params["limit"] . ' OFFSET ' . $params["offset"];
        }

        //return array("data" => sql($statement), "records" => sql($count), "sql" => $statement);

        $data_result = array("data" => sql($statement,false, false, (isset($params['format'])&&!isset($params['process']))?$params['format']:'object', $desc), 
                             "records" => sql($count, false, false, 'object', $desc." (количество)"), 
                             "offset" =>$params["offset"], 
                             "fields"=>$field_array, 
                             "sql" => $statement);


        if(isset($params['predicate']['operands'][0])){
            $fst_operand = $params['predicate']['operands'][0];
            if ($fst_operand['operand']['op'] == "FTS") {
               $ts_query = json_decode($fst_operand['operand']['value'], true);
               $ts_n = sql('select plainto_tsquery(\'' . pg_escape_string($ts_query["language"]) . 
               '\', \'' . pg_escape_string($ts_query["ft_query"]) . '\')');
               $data_result['ft_keywords'] = $ts_n[0]['plainto_tsquery'];
            }
        }

        if(sizeof($params["aggregate"])){
            $data_aggregates = sql($sql_aggregates, false, false, 'object', $desc." (агрегирование)");
            foreach($params["aggregate"] as $aggrIndex => $aggregateDescription) {
                $data_result[$aggregateDescription["func"] . '(' . $aggregateDescription["field"] . ')'][][$aggregateDescription["func"]] = $data_aggregates[0][$aggregateDescription["func"] . '(' . $aggregateDescription["field"] . ')'];
            }  

        }

        return static::postProcessing($data_result, $params);
    }

    public static function getEntitiesByKey($params, $order_by_key = true)
    {
        if (isset($params["fields"])) {
            $field_list = "";
            foreach ($params["fields"] as $k => $n) {
                if ($field_list) {
                    $field_list .= ", ";
                }
                $field_list .= "t." . id_quote($n);
            }
        } else {
            $field_list = "*";
        }

        $join = "";
        if (isset($params["join"]))
            foreach ($params["join"] as $k => $j) {
                $j_field_list = "";
                foreach ($j["fields"] as $m => $n) {
                    if ($j_field_list) {
                        $j_field_list .= "||' '|| ";
                    }
                    $j_field_list .= "COALESCE(t$k." . id_quote($n) . "::text,'')";
                }
                if ($field_list) {
                    $field_list .= ", ";
                }
                $field_list .= "row_to_json(row($j_field_list, t." . id_quote($j["key"]) . "::text,  ARRAY(select row_to_json(row($j_field_list, t$k." . id_quote($j["entityKey"]) . "::text)) from " . relation($j["schema"],$j["entity"]) . " as t$k limit 10))) as " . id_quote($j["key"]);
                $join .= " left join " . relation($j["schema"],$j["entity"]) . " as t$k on t." . id_quote($j["key"]) . " = t$k." . id_quote($j["entityKey"]);
            }

        if(is_array($params["value"]))
        {
            $value_arr = "";
            foreach ($params["value"] as $i => $v) {
                if ($value_arr) {
                    $value_arr .= ", ";
                }
                $value_arr .= "'".pg_escape_string($v)."'";;
            }

            $res = sql('SELECT ' . $field_list . ' FROM ' . relation($params["schemaName"],$params["entityName"]) . ' as t ' . $join . ' WHERE t.' . id_quote($params["key"]) . ' IN (' . $value_arr . ') '.
               ($order_by_key?(' order by t.' . id_quote($params["key"])):''));
            return $res;
        }
        return sql('SELECT ' . $field_list . ' FROM ' . relation($params["schemaName"],$params["entityName"]) . ' as t ' . $join . ' WHERE t.' . id_quote($params["key"]) . ' = \'' . pg_escape_string($params["value"]) . '\'');
    }

    /*public static function isEmailUsed($params)
    {
        return count(sql_s('SELECT * FROM users_view WHERE user_email = \'' . $params["email"] . '\'')) == 1;
    } */ // users_view

    public static function deleteEntitiesByKey($params)
    {
        return sql('DELETE FROM ' . id_quote($params["schemaName"]) . '.' .
                   id_quote($params["entityName"]) . ' WHERE ' . id_quote($params["key"]) . ' = \'' . pg_escape_string($params["value"]) . '\'', null, true);
    }

    public static function addEntities($params)
    {
        
        $desc =  isset($params['desc'])? $params['desc']:'';
        $sql = '';

        foreach ($params["fields"] as $r => $row) {
            $fields = '';
            $values = '';
            foreach ($row as $field => $value) {
                if ($value) {
                    $type_conversion = '';
                    if($params["types"][$field]) {}
                     // $type_conversion = id_quote($params["types"][$field])."('".pg_escape_string($value)."')";
                    else   
                      $type_conversion = "'" . pg_escape_string($value) . "'";

                    if ($fields) {
                        $fields .= ', ' . id_quote($field);
                    } else {
                        $fields = id_quote($field);
                    }

                    if ($values) {
                        $values .= ", ". $type_conversion;
                    } else {
                        $values = $type_conversion;
                    }


                }

            }
            
            /* if ($fields === '') { // prevent error on empty data
                $fields = array_keys($params["fields"][0])[0];
                $values = 'uuid_generate_v4()';
            } */  // пустой запрос на добавление 




                $sql .= 'INSERT INTO ' . id_quote($params["schemaName"]) . '.' .
                   id_quote($params["entityName"]) . ' (' . $fields . 
                    ') VALUES (' . $values . ') returning '.id_quote($params["key"]).';';
        }



        $ins_ret = sql($sql, null, true, 'object', $desc." (файлы)"); 
        $key = $ins_ret[0][$params["key"]];


        /*if($params["files"])
        {
            foreach($params["files"] as $i=>$f){
                foreach(json_encode($params["fields"][$f]) as $i1=>$f1){
                    $f_key = $f1["file"];
                    sql("update system.tmp_files set row_key = '$key'  where key = '$f_key'", null, true, 'object', $desc." (файлы)");
                }
            }
        }*/   // delete

        return $ins_ret;
    }

    public static function updateEntity($params)
    {
        
        if(is_array($params["value"]))
          $key_arr = $params["value"];
        else
          $key_arr = array($params["value"]);

        $sql = '';
        foreach($key_arr as $i=>$key){
            $set = null;
            foreach ($params["fields"] as $field => $values) {


                if (is_array($values))
                  $value = $values[$i];
                else
                  $value = $values;


                if (isset($value) && trim($value)!=='') {
                    $type_conversion = '';
                    if($params["types"][$field])
                      $type_conversion = '::'.$params["types"][$field];

                    if ($set) {
                        $set .= ', ' . id_quote($field) . " = '" . pg_escape_string($value) . "'".$type_conversion;
                    } else {
                        $set = id_quote($field) . " = '" . pg_escape_string($value) . "'".$type_conversion;
                    }
                } else {
                    if ($set) {
                        $set .= ', ' . id_quote($field) . " = NULL";
                    } else {
                        $set = id_quote($field) . " = NULL";
                    }
                }
            };

            /*if($params["files"])
            {
                foreach($params["files"] as $i=>$f){
                    foreach(json_decode($params["fields"][$f], true) as $i1=>$f1){
                        $f_key = $f1["file"];
                        $sql .= "update system.tmp_files set row_key = '$key'  where key = '$f_key'";
                    }
                }
            } */   // delete pia
            
            $type_conversion = '';
            if($params["types"][$params["key"]])
                $type_conversion = '::'.$params["types"][$params["key"]]; 
            
            $sql .= 'UPDATE ' . id_quote($params["schemaName"]) . '.' .
                   id_quote($params["entityName"]) . ' SET ' . $set . '  where ' . id_quote($params["key"]).$type_conversion . " = '" . pg_escape_string($key) . "'".$type_conversion.';';
     
        }

        return sql($sql, null, true);
    }
    public static function getPIDs($params){
        $r = sql('SELECT * FROM pg_stat_activity where datname = current_database()');
        $pid_map = array();
        foreach ($r as $i=>$v){
            $pid_map[$v['pid']] = 1;
        } 
        foreach ($_SESSION['pids'] as $p=>$v){
            if(!isset($pid_map[$p]))
              unset($_SESSION['pids'][$p]);
        } 
        return array('pids'=>$_SESSION['pids']);
    }

    public static function killPID($params){
        $r = sql('select pg_terminate_backend('.pg_escape_string($params['pid']).')');
        unset($_SESSION['pids'][$params['pid']]);
        return $r; 
    }

    public static function getExtensionsVersion($params){
        $r = sql("SELECT * FROM pg_available_extensions pe where pe.name in ('pg_abris')");
        return $r;
    }
    
    public static function getTableDefaultValues($params){
        return [];
    }
	
    private static function mergeMetadata($proj_arr, $prop_arr, $rel_arr, $buttons)
    {
        $metadata = array();

        foreach ($proj_arr as $i => $p) {
            $metadata[$p['projection_name']] = $p;
            $metadata[$p['projection_name']]['properties'] = array();
            $metadata[$p['projection_name']]['relations'] = array();
            $metadata[$p['projection_name']]['buttons'] = array();
        }


        foreach ($prop_arr as $i => $prop) {
            $p = $metadata[$prop['projection_name']];
            $metadata[$prop['projection_name']]['properties'][$prop['column_name']] = $prop;
        }

        foreach ($rel_arr as $i => $r) {
            if ($r['related_projection_name']) {
                $metadata[$r['projection_name']]['relations'][$r['projection_relation_name']] = $r;
            }
        }

        return $metadata;
    }
	
    public static function getAllModelMetadata()
    {
        global $metaSchema;
        $proj_arr = sql("SELECT * FROM $metaSchema.view_projection_entity");
        if (@count($proj_arr) == 0) {
            //throw new Exception("Metadata: no projections");
        }



        $prop_arr = methodsBase::getAllEntities(array("schemaName" => $metaSchema,
            "entityName" => "view_projection_property"));

        $rel_arr = methodsBase::getAllEntities(array("schemaName" => $metaSchema,
            "entityName" => "view_projection_relation"));


        $metadata = methodsBase::mergeMetadata($proj_arr, $prop_arr, $rel_arr, $buttons);
        return array('projections'=>$metadata, 'pages'=>$pages);
    }


    public static function test($params)
    {
        return $params;
    }
}
