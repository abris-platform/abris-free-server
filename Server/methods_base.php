<?php

/**
 * Abris - Web Application RAD Framework
 * @version v2.0.1
 * @license (c) TRO MOO AIO, Commercial Product
 * @date Sat Sep 17 2016 09:45:15
 */
//require_once dirname(__FILE__).'/vendor/autoload.php';
if (file_exists(dirname(__FILE__) . '/tcpdf/tcpdf.php'))
    include_once(dirname(__FILE__) . '/tcpdf/tcpdf.php');
if (file_exists(dirname(__FILE__) . '/xlsxwriter.class.php'))
    include_once(dirname(__FILE__) . '/xlsxwriter.class.php');

require_once "db.php";
require_once "sql_view_projection.php";

if (file_exists(dirname(__FILE__) . '/plugins.php'))
    require_once "plugins.php";

$data_result = array();

function relation($schema, $table) {
    global $dbUnrollViews;
    $rel = id_quote($schema) . "." . id_quote($table);
    if (in_array($rel, $dbUnrollViews ?: array())) {
        $r = sql("select pg_get_viewdef(to_regclass('$rel'));");
        return "(" . trim($r[0]["pg_get_viewdef"], ';') . ")";
    } else
        return $rel;
}

class methodsBase
{
    protected static function postProcessing(&$data_result, &$params) {
        return $data_result;
    }

    public static function setEnvKRB5currentUser() {
        putenv("KRB5CCNAME=" . $_SERVER['KRB5CCNAME']);
    }

    public static function getEnvKRB5currentUser() {
        return getenv('KRB5CCNAME');
    }

    public static function getShortEnvKRB5currentUser() {
        global $nameALD;
        methodsBase::setEnvKRB5currentUser();
        return (!methodsBase::getEnvKRB5currentUser()) ? $_SERVER['PHP_AUTH_USER'] : str_replace("@$nameALD", '', $_SERVER['PHP_AUTH_USER']);
    }

    public static function authenticate($params) {
        global $flag_astra, $_STORAGE;

        if (!$flag_astra) {
            if ($params['usename'] <> '' and $params['passwd'] <> '') {
                $_STORAGE['login'] = $params['usename'];
                $_STORAGE['password'] = $params['passwd'];

                $usenameDB = sql("SELECT '$params[usename]' as usename", false, false, 'object', '', false); //run a request to verify authentication

                $privateKey = GenerateRandomString();
                if ((!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__'))){
                    setcookie('private_key', null, -1);
                    setcookie('private_key', $privateKey);    
                }

                $_STORAGE['password'] = EncryptStr($_STORAGE['password'], $privateKey);
                return $usenameDB;
            } else {
                if ($_STORAGE['login'] <> '' and $_STORAGE['password'] <> '') {
                    global $adminSchema, $ipAddr;
                    if ($_STORAGE['enable_admin'] == 't')
                        sql("SELECT $adminSchema.update_session('$_STORAGE[login]', '$ipAddr', '$_COOKIE[PHPSESSID]');", true);
                }

                unset_auth_session();
            }
        } else {
            $slogin = methodsBase::getShortEnvKRB5currentUser();

            checkSchemaAdmin();
            $usenameDB = sql("SELECT ' $slogin ' as usename", false, false, 'object', '', false); //run a request to verify authentication
            return $usenameDB;
        }
    }

    public static function getAllEntities($params) {
        return sql('SELECT * FROM ' . relation($params["schemaName"], $params["entityName"]) . ' t');
    }

    public static function getCurrentUser() {
        global $domain, $user, $flag_astra, $_STORAGE;

        if ($flag_astra)
            return methodsBase::getShortEnvKRB5currentUser();

        if (strval($_STORAGE['login']) <> '') {
            return $_STORAGE['login'];
        } else
            if (isset($_SERVER['REMOTE_USER'])) {
                $cred = explode('\\', $_SERVER['REMOTE_USER']);
                list($domain, $user) = $cred;
                $_STORAGE['login'] = $user;
                return $user;
            } else
                return 'guest';
    }


    public static function isGuest() {
        global $flag_astra, $_STORAGE;
        if ($flag_astra)
            return $_SERVER['PHP_AUTH_USER'];

        return isset($_STORAGE['login']);
    }


    public static function getTableData($params) {

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
        if (isset($params["distinct"])) {
            if (is_array($params["distinct"])) {
            } else {
                $statement = "SELECT DISTINCT $field_list FROM " . relation($params["schemaName"], $params["entityName"]) . ' t';
                $count = "SELECT count(DISTINCT $field_list) FROM " . relation($params["schemaName"], $params["entityName"]) . ' t';
            }
        } else {
            $statement = "SELECT $field_list FROM " . relation($params["schemaName"], $params["entityName"]) . ' t';
            $count = "SELECT count(*) FROM " . relation($params["schemaName"], $params["entityName"]) . ' t';
        }

        $where = "";

        if ($params["fields"]) {
            foreach ($params["fields"] as $k => $n) {
                if (!in_array($n, $params['exclude'])) {
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
        $statement = $statement . ' ' . $orderfields;


        $statement_count = $statement;
        if ($params["limit"] != 0 or $params["offset"] != 0) {
            $statement = $statement . ' LIMIT ' . $params["limit"] . ' OFFSET ' . $params["offset"];
        }

        $data_result_statement = sql($statement);
        $count_data = count($data_result_statement);

        if (($count_data < $params["limit"]) || ($params["limit"] == 1)) {
            $number_count[0]["count"] = $count_data + $params["offset"];
        } else {
            $number_count[0]["count"] = methodsBase::sql_count_estimate($params, $statement_count, $count);
        }

        return array("data" => $data_result_statement, "records" => $number_count);
    }
    //---------------------------------------------------------------------------------------
    // If anything return to function - getTableDataPredicate 
    public static function quote($n) {
        return "'" . pg_escape_string($n) . "'";
    }

    public static function makeSimplePredicate($operand, $replace_rules, $fields, $params, &$result) {
        $func = null;
        $type_desc = '';
        $field = '';
        if (isset($operand["field"]))
            $field = $operand["field"];
        if (isset($operand["func"]))
            $func = $operand["func"];

        if (isset($fields[$field]))
            if (isset($fields[$field]['type']))
                $type_desc = '::' . $fields[$field]['type'];


        if (!isset($operand["search_in_key"]) && isset($replace_rules[$field])) {
            $field = $replace_rules[$field];
        } else {
            if (isset($operand["table_alias"])) {
                $field = id_quote($operand["table_alias"]) . "." . id_quote($field);
            } else {
                $field = "t." . id_quote($field);
            }
        }

        if (isset($operand["type"]))
            $field .= '::' . id_quote($operand["type"]);

        if(isset($operand["value"]))
            $value = $operand["value"];
        else
            $value = "";
        switch ($operand["op"]) {
            case "EQ":
                if (is_array($value)) {
                    if (count($value) > 0) {
                        $null_condition = '';
                        foreach ($value as $k => $v) {
                            if (!$v) {
                                $null_condition = $field . " is null or " . $field . "::text = ''";
                                unset($value[$k]);

                            }

                        }
                        $value_list = implode(",", array_map("methodsBase::quote", $value));
                        if (count($value) == 0)
                            return $null_condition;
                        else
                            if ($null_condition == '')
                                return $field . " IN ($value_list)";
                        return $null_condition . ' or ' . $field . " IN ($value_list)";
                    } else
                        return $field . " is null or trim(" . $field . "::text) = ''";
                }
                if (empty($value)) {
                    return $field . " is null";
                }
                return $field . " = '" . pg_escape_string($value) . "'" . $type_desc;
            case "NEQ":
                if (is_array($value)) {
                    if (count($value) > 0) {
                        $null_condition = '';
                        foreach ($value as $k => $v) {
                            if (!$v) {
                                $null_condition = $field . " is not null and trim(" . $field . "::text) <> ''";
                                unset($value[$k]);

                            }

                        }
                        $value_list = implode(",", array_map("methodsBase::quote", $value));
                        if (count($value) == 0)
                            return $null_condition;
                        else
                            if ($null_condition == '')
                                return $field . " NOT IN ($value_list)";
                        return $null_condition . ' and ' . $field . " NOT IN ($value_list)";
                    } else
                        return $field . " is not null and " . $field . "::text <> ''";
                }
                if (empty($value)) {
                    return $field . " is null";
                }
                return $field . " <> '" . pg_escape_string($value) . "'";
            case "G":
                return $field . " > '" . pg_escape_string($value) . "'";
            case "F":
                return id_quote($value) . "($field)";
            case "FC":
                return id_quote($value) . "('" . pg_escape_string($params["schemaName"] . '.' . $params["entityName"]) . "', $field)";
            case "EQF":
                return $field . " =  " . id_quote($value) . "()";
            case "FEQ":
                return id_quote($func) . "($field) =  '" . pg_escape_string($value) . "'";
            case "L":
                return $field . " < '" . pg_escape_string($value) . "'";
            case "GEQ":
                return $field . " >= '" . pg_escape_string($value) . "'";
            case "LEQ":
                return $field . " <= '" . pg_escape_string($value) . "'";
            case "C":
                if ($value) {
                    $value_parts = explode(' ', $value);
                    $where_arr = array();

                    if ($field != "t.\"\"") {
                        foreach ($value_parts as $i => $v)
                            $where_arr[] = $field . "::TEXT ilike '%" . pg_escape_string($v) . "%'::TEXT";
                        return implode(' and ', $where_arr);
                    } else {
                        $where = "";

                        foreach ($fields as $k => $field_description) {
                            if (isset($field_description["hidden"]))
                                if ($field_description["hidden"])
                                    continue;
                            if (isset($field_description["subfields"])) {
                                foreach ($field_description["subfields"] as $m => $j_field) {
                                    if ($where) {
                                        $where .= " OR ";
                                    }
                                    $where .= $field_description["subfields_table_alias"][$m] . "." . id_quote($j_field) . "::TEXT ILIKE '" . pg_escape_string('%' . $value . '%') . "'::TEXT";
                                }
                            } else {
                                if ($where) {
                                    $where .= " OR ";
                                }
                                $where_arr = array();
                                foreach ($value_parts as $i => $v)
                                    $where_arr[] = "t." . id_quote($k) . "::TEXT ILIKE '" . pg_escape_string('%' . $v . '%') . "'::TEXT";

                                $where .= implode(' and ', $where_arr);
                            }
                            if ($operand["m_order"]) {
                                if (isset($result["m_order"]))
                                    $result["m_order"] .= ", ";
                                else
                                    $result["m_order"] = "";
                                $value = $value_parts[0];
                                $result["m_order"] .= " not(t." . id_quote($k) . "::TEXT ILIKE '" . pg_escape_string($value . '%') . "'::TEXT), t." . id_quote($k) . "::TEXT";
                            }

                        }

                        return "($where)";
                    }

                } else
                    return "true";
            case "ISN":
                return $field . " IS NULL ";
            case "ISNN":
                return $field . " IS NOT NULL ";
            case "DUR":
                return $field . " <= now() and " . $field . " > now() - '" . $value . "'::interval";

        }
    }

    public static function makePredicateString($predicate_object, $replace_rules, $fields, $params, &$result) {

        $operator = '';
        $string = '';
        foreach ($predicate_object["operands"] as $op) {
            if (!$op["levelup"]) {
                $string .= $operator . '(' . self::makeSimplePredicate($op["operand"], $replace_rules, $fields, $params, $result) . ')';
            } else {
                $string .= $operator . '(' . self::makePredicateString($op["operand"], $replace_rules, $fields, $params, $result) . ')';
            }
            $operator = ($predicate_object["strict"]) ? " AND " : " OR ";
        }
        return $string;
    }

    //---------------------------------------------------------------------------------------
    public static function makeOrderAndDistinctString($order_object, $params) {
        $orderfields = '';
        $orderfields_no_aliases = '';
        $distinctfields = '';
        foreach ($order_object as $i => $o) {
            $o_t_alias = $params["fields"][$o["field"]]["table_alias"];
            if (!$o_t_alias)
                $o_t_alias = 't';

            if ($orderfields_no_aliases) {
                $orderfields_no_aliases .= ', ' . id_quote($o["field"]);
            } else {
                $orderfields_no_aliases = 'ORDER BY ' . id_quote($o["field"]);
            }

            if (isset($params["fields"][$o["field"]]["subfields"]))
                $o_f = id_quote($o["field"]);
            else
                $o_f = id_quote($o_t_alias) . '.' . id_quote($o["field"]);

            if (isset($o["func"])) {
                $o_f = id_quote($o["func"]) . '(' . $o_f . ')';
            }
            if (isset($o["type"])) {
                $o_f = $o_f . '::' . id_quote($o["type"]);
            }
            if (isset($o["distinct"])) {
                if ($distinctfields) {
                    $distinctfields .= ', ' . $o_f;
                } else {
                    $distinctfields = $o_f;
                }
            }

            if ($orderfields) {
                $orderfields .= ', ' . $o_f;
            } else {
                $orderfields = 'ORDER BY ' . $o_f;
            }

            if (isset($o["desc"])) {
                if ($o["desc"])
                {
                    $orderfields .= " DESC";
                    $orderfields_no_aliases .= " DESC";
                }
                    
            }
        }
        if ($orderfields && $params["primaryKey"]) {
            $orderfields .= ', ' . $params["primaryKey"];
        }
        return array('orderfields' => $orderfields, 'orderfields_no_aliases' => $orderfields_no_aliases, 'distinctfields' => $distinctfields);
    }

    //---------------------------------------------------------------------------------------

    public static function getTableDataPredicate($params) {
        $desc = isset($params['desc']) ? $params['desc'] : '';
        $replace_rules = array();

        if (isset($params["fields"])) {
            $field_list = "";
        } else {
            $field_list = "*";
        }


        $orderfields = '';
        $orderfields_no_aliases = '';
        $distinctfields = '';

        if (isset($params["process"])) {
            if (isset($params["process"]["aggregate"]))
                $params["aggregate"] = $params["process"]["aggregate"];
            if (isset($params["process"]["group"]))
                $params["group"] = $params["process"]["group"];
        }

        $field_array = array();


        if (isset($params["aggregate"]) && count($params["aggregate"]) && isset($params["group"])) {
            foreach ($params["group"] as $i => $field_obj) {
                $field_name = $field_obj["field"];
                if ($field_list) {
                    $field_list .= ", ";
                }
                $field_description = $params["fields"][$field_name];
                $field_list .= id_quote($field_description["table_alias"]) . "." . id_quote($field_name);
                $field_array[] = $field_name;
            }
            foreach ($params["aggregate"] as $i => $field_obj) {
                if ($field_list) {
                    $field_list .= ", ";
                }
                $field_name = $field_obj["field"];
                $field_func = $field_obj["func"];
                $field_description = $params["fields"][$field_name];
                $field_list .= $field_func . "(" . id_quote($field_description["table_alias"]) . "." . id_quote($field_name) . ") as $field_name";
                $field_array[] = $field_name;
            }
        } else {

            foreach ($params["fields"] as $field_name => $field_description) {
                if ($field_list) {
                    $field_list .= ", ";
                }


                if (isset($field_description["subfields"])) {
                    $j_field_list_array = array();
    
    
                    foreach ($field_description["subfields"] as $m => $j_field) {
                        $j_field_list_array[] = "COALESCE(" . $field_description["subfields_table_alias"][$m] . "." . id_quote($j_field) . "::text,'')";
                    }

                    if(isset($field_description["format"]))
                        $j_field_list = 'format(\''.pg_escape_string($field_description["format"]).'\', '.implode(", ", $j_field_list_array).')';
                    else
                        $j_field_list = implode("||' '|| ", $j_field_list_array);

                    if (isset($field_description["virtual"]))
                        $field_list .= "(row_to_json(row($j_field_list, " . $field_description["subfields_navigate_alias"] . "." . id_quote($field_description["subfields_key"]) . "::text))::text) collate \"C\" as " . id_quote($field_name);
                    else
                        $field_list .= "(row_to_json(row($j_field_list, " . id_quote($field_description["table_alias"]) . "." . id_quote($field_name) . "::text))::text) collate \"C\" as " . id_quote($field_name);
                    $field_array[] = $field_name;

                    $replace_rules[$field_name] = $j_field_list;
                } else {

                    if (isset($field_description["only_filled"]))
                        $field_list .= id_quote($field_description["table_alias"]) . "." . id_quote($field_name) . " is not null as " . id_quote($field_name);
                    else
                        $field_list .= id_quote($field_description["table_alias"]) . "." . id_quote($field_name);
                    if (isset($field_description['type']))
                            $field_list .= '::' . id_quote($field_description['type']);
                    $field_array[] = $field_name;
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

                $field_list .= id_quote($function_description["schema"]) . "." . id_quote($function_description["func"]) . "($param_list)";
                $field_array[] = $function_description["func"];
            }
        }


        $join = "";

        foreach ($params["join"] as $k => $j) {


            if (isset($j["distinct"])) {
                $order_distinct = self::makeOrderAndDistinctString($j["distinct"], $params);
                $join .= " left join (select distinct on (" . $order_distinct['distinctfields'] . ") * from " . relation($j["schema"], $j["entity"]) . " t " . $order_distinct['orderfields'] . ")as " . id_quote($j["table_alias"]) . " on " . id_quote($j["parent_table_alias"]) . "." . id_quote($j["key"]) . " = " . id_quote($j["table_alias"]) . "." . id_quote($j["entityKey"]);
            } else
                $join .= " left join " . relation($j["schema"], $j["entity"]) . " as " . id_quote($j["table_alias"]) . " on " . id_quote($j["parent_table_alias"]) . "." . id_quote($j["key"]) . " = " . id_quote($j["table_alias"]) . "." . id_quote($j["entityKey"]);
        }


        $order_distinct = self::makeOrderAndDistinctString($params['order'], $params);
        $orderfields = $order_distinct['orderfields'];
        $orderfields_no_aliases = $order_distinct['orderfields_no_aliases'];
        $distinctfields = $order_distinct['distinctfields'];

        $pred_res = array();
        $predicate = self::makePredicateString($params["predicate"], $replace_rules, $params["fields"], $params, $pred_res);

        if ($distinctfields)
            $count = 'SELECT count(distinct ' . $distinctfields . ') FROM (SELECT ' . $field_list . ' FROM ' . relation($params["schemaName"], $params["entityName"]) . ' as t ' . $join;
        else
            $count = 'SELECT count(*) FROM ' . relation($params["schemaName"], $params["entityName"]) . ' as t ' . $join;

        if ($distinctfields) {
            $distinctfields = 'distinct on (' . $distinctfields . ')';
        }
        $statement = 'SELECT ' . $distinctfields . ' ' . $field_list . ' FROM ' . relation($params["schemaName"], $params["entityName"]) . ' as t ' . $join;

        $sql_aggregates = "";
        foreach ($params["aggregate"] as $aggregateDescription) {
            if ($aggregateDescription == end($params["aggregate"])) {
                $sql_aggregates = $sql_aggregates . $aggregateDescription["func"] . '(t.' . $aggregateDescription["field"] . ') as "' . $aggregateDescription["func"] . '(' . $aggregateDescription["field"] . ')"';
            } else {
                $sql_aggregates = $sql_aggregates . $aggregateDescription["func"] . '(t.' . $aggregateDescription["field"] . ') as "' . $aggregateDescription["func"] . '(' . $aggregateDescription["field"] . ')", ';
            }
        }
        $sql_aggregates = 'SELECT ' . $sql_aggregates . ' FROM ' . relation($params["schemaName"], $params["entityName"]) . ' as t ' . $join;

        if (isset($params["sample"])) {
            $ratio = intval($params["sample"]);
            $statement .= 'tablesample bernoulli(' . $ratio . ')';
        }

        if ($predicate != '') {
            $statement = $statement . ' where ' . $predicate;
            $sql_aggregates = $sql_aggregates . ' where ' . $predicate;
            $count = $count . ' where ' . $predicate;
        } else
            $predicate = 'true';

        if ($distinctfields)
            $count .= ') t';

        $rollupfields = '';
        if (isset($pred_res["m_order"])) {
            if ($orderfields)
                $orderfields .= ', ' . $pred_res["m_order"];
            else
                $orderfields = 'ORDER BY ' . $pred_res["m_order"];
        }
        $statement = $statement . "  " . $orderfields;
        $rowNumber = 0;
        if (isset($params["currentKey"])) {
            if ($params["currentKey"] && ($params["limit"] != 0 and $params["limit"] != -1)) {
                $equation = '(trunc((k.row_number-1)/' . $params["limit"] . ')*' . $params["limit"] . ')';
                if (isset($params["middleRow"])) {
                    if ($params["middleRow"]) $equation = 'trunc(k.row_number-(' . $params["limit"] . '/2)-1)';
                }
                $pageNumberStatement = 'SELECT CASE WHEN k.row_number = 0 THEN 0 ELSE ' . $equation . ' END as row_number
                    FROM (select row_number() over (' . $orderfields_no_aliases . '), t.' . id_quote($params["primaryKey"]) .
                    '  from (' . $statement . ') t ) k where k.' . $params["primaryKey"] . '=\'' . pg_escape_string($params["currentKey"]) . '\'';

                $rowNumberRes = sql($pageNumberStatement);
                $params["offset"] = 0;
                if(isset($rowNumberRes[0]["row_number"]))
                $params["offset"] = $rowNumberRes[0]["row_number"];

            }
        }


        $statement_count = $statement;
        if (($params["limit"] != 0 and $params["limit"] != -1) or ($params["offset"] != 0 && $params["offset"] >= 0)) {
            $statement = $statement . ' LIMIT ' . $params["limit"] . ' OFFSET ' . $params["offset"];
        }

        global $data_result;
        $data_result = array(
            "offset" => $params["offset"],
            "fields" => $field_array,
            "sql" => $statement
        );

        $data_result_statement = sql($statement, false, false, (isset($params['format']) && !isset($params['process'])) ? $params['format'] : 'object', $desc);
        $count_data = count($data_result_statement);

        if (($count_data < $params["limit"]) || ($params["limit"] == 1)) {
            $number_count[0]["count"] = $count_data + $params["offset"];
        } else {
            $number_count[0]["count"] = methodsBase::sql_count_estimate($params, $statement_count, $count);
        }

        $data_result["data"] = $data_result_statement;
        $data_result["records"] = $number_count;

        if (isset($params['predicate']['operands'][0])) {
            $fst_operand = $params['predicate']['operands'][0];
            if ($fst_operand['operand']['op'] == "FTS") {
                $ts_query = json_decode($fst_operand['operand']['value'], true);
                $ts_n = sql('select plainto_tsquery(\'' . pg_escape_string($ts_query["language"]) .
                    '\', \'' . pg_escape_string($ts_query["ft_query"]) . '\')');
                $data_result['ft_keywords'] = $ts_n[0]['plainto_tsquery'];
            }
        }

        if (sizeof($params["aggregate"])) {
            $data_aggregates = sql($sql_aggregates, false, false, 'object', $desc . " (aggregate)");
            foreach ($params["aggregate"] as $aggrIndex => $aggregateDescription) {
                $data_result[$aggregateDescription["func"] . '(' . $aggregateDescription["field"] . ')'][][$aggregateDescription["func"]] = $data_aggregates[0][$aggregateDescription["func"] . '(' . $aggregateDescription["field"] . ')'];
            }
        }

        return static::postProcessing($data_result, $params);
    }

    public static function getEntitiesByKey($params, $order_by_key = true) {
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
                $field_list .= "row_to_json(row($j_field_list, t." . id_quote($j["key"]) . "::text,  ARRAY(select row_to_json(row($j_field_list, t$k." . id_quote($j["entityKey"]) . "::text)) from " . relation($j["schema"], $j["entity"]) . " as t$k limit 10))) as " . id_quote($j["key"]);
                $join .= " left join " . relation($j["schema"], $j["entity"]) . " as t$k on t." . id_quote($j["key"]) . " = t$k." . id_quote($j["entityKey"]);
            }

        if (is_array($params["value"])) {
            $value_arr = "";
            foreach ($params["value"] as $i => $v) {
                if ($value_arr) {
                    $value_arr .= ", ";
                }
                $value_arr .= "'" . pg_escape_string($v) . "'";;
            }

            $res = sql('SELECT ' . $field_list . ' FROM ' . relation($params["schemaName"], $params["entityName"]) . ' as t ' . $join . ' WHERE t.' . id_quote($params["key"]) . ' IN (' . $value_arr . ') ' .
                ($order_by_key ? (' order by t.' . id_quote($params["key"])) : ''));
            return $res;
        }
        return sql('SELECT ' . $field_list . ' FROM ' . relation($params["schemaName"], $params["entityName"]) . ' as t ' . $join . ' WHERE t.' . id_quote($params["key"]) . ' = \'' . pg_escape_string($params["value"]) . '\'');
    }

    public static function deleteEntitiesByKey($params) {
        $sql = '';
        $value_arr = array();
        $key_arr = array();
        $request_number = array();

        if (is_array($params["key"])) {
            $key_arr = $params["key"];
            $value_arr = $params["value"];
            if (is_array($params["value"][0])) {
                $request_number = $params["value"][0];
            } else {
                $request_number = array($params["value"][0]);
            }

        } else {
            $key_arr = array($params["key"]);
            if (is_array($params["value"]))
                $value_arr[0] = $params["value"];
            else
                $value_arr[0] = array($params["value"]);
            $request_number = $value_arr[0];

        }

        foreach ($request_number as $i => $request) {
            $sql_where = '';
            $type_conversion = '';

            foreach ($key_arr as $j => $key) {
                if (isset($params["types"]))
                    if(isset($params["types"][$key]))
                        if ($params["types"][$key]) $type_conversion = '::' . $params["types"][$key];
                $sql_where .= id_quote($key). " = '" . pg_escape_string($value_arr[$j][$i]) . "'" . $type_conversion;
                if ($key != end($key_arr))
                    $sql_where .= " AND ";

            }

            $sql .= 'DELETE FROM ' . id_quote($params["schemaName"]) . '.' . id_quote($params["entityName"]) . ' WHERE ' . $sql_where . ';';


        }

        sql($sql, null, true, 'object');
        $return_data["sql"] = $sql;
        return $return_data;
    }

    public static function addEntities($params) {

        $desc = isset($params['desc']) ? $params['desc'] : '';
        $sql = '';

        foreach ($params["fields"] as $r => $row) {
            $fields = '';
            $values = '';
            foreach ($row as $field => $value) {
                if ($value) {
                    $type_conversion = '';
                    $type_conversion = "'" . pg_escape_string($value) . "'";
                    if (isset($params["types"])) {
                        if(isset($params["types"][$field]))
                            if ($params["types"][$field]) {
                                $type_conversion .= '::' .$params["types"][$field];
                            }
                    }

                    if ($fields) {
                        $fields .= ', ' . id_quote($field);
                    } else {
                        $fields = id_quote($field);
                    }

                    if ($values) {
                        $values .= ", " . $type_conversion;
                    } else {
                        $values = $type_conversion;
                    }
                }
            }

            $sql .= 'INSERT INTO ' . id_quote($params["schemaName"]) . '.' .
                id_quote($params["entityName"]) . ' (' . $fields .
                ') VALUES (' . $values . ') returning ' . id_quote($params["key"]) . ';';
        }

        $ins_ret = sql($sql, null, true, 'object', $desc . " (files)");
        $key = $ins_ret[0][$params["key"]];

        return $ins_ret;
    }

    public static function updateEntity($params) {
        $sql = '';

        $sql = '';
        $value_arr = array();
        $key_arr = array();
        $request_number = array();

        if (is_array($params["key"])) {
            $key_arr = $params["key"];
            $value_arr = $params["value"];
            if (is_array($params["value"][0])) {
                $request_number = $params["value"][0];
            } else {
                $request_number = array($params["value"][0]);
            }

        } else {
            $key_arr = array($params["key"]);
            if (is_array($params["value"]))
                $value_arr[0] = $params["value"];
            else
                $value_arr[0] = array($params["value"]);
            $request_number = $value_arr[0];

        }

        foreach ($request_number as $i => $request) {
            $set = '';
            $sql_where = '';
            $type_conversion = '';

            foreach ($params["fields"] as $field => $values) {

                if (is_array($values))
                    $value = $values[$i];
                else
                    $value = $values;

                if (isset($value) && trim($value) !== '') {
                    $type_conversion = '';
                    if(isset($params["types"]))
                        if ($params["types"][$field])
                            $type_conversion = '::' . $params["types"][$field];

                    if ($set) {
                        $set .= ', ' . id_quote($field) . " = '" . pg_escape_string($value) . "'" . $type_conversion;
                    } else {
                        $set = id_quote($field) . " = '" . pg_escape_string($value) . "'" . $type_conversion;
                    }
                } else {
                    if ($set) {
                        $set .= ', ' . id_quote($field) . " = NULL";
                    } else {
                        $set = id_quote($field) . " = NULL";
                    }
                }
            };

            foreach ($key_arr as $j => $key) {
                if (isset($params["types"]))
                    if ($params["types"][$key]) $type_conversion = '::' . $params["types"][$key];
                $sql_where .= id_quote($key) . $type_conversion . " = '" . pg_escape_string($value_arr[$j][$i]) . "'" . $type_conversion;
                if ($key != end($key_arr))
                    $sql_where .= " AND ";

            }

            $sql .= 'UPDATE ' . id_quote($params["schemaName"]) . '.' . id_quote($params["entityName"]) . ' SET ' . $set . '  WHERE ' . $sql_where . ';';


        }

        sql($sql, null, true, 'object');
        $return_data["sql"] = $sql;
        return $return_data;
    }

    public static function getPIDs($params) {
        global $_STORAGE;
        $r = sql('SELECT * FROM pg_stat_activity where datname = current_database()');
        $pid_map = array();
        foreach ($r as $i => $v) {
            $pid_map[$v['pid']] = 1;
        }
        if (isset($_STORAGE['pids']))
            foreach ($_STORAGE['pids'] as $p => $v) {
                if (!isset($pid_map[$p]))
                    unset($_STORAGE['pids'][$p]);
            }
        return array('pids' => isset($_STORAGE['pids']) ? $_STORAGE['pids'] : array());
    }

    public static function killPID($params) {
        global $_STORAGE;
        $r = sql('select pg_terminate_backend(' . pg_escape_string($params['pid']) . ')');
        unset($_STORAGE['pids'][$params['pid']]);
        return $r;
    }

    public static function getExtensionsVersion($params) {
        $r = sql("SELECT * FROM pg_available_extensions pe where pe.name in ('pg_abris')");
        return $r;
    }

    public static function getTableDefaultValues($params) {
        return [];
    }

    private static function mergeMetadata($proj_arr, $prop_arr, $rel_arr, $buttons) {
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

    public static function getAllModelMetadata() {
        global $metaSchema;
        global $sql_view_projection;
        $buttons = "";
        $pages = "";

        $proj_arr = sql("SELECT * FROM $metaSchema.view_projection_entity");
        if (@count($proj_arr) == 0) {
            //throw new Exception("Metadata: no projections");
        }

        $prop_arr = methodsBase::getAllEntities(array(
            "schemaName" => $metaSchema,
            "entityName" => "view_projection_property"
        ));

        $rel_arr = methodsBase::getAllEntities(array(
            "schemaName" => $metaSchema,
            "entityName" => "view_projection_relation"
        ));


        $metadata = methodsBase::mergeMetadata($proj_arr, $prop_arr, $rel_arr, $buttons);

        $options = sql("SELECT * FROM $metaSchema.options");

        return array('projections' => $metadata, 'pages' => $pages, 'options' => $options);
    }


    public static function test($params) {
        return $params;
    }

    private static function sql_count_estimate($params, $statement, $count) {
        $desc = isset($params['desc']) ? $params['desc'] : '';
        $count_explain = 'explain (format json) ' . $statement;
        $json_explain = sql($count_explain, false, false, 'object', $desc . " (explain)");
        $obj_json = json_decode($json_explain[0]["QUERY PLAN"]);
        $plan_rows = $obj_json[0]->{"Plan"}->{"Plan Rows"};
        $total_cost = $obj_json[0]->{"Plan"}->{"Total Cost"};


        $threshold_plan_rows = 10000;

        if (isset($params["max_cost"])){
            if ($total_cost > $params["max_cost"]) {
                return $plan_rows;
            }
        }

        $arr_count = sql($count, false, false, 'object', $desc . " (count)");
        $plan_rows = $arr_count[0]["count"];
        return $plan_rows;
    }


    public static function getUserDescription() {
        $res = sql('SELECT rolname AS user,  description AS comment
        FROM pg_roles r
        JOIN pg_shdescription c ON c.objoid = r.oid 
        WHERE r.rolname = \'' . methodsBase::getCurrentUser() . '\'');
        return $res[0];
    }
}