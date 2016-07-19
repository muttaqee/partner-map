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

  //$scripts_path = "C:\\xampp\htdocs\muttaqee-projects\\partner-map\\";
  // $scripts_path = "C:\\xampp\htdocs\sas_app\\";
  $scripts_path = "";

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

  // FIXME: Remove
  // Populate table, but insert partner_id value instead of partner_name
  function populateTableSpecial($table_name, $columns, $rows) {
    // foreach ($rows as $row)
  }

  // Populate table: partner_strength_ratings
  function populate_partner_strength_ratings() {
    global $scripts_path;

    $table_name = "partner_strength_ratings";
    $columns = array("primary_id", "lookup_id", "rating_id");

    // Execute and retrieve JSON rows from Python script (store into $rows)
    $prog = "C:\Python34\python";
    $script = $scripts_path . "read-partner-strength-ratings.py";
    $cmd = $prog . " " . $script;
    $rows = json_decode(shell_exec($cmd), true);
    echo("<pre>" . print_r($rows, $return = true) . "</pre>"); // FIXME: May remove

    // Prepare $rows array: replays key-value pairs so as to match table columns
    foreach ($rows as $key => $row) {
      // Replace "partner_name":<name> with "partner_id":<id>
      $sql = "
      SELECT id FROM partners_primary
      WHERE partners_primary.name LIKE \"" . $row["partner_name"] . "\"
      LIMIT 1
      ";
      $partner_id = mysql_result(query($sql, $sql, false), 0);
      $row["primary_id"] = $partner_id;
      unset($row['partner_name']);

      // Replace "partner_strength":<name> with "partner_strength_id":<id>
      $sql = "
      SELECT id FROM partner_strengths_lookup
      WHERE partner_strengths_lookup.name LIKE \"" . $row["partner_strength"] . "\"
      LIMIT 1
      ";
      $partner_strength_id = mysql_result(query($sql, $sql, false), 0);
      $row["lookup_id"] = $partner_strength_id;
      unset($row["partner_strength"]);

      // Replace "rating":<name> with "rating_id":<id>
      $sql = "
      SELECT id FROM ratings_simple_lookup
      WHERE ratings_simple_lookup.name LIKE \"" . $row["rating"] . "\"
      LIMIT 1
      ";
      $rating_id = mysql_result(query($sql, $sql, false), 0);
      $row["rating_id"] = $rating_id;
      unset($row["rating"]);

      $rows[$key] = $row;
    }

    populateTable($table_name, $columns, $rows);
  }

  // Populate table: partner_technology_ratings
  function populate_partner_technology_ratings() {
    global $scripts_path;

    $table_name = "partner_technology_ratings";
    $columns = array("primary_id", "lookup_id", "rating_id");

    // Execute and retrieve JSON rows from Python script (store into $rows)
    $prog = "C:\Python34\python";
    $script = $scripts_path . "read-partner-technology-ratings.py";
    $cmd = $prog . " " . $script;
    $rows = json_decode(shell_exec($cmd), true);
    if (!$rows) {
      report("<pre>Error: \$rows is empty. Make sure the script is only printing the desired result.</pre>");
    }

    // FIXME: Prepare $rows before passing to populateTable
    foreach ($rows as $key => $row) {
      $sql = "
      SELECT id FROM partners_primary
      WHERE partners_primary.name LIKE \"" . $row["partner_name"] . "\"
      LIMIT 1
      ";
      $partner_id = mysql_result(query($sql, $sql, false), 0);

      $sql = "
      SELECT id FROM technologies_lookup
      WHERE technologies_lookup.name LIKE \"" . $row["technology"] . "\"
      LIMIT 1
      ";
      $technology_id = mysql_result(query($sql, $sql, false), 0);

      // Replace "rating":<name> with "rating_id":<id>
      $sql = "
      SELECT id FROM ratings_simple_lookup
      WHERE ratings_simple_lookup.name LIKE \"" . $row["rating"] . "\"
      LIMIT 1
      ";
      $rating_id = mysql_result(query($sql, $sql, false), 0);

      $row["rating_id"] = $rating_id;
      $row["primary_id"] = $partner_id;
      $row["lookup_id"] = $technology_id;
      unset($row["rating"]);
      unset($row["partner_name"]);
      unset($row["technology"]);
      unset($row["technology_type"]);

      $rows[$key] = $row;
    }

    populateTable($table_name, $columns, $rows);
  }

  // Populate table: partner_solution_ratings
  function populate_partner_solution_ratings() {
    global $scripts_path;

    $table_name = "partner_solution_ratings";
    $columns = array("primary_id", "lookup_id", "rating_id");

    // Execute and retrieve JSON rows from Python script (store into $rows)
    $prog = "C:\Python34\python";
    $script = $scripts_path . "read-partner-solution-ratings.py";
    $cmd = $prog . " " . $script;
    $rows = json_decode(shell_exec($cmd), true);
    if (!$rows) {
      report("<pre>Error: \$rows is empty. Make sure the script is only printing the desired result.</pre>");
    }

    // FIXME: Prepare $rows before passing to populateTable
    foreach ($rows as $key => $row) {
      $sql = "
      SELECT id FROM partners_primary
      WHERE partners_primary.name LIKE \"" . $row["partner_name"] . "\"
      LIMIT 1
      ";
      $partner_id = mysql_result(query($sql, $sql, false), 0);

      $sql = "
      SELECT id FROM solutions_lookup
      WHERE solutions_lookup.name LIKE \"" . $row["solution"] . "\"
      LIMIT 1
      ";
      $solution_id = mysql_result(query($sql, $sql, false), 0);

      $sql = "
      SELECT id FROM ratings_simple_lookup
      WHERE ratings_simple_lookup.name LIKE \"" . $row["rating"] . "\"
      LIMIT 1
      ";
      $rating_id = mysql_result(query($sql, $sql, false), 0);

      $row["primary_id"] = $partner_id;
      $row["lookup_id"] = $solution_id;
      $row["rating_id"] = $rating_id;
      unset($row["partner_name"]);
      unset($row["solution"]);
      unset($row["solution_type"]);
      unset($row["rating"]);

      $rows[$key] = $row;
    }
    #echo("<pre>" . print_r($rows, $return = true) . "</pre>"); // FIXME: May remove

    populateTable($table_name, $columns, $rows);
  }

  // Populate table: partner_misc_ratings
  function populate_partner_misc_ratings() {
    global $scripts_path;

    $table_name = "partner_misc_ratings";
    $columns = array("primary_id", "lookup_id", "rating_id");

    // Execute and retrieve JSON rows from Python script (store into $rows)
    $prog = "C:\Python34\python";
    $script = $scripts_path . "read-partner-misc-ratings.py";
    $cmd = $prog . " " . $script;
    $rows = json_decode(shell_exec($cmd), true);
    if (!$rows) {
      report("<pre>Error: \$rows is empty. Make sure the script is only printing the desired result.</pre>");
    }

    // FIXME: Prepare $rows before passing to populateTable
    foreach ($rows as $key => $row) {
      $sql = "
      SELECT id FROM partners_primary
      WHERE partners_primary.name LIKE \"" . $row["partner_name"] . "\"
      LIMIT 1
      ";
      $partner_id = mysql_result(query($sql, $sql, false), 0);

      $sql = "
      SELECT id FROM misc_lookup
      WHERE misc_lookup.name LIKE \"" . $row["misc_type"] . "\"
      LIMIT 1
      ";
      $misc_id = mysql_result(query($sql, $sql, false), 0);

      $sql = "
      SELECT id FROM ratings_simple_lookup
      WHERE ratings_simple_lookup.name LIKE \"" . $row["rating"] . "\"
      LIMIT 1
      ";
      $rating_id = mysql_result(query($sql, $sql, false), 0);

      $row["primary_id"] = $partner_id;
      $row["lookup_id"] = $misc_id;
      $row["rating_id"] = $rating_id;
      unset($row["partner_name"]);
      unset($row["misc_type"]);
      unset($row["rating"]);

      $rows[$key] = $row;
    }
    # echo("<pre>" . print_r($rows, $return = true) . "</pre>"); // FIXME: May remove
    populateTable($table_name, $columns, $rows);
  }

  // Populate table: partner_vertical_junction
  function populate_partner_vertical_junction() {
    global $scripts_path;

    $table_name = "partner_vertical_junction";
    $columns = array("primary_id", "lookup_id");

    // Execute and retrieve JSON rows from Python script (store into $rows)
    $prog = "C:\Python34\python";
    $script = $scripts_path . "read-partner-vertical-junction.py";
    $cmd = $prog . " " . $script;
    $rows = json_decode(shell_exec($cmd), true);
    if (!$rows) {
      report("<pre>Error: \$rows is empty. Make sure the script is only printing the desired result.</pre>");
    }

    // FIXME: Prepare $rows before passing to populateTable
    foreach ($rows as $key => $row) {
      $sql = "
      SELECT id FROM partners_primary
      WHERE partners_primary.name LIKE \"" . $row["partner_name"] . "\"
      LIMIT 1
      ";
      $partner_id = mysql_result(query($sql, $sql, false), 0);

      $sql = "
      SELECT id FROM verticals_lookup
      WHERE verticals_lookup.name LIKE \"" . $row["vertical"] . "\"
      LIMIT 1
      ";
      $vertical_id = mysql_result(query($sql, $sql, false), 0);

      $row["primary_id"] = $partner_id;
      $row["lookup_id"] = $vertical_id;
      unset($row["partner_name"]);
      unset($row["vertical"]);

      $rows[$key] = $row;
    }
    # echo("<pre>" . print_r($rows, $return = true) . "</pre>"); // FIXME: May remove
    populateTable($table_name, $columns, $rows);
  }

  // Populate table: partner_region_junction
  function populate_partner_region_junction() {
    global $scripts_path;

    $table_name = "partner_region_junction";
    $columns = array("primary_id", "lookup_id");

    // Execute and retrieve JSON rows from Python script (store into $rows)
    $prog = "C:\Python34\python";
    $script = $scripts_path . "read-partner-region-junction.py";
    $cmd = $prog . " " . $script;
    $rows = json_decode(shell_exec($cmd), true);
    if (!$rows) {
      report("<pre>Error: \$rows is empty. Make sure the script is only printing the desired result.</pre>");
    }
    echo("<pre>" . print_r($rows, $return = true) . "</pre>"); // FIXME: May remove

    // FIXME: Prepare $rows before passing to populateTable
    foreach ($rows as $key => $row) {
      $sql = "
      SELECT id FROM partners_primary
      WHERE partners_primary.name LIKE \"" . $row["partner_name"] . "\"
      LIMIT 1
      ";
      $partner_id = mysql_result(query($sql, $sql, false), 0);

      $sql = "
      SELECT id FROM regions_lookup
      WHERE regions_lookup.name LIKE \"" . $row["region"] . "\"
      LIMIT 1
      ";
      $region_id = mysql_result(query($sql, $sql, false), 0);

      $row["primary_id"] = $partner_id;
      $row["lookup_id"] = $region_id;
      unset($row["partner_name"]);
      unset($row["region"]);

      $rows[$key] = $row;
    }
    echo("<pre>" . print_r($rows, $return = true) . "</pre>"); // FIXME: May remove
    populateTable($table_name, $columns, $rows);
  }

  // Populate table: consultants
  function populate_consultants() {
    global $scripts_path;

    $table_name = "consultants_primary";
    # FIXME: Temporarily ignoring some columns
    # $columns = array("id", "first_name", "last_name", "rating", "is_rejected");
    $columns = array("last_name", "rating_id", "is_rejected");

    // Execute and retrieve JSON rows from Python script (store into $rows)
    $prog = "C:\Python34\python";
    $script = $scripts_path . "read-consultants.py";
    $cmd = $prog . " " . $script;
    $rows = json_decode(shell_exec($cmd), true);
    if (!$rows) {
      report("<pre>Error: \$rows is empty. Make sure the script is only printing the desired result.</pre>");
    }
    echo("<pre>" . print_r($rows, $return = true) . "</pre>"); // FIXME: May remove

    // Remember rating id for "No rating"
    $sql = "
    SELECT id FROM ratings_lookup
    WHERE ratings_lookup.name LIKE \"" . "No rating" . "\"
    LIMIT 1
    ";
    $no_rating_id = mysql_result(query($sql, $sql, false), 0);

    foreach ($rows as $key => $row) {
      $row["is_rejected"] = 0; # FIXME: Temporary

      if ($row["rating"] == null || $row["rating"] == "null") {
        $row["rating_id"] = $no_rating_id;
      } else {
        $sql = "
        SELECT id FROM ratings_lookup
        WHERE ratings_lookup.name LIKE \"" . $row["rating"] . "\"
        LIMIT 1
        ";
        $rating_id = mysql_result(query($sql, $sql, false), 0);
        $row["rating_id"] = $rating_id;
      }
      unset($row["rating"]);

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
        } else if ($columns[$i] == "rating_id" && $val == "null") {
          $vals_string .= "\"" . $no_rating_id . "\",";
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
    global $scripts_path;

    $table_name = "consultant_ratings";
    $columns = array("primary_id", "lookup_id", "rating_id");

    // Execute and retrieve JSON rows from Python script (store into $rows)
    $prog = "C:\Python34\python";
    $script = $scripts_path . "read-consultant-ratings.py";
    $cmd = $prog . " " . $script;
    $rows = json_decode(shell_exec($cmd), true);
    if (!$rows) {
      report("<pre>Error: \$rows is empty. Make sure the script is only printing the desired result.</pre>");
    }
    echo("<pre>" . print_r($rows, $return = true) . "</pre>"); // FIXME: May remove

    // Remember rating id for "No rating"
    $sql = "
    SELECT id FROM ratings_simple_lookup
    WHERE ratings_simple_lookup.name LIKE \"" . "No rating" . "\"
    LIMIT 1
    ";
    $no_rating_id = mysql_result(query($sql, $sql, false), 0);

    // FIXME: Prepare $rows before passing to populateTable
    foreach ($rows as $key => $row) {
      $sql = "
      SELECT id FROM consultants_primary
      WHERE consultants_primary.last_name LIKE \"" . $row["last_name"] . "\"
      LIMIT 1
      ";
      $consultant_id = mysql_result(query($sql, $sql, false), 0);

      $sql = "
      SELECT id FROM consultant_rating_areas_lookup
      WHERE consultant_rating_areas_lookup.name LIKE \"" . $row["area"] . "\"
      LIMIT 1
      ";
      $area_id = mysql_result(query($sql, $sql, false), 0);

      $sql = "
      SELECT id FROM ratings_simple_lookup
      WHERE ratings_simple_lookup.name LIKE \"" . $row["rating"] . "\"
      LIMIT 1
      ";
      $rating_id = mysql_result(query($sql, $sql, false), 0);

      $row["primary_id"] = $consultant_id;
      $row["lookup_id"] = $area_id;
      if ($rating_id == "" || $rating_id === null) {
        $row["rating_id"] = $no_rating_id;
      } else {
        $row["rating_id"] = $rating_id;
      }
      unset($row["last_name"]);
      unset($row["area"]);
      unset($row["rating"]);

      $rows[$key] = $row;
    }
    # echo("<pre>" . print_r($rows, $return = true) . "</pre>"); // FIXME: May remove
    populateTable($table_name, $columns, $rows);
  }

  // Populate table: consultant_partner_junction
  function populate_consultant_partner_junction() {
    global $scripts_path;

    $table_name = "consultant_partner_junction";
    $columns = array("consultant_id", "partner_id");

    // Execute and retrieve JSON rows from Python script (store into $rows)
    $prog = "C:\Python34\python";
    $script = $scripts_path . "read-consultant-partner-junction.py";
    $cmd = $prog . " " . $script;
    $rows = json_decode(shell_exec($cmd), true);
    if (!$rows) {
      report("<pre>Error: \$rows is empty. Make sure the script is only printing the desired result.</pre>");
    }

    // FIXME: Prepare $rows before passing to populateTable
    foreach ($rows as $key => $row) {
      $sql = "
      SELECT id FROM partners_primary
      WHERE partners_primary.name LIKE '%" . $row["partner_name"] . "%'
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
      SELECT id FROM consultants_primary
      WHERE consultants_primary.last_name LIKE '%" . $row["consultant_name"] . "%'
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

  // Populate table: partners
  function populate_partners() {
    // Execute and retrieve JSON rows from Python script (store into $rows)
    $prog = "C:\Python34\python";
    $script = "read-partners-pass1.py";
    $cmd = $prog . " " . $script;
    $result = json_decode(shell_exec($cmd), true);
    echo("<pre>" . print_r($result, $return = true) . "</pre>"); // FIXME: May remove


    // Store each row to the database
    foreach ($result as $key => $row) {
      # FIXME. Can remove this. Printing row id.
      echo "<pre>" . $key . "</pre>";

      // Remember column names (used for query string construction)
      $columns = array();
      foreach ($row as $key => $value) {
        array_push($columns, $key);
      }
      // Construct query in this format:
      // "INSERT INTO table (col1,...,colN) VALUES ('val1',...,'valN')"
      // NOTE: All non-null values are encased in single quotes

      // Columns
      $query = "INSERT INTO partners_primary (";
      $size = count($columns);
      for ($i = 0; $i < $size-1; $i++) {
        $query .= "$columns[$i],";
      }
      // Last column (no comma)
      $query .= $columns[$size-1];
      $query .= ") VALUES (";
      // Values
      for ($i = 0; $i < $size-1; $i++) {
        $value = $row[$columns[$i]];
        echo "<pre>" . $columns[$i] . ": " . $value . "</pre>"; // FIXME. Remove this.
        if ($columns[$i] == "is_partner_plus") {
          $query .= ($value ? 1 : 0) . ",";
        } else if ($value) {
          $query .= "\"" . $value . "\"" . ",";
        } else {
          $query .= "null,";
        }
      }
      // Last value (no comma)
      $value = $row[$columns[$size-1]];
      if ($columns[$i] == "is_partner_plus") {
        #$query .= ($value ? 1 : 0) . ",";
        $query .= ($value ? 1 : 0); // FIXME. Value assigned - BIT(1)
      } else if ($value) {
        $query .= "\"" . $value . "\"";
      } else {
        $query .= "null";
      }
      $query .= ")";
      echo("<pre>" . $query . "</pre>"); # FIXME remove this test

      query($query, $query, false);

      // Send query
      // if (!mysql_query($query, $link)) {
      //   echo("<pre>FAILURE. Could not store a row to the database: " . mysql_error() . "\n</pre>");
      // } else {
      //   echo("<pre>SUCCESS!\n</pre>");
      // }
    }

  }

  // Populate tables that do no need to be read from the workbook
  function populateTables() {
    // populate_partners(); # FIXME: Uncomment (works)
    // populate_partner_strength_ratings(); # FIXME: Uncomment (works)
    // populate_partner_technology_ratings(); # FIXME: Uncomment (works)
    // populate_partner_solution_ratings(); # FIXME: Uncomment (works)
    // populate_partner_misc_ratings(); # FIXME: Uncomment (works)
    // populate_partner_vertical_junction(); # FIXME: Uncomment (works)
    // populate_partner_region_junction(); # FIXME: Uncomment (works)
    // populate_consultants(); # FIXME: MAKESHIFT INSERTS - whole name stored in last_name, and is_rejected set to 0 for all rows
    // populate_consultant_ratings();# FIXME: Depends on MAKESHIFT consultants table
    // populate_consultant_partner_junction(); # FIXME: Used MAKESHIFT consultants table; not all partner ids found in partners
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
