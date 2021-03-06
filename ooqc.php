<?php

    require_once 'functions.php';
    require_once 'config.php'

    $db_file = fopen("exchange.sql", "r");
    $output_script_file = fopen("db_api.php", "w");

    if (($db_file) && (is_writable("db_api.php"))) {
        fwrite($output_script_file, "<?php\n\n");
        fwrite($output_script_file, "\tdefine('DB_LOGIN', $db_login);\n");
        fwrite($output_script_file, "\tdefine('DB_PASSWORD', $db_password);\n\n");

        /* lets write about connection */
        fwrite($output_script_file, "\t" . '$' . "conn = new PDO('mysql:host=$db_host;"
                                    . "dbname=$db_name', DB_USERNAME, DB_PASSWORD);\n");

        fwrite($output_script_file, "\t" . '$' . "conn->setAttribute(PDO::ATTR_ERRMODE, "
                                    . " PDO::ERRMODE_EXCEPTION);\n");

        /* reading SQL file line by line */
        while (($current_string = fgets($db_file, 4096)) !== false) {
            if (strpos($current_string, "CREATE TABLE") !== false) {
                //  finding a symbol from which starts table name
                if (strpos($current_string, "`") !== false) {

                    $table_name = cutWordInBrackersInString($current_string);
                    echo $table_name . '<br>';

                    $table_fields_data = array();
                    // we found table with name == $table_name, lets go through DB fields
                    while (($table_field = fgets($db_file, 4096)) !== false) {
                        if (strpos($table_field, ") ENGINE=") !== false) {
                            // thats for table name for function header
                            $table_name = ucfirst($table_name);

                            // write queries like SELECT * FROM
                            fwrite($output_script_file,
                                    "\n\tfunction getAll$table_name()\n\t{\n ");

                            fwrite($output_script_file, "\t\ttry {\n");

                            fwrite($output_script_file, "\t\t\tglobal " . '$' . "conn;\n");
                            fwrite($output_script_file, "\t\t\t" . '$' . "query = "
                                                        . '$' . "conn->prepare("
                                                        . "'SELECT * FROM "
                                                        . lcfirst($table_name)
                                                        . "');\n");
                            fwrite($output_script_file, "\t\t\t" . '$' . "query->"
                                                        . "execute();\n");
                            fwrite($output_script_file, "\t\t\t" . '$' . "result = "
                                                        . '$' . "query->"
                                                        . "fetchAll();\n");
                            fwrite($output_script_file, "\t\t\treturn "
                                                        . '$' . "result;\n");

                            fwrite($output_script_file, "\t\t} catch"
                                                         . "(PDOException "
                                                         . '$' . "e) {\n");

                            fwrite($output_script_file, "\t\t\treturn null;\n"
                                                        . "\t\t}\n");

                            fwrite($output_script_file, "\t}\n");


                            // write queries for get data from SINGLE fields
                            $fields_num = count($table_fields_data);

                            for ($i = 0; $i < $fields_num; $i++) {
                                $select_query_string = lcfirst($table_name)
                                                     . "." . $table_fields_data[$i]['name']
                                                     . " = :" . $table_fields_data[$i]['name'];

                                $bind_value_string = "\t\t\t" . '$' . "query->bindValue("
                                                   . "':" . $table_fields_data[$i]['name']
                                                   . "', " . '$'
                                                   . $table_fields_data[$i]['name']
                                                   . ");\n";

                                $function_name_string = ucfirst($table_fields_data[$i]['name']);

                                $function_params_string = '$' . $table_fields_data[$i]['name'];

                                fwrite($output_script_file,
                                        "\n\tfunction get"
                                        . $table_name
                                        . "By");

                                fwrite($output_script_file, $function_name_string);

                                fwrite($output_script_file, "(");

                                fwrite($output_script_file, $function_params_string);

                                fwrite($output_script_file, ")\n\t{\n");

                                fwrite($output_script_file, "\t\ttry {\n");

                                fwrite($output_script_file, "\t\t\tglobal "
                                                            . '$' . "conn;\n");

                                fwrite($output_script_file, "\t\t\t" . '$' . "query = "
                                                            . '$' . "conn->prepare("
                                                            . "'SELECT * FROM "
                                                            . lcfirst($table_name)
                                                            . " WHERE "
                                                            . $select_query_string
                                                            . "');" . "\n");

                                fwrite($output_script_file, $bind_value_string);

                                fwrite($output_script_file, "\t\t\t" . '$' . "query->"
                                                            . "execute();\n");

                                fwrite($output_script_file, "\t\t\t" . '$'
                                                            . "result = "
                                                            . '$' . "query->"
                                                            . "fetchAll();\n");

                                fwrite($output_script_file, "\t\t\treturn "
                                                            . '$'
                                                            . "result;\n");

                                fwrite($output_script_file, "\t\t} catch"
                                                             . "(PDOException "
                                                             . '$' . "e) {\n");

                                fwrite($output_script_file, "\t\t\treturn null;\n"
                                                            . "\t\t}\n");

                                fwrite($output_script_file, "\t}\n");
                            }


                            // write queries for ALL fields combinations
                            $step = 1;

                            while ($step != $fields_num ) {
                                for ($i = 0; $i < $fields_num - $step; $i++) {
                                    $function_name = array();

                                    for ($s = $i; $s < $i + $step; $s++) {
                                        $function_name[] = $table_fields_data[$s];
                                    }

                                    for ($j = $i + $step; $j < $fields_num; $j++) {
                                        $function_name[] = $table_fields_data[$j];
                                        $fn_len = count($function_name);

                                        $select_query_string = "";
                                        $bind_value_string = "";
                                        $function_name_string = "";
                                        $function_params_string = "";

                                        for ($k = 0; $k < $fn_len; $k++) {
                                            $select_query_string .= lcfirst($table_name)
                                                                 . "." . $function_name[$k]['name']
                                                                 . " = :" . $function_name[$k]['name'];

                                            $bind_value_string .= "\t\t\t" . '$' . "query->bindValue("
                                                               . "':" . $function_name[$k]['name']
                                                               . "', " . '$'
                                                               . $function_name[$k]['name']
                                                               . ");\n";

                                            $function_name_string .= ucfirst($function_name[$k]['name']);

                                            $function_params_string .= '$' . $function_name[$k]['name'];

                                            if ($k != $fn_len - 1) {
                                                $select_query_string .= ", ";
                                                $function_params_string .= ", ";
                                            }
                                        }

                                        fwrite($output_script_file,
                                                "\n\tfunction get"
                                                . $table_name
                                                . "By");

                                        fwrite($output_script_file, $function_name_string);

                                        fwrite($output_script_file, "(");

                                        fwrite($output_script_file, $function_params_string);

                                        fwrite($output_script_file, ")\n\t{\n");

                                        fwrite($output_script_file, "\t\ttry {\n");

                                        fwrite($output_script_file, "\t\t\tglobal "
                                                                    . '$' . "conn;\n");

                                        fwrite($output_script_file, "\t\t\t" . '$' . "query = "
                                                                    . '$' . "conn->prepare("
                                                                    . "'SELECT * FROM "
                                                                    . lcfirst($table_name)
                                                                    . " WHERE "
                                                                    . $select_query_string
                                                                    . "');" . "\n");

                                        fwrite($output_script_file, $bind_value_string);

                                        fwrite($output_script_file, "\t\t\t" . '$' . "query->"
                                                                    . "execute();\n");

                                        fwrite($output_script_file, "\t\t\t" . '$'
                                                                    . "result = "
                                                                    . '$' . "query->"
                                                                    . "fetchAll();\n");

                                        fwrite($output_script_file, "\t\t\treturn "
                                                                    . '$'
                                                                    . "result;\n");

                                        fwrite($output_script_file, "\t\t} catch"
                                                                     . "(PDOException "
                                                                     . '$' . "e) {\n");

                                        fwrite($output_script_file, "\t\t\treturn null;\n"
                                                                    . "\t\t}\n");

                                        fwrite($output_script_file, "\t}\n");


                                        array_pop($function_name);
                                    }

                                }

                                $step++;
                            }

                            break;
                        }

                        $field_name = cutWordInBrackersInString($table_field);
                        $field_type = cutTypeFromString($table_field);

                        $field_data = array(
                            'name' => $field_name,
                            'type' => $field_type
                        );

                        $table_fields_data[] = $field_data;
                    }


                }

            }

        }

        if (!feof($db_file)) {
            echo "Error: unexpected fgets() fail\n";
        }
        fclose($db_file);

    }
