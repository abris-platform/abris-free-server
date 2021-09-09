<?php

/**
 * Abris - Web Application RAD Framework
 * @version v2.0.1
 * @license (c) TRO MOO AIO, Commercial Product
 * @date Sat Sep 17 2016 09:45:15
 */

$data_result = array();


class methodsBase
{
    protected static function preProcessing(&$params, $method, &$replaceDataWithSQL) {
    }
    
    protected static function postProcessing(&$data_result, &$params) {
        return $data_result;
    }

    private static function sql_count_estimate($params, $statement, $count) {
        global $_STORAGE;
        $desc = $params['desc'] ?? '';

        $options = static::GetDefaultOptions();
        $options->SetQueryDescription("$desc (explain)");
        $explain = $_STORAGE['Controller']->SqlCountEstimate($statement, $options);

        $plan_rows = $explain['plan_rows'];
        $total_cost = $explain['total_cost'];

        $threshold_plan_rows = 10000;

        if (isset($params["max_cost"])) {
            if ($total_cost > $params["max_cost"]) {
                return $plan_rows;
            }
        }

        $options = static::GetDefaultOptions();
        $options->SetQueryDescription("$desc (count)");
        $arr_count = $_STORAGE['Controller']->sql($count, $options);
        $plan_rows = $arr_count[0]["count"];
        return $plan_rows;
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
            //$p = $metadata[$prop['projection_name']];
            $metadata[$prop['projection_name']]['properties'][$prop['column_name']] = $prop;
        }

        foreach ($rel_arr as $i => $r) {
            if ($r['related_projection_name']) {
                $metadata[$r['projection_name']]['relations'][$r['projection_relation_name']] = $r;
            }
        }

        return $metadata;
    }

    protected static function queryModifyEntities($query, $options = null, $query_description = '') {
        global $_STORAGE;
        if (is_null($options))
            $options = static::GetDefaultOptions();

        $options->SetPreprocessData(null);
        $options->SetQueryDescription($query_description);

        return $_STORAGE['Controller']->sql($query, $options);
    }

    protected static function GetDefaultOptions() {
        global $_STORAGE;
        return $_STORAGE['Controller']->GetDefaultOptions();
    }

    protected static function SetCookie($name, $value = '', $expires = 0, $path = '', $domain = '', $secure = false) {
        global $_STORAGE;
        if (php_sapi_name() == 'cli') {
            $_STORAGE[$name] = $value;
            return;
        }

        if (is_array($expires))
            setcookie($name, $value, $expires);
        else
            setcookie($name, $value, $expires, $path, $domain, $secure);
    }

    public static function authenticate($params) {
        global $_STORAGE;

        if ($params['usename'] <> '' and $params['passwd'] <> '') {
            $_STORAGE['login'] = $params['usename'];
            $_STORAGE['password'] = $params['passwd'];

            $options = static::GetDefaultOptions();
            $options->SetEncryptPassword(false);
            $usenameDB = $_STORAGE['Controller']->sql("SELECT '$params[usename]' as usename", $options); //run a request to verify authentication

            $privateKey = GenerateRandomString();
            if ((!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__'))) {
                $isHTTPS = isset($_SERVER['HTTPS']);
                $cookieNameAuth = 'private_key';
                static::SetCookie($cookieNameAuth, null, -1);

                if (PHP_VERSION_ID < 70300) {
                    $path = $isHTTPS ? '/; SameSite=None' : '';
                    static::SetCookie($cookieNameAuth, $privateKey, 0, $path, '', $isHTTPS);
                } else {
                    $options = array();
                    if ($isHTTPS) {
                        $options['SameSite'] = 'None';
                        $options['Secure'] = true;
                    }

                    static::SetCookie($cookieNameAuth, $privateKey, $options);
                }
            }

            $_STORAGE['password'] = EncryptStr($_STORAGE['password'], $privateKey);
            return $usenameDB;
        }

        unset_auth_session();
        return null;
    }

    public static function getAnotherUsername() {
        global $_STORAGE;
        return $_STORAGE['login'];
    }

    public static function getCurrentUser() {
        global $domain, $user, $_STORAGE;

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

    public static function getUserDescription() {
        global $_STORAGE;
        $res = $_STORAGE['Controller']->GetUserDescription(
            methodsBase::getCurrentUser()
        );
        return $res[0];
    }

    public static function isGuest() {
        global $_STORAGE;

        return isset($_STORAGE['login']);
    }

    public static function getAllEntities($params) {
        global $_STORAGE;
        return $_STORAGE['Controller']->sql('SELECT * FROM ' . $_STORAGE['Controller']->relation($params['schemaName'], $params['entityName']) . ' t');
    }

    public static function getTableData($params) {
        global $_STORAGE;
        if ($params["fields"]) {
            $field_list = "";
            foreach ($params["fields"] as $field_index => $field_name) {
                if ($field_list) {
                    $field_list .= ", ";
                }

                $field_list .= $_STORAGE['Controller']->IdQuote($field_name);
            }
        } else {
            $field_list = "*";
        }


        $distinctfields = '';
        $orderfields = '';
        if (isset($params["distinct"])) {
            if (is_array($params["distinct"])) {
            } else {
                $statement = "SELECT DISTINCT $field_list FROM " . $_STORAGE['Controller']->relation($params["schemaName"], $params["entityName"]) . ' t';
                $count = "SELECT count(DISTINCT $field_list) FROM " . $_STORAGE['Controller']->relation($params["schemaName"], $params["entityName"]) . ' t';
            }
        } else {
            $statement = "SELECT $field_list FROM " . $_STORAGE['Controller']->relation($params["schemaName"], $params["entityName"]) . ' t';
            $count = "SELECT count(*) as count FROM " . $_STORAGE['Controller']->relation($params["schemaName"], $params["entityName"]) . ' t';
        }

        $where = "";

        if ($params["fields"]) {
            foreach ($params["fields"] as $k => $n) {
                if (!in_array($n, $params['exclude'])) {
                    if ($where) {
                        $where .= " OR ";
                    }
                    $where .= $_STORAGE['Controller']->typeField($_STORAGE['Controller']->IdQuote($n), 'text')
                        .' ' . $_STORAGE['Controller']->Like() .' '
                        . $_STORAGE['Controller']->type(
                            $_STORAGE['Controller']->EscapeString('%' . $params['predicate'] . '%'),
                            'text'
                        );
                }
            }
        }

        if ($params["key"] && $params["value"]) {
            if ($where) {
                $where = "(" . $where . ") AND ";
            }
            $where .= $_STORAGE['Controller']->IdQuote($params["key"]) . " = '" . $_STORAGE['Controller']->EscapeString($params["value"]) . "'";
        }

        if ($where) {
            $count = $count . ' WHERE ' . $where;
            $statement = $statement . ' WHERE ' . $where;
        }
        $statement = $statement . ' ' . $orderfields;


        $statement_count = $statement;
        if ($params["limit"] != 0 or $params["offset"] != 0) {
            $statement = $statement . ' LIMIT ' . $params["limit"] . ' OFFSET ' . $params["offset"];
        }

        $data_result_statement = $_STORAGE['Controller']->sql($statement);
        $count_data = count($data_result_statement);

        if (($count_data < $params["limit"]) || ($params["limit"] == 1)) {
            $number_count[0]["count"] = $count_data + $params["offset"];
        } else {
            $number_count[0]["count"] = methodsBase::sql_count_estimate($params, $statement_count, $count);
        }

        return array("data" => $data_result_statement, "records" => $number_count);
    }

    // If anything return to function - getTableDataPredicate 
    public static function quote($n) {
        global $_STORAGE;
        return "'" . $_STORAGE['Controller']->EscapeString($n) . "'";
    }

    public static function makeSimplePredicate($operand, $replace_rules, $fields, $params, &$result) {
        global $_STORAGE;
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



        $search_in_key = false;
        if(isset($operand["search_in_key"]))
            $search_in_key = $operand["search_in_key"];

        if (!$search_in_key && isset($replace_rules[$field])) {
            $field = $replace_rules[$field];
        } else {
            if (isset($operand["table_alias"])) {
                $field = $_STORAGE['Controller']->IdQuote($operand["table_alias"]) . "." . $_STORAGE['Controller']->IdQuote($field);
            } else {
                $field = "t." . $_STORAGE['Controller']->IdQuote($field);
            }
        }

        if (isset($operand["type"]))
            $field .= '::' . $_STORAGE['Controller']->IdQuote($operand["type"]);

        if (isset($operand["value"]))
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
                                $null_condition = $field . ' IS NULL OR ' . $_STORAGE['Controller']->typeField($field, 'text') ." = ''";
                                unset($value[$k]);
                            }

                        }
                        $value_list = implode(",", array_map("methodsBase::quote", $value));
                        if (count($value) == 0)
                            return $null_condition;
                        else
                            if ($null_condition == '')
                                return $field . " IN ($value_list)";
                        return $null_condition . ' OR ' . $field . " IN ($value_list)";
                    } else
                        return $field . ' IS NULL OR trim(' . $_STORAGE['Controller']->typeField($field, 'text') . ") = ''";
                }
                if (empty($value)) {
                    return $field . ' is null';
                }
                return $field . ' = ' . $_STORAGE['Controller']->type($_STORAGE['Controller']->EscapeString($value), $type_desc);
            case "NEQ":
                if (is_array($value)) {
                    if (count($value) > 0) {
                        $null_condition = '';
                        foreach ($value as $k => $v) {
                            if (!$v) {
                                $null_condition = $field . ' IS NOT NULL AND trim(' . $_STORAGE['Controller']->typeField($field, 'text') . ") <> ''";
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
                        return $field . ' IS NOT NULL AND ' . $_STORAGE['Controller']->typeField($field, 'text') . " <> ''";
                }
                if (empty($value)) {
                    return $field . " is null";
                }
                return $field . " <> '" . $_STORAGE['Controller']->EscapeString($value) . "'";
            case "G":
                return $field . " > '" . $_STORAGE['Controller']->EscapeString($value) . "'";
            case "F":
                return $_STORAGE['Controller']->IdQuote($value) . "($field)";
            case "FC":
                return $_STORAGE['Controller']->IdQuote($value) . "('" . $_STORAGE['Controller']->relation($params['schemaName'], $params['entityName']) . "', $field)";
            case "EQF":
                return $field . " =  " . $_STORAGE['Controller']->IdQuote($value) . "()";
            case "FEQ":
                return $_STORAGE['Controller']->IdQuote($func) . "($field) =  '" . $_STORAGE['Controller']->EscapeString($value) . "'";
            case "L":
                return $field . " < '" . $_STORAGE['Controller']->EscapeString($value) . "'";
            case "GEQ":
                return $field . " >= '" . $_STORAGE['Controller']->EscapeString($value) . "'";
            case "LEQ":
                return $field . " <= '" . $_STORAGE['Controller']->EscapeString($value) . "'";
            case "C":
                if ($value) {
                    $value_parts = array();
                    if(isset($operand["m_order"])){
                        if($operand["m_order"])
                            $value_parts = explode(' ', $value);
                    }
                    else
                            $value_parts = array(0 => $value);
                    $where_arr = array();

                    if ($field != "t.\"\"") {
                        foreach ($value_parts as $i => $v) {
                            $where_arr[] = $_STORAGE['Controller']->typeField($field, 'text')
                                . ' ' . $_STORAGE['Controller']->Like() . ' '
                                . $_STORAGE['Controller']->type(
                                    '%' . $_STORAGE['Controller']->EscapeString($v) . '%',
                                    'text'
                                );
                            ;
                        }
                        return implode(' and ', $where_arr);
                    } else {
                        $where = '';

                        foreach ($fields as $k => $field_description) {
                            if (isset($field_description["hidden"]))
                                if ($field_description["hidden"])
                                    continue;
                            if (isset($field_description["subfields"])) {
                                foreach ($field_description["subfields"] as $m => $j_field) {
                                    if ($where) {
                                        $where .= " OR ";
                                    }
                                    $where .= $field_description['subfields_table_alias'][$m] . '.'
                                        .$_STORAGE['Controller']->typeField($_STORAGE['Controller']->IdQuote($j_field), 'text')
                                        . ' ' .$_STORAGE['Controller']->Like() .' '
                                        .$_STORAGE['Controller']->type(
                                            $_STORAGE['Controller']->EscapeString('%' . $value . '%'),
                                        'text'
                                        );
                                }
                            } else {
                                if ($where) {
                                    $where .= ' OR ';
                                }
                                $where_arr = array();
                                foreach ($value_parts as $i => $v)
                                    $where_arr[] = $_STORAGE['Controller']->typeField(
                                            't.' . $_STORAGE['Controller']->IdQuote($k),
                                            'text'
                                        )
                                        . ' ' . $_STORAGE['Controller']->Like() .' '
                                        .$_STORAGE['Controller']->type(
                                            $_STORAGE['Controller']->EscapeString('%' . $v . '%'),
                                            'text'
                                        );

                                $where .= implode(' and ', $where_arr);
                            }
                            if (isset($operand['m_order'])) {
                                if (isset($result['m_order']))
                                    $result['m_order'] .= ', ';
                                else
                                    $result['m_order'] = '';
                                $value = $value_parts[0];
                                $result['m_order'] .= ' NOT('
                                    .$_STORAGE['Controller']->typeField('t.' .$_STORAGE['Controller']->IdQuote($k), 'text')
                                    .' ' .$_STORAGE['Controller']->Like() .' '
                                    .$_STORAGE['Controller']->type(
                                        $_STORAGE['Controller']->EscapeString($value . '%'),'text')
                                    .'), t.'
                                    .$_STORAGE['Controller']->typeField($_STORAGE['Controller']->IdQuote($k), 'text');
                            }
                        }

                        return "($where)";
                    }

                } else
                    return 'true';
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

    public static function makeOrderAndDistinctString($order_object, $params) {
        global $_STORAGE;
        $orderfields = '';
        $orderfields_no_aliases = '';
        $distinctfields = '';
        foreach ($order_object as $i => $o) {
            $o_t_alias = $params["fields"][$o["field"]]["table_alias"];
            if (!$o_t_alias)
                $o_t_alias = 't';

            if ($orderfields_no_aliases) {
                $orderfields_no_aliases .= ', ' . $_STORAGE['Controller']->IdQuote($o["field"]);
            } else {
                $orderfields_no_aliases = 'ORDER BY ' . $_STORAGE['Controller']->IdQuote($o["field"]);
            }

            if (isset($params["fields"][$o["field"]]["subfields"]))
                $o_f = $_STORAGE['Controller']->IdQuote($o["field"]);
            else
                $o_f = $_STORAGE['Controller']->IdQuote($o_t_alias) . '.' . $_STORAGE['Controller']->IdQuote($o["field"]);

            if (isset($o["func"])) {
                $o_f = $_STORAGE['Controller']->IdQuote($o["func"]) . '(' . $o_f . ')';
            }
            if (isset($o["type"])) {
                $o_f = $o_f . '::' . $_STORAGE['Controller']->IdQuote($o["type"]);
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
                if ($o["desc"]) {
                    $orderfields .= " DESC";
                    $orderfields_no_aliases .= " DESC";
                }
            }
        }
        if ($params["primaryKey"]) {
            // array('val1', 'val2') => '\"val1\", \"val2\"',
            // 'val1' => 'val1'
            $orders_by_primary = is_array($params['primaryKey']) ?
                implode(
                    ', ',
                    array_map(
                        function ($value) use($_STORAGE) {
                            return $_STORAGE['Controller']->IdQuote($value);
                        },
                        $params['primaryKey']
                    )
                ) : $params['primaryKey'];

            if ($orderfields)
                $orderfields .= ', ' . $orders_by_primary;
            else
                $orderfields = 'ORDER BY ' . $orders_by_primary;

            if ($orderfields_no_aliases)
                $orderfields_no_aliases .= ', ' . $orders_by_primary;
            else
                $orderfields_no_aliases = 'ORDER BY ' . $orders_by_primary;
        }
        return array('orderfields' => $orderfields, 'orderfields_no_aliases' => $orderfields_no_aliases, 'distinctfields' => $distinctfields);
    }

    public static function getTableDataPredicate($params) {
        global $_STORAGE;
        $desc = $params['desc'] ?? '';
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
                $field_list .= $_STORAGE['Controller']->IdQuote($field_description["table_alias"]) . "." . $_STORAGE['Controller']->IdQuote($field_name);
                $field_array[] = $field_name;
            }
            foreach ($params["aggregate"] as $i => $field_obj) {
                if ($field_list) {
                    $field_list .= ", ";
                }
                $field_name = $field_obj["field"];
                $field_func = $field_obj["func"];
                $field_description = $params["fields"][$field_name];
                $field_list .= $field_func . "(" . $_STORAGE['Controller']->IdQuote($field_description["table_alias"]) . "." . $_STORAGE['Controller']->IdQuote($field_name) . ") as $field_name";
                $field_array[] = $field_name;
            }
        } else {

            foreach ($params["fields"] as $field_name => $field_description) {
                if ($field_list) {
                    $field_list .= ", ";
                }

                if (isset($field_description["subfields"])) {
                    $j_field_list_array = array();

                    foreach ($field_description['subfields'] as $m => $j_field) {
                        $j_field_list_array[] = 'COALESCE('
                            . $_STORAGE['Controller']->IdQuote($field_description['subfields_table_alias'][$m])
                            . '.'
                            . $_STORAGE['Controller']->typeField($j_field, 'text')
                            . ", '')";
                    }

                    if (isset($field_description["format"]))
                        $j_field_list = $_STORAGE['Controller']->FormatColumns(
                            $j_field_list_array,
                            $_STORAGE['Controller']->EscapeString($field_description["format"])
                        );
                    else
                        $j_field_list = $_STORAGE['Controller']->Concat($j_field_list_array);

                    if (isset($field_description["virtual"]))
                        $field_list .= $_STORAGE['Controller']->RowToJson(
                                array(
                                    $j_field_list,
                                    $field_description['subfields_navigate_alias'] . '.' . $_STORAGE['Controller']->IdQuote($field_description['subfields_key']))
                            ) .' ' .$_STORAGE['Controller']->Collate() .' AS ' . $_STORAGE['Controller']->IdQuote($field_name);
                    else
                        $field_list .= $_STORAGE['Controller']->RowToJson(
                                array(
                                    $j_field_list,
                                    $_STORAGE['Controller']->IdQuote($field_description['table_alias']) . '.' . $_STORAGE['Controller']->IdQuote($field_name)
                                )
                            ) .' ' .$_STORAGE['Controller']->Collate() .' AS ' . $_STORAGE['Controller']->IdQuote($field_name);
                    $field_array[] = $field_name;

                    $replace_rules[$field_name] = $j_field_list;
                } else {
                    if (isset($field_description["table_alias"]))
                        $field_table_alias = $field_description["table_alias"];
                    else
                        $field_table_alias = 't';

                    if (isset($field_description["only_filled"]))
                        $field_list .= $_STORAGE['Controller']->IdQuote($field_table_alias) . "." . $_STORAGE['Controller']->IdQuote($field_name) . " is not null as " . $_STORAGE['Controller']->IdQuote($field_name);
                    else
                        $field_list .= $_STORAGE['Controller']->IdQuote($field_table_alias) . "." .  $_STORAGE['Controller']->IdQuote($field_name);
                    if (isset($field_description['type']))
                        $field_list .= '::' .  $_STORAGE['Controller']->IdQuote($field_description['type']);
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
                    $param_list .=  $_STORAGE['Controller']->IdQuote($param['field']);
                }

                $field_list .=  $_STORAGE['Controller']->IdQuote($function_description["schema"]) . "." .  $_STORAGE['Controller']->IdQuote($function_description["func"]) . "($param_list)";
                $field_array[] = $function_description["func"];
            }
        }


        $join = "";

        foreach ($params["join"] as $k => $j) {
            if (isset($j["distinct"])) {
                $order_distinct = self::makeOrderAndDistinctString($j["distinct"], $params);
                $join .= " left join (select distinct on (" . $order_distinct['distinctfields'] . ") * from " .  $_STORAGE['Controller']->relation($j["schema"], $j["entity"]) . " t " . $order_distinct['orderfields'] . ")as " .  $_STORAGE['Controller']->IdQuote($j["table_alias"]) . " on " .  $_STORAGE['Controller']->IdQuote($j["parent_table_alias"]) . "." .  $_STORAGE['Controller']->IdQuote($j["key"]) . " = " .  $_STORAGE['Controller']->IdQuote($j["table_alias"]) . "." .  $_STORAGE['Controller']->IdQuote($j["entityKey"]);
            } else
                $join .= " left join " .  $_STORAGE['Controller']->relation($j["schema"], $j["entity"]) . " as " .  $_STORAGE['Controller']->IdQuote($j["table_alias"]) . " on " .  $_STORAGE['Controller']->IdQuote($j["parent_table_alias"]) . "." .  $_STORAGE['Controller']->IdQuote($j["key"]) . " = " .  $_STORAGE['Controller']->IdQuote($j["table_alias"]) . "." .  $_STORAGE['Controller']->IdQuote($j["entityKey"]);
        }


        $order_distinct = self::makeOrderAndDistinctString($params['order'], $params);
        $orderfields = $order_distinct['orderfields'];
        $orderfields_no_aliases = $order_distinct['orderfields_no_aliases'];
        $distinctfields = $order_distinct['distinctfields'];

        $pred_res = array();
        $predicate = self::makePredicateString($params["predicate"], $replace_rules, $params["fields"], $params, $pred_res);

        if ($distinctfields)
            $count = 'SELECT count(distinct ' . $distinctfields . ') FROM (SELECT ' . $field_list . ' FROM ' .  $_STORAGE['Controller']->relation($params["schemaName"], $params["entityName"]) . ' as t ' . $join;
        else
            $count = 'SELECT count(*) AS count FROM ' .  $_STORAGE['Controller']->relation($params["schemaName"], $params["entityName"]) . ' as t ' . $join;

        if ($distinctfields) {
            $distinctfields = 'distinct on (' . $distinctfields . ')';
        }
        $statement = 'SELECT ' . $distinctfields . ' ' . $field_list . ' FROM ' .  $_STORAGE['Controller']->relation($params["schemaName"], $params["entityName"]) . ' as t ' . $join;

        $sql_aggregates = "";
        foreach ($params["aggregate"] as $aggregateDescription) {
            if ($aggregateDescription == end($params["aggregate"])) {
                $sql_aggregates = $sql_aggregates . $aggregateDescription["func"] . '(t.' . $aggregateDescription["field"] . ') as "' . $aggregateDescription["func"] . '(' . $aggregateDescription["field"] . ')"';
            } else {
                $sql_aggregates = $sql_aggregates . $aggregateDescription["func"] . '(t.' . $aggregateDescription["field"] . ') as "' . $aggregateDescription["func"] . '(' . $aggregateDescription["field"] . ')", ';
            }
        }
        $sql_aggregates = 'SELECT ' . $sql_aggregates . ' FROM ' .  $_STORAGE['Controller']->relation($params["schemaName"], $params["entityName"]) . ' as t ' . $join;

        if (isset($params["sample"])) {
            $ratio = intval($params["sample"]);
            $statement .= 'tablesample bernoulli(' . $ratio . ')';
        }

        if ($predicate != '') {
            $statement = $statement . ' WHERE ' . $predicate;
            $sql_aggregates = $sql_aggregates . ' WHERE ' . $predicate;
            $count = $count . ' WHERE ' . $predicate;
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

        if(!(isset($params["aggregate"]) && count($params["aggregate"]) && isset($params["group"])))
            $statement = $statement . "  " . $orderfields;

        $rowNumber = 0;
        if (isset($params["currentKey"])) {
            if ($params["currentKey"] && ($params["limit"] != 0 and $params["limit"] != -1)) {
                $equation = '(' . $_STORAGE['Controller']->NumericTruncate("(k.row_number - 1) / $params[limit]") ." * $params[limit])";
                if (isset($params["middleRow"])) {
                    if ($params["middleRow"]) $equation = 'greatest(' . $_STORAGE['Controller']->NumericTruncate("k.row_number - ($params[limit] / 2) - 1") .', 0)';
                }
                $pageNumberStatement = 'SELECT CASE WHEN k.row_number = 0 THEN 0 ELSE ' . $equation . ' END as row_number
                    FROM (select row_number() over (' . $orderfields_no_aliases . '), t.' .  $_STORAGE['Controller']->IdQuote($params["primaryKey"]) .
                    '  from (' . $statement . ') t ) k WHERE k.' . $params["primaryKey"] . '=\'' .  $_STORAGE['Controller']->EscapeString($params["currentKey"]) . '\'';
                $rowNumberRes =  $_STORAGE['Controller']->sql($pageNumberStatement);
                $params["offset"] = 0;
                if (isset($rowNumberRes[0]["row_number"]))
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

        $options = static::GetDefaultOptions();
        $options->SetFormat((isset($params['format']) && !isset($params['process'])) ? $params['format'] : 'object');
        $options->SetQueryDescription($desc);
        $data_result_statement =  $_STORAGE['Controller']->sql($statement, $options);
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
                $ts_n =  $_STORAGE['Controller']->sql('select plainto_tsquery(\'' .  $_STORAGE['Controller']->EscapeString($ts_query["language"]) .
                    '\', \'' .  $_STORAGE['Controller']->EscapeString($ts_query["ft_query"]) . '\')');
                $data_result['ft_keywords'] = $ts_n[0]['plainto_tsquery'];
            }
        }

        if (sizeof($params["aggregate"])) {
            $options = static::GetDefaultOptions();
            $options->SetQueryDescription("$desc (aggregate)");
            $data_aggregates =  $_STORAGE['Controller']->sql($sql_aggregates, $options);

            foreach ($params["aggregate"] as $aggrIndex => $aggregateDescription) {
                $data_result[$aggregateDescription["func"] . '(' . $aggregateDescription["field"] . ')'][][$aggregateDescription["func"]] = $data_aggregates[0][$aggregateDescription["func"] . '(' . $aggregateDescription["field"] . ')'];
            }
        }

        return static::postProcessing($data_result, $params);
    }

    public static function getEntitiesByKey($params, $order_by_key = true) {
        throw new Exception('Function ' .__FUNCTION__ .' is deleted!!!');
    }

    public static function deleteEntitiesByKey($params) {
        global $_STORAGE;
        $replaceDataWithSQL;
        static::preProcessing($params, "deleteEntitiesByKey", $replaceDataWithSQL);
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
                    if (isset($params["types"][$key]))
                        if ($params["types"][$key]) $type_conversion = '::' . $params["types"][$key];
                $sql_where .=  $_STORAGE['Controller']->IdQuote($key) . " = '" .  $_STORAGE['Controller']->EscapeString($value_arr[$j][$i]) . "'" . $type_conversion;
                if ($key != end($key_arr))
                    $sql_where .= " AND ";

            }

            $sql .= 'DELETE FROM ' .  $_STORAGE['Controller']->relation($params['schemaName'], $params['entityName']) . ' WHERE ' . $sql_where . ';';
        }

        static::queryModifyEntities($sql);

        $return_data["sql"] = $sql;
        return $return_data;
    }


    public static function addEntities($params) {
        global $_STORAGE;
        $replaceDataWithSQL;
        static::preProcessing($params, "addEntities", $replaceDataWithSQL);

        $desc = isset($params['desc']) ? $params['desc'] : '';
        $sql = '';

        foreach ($params["fields"] as $r => $row) {
            $fields = '';
            $values = '';
            foreach ($row as $field => $value) {
                if (!is_null($value) && $value!='') {
                    $sql_to_set = '';
                    $sql_to_set = "'" .  $_STORAGE['Controller']->EscapeString($value) . "'";
                    if (isset($params["types"])) {
                        if (isset($params["types"][$field]))
                            if ($params["types"][$field]) {
                                $sql_to_set =  $_STORAGE['Controller']->type( $_STORAGE['Controller']->EscapeString($value), $params["types"][$field]);
                            }
                    }

                    if(isset($replaceDataWithSQL[$field]))
                        $sql_to_set = $replaceDataWithSQL[$field];

                    if ($fields) {
                        $fields .= ', ' .  $_STORAGE['Controller']->IdQuote($field);
                    } else {
                        $fields =  $_STORAGE['Controller']->IdQuote($field);
                    }

                    if ($values) {
                        $values .= ", " . $sql_to_set;
                    } else {
                        $values = $sql_to_set;
                    }
                }
            }

            $sql .= 'INSERT INTO ' . $_STORAGE['Controller']->relation($params['schemaName'], $params['entityName'])
                    . "($fields) "  . $_STORAGE['Controller']->InsertValues($values) .' '
                    . $_STORAGE['Controller']->ReturningPKey( $_STORAGE['Controller']->IdQuote($params['key']))  .';';
        }

        $ins_ret = static::queryModifyEntities($sql, null, "$desc (files)");
        return $ins_ret;
    }

    public static function updateEntity($params) {
        global $_STORAGE;
        $replaceDataWithSQL;
        static::preProcessing($params, "updateEntity", $replaceDataWithSQL);

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
                    if (isset($params["types"]))
                        if ($params["types"][$field])
                            $type_conversion = '::' . $params["types"][$field];

                                                
                    if(isset($replaceDataWithSQL[$field]))
                        $sql_to_set = $replaceDataWithSQL[$field];
                    else
                        $sql_to_set = "'" .  $_STORAGE['Controller']->EscapeString($value) . "'". $type_conversion;

                    if ($set) {
                        $set .= ', ' .  $_STORAGE['Controller']->IdQuote($field) . " = $sql_to_set";
                    } else {
                        $set =  $_STORAGE['Controller']->IdQuote($field) . " = $sql_to_set";
                    }
                } else {
                    if ($set) {
                        $set .= ', ' .  $_STORAGE['Controller']->IdQuote($field) . " = NULL";
                    } else {
                        $set =  $_STORAGE['Controller']->IdQuote($field) . " = NULL";
                    }
                }
            };

            foreach ($key_arr as $j => $key) {
                $type_conversion = '';
                if (isset($params["types"]))
                    if ($params["types"][$key]) {
                        $type_conversion = $params["types"][$key];
                    }
                $sql_where .=  $_STORAGE['Controller']->typeField($key, $type_conversion, true)
                    . " = "
                    .  $_STORAGE['Controller']->type( $_STORAGE['Controller']->EscapeString($value_arr[$j][$i]), $type_conversion);
                if ($key != end($key_arr))
                    $sql_where .= " AND ";

            }

            $sql .= 'UPDATE ' .  $_STORAGE['Controller']->relation($params['schemaName'], $params['entityName']) . " SET $set WHERE $sql_where;";
        }

        static::queryModifyEntities($sql);

        $return_data["sql"] = $sql;
        return $return_data;
    }

    public static function getPIDs($params) {
        global $_STORAGE;
        $r =  $_STORAGE['Controller']->sql('SELECT * FROM pg_stat_activity WHERE datname = current_database()');
        $pid_map = array();
        foreach ($r as $i => $v) {
            $pid_map[$v['pid']] = 1;
        }

        if (isset($_STORAGE['pids']))
            foreach ($_STORAGE['pids'] as $p => $v) {
                if (!isset($pid_map[$p]))
                    unset($_STORAGE['pids'][$p]);
            }
        return array('pids' => $_STORAGE['pids'] ?? array());
    }

    public static function killPID($params) {
        global $_STORAGE;
        $r =  $_STORAGE['Controller']->sql('select pg_terminate_backend(' .  $_STORAGE['Controller']->EscapeString($params['pid']) . ')');
        unset($_STORAGE['pids'][$params['pid']]);
        return $r;
    }

    public static function getTableDefaultValues($params) {
        return [];
    }

    public static function getAllModelMetadata() {
        global $_CONFIG, $_STORAGE;
        $buttons = "";
        $pages = "";

        $proj_arr =  $_STORAGE['Controller']->sql("SELECT * FROM $_CONFIG->metaSchema.view_projection_entity");
        if (@count($proj_arr) == 0) {
            //throw new Exception("Metadata: no projections");
        }

        $prop_arr = methodsBase::getAllEntities(array(
            "schemaName" => $_CONFIG->metaSchema,
            "entityName" => "view_projection_property"
        ));

        $rel_arr = methodsBase::getAllEntities(array(
            "schemaName" => $_CONFIG->metaSchema,
            "entityName" => "view_projection_relation"
        ));


        $metadata = methodsBase::mergeMetadata($proj_arr, $prop_arr, $rel_arr, $buttons);

        $options =  $_STORAGE['Controller']->sql("SELECT * FROM $_CONFIG->metaSchema.options");

        return array('projections' => $metadata, 'pages' => $pages, 'options' => $options);
    }

    public static function test($params) {
        return $params;
    }
}