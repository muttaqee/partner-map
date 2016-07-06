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
  $config = include('config\config.php');
  $db_host = $config["host"];
  $db_user = $config["username"];
  $db_pass = $config["password"];
  $db_name = $config["database"];

  // Connect to MySQL server
  function connect() {
    global $db_host, $db_user, $db_pass, $db_name, $link; // FIXME: Declared correctly? ($link, etc)
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
      if ($die_on_failure) { // FIXME: Have better error handling than this
        die("Terminating. Error: " . mysql_error());
      } else {
        report("Error: " . mysql_error());
      }
    } else if ($success_message) {
      smallReport($success_message);
      return $result;
    }
  }

  // Helper: checks if value is 0 or 1
  function isBoolean($val) {
    return ($val == 0 || $val == 1);
  }

  // Helper: inserts single value into a one-column table
  function populate1TupleTable($table_name, $value_array) {
    foreach ($value_array as $value) {
      // Construct and submit query
      $sql = "INSERT INTO $table_name VALUES (\"$value\")";
      query($sql, "Populated $table_name table: $sql", false);
    }
  }

  // Helper: insert values into a table
  // $columns: an indexed array of column names
  // $rows: an indexed array of rows, where each row is an associative array of
  // column-name=>column-value pairs.
  function populateTable($table_name, $columns, $rows) {
    $row_width = count($columns);
    foreach ($rows as $row) {
      // Build columns and values lists for query
      $cols_string = "";
      $vals_string = "";
      for ($i = 0; $i < $row_width; $i++) {
        $cols_string .= $columns[$i] . ",";
        $vals_string .= "\"" . $row[$columns[$i]] . "\",";
      }
      // Remove trailing commas
      $cols_string = rtrim($cols_string, ",");
      $vals_string = rtrim($vals_string, ",");
      // Send query
      $sql = "INSERT INTO $table_name ($cols_string) VALUES ($vals_string)";
      query($sql, $sql, false);
    }
  }

  // Populate table, but insert partner_id value instead of partner_name
  function populateTableSpecial($table_name, $columns, $rows) {
    // foreach ($rows as $row)
  }

  // Populate table: partner_strength_ratings
  function populate_partner_strength_ratings() {
    $table_name = "partner_strength_ratings";
    $columns = array("partner_id", "strength", "rating");

    // Execute and retrieve JSON rows from Python script (store into $rows)
    $prog = "C:\Python34\python";
    $script = "C:\\xampp\htdocs\sas_app\\read-partner-strength-ratings.py";
    $cmd = $prog . " " . $script;
    $rows = json_decode(shell_exec($cmd), true);
    echo("<pre>" . print_r($rows, $return = true) . "</pre>"); // FIXME: May remove

    // Prepare $rows array: replace "partner_name" with "partner_id"
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

  // Populate table: partner_technology_ratings
  function populate_partner_technology_ratings() {
    $table_name = "partner_technology_ratings";
    $columns = array("partner_id", "technology_id", "rating");

    // Execute and retrieve JSON rows from Python script (store into $rows)
    $prog = "C:\Python34\python";
    $script = "C:\\xampp\htdocs\sas_app\\read-partner-technology-ratings.py";
    $cmd = $prog . " " . $script;
    $rows = json_decode(shell_exec($cmd), true);
    if (!$rows) {
      report("<pre>Error: \$rows is empty. Make sure the script is only printing the desired result.</pre>");
    }

    // FIXME: Prepare $rows before passing to populateTable
    foreach ($rows as $key => $row) {
      $sql = "
      SELECT id FROM partners
      WHERE partners.official_name LIKE \"" . $row["partner_name"] . "\"
      LIMIT 1
      ";
      $partner_id = mysql_result(query($sql, $sql, false), 0);

      $sql = "
      SELECT id FROM technologies
      WHERE technologies.technology LIKE \"" . $row["technology"] . "\"
      LIMIT 1
      ";
      $technology_id = mysql_result(query($sql, $sql, false), 0);

      $row["partner_id"] = $partner_id;
      $row["technology_id"] = $technology_id;
      unset($row["partner_name"]);
      unset($row["technology"]);
      unset($row["technology_type"]);

      $rows[$key] = $row;
    }

    populateTable($table_name, $columns, $rows);
  }

  // Populate table: partner_solution_ratings
  function populate_partner_solution_ratings() {
    $table_name = "partner_solution_ratings";
    $columns = array("partner_id", "solution_id", "rating");

    // Execute and retrieve JSON rows from Python script (store into $rows)
    $prog = "C:\Python34\python";
    $script = "C:\\xampp\htdocs\sas_app\\read-partner-solution-ratings.py";
    $cmd = $prog . " " . $script;
    $rows = json_decode(shell_exec($cmd), true);
    if (!$rows) {
      report("<pre>Error: \$rows is empty. Make sure the script is only printing the desired result.</pre>");
    }

    // FIXME: Prepare $rows before passing to populateTable
    foreach ($rows as $key => $row) {
      $sql = "
      SELECT id FROM partners
      WHERE partners.official_name LIKE \"" . $row["partner_name"] . "\"
      LIMIT 1
      ";
      $partner_id = mysql_result(query($sql, $sql, false), 0);

      $sql = "
      SELECT id FROM solutions
      WHERE solutions.solution LIKE \"" . $row["solution"] . "\"
      LIMIT 1
      ";
      $solution_id = mysql_result(query($sql, $sql, false), 0);

      $row["partner_id"] = $partner_id;
      $row["solution_id"] = $solution_id;
      unset($row["partner_name"]);
      unset($row["solution"]);
      unset($row["solution_type"]);

      $rows[$key] = $row;
    }
    #echo("<pre>" . print_r($rows, $return = true) . "</pre>"); // FIXME: May remove

    populateTable($table_name, $columns, $rows);
  }

  // Populate table: partner_misc_ratings
  function populate_partner_misc_ratings() {
    $table_name = "partner_misc_ratings";
    $columns = array("partner_id", "misc_type", "rating");

    // Execute and retrieve JSON rows from Python script (store into $rows)
    $prog = "C:\Python34\python";
    $script = "C:\\xampp\htdocs\sas_app\\read-partner-misc-ratings.py";
    $cmd = $prog . " " . $script;
    $rows = json_decode(shell_exec($cmd), true);
    if (!$rows) {
      report("<pre>Error: \$rows is empty. Make sure the script is only printing the desired result.</pre>");
    }

    // FIXME: Prepare $rows before passing to populateTable
    foreach ($rows as $key => $row) {
      $sql = "
      SELECT id FROM partners
      WHERE partners.official_name LIKE \"" . $row["partner_name"] . "\"
      LIMIT 1
      ";
      $partner_id = mysql_result(query($sql, $sql, false), 0);

      $row["partner_id"] = $partner_id;
      unset($row["partner_name"]);

      $rows[$key] = $row;
    }
    # echo("<pre>" . print_r($rows, $return = true) . "</pre>"); // FIXME: May remove
    populateTable($table_name, $columns, $rows);
  }

  // Populate table: partner_vertical_junction
  function populate_partner_vertical_junction() {
    $table_name = "partner_vertical_junction";
    $columns = array("partner_id", "vertical");

    // Execute and retrieve JSON rows from Python script (store into $rows)
    $prog = "C:\Python34\python";
    $script = "C:\\xampp\htdocs\sas_app\\read-partner-vertical-junction.py";
    $cmd = $prog . " " . $script;
    $rows = json_decode(shell_exec($cmd), true);
    if (!$rows) {
      report("<pre>Error: \$rows is empty. Make sure the script is only printing the desired result.</pre>");
    }

    // FIXME: Prepare $rows before passing to populateTable
    foreach ($rows as $key => $row) {
      $sql = "
      SELECT id FROM partners
      WHERE partners.official_name LIKE \"" . $row["partner_name"] . "\"
      LIMIT 1
      ";
      $partner_id = mysql_result(query($sql, $sql, false), 0);

      $row["partner_id"] = $partner_id;
      unset($row["partner_name"]);

      $rows[$key] = $row;
    }
    # echo("<pre>" . print_r($rows, $return = true) . "</pre>"); // FIXME: May remove
    populateTable($table_name, $columns, $rows);
  }

  // Populate table: partner_region_junction
  function populate_partner_region_junction() {
    $table_name = "partner_region_junction";
    $columns = array("partner_id", "region");

    // Execute and retrieve JSON rows from Python script (store into $rows)
    $prog = "C:\Python34\python";
    $script = "C:\\xampp\htdocs\sas_app\\read-partner-region-junction.py";
    $cmd = $prog . " " . $script;
    $rows = json_decode(shell_exec($cmd), true);
    if (!$rows) {
      report("<pre>Error: \$rows is empty. Make sure the script is only printing the desired result.</pre>");
    }

    // FIXME: Prepare $rows before passing to populateTable
    foreach ($rows as $key => $row) {
      $sql = "
      SELECT id FROM partners
      WHERE partners.official_name LIKE \"" . $row["partner_name"] . "\"
      LIMIT 1
      ";
      $partner_id = mysql_result(query($sql, $sql, false), 0);

      $row["partner_id"] = $partner_id;
      unset($row["partner_name"]);

      $rows[$key] = $row;
    }
    #echo("<pre>" . print_r($rows, $return = true) . "</pre>"); // FIXME: May remove
    populateTable($table_name, $columns, $rows);
  }

  // Populate table: consultants
  function populate_consultants() {
    $table_name = "consultants";
    # FIXME: Temporarily ignoring some columns
    # $columns = array("id", "first_name", "last_name", "rating", "is_rejected");
    $columns = array("last_name", "rating", "is_rejected");

    // Execute and retrieve JSON rows from Python script (store into $rows)
    $prog = "C:\Python34\python";
    $script = "C:\\xampp\htdocs\sas_app\\read-consultants.py";
    $cmd = $prog . " " . $script;
    $rows = json_decode(shell_exec($cmd), true);
    if (!$rows) {
      report("<pre>Error: \$rows is empty. Make sure the script is only printing the desired result.</pre>");
    }

    foreach ($rows as $key => $row) {
      $row["is_rejected"] = 0;
      $rows[$key] = $row;
    }
    echo("<pre>" . print_r($rows, $return = true) . "</pre>"); // FIXME: May remove

    $row_width = count($columns);
    foreach ($rows as $row) {
      $cols_string = "";
      $vals_string = "";
      for ($i = 0; $i < $row_width; $i++) {
        // Build columns and values lists to put into query
        // COLUMN
        $cols_string .= $columns[$i] . ",";
        // VALUE
        $val = $row[$columns[$i]];
        // FIXME: Temporary fix to issue of passing 1-BIT value (0 or 1)
        if ($columns[$i] == "is_rejected") {
          $vals_string .= "0,";
        } else if ($columns[$i] == "rating" && $val == "null") {
          $vals_string .= "\"No rating\",";
        } else {
          $vals_string .= "\"" . $row[$columns[$i]] . "\",";
        }
      }
      // Remove trailing commas
      $cols_string = rtrim($cols_string, ",");
      $vals_string = rtrim($vals_string, ",");

      # Query here (need to pass 0/1 values as integers, not string)
      $sql = "INSERT INTO $table_name ($cols_string) VALUES ($vals_string)";
      query($sql, $sql, false);
    }
  }

  // Populate table: consultant_ratings
  function populate_consultant_ratings() {
    $table_name = "consultant_ratings";
    $columns = array("consultant_id", "area", "rating");

    // Execute and retrieve JSON rows from Python script (store into $rows)
    $prog = "C:\Python34\python";
    $script = "C:\\xampp\htdocs\sas_app\\read-consultant-ratings.py";
    $cmd = $prog . " " . $script;
    $rows = json_decode(shell_exec($cmd), true);
    if (!$rows) {
      report("<pre>Error: \$rows is empty. Make sure the script is only printing the desired result.</pre>");
    }

    // FIXME: Prepare $rows before passing to populateTable
    foreach ($rows as $key => $row) {
      $sql = "
      SELECT id FROM consultants
      WHERE consultants.last_name LIKE \"" . $row["last_name"] . "\"
      LIMIT 1
      ";
      $consultant_id = mysql_result(query($sql, $sql, false), 0);

      $row["consultant_id"] = $consultant_id;
      unset($row["last_name"]);

      $rows[$key] = $row;
    }
    # echo("<pre>" . print_r($rows, $return = true) . "</pre>"); // FIXME: May remove
    populateTable($table_name, $columns, $rows);
  }

  // Populate table: consultant_partner_junction
  function populate_consultant_partner_junction() {
    $table_name = "consultant_partner_junction";
    $columns = array("consultant_id", "partner_id");

    // Execute and retrieve JSON rows from Python script (store into $rows)
    $prog = "C:\Python34\python";
    $script = "C:\\xampp\htdocs\sas_app\\read-consultant-partner-junction.py";
    $cmd = $prog . " " . $script;
    $rows = json_decode(shell_exec($cmd), true);
    if (!$rows) {
      report("<pre>Error: \$rows is empty. Make sure the script is only printing the desired result.</pre>");
    }

    // FIXME: Prepare $rows before passing to populateTable
    foreach ($rows as $key => $row) {
      $sql = "
      SELECT id FROM partners
      WHERE partners.official_name LIKE '%" . $row["partner_name"] . "%'
      LIMIT 1
      ";
      $partner_id = mysql_result(query($sql, $sql, false), 0);
      // Reporting
      if ($partner_id) {
        smallReport($row["partner_name"] . " matched to " . $partner_id);
      } else {
        smallReport("Error: no match for " . $row["partner_name"]);
      }

      $sql = "
      SELECT id FROM consultants
      WHERE consultants.last_name LIKE '%" . $row["consultant_name"] . "%'
      LIMIT 1
      ";
      $consultant_id = mysql_result(query($sql, $sql, false), 0);
      // Reporting
      if ($consultant_id) {
        smallReport($row["consultant_name"] . " matched to " . $consultant_id);
      } else {
        smallReport("Error: No match for " . $row["consultant_name"]);
      }

      $row["partner_id"] = $partner_id;
      $row["consultant_id"] = $consultant_id;
      unset($row["partner_name"]);
      unset($row["consultant_name"]);

      $rows[$key] = $row;
    }
    echo("<pre>" . print_r($rows, $return = true) . "</pre>"); // FIXME: May remove

    populateTable($table_name, $columns, $rows);
  }

  // Populate tables that do no need to be read from the workbook
  function populateTables() {
    // populate_partners(); # FIXME: use separate PHP file, write-partners-pass1.php - for this
    //populate_partner_strength_ratings(); # FIXME: Uncomment (works)
    //populate_partner_technology_ratings(); # FIXME: Uncomment (works)
    //populate_partner_solution_ratings(); # FIXME: Uncomment (works)
    //populate_partner_misc_ratings(); # FIXME: Uncomment (works)
    //populate_partner_vertical_junction(); # FIXME: Uncomment (works)
    //populate_partner_region_junction(); # FIXME: Uncomment (works)
    //populate_consultants(); # FIXME: MAKESHIFT INSERTS - whole name stored in last_name, and is_rejected set to 0 for all rows
    //populate_consultant_ratings();# FIXME: Depends on MAKESHIFT consultants table
    //populate_consultant_partner_junction(); # FIXME: Used MAKESHIFT consultants table; not all partner ids found in partners
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
