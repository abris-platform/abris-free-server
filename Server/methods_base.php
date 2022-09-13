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
        $arr_count = $_STORAGE['Controller']->Sql($count, $options);
        $plan_rows = $arr_count[0]["count"];
        return $plan_rows;
    }

    private static function mergeMetadata($proj_arr, $prop_arr, $rel_arr, $buttons) {
        $metadata = array();

        foreach ($proj_arr as $i => $p) {
            $metadata[$p['projection_name']] = $p;
            $metadata[$p['projection_name']]['properties'] = array();
            $metadata[$p['projection_name']]['relations'] = array();
            // $metadata[$p['projection_name']]['buttons'] = array();
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

    protected static function queryModifyEntities($queries, $options = null, $query_description = '') {
        global $_STORAGE;
        if (is_null($options))
            $options = static::GetDefaultOptions();

        $options->SetPreprocessData(null);
        $options->SetQueryDescription($query_description);
        // $options->SetInfoAffectedRows(true);

        $result = array();

        foreach ($queries as $query) {
            $s = $_STORAGE['Controller']->Sql($query, $options);
            $result[] = $s[0] ?? $s;
        }

        return $result;
    }

    protected static function GetDefaultOptions() {
        global $_STORAGE;
        return $_STORAGE['Controller']->GetDefaultOptions();
    }

    protected static function SetCookie($name, $value = '', $expires = 0, $path = '', $domain = '', $secure = false) {
        if (php_sapi_name() == 'cli')
            return;

        if (is_array($expires))
            setcookie($name, $value, $expires);
        else
            setcookie($name, $value, $expires, $path, $domain, $secure);
    }

    public static function authenticate($params, $needCookie = true) {
        global $_STORAGE;

        if ($params['usename'] <> '' and $params['passwd'] <> '') {
            $_STORAGE['login'] = $params['usename'];
            $_STORAGE['password'] = $params['passwd'];

            $options = static::GetDefaultOptions();
            $options->SetEncryptPassword(false);
            $usenameDB = $_STORAGE['Controller']->Sql("SELECT '$params[usename]' as usename", $options); //run a request to verify authentication

            $privateKey = GenerateRandomString();
            if ((!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__'))
                && $needCookie) {
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

            $_STORAGE['private_key'] = $privateKey;
            $_STORAGE['password'] = EncryptStr($_STORAGE['password'], $privateKey);
            return $usenameDB;
        }
        
        static::clear_auth_session();
        return null;
    }

    public static function clear_auth_session() {
        unset_auth_session();
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
        return $_STORAGE['Controller']->Sql('SELECT * FROM ' . $_STORAGE['Controller']->relation($params['schemaName'], $params['entityName']) . ' t');
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
                    $where .= $_STORAGE['Controller']->typeField($n, 'text')
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

        $data_result_statement = $_STORAGE['Controller']->Sql($statement);
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
                $field = $_STORAGE['Controller']->IdQuote($fields[$field]['virtual'] ? $fields[$field]['subfields_navigate_alias'] : $operand["table_alias"])
                    . "." . $_STORAGE['Controller']->IdQuote($field);
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
                    if (isset($operand["m_order"])) {
                        if ($operand["m_order"])
                            $value_parts = explode(' ', $value);
                    } else
                        $value_parts = array(0 => $value);
                    $where_arr = array();

                    if (($field != "t.\"\"") && ($field != "t.``")) {
                        foreach ($value_parts as $i => $v) {
                            $where_arr[] = $_STORAGE['Controller']->typeField($field, 'text')
                                . ' ' . $_STORAGE['Controller']->Like() . ' '
                                . $_STORAGE['Controller']->type(
                                    '%' . $_STORAGE['Controller']->EscapeString($v) . '%',
                                    'text'
                                );
                        }

                        return implode(' AND ', $where_arr);
                    } else {
                        $where = '';

                        foreach ($fields as $k => $field_description) {
                            $table_alias = $field_description['table_alias'];
                            if (($table_alias === 'c') || ($table_alias === 'r'))
                                continue;

                            if (isset($field_description["hidden"]))
                                if ($field_description["hidden"])
                                    continue;
                            if (isset($field_description["subfields"])) {
                                foreach ($field_description["subfields"] as $m => $j_field) {
                                    if ($where) {
                                        $where .= " OR ";
                                    }

                                    $where .= $field_description['subfields_table_alias'][$m] . '.'
                                        .$_STORAGE['Controller']->typeField($j_field, 'text')
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
                                    . $_STORAGE['Controller']->typeField(
                                        't.' . $_STORAGE['Controller']->IdQuote($k),
                                        'text'
                                    )
                                    . ' ' . $_STORAGE['Controller']->Like() . ' '
                                    . $_STORAGE['Controller']->type(
                                        $_STORAGE['Controller']->EscapeString($value . '%'), 'text')
                                    . '), '
                                    . $_STORAGE['Controller']->typeField(
                                        't.' . $_STORAGE['Controller']->IdQuote($k),
                                        'text'
                                    );
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
                return "$field <= now() AND $field > (now() - " .$_STORAGE['Controller']->type($value, 'interval') .")";
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
                    $orderfields .= ' ' .$_STORAGE['Controller']->Desc();
                    $orderfields_no_aliases .= ' ' .$_STORAGE['Controller']->Desc();
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

    public static function createBaseQuery($params) {
        global $_STORAGE;
        $controller = $_STORAGE['Controller'];
        $replace_rules = array();

        if ($params['entityName'] === 'doctor')
            $a = 1;

        if (isset($params['fields'])) {
            $field_list = '';
        } else {
            $field_list = '*';
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
                $field_list .= $controller->IdQuote($field_description["table_alias"]) . "." . $controller->IdQuote($field_name);
                $field_array[] = $field_name;
            }
            foreach ($params["aggregate"] as $i => $field_obj) {
                if ($field_list) {
                    $field_list .= ", ";
                }
                $field_name = $field_obj["field"];
                $field_func = $field_obj["func"];
                $field_description = $params["fields"][$field_name];
                $field_list .= $field_func . "(" . $controller->IdQuote($field_description["table_alias"]) . "." . $controller->IdQuote($field_name) . ") as $field_name";
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
                            . $controller->typeField(
                                $controller->IdQuote($field_description['subfields_table_alias'][$m])
                                . '.'
                                .$controller->IdQuote($j_field),
                                'text')
                            . ", '')";
                    }

                    if (isset($field_description["format"]))
                        $j_field_list = $controller->FormatColumns(
                            $j_field_list_array,
                            $controller->EscapeString($field_description["format"])
                        );
                    else
                        $j_field_list = $controller->Concat($j_field_list_array);

                    if (isset($field_description["virtual"]))
                        $field_list .= $controller->RowToJson(
                                array(
                                    $j_field_list,
                                    $field_description['subfields_navigate_alias'] . '.' . $controller->IdQuote($field_description['subfields_key']))
                            ) .' ' .$controller->Collate() .' AS ' . $controller->IdQuote($field_name);
                    else
                        $field_list .= $controller->RowToJson(
                                array(
                                    $j_field_list,
                                    $controller->IdQuote($field_description['table_alias']) . '.' . $controller->IdQuote($field_name)
                                )
                            ) .' ' .$controller->Collate() .' AS ' . $controller->IdQuote($field_name);
                    $field_array[] = $field_name;

                    $replace_rules[$field_name] = $j_field_list;
                } else {
                    $field_table_alias = $field_description['table_alias'] ?? 't';

                    if ((($field_table_alias == 'r') || ($field_table_alias == 'c'))
                        && (!empty($field_description['table_name']))) {
                        $start = $field_table_alias == 'c' ? '(SELECT count(*) FROM' : 'EXISTS(SELECT 1 FROM';

                        $field_list .= "$start "
                            . $controller->relation($field_description['schema_name'] ?? 'public', $field_description['table_name'])
                            . ' ' . $field_table_alias
                            . " WHERE $field_table_alias.$field_description[key] = t.$field_description[ref_key]) AS $field_name";

                        if ($field_table_alias == 'r') {
                            $field_list .= ", $field_description[key]";
                            $field_array[] = $field_name;
                            $field_array[] = $field_description['key'];
                            continue;
                        }

                        $field_array[] = $field_name;
                        continue;
                    }

                    if (isset($field_description['only_filled']))
                        $field_list .= $controller->IdQuote($field_table_alias) . '.' . $controller->IdQuote($field_name) . ' is not null as ' . $controller->IdQuote($field_name);
                    else if (($field_description['table_alias'] !== 'r') && ($field_description['table_alias'] !== 'c'))
                        $field_list .= $controller->IdQuote($field_table_alias) . "." .  $controller->IdQuote($field_name);
                    if (isset($field_description['type']))
                        $field_list .= "::$field_description[type]";
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
                    $param_list .=  $controller->IdQuote($param['field']);
                }

                $field_list .=  $controller->IdQuote($function_description["schema"]) . "." .  $controller->IdQuote($function_description["func"]) . "($param_list)";
                $field_array[] = $function_description["func"];
            }
        }

        $join = "";

        foreach ($params["join"] as $k => $j) {
            if (isset($j["distinct"])) {
                $order_distinct = self::makeOrderAndDistinctString($j["distinct"], $params);
                $join .= " LEFT JOIN (SELECT DISTINCT ON ($order_distinct[distinctfields]) * FROM " . $controller->relation($j["schema"], $j["entity"]) . ' t ' . $order_distinct['orderfields'] . ") AS " . $controller->IdQuote($j["table_alias"]) . " ON " . $controller->IdQuote($j["parent_table_alias"]) . "." . $controller->IdQuote($j["key"]) . " = " . $controller->IdQuote($j["table_alias"]) . "." . $controller->IdQuote($j["entityKey"]);
            } else
                $join .= " LEFT JOIN " . $controller->relation($j["schema"], $j["entity"]) . " AS " . $controller->IdQuote($j["table_alias"]) . " ON " . $controller->IdQuote($j["parent_table_alias"]) . "." . $controller->IdQuote($j["key"]) . " = " . $controller->IdQuote($j["table_alias"]) . "." . $controller->IdQuote($j["entityKey"]);
        }


        $order_distinct = self::makeOrderAndDistinctString($params['order'], $params);
        $orderfields = $order_distinct['orderfields'];
        $orderfields_no_aliases = $order_distinct['orderfields_no_aliases'];
        $distinctfields = $order_distinct['distinctfields'];

        $pred_res = array();
        $predicate = self::makePredicateString($params["predicate"], $replace_rules, $params["fields"], $params, $pred_res);

        if ($distinctfields)
            $count = 'SELECT count(DISTINCT ' . $distinctfields . ') AS count FROM (SELECT ' . $field_list . ' FROM ' . $controller->relation($params["schemaName"], $params["entityName"]) . ' as t ' . $join;
        else
            $count = 'SELECT count(*) AS count FROM ' . $controller->relation($params["schemaName"], $params["entityName"]) . ' AS t ' . $join;

        if ($distinctfields) {
            $distinctfields = $controller->distinct_on($distinctfields);
        }

        $statement = 'SELECT ' . $distinctfields . ' ' . $field_list . ' FROM ' . $controller->relation($params["schemaName"], $params["entityName"]) . ' as t ' . $join;

        $aggregates = array();
        $aggregates_str = '1';
        if (!empty($params['aggregate'])) {
            foreach ($params["aggregate"] as $aggregateDescription) {
                $field_name = $aggregateDescription['field'];
                $func_name = $aggregateDescription['func'];
                $field_alias = $params['fields'][$field_name]['table_alias'];

                $aggregates[] = "${func_name}(${field_alias}.${field_name}) AS \"${func_name}(${field_name})\"";
            }

            $aggregates_str = implode(', ', $aggregates);
        }
        $sql_aggregates = "SELECT $aggregates_str FROM " . $controller->relation($params["schemaName"], $params["entityName"]) . ' as t ' . $join;

        if (isset($params["sample"])) {
            $ratio = intval($params["sample"]);
            $statement .= 'tablesample bernoulli(' . $ratio . ')';
        }

        $sql_without_condition = $statement;

        if ($predicate != '') {
            $statement = $statement . ' WHERE ' . $predicate;
            $sql_aggregates = $sql_aggregates . ' WHERE ' . $predicate;
            $count = $count . ' WHERE ' . $predicate;
        } else
            $predicate = 'true';

        if ($distinctfields)
            $count .= ') t';

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
                $equation = '(' . $controller->NumericTruncate("(k.row_number - 1) / $params[limit]") ." * $params[limit])";
                if (isset($params["middleRow"])) {
                    if ($params["middleRow"]) $equation = 'greatest(' . $controller->NumericTruncate("k.row_number - ($params[limit] / 2) - 1") .', 0)';
                }
                $pageNumberStatement = "SELECT CASE WHEN k.row_number = 0 THEN 0 ELSE $equation END AS " .$controller->IdQuote('row_number')
                    ." FROM (select row_number() OVER ($orderfields_no_aliases) AS " .$controller->IdQuote('row_number') .', t.' . $controller->IdQuote($params["primaryKey"]) .
                    " FROM ($statement) t ) k WHERE k.{$params['primaryKey']} ='" . $controller->EscapeString($params["currentKey"]) . "'";

                $rowNumberRes = $controller->Sql($pageNumberStatement);
                $params["offset"] = 0;
                if (isset($rowNumberRes[0]["row_number"]))
                    $params["offset"] = $rowNumberRes[0]["row_number"];
            }
        }

        $statement_count = $statement;
        if (($params["limit"] != 0 and $params["limit"] != -1) or ($params["offset"] != 0 && $params["offset"] >= 0)) {
            $statement = "$statement LIMIT $params[limit] OFFSET $params[offset]";
        }

        global $data_result;
        $data_result = array(
            "offset" => $params["offset"],
            "fields" => $field_array,
            "sql" => $statement
        );

        if (!empty($params['predicate']['operands']))
            static::createWrapper($statement, $sql_without_condition, $params, $field_array);

        return array(
            'statement' => $statement,
            'statement_count'=> $statement_count,
            'count' => $count,
            'sql_aggregates' => $sql_aggregates
        );
    }

    public static function getTableDataPredicate($params) {
        global $_STORAGE;
        $controller = $_STORAGE['Controller'];
        $desc = $params['desc'] ?? '';

        $queries = static::createBaseQuery($params);
////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $options = static::GetDefaultOptions();
        $options->SetFormat((isset($params['format']) && !isset($params['process'])) ? $params['format'] : 'object');
        $options->SetQueryDescription($desc);

        $data_result_statement = $controller->Sql($queries['statement'], $options);

        $count_data = count($data_result_statement);

        if (($count_data < $params['limit']) || ($params['limit'] == 1)) {
            $number_count[0]['count'] = $count_data + $params['offset'];
        } else {
            $number_count[0]['count'] = methodsBase::sql_count_estimate($params, $queries['statement_count'], $queries['count']);
        }

        global $data_result;
        $data_result['data'] = $data_result_statement;
        $data_result['records'] = $number_count;

        if (isset($params['predicate']['operands'][0])) {
            $fst_operand = $params['predicate']['operands'][0];
            if ($fst_operand['operand']['op'] == "FTS") {
                $ts_query = json_decode($fst_operand['operand']['value'], true);
                $ts_n = $controller->Sql('select plainto_tsquery(\'' . $controller->EscapeString($ts_query["language"]) .
                    '\', \'' . $controller->EscapeString($ts_query["ft_query"]) . '\')');
                $data_result['ft_keywords'] = $ts_n[0]['plainto_tsquery'];
            }
        }

        if (!empty($params['aggregate'])) {
            $options = static::GetDefaultOptions();
            $options->SetQueryDescription("$desc (aggregate)");
            $data_aggregates = $controller->Sql($queries['sql_aggregates'], $options);

            foreach ($params['aggregate'] as $aggrIndex => $aggregateDescription) {
                $data_result[$aggregateDescription['func'] . '(' . $aggregateDescription['field'] . ')'][][$aggregateDescription['func']] = $data_aggregates[0][$aggregateDescription['func'] . '(' . $aggregateDescription['field'] . ')'];
            }
        }

        return static::postProcessing($data_result, $params);
    }

    public static function createWrapper(&$source_sql, $sql_without_condition, $params, $fields) {}

    public static function getEntitiesByKey($params, $order_by_key = true) {
        throw new Exception('Function ' .__FUNCTION__ .' is deleted!!!');
    }

    public static function deleteEntitiesByKey($params) {
        global $_STORAGE;
        $controller = $_STORAGE['Controller'];

        $replaceDataWithSQL;
        static::preProcessing($params, 'deleteEntitiesByKey', $replaceDataWithSQL);
        $sql = array();
        $value_arr = array();
        $key_arr = array();
        $request_number = array();

        if (is_array($params['key'])) {
            $key_arr = $params['key'];
            $value_arr = $params['value'];
            if (is_array($params['value'][0])) {
                $request_number = $params['value'][0];
            } else {
                $request_number = array($params['value'][0]);
            }

        } else {
            $key_arr = array($params['key']);
            if (is_array($params['value']))
                $value_arr[0] = $params['value'];
            else
                $value_arr[0] = array($params['value']);
            $request_number = $value_arr[0];

        }

        foreach ($request_number as $i => $request) {
            $sql_where = '';
            // $type_conversion = '';
            $type_conversion = false;

            foreach ($key_arr as $j => $key) {
                if (isset($params['types']))
                    if (isset($params['types'][$key]))
                        if ($params['types'][$key]) $type_conversion = true; // $type_conversion = '::' . $params['types'][$key];

                if ($type_conversion)
                    $sql_where .=  $controller->IdQuote($key) . " = " .
                        $controller->type($controller->EscapeString($value_arr[$j][$i]), $params['types'][$key]);
                else
                    $sql_where .=  $controller->IdQuote($key) . " = '" .  $controller->EscapeString($value_arr[$j][$i]) . "'";
                    //$sql_where .=  $controller->IdQuote($key) . " = '" .  $controller->EscapeString($value_arr[$j][$i]) . "'" . $type_conversion;
                if ($key != end($key_arr))
                    $sql_where .= ' AND ';

            }

            $sql[] = 'DELETE FROM ' .  $controller->relation($params['schemaName'], $params['entityName']) . ' WHERE ' . $sql_where . ';';
        }

        static::queryModifyEntities($sql);

        $return_data['sql'] = implode('', $sql);
        return $return_data;
    }

    public static function addEntities($params) {
        global $_STORAGE;
        $controller = $_STORAGE['Controller'];

        $replaceDataWithSQL;
        static::preProcessing($params, 'addEntities', $replaceDataWithSQL);

        $desc = $params['desc'] ?? '';
        $ins_values = array();

        $is_one_row = count($params['fields']) === 1;

        foreach ($params['fields'] as $row) {
            $fields = array();
            $values = array();
            foreach ($row as $field => $value) {
                $functions = array();

                if ($is_one_row && empty($value))
                    continue;

                $row_value = $value;
                $sql_to_set = "'" . $controller->EscapeString($row_value) . "'";

                if ($row_value == '') {
                    $row_value = 'default';
                    $sql_to_set = $row_value;
                }

                if (isset($params['types'])) {
                    if (isset($params['types'][$field]))
                        if ($params['types'][$field]) {
                            $sql_to_set = $controller->type(
                                $row_value == 'default' ? $row_value : $controller->EscapeString($row_value),
                                $params['types'][$field]
                            );
                        }
                }

                if (isset($replaceDataWithSQL[$field]))
                    $sql_to_set = $replaceDataWithSQL[$field];

                $fields[] = $controller->IdQuote($field);

                if (isset($params['additional']['functions']))
                    foreach ($params['additional']['functions'] as $func) {
                        if (!isset($func['fname']) || !isset($func['fields']))
                            continue;

                        if (in_array($field, $func['fields']))
                            $functions[$func['fname']][] = $sql_to_set;
                    }

                if (!empty($functions))
                    $values[] = $functions;
                else
                    $values[] = $sql_to_set;
            }

            $values = array_map(
                function ($element) {
                    if (is_array($element)) {
                        $key = array_key_first($element);
                        return '"' .$key .'"(' .implode(', ', $element[$key]) .')';
                    }
                    return  $element;
                },
                $values
            );

            $ins_values[] = '(' .implode(', ', $values) .')';
        }

        $columns = array_keys($params['fields'][0]);

        if ($is_one_row)
            $columns = array_filter($columns,
                function ($e) use ($is_one_row, $params) {
                    if ($is_one_row && empty($params['fields'][0][$e]))
                        return false;
                    return true;
                });

        $columns = array_map(
            function ($e) use ($controller) {
                return $controller->IdQuote($e);
            }, $columns);

        $sql = 'INSERT INTO ' . $controller->relation($params['schemaName'], $params['entityName'])
            . '(' .implode(', ', $columns) .') VALUES '  . implode(', ', $ins_values) .' '
            . $controller->ReturningPKey( $_STORAGE['Controller']->IdQuote($params['key']))  .';';

        $ins_ret = static::queryModifyEntities(array($sql), null, "$desc (files)");
        return $ins_ret;
    }

    public static function updateEntity($params) {
        global $_STORAGE;
        $controller = $_STORAGE['Controller'];

        $replaceDataWithSQL;
        static::preProcessing($params, 'updateEntity', $replaceDataWithSQL);

        $sql = array();
        $value_arr = array();
        $key_arr = array();
        $request_number = array();

        if (is_array($params['key'])) {
            $key_arr = $params['key'];
            $value_arr = $params['value'];
            if (is_array($params['value'][0])) {
                $request_number = $params['value'][0];
            } else {
                $request_number = array($params['value'][0]);
            }

        } else {
            $key_arr = array($params['key']);
            if (is_array($params['value']))
                $value_arr[0] = $params['value'];
            else
                $value_arr[0] = array($params['value']);
            $request_number = $value_arr[0];

        }

        foreach ($request_number as $i => $request) {
            $set = '';
            $sql_where = '';
            $type_conversion = '';

            foreach ($params['fields'] as $field => $values) {

                if (is_array($values))
                    $value = $values[$i];
                else
                    $value = $values;

                if (isset($value) && trim($value) !== '') {
                    $type_conversion = false;
                    if (isset($params['types']))
                        if ($params['types'][$field])
                            $type_conversion = true;

                    if(isset($replaceDataWithSQL[$field]))
                        $sql_to_set = $replaceDataWithSQL[$field];
                    else
                        if ($type_conversion)
                            $sql_to_set = $controller->type(
                                $controller->EscapeString($value),
                                $params['types'][$field]
                            );
                        else
                            $sql_to_set =  "'" .  $controller->EscapeString($value) . "'";

                    if ($set) {
                        $set .= ', ' .  $controller->IdQuote($field) . " = $sql_to_set";
                    } else {
                        $set =  $controller->IdQuote($field) . " = $sql_to_set";
                    }
                } else {
                    if ($set) {
                        $set .= ', ' .  $controller->IdQuote($field) . ' = NULL';
                    } else {
                        $set =  $controller->IdQuote($field) . ' = NULL';
                    }
                }
            };

            foreach ($key_arr as $j => $key) {
                $type_conversion = '';
                if (isset($params['types']))
                    if ($params['types'][$key]) {
                        $type_conversion = $params['types'][$key];
                    }
                $sql_where .=  $controller->typeField($key, $type_conversion, true)
                    . ' = '
                    .  $controller->type($controller->EscapeString($value_arr[$j][$i]), $type_conversion);
                if ($key != end($key_arr))
                    $sql_where .= ' AND ';

            }

            $sql[] = 'UPDATE ' .  $controller->relation($params['schemaName'], $params['entityName']) . " SET $set WHERE $sql_where;";
        }

        static::queryModifyEntities($sql);
        $return_data['sql'] = implode('', $sql);
        return $return_data;
    }

    public static function getPIDs($params) {
        global $_STORAGE;
        $controller = $_STORAGE['Controller'];

        $r =  $controller->Sql(
            $controller->GetAllDbPIDs()
        );

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
        $controller = $_STORAGE['Controller'];

        $r = $controller->KillProcess(
            $controller->EscapeString($params['pid'])
        );

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

        $proj_arr =  $_STORAGE['Controller']->Sql("SELECT * FROM $_CONFIG->metaSchema.view_projection_entity");
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

        $options =  $_STORAGE['Controller']->Sql("SELECT * FROM $_CONFIG->metaSchema.options");

        return array('projections' => $metadata, 'pages' => $pages, 'options' => $options);
    }

    public static function test($params) {
        return $params;
    }
}
