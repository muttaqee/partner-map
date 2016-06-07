<?php

  /*
  This script populates a set of tables for use by the Partner Map.
  It is only intended to be used as a one-time setup.

  FIXME: Find all FIXME tags and fix them before removing this line.
  */

  /*
  TABLES TO POPULATE
  (Tables having more indentation reference one or more tables having less
  indentation, and must be populated after them)

  partner_strength_ratings
  partner_technology_ratings
  partner_solution_ratings
  partner_misc_ratings

  partner_vertical_junction
  partner_region_junction

  consultants
    consultant_partner_junction
    consultant_ratings

  partners (pass 2 only)
  */

  // For reporting larger actions to DOM (webpage)
  function report($string) {
    echo "<pre>" . $string . "</pre>";
  }

  // For reporting smaller actions to DOM (webpage)
  function smallReport($string) {
    report("-- " . $string);
  }

  // Connection variables
  $db_host = "localhost:7860";
  $db_user = "root";
  $db_pass = "password";
  $db_name = "partner_map_db";

  // Connect to MySQL server
  function connect() {
    global $db_host, $db_user, $db_pass, $db_name, $link;
    $link = mysql_connect($db_host, $db_user, $db_pass);
    if (!$link) {
      die("Could not connect to server: " . mysql_error());
    } else {
      report("Connected to $db_host");
    }
    mysql_select_db($db_name) or die("Could not select database $db_name: " . mysql_error());
  }

  // Close server connection
  function disconnect() {
    global $link;
    mysql_close($link);
  }

  // Submit query; report or die on failure
  function query($query_string, $success_message, $die_on_failure) {
    global $link;
    $result = mysql_query($query_string, $link);
    if (!$result) {
      if ($die_on_failure) {
        die("Terminating. Error: " . mysql_error());
      } else {
        report("Error: " . mysql_error());
      }
    } else if ($success_message) {
      smallReport($success_message);
      return $result;
    }
  }

  // Helper: inserts single value into a one-column table
  function populate1TupleTable($table_name, $value_array) {
    foreach ($value_array as $value) {
      // Construct and submit query
      $sql = "INSERT INTO $table_name VALUES (\"$value\")";
      query($sql, "Populated $table_name table: $sql", false);
    }
  }

  // Populate table with complication: must insert partners.id instead of partner_name
  // function populateTableSpecial($table_name, $columns, $rows) {
  //   $row_width = count($columns);
  //   foreach ($rows as $row) {
  //     $cols_string = "";
  //     $vals_string = "";
  //     for ($i = 0; $i < $row_width-1; $i++) {
  //       if ($i == 0) {
  //         $cols_string .= "partner_id,";
  //       } else {
  //         $cols_string .= $columns[$i] . ",";
  //       }
  //       if ($i == 0) {
  //         $vals_string .= "\"" . $row[$columns[$i]] . "\",";
  //       } else {
  //         $sql = "
  //         SELECT id
  //         FROM partners
  //         WHERE partners.official_name LIKE \"" . $row["partner_name"] . "\"
  //         LIMIT 1
  //         ";
  //         $vals_string .= "\"" . mysql_result(query($sql, $sql, false), 0) . "\",";
  //       }
  //     }
  //     $cols_string .= $columns[$row_width-1]; # Fixme: Remove
  //     $vals_string .= "\"" . $row[$columns[$row_width-1]] . "\""; # FIXME: Remove
  //     // if ($i == 0) {
  //     //   $vals_string .= "\"" . $row[$columns[$row_width-1]] . "\",";
  //     // } else {
  //     //   $sql = "
  //     //   SELECT id
  //     //   FROM partners
  //     //   WHERE partners.official_name LIKE \"" . $row["partner_name"] . "\"
  //     //   LIMIT 1
  //     //   ";
  //     //   $vals_string .= "\"" . mysql_result(query($sql, $sql, false), 0) . "\",";
  //     // }
  //     $sql = "INSERT INTO $table_name ($cols_string) VALUES ($vals_string)";
  //     query($sql, $sql, false);
  //   }
  // }

  // Helper: insert values into a table
  // $columns: an indexed array of column names
  // $rows: an indexed array of rows, where each row is an associative array of
  // column-name=>column-value pairs.
  function populateTable($table_name, $columns, $rows) {
    $row_width = count($columns);
    foreach ($rows as $row) {
      $cols_string = "";
      $vals_string = "";
      for ($i = 0; $i < $row_width-1; $i++) {
        $cols_string .= $columns[$i] . ",";
        $vals_string .= "\"" . $row[$columns[$i]] . "\",";
      }
      $cols_string .= $columns[$row_width-1];
      $vals_string .= "\"" . $row[$columns[$row_width-1]] . "\"";
      $sql = "INSERT INTO $table_name ($cols_string) VALUES ($vals_string)";
      query($sql, $sql, false);
    }
  }

  // Populate table, but insert partner_id value instead of partner_name
  function populateTableSpecial($table_name, $columns, $rows) {
    // foreach ($rows as $row)
  }

  // Populate table: populate_partner_strength_ratings
  function populate_partner_strength_ratings() {
    $table_name = "partner_strength_ratings";
    $columns = array("partner_id", "strength", "rating");

    // Execute and retrieve JSON rows from Python script (store into $rows)
    $prog = "C:\Python34\python";
    $script = "C:\\xampp\htdocs\muttaqee-projects\\partner-map\\read-partner-strength-ratings.py";
    $cmd = $prog . " " . $script;
    $rows = json_decode(shell_exec($cmd), true);
    if (!$rows) {
      echo("<pre>result is empty</pre>");
    }
    echo("<pre>" . print_r($rows, $return = true) . "</pre>");

    # FIXME: Document what occurs below

    foreach ($rows as $key => $row) {
      $sql = "
        SELECT id FROM partners
        WHERE partners.official_name LIKE \"" . $row["partner_name"] . "\"
        LIMIT 1
      ";
      $partner_id = mysql_result(query($sql, $sql, false), 0);
      $row["partner_id"] = $partner_id;
      unset($row['partner_name']);
      $rows[$key] = $row;
    }
    populateTable($table_name, $columns, $rows);
  }

  // Populate tables that do no need to be read from the workbook
  function populateTables() {
    // populate_partners(); # FIXME: Uncomment (this works)
    // populate_partner_strength_ratings(); # FIXME: Uncomment (this works)
    // populate_partner_technology_ratings();
    // populate_partner_solution_ratings();
    // populate_partner_misc_ratings();
    // populate_partner_vertical_junction();
    // populate_partner_region_junction();
    // populate_consultants();
    // populate_consultant_ratings();
    // populate_consultant_partner_junction();
  }

  // Main function
  function execute() {
    connect();
    populateTables();
    disconnect();
  }

  // Main function call
  execute();
?>
