<?php

  /*
  This script creates a database and a set of new tables for use by the
  Partner Map. Most of the created tables are empty.
  It is only intended to be used as a one-time setup.

  !!! NOTE: It first removes any database and tables having the same names
  without any warning.
  Don't execute this without first saving important data!

  FIXME: Find all FIXME tags and fix them before removing this line.
  */

  /*
  TABLES TO CREATE
  (Tables having more indentation reference one or more tables having less
  indentation, and must be created after them)

    ratings
    ratings_simple

    partners
      partner_strengths_ratings - FIXME: split into two tables, as with others?

    technologies
      partner_technology_ratings
    solutions
      partner_solution_ratings
    misc - FIXME: rename, and rename partner_misc_ratings?
      partner_misc_ratings

    verticals
      partner_vertical_junction
    geographical_regions
      partner_region_junction

    consultants
    consultant_rating_areas
      consultant_ratings

    customers - FIXME: deal with later; may not implement

    oppotunity_statuses
      opportunities - FIXME: MAY reference or be referenced by customers

        opportunity_partner_junction
        opportunity_consultant_junction
        consultant_partner_junction
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
    global $db_host, $db_user, $db_pass, $link;
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
    if (!mysql_query($query_string, $link)) {
      if ($die_on_failure) {
        die("Terminating. Error: " . mysql_error());
      } else {
        report("Error: " . mysql_error());
      }
    } else if ($success_message) {
      smallReport($success_message);
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

  # FIXME: REMOVE UNNEEDED POPULATE TABLE FUNCTIONS

  // Populate table: ratings
  function populate_ratings() {
    $table_name = "ratings";
    $rows = array(
      "A+", "A", "A-",
      "B+", "B", "B-",
      "C+", "C", "C-",
      "D+", "D", "D-"
    );
    populate1TupleTable($table_name, $rows);
  }

  // Populate table: ratings_simple
  function populate_ratings_simple() {
    $table_name = "ratings_simple";
    $rows = array("A", "B", "C", "D");
    populate1TupleTable($table_name, $rows);
  }

  // Populate table: partner_strengths
  function populate_partner_strengths() {
    $table_name = "partner_strengths";
    $rows = array(
      "Technical - Quality",
      "Financial Rate Negotiation",
      "Process & Training",
      "Political - SAS/Customer",
      "Social - Responsive"
    );
    populate1TupleTable($table_name, $rows);
  }

  // Populate table: technologies
  function populate_technologies() {
    $table_name = "technologies";
    $columns = array("technology_type", "technology");
    $rows = array(
      // Each row is in the form:
      // col1 name => value1, col2 name => value2, ...
      array($columns[0]=>"Hadoop", $columns[1]=>"Data Loader for Hadoop"),

      array($columns[0]=>"Analytics", $columns[1]=>"Enterprise Miner"),
      array($columns[0]=>"Analytics", $columns[1]=>"Workbench for SAP HANA"),
      array($columns[0]=>"Analytics", $columns[1]=>"Text Miner"),
      array($columns[0]=>"Analytics", $columns[1]=>"Visual Statistics"),
      array($columns[0]=>"Analytics", $columns[1]=>"Forecast Studio"),
      array($columns[0]=>"Analytics", $columns[1]=>"OR"),
      array($columns[0]=>"Analytics", $columns[1]=>"ETS"),
      array($columns[0]=>"Analytics", $columns[1]=>"Sentiment Analysis"),
      array($columns[0]=>"Analytics", $columns[1]=>"Decision Manager"),
      array($columns[0]=>"Analytics", $columns[1]=>"Model Manager"),
      array($columns[0]=>"Analytics", $columns[1]=>"Business Rules Manager"),
      array($columns[0]=>"Analytics", $columns[1]=>"Scoring Accelerator"),
      array($columns[0]=>"Analytics", $columns[1]=>"Analytic Technologies"),

      array($columns[0]=>"BI", $columns[1]=>"BI Server"),
      array($columns[0]=>"BI", $columns[1]=>"Enterprise BI Server"),
      array($columns[0]=>"BI", $columns[1]=>"Visual Analytics"),
      array($columns[0]=>"BI", $columns[1]=>"BI Technologies"),

      array($columns[0]=>"DI", $columns[1]=>"Data Management with DI/DM Studio"),
      array($columns[0]=>"DI", $columns[1]=>"Data Surveyor for SAP"),
      array($columns[0]=>"DI", $columns[1]=>"Event Stream Processing"),
      array($columns[0]=>"DI", $columns[1]=>"Federation Server"),
      array($columns[0]=>"DI", $columns[1]=>"DI Architects"),

      array($columns[0]=>"DQ", $columns[1]=>"Master Data Management"),
      array($columns[0]=>"DQ", $columns[1]=>"Data Governance"),
      array($columns[0]=>"DQ", $columns[1]=>"Data Quality Standard-Advanced / DataFlux"),

      array($columns[0]=>"Security", $columns[1]=>"Fraud Management"),
      array($columns[0]=>"Security", $columns[1]=>"AML"),
      array($columns[0]=>"Security", $columns[1]=>"Enterprise Case Management"),

      array($columns[0]=>"IPA/GRID", $columns[1]=>"Grid Manager"),
      array($columns[0]=>"IPA/GRID", $columns[1]=>"SAS HPA"),
      array($columns[0]=>"IPA/GRID", $columns[1]=>"HPA/Grid")
    );
    populateTable($table_name, $columns, $rows);
  }

  // Populate table: solutions
  function populate_solutions() {
    $table_name = "solutions";
    $columns = array("solution_type", "solution");
    $rows = array(
      // Each row is in the form:
      // col1 name => value1, col2 name => value2, ...
      array($columns[0]=>"CFS", $columns[1]=>"Fraud and Financial Crimes"),
      array($columns[0]=>"CFS", $columns[1]=>"Anti-Money Laundering"),
      array($columns[0]=>"CFS", $columns[1]=>"Credit Scoring"),
      array($columns[0]=>"CFS", $columns[1]=>"Credit Risk Managment"),
      array($columns[0]=>"CFS", $columns[1]=>"Risk Dimensions / Management"), # FIXME: Does this " / " make sense?
      array($columns[0]=>"CFS", $columns[1]=>"OpRisk Management"), # FIXME: Rename OpRisk?
      array($columns[0]=>"CFS", $columns[1]=>"Enterprise GRC"),
      array($columns[0]=>"CFS", $columns[1]=>"CFS Solutions"),

      array($columns[0]=>"CIS", $columns[1]=>"Marketing Automation"),
      array($columns[0]=>"CIS", $columns[1]=>"Marketing Optimization"),
      array($columns[0]=>"CIS", $columns[1]=>"Rel-Time Decision Mgr"), # FIXME: Rename?
      array($columns[0]=>"CIS", $columns[1]=>"Marketing Operations Management"),
      array($columns[0]=>"CIS", $columns[1]=>"Realtime Decision Manager"),
      array($columns[0]=>"CIS", $columns[1]=>"CI Solutions"),

      array($columns[0]=>"PMS", $columns[1]=>"ABM / Profictability Managament"), # FIXME: Rename?
      array($columns[0]=>"PMS", $columns[1]=>"Strategy Management"),
      array($columns[0]=>"PMS", $columns[1]=>"Financial Management"),
      array($columns[0]=>"PMS", $columns[1]=>"Human Capital Management"),
      array($columns[0]=>"PMS", $columns[1]=>"PM Solutions"),

      array($columns[0]=>"SCS", $columns[1]=>"Collaborative Planning Workbench"),
      array($columns[0]=>"SCS", $columns[1]=>"Demand Signal Analytics"),
      array($columns[0]=>"SCS", $columns[1]=>"Forecast Analyst Workbench"),
      array($columns[0]=>"SCS", $columns[1]=>"New Product Forecasting"),
      array($columns[0]=>"SCS", $columns[1]=>"Asset Performance Analytics"),
      array($columns[0]=>"SCS", $columns[1]=>"Field Quality Analytics"),
      array($columns[0]=>"SCS", $columns[1]=>"Production Quality Analytics"),
      array($columns[0]=>"SCS", $columns[1]=>"Suspect Claims Detection"),
      array($columns[0]=>"SCS", $columns[1]=>"Service Parts Optimization"),
      array($columns[0]=>"SCS", $columns[1]=>"Inventory Optimization"),
      array($columns[0]=>"SCS", $columns[1]=>"SC Solutions"),

      array($columns[0]=>"HLS", $columns[1]=>"Clinical Data Integration"),
      array($columns[0]=>"HLS", $columns[1]=>"Drug Development"),
      array($columns[0]=>"HLS", $columns[1]=>"Healthcare Fraud"),
      array($columns[0]=>"HLS", $columns[1]=>"Episode Analytics"),
      array($columns[0]=>"HLS", $columns[1]=>"Safety Analytics"),
      array($columns[0]=>"HLS", $columns[1]=>"Claims Analytics"),
      array($columns[0]=>"HLS", $columns[1]=>"Health Life Sci Solutions"), # FIXME: Rename?

      array($columns[0]=>"RTS", $columns[1]=>"Integrated Merchandise Planning"),
      array($columns[0]=>"RTS", $columns[1]=>"Revenue Optimization"),
      array($columns[0]=>"RTS", $columns[1]=>"Size/Pack Optimization"),
      array($columns[0]=>"RTS", $columns[1]=>"Demand-Driven Forecasting"),
      array($columns[0]=>"RTS", $columns[1]=>"Retail Solutions"),

      array($columns[0]=>"EN", $columns[1]=>"Energy Forecasting")
    );
    populateTable($table_name, $columns, $rows);
  }

  // Populate table: misc
  function populate_misc() {
    $table_name = "misc";
    $rows = array(
      "Platform Administration",
      "Base SAS Programming",
      "Migration",
      "Validation (IQ/OQ/PQ)", # FIXME: Rename these?
      "Certified Installers",
      "Grid Administration"
    );
    populate1TupleTable($table_name, $rows);
  }

  // Populate table: verticals
  function populate_verticals() {
    $table_name = "verticals";
    $rows = array(
      "All", "FS", "COM", "HLS", "FED", "RCCM", "SLG", "EN/MFG", "UTL"
    );
    populate1TupleTable($table_name, $rows);
  }

  // Populate table: geographical_regions
  function populate_geographical_regions() {
    $table_name = "geographical_regions";
    $rows = array(
      "All", "NE", "SE", "MW", "NW", "SW", "Other"
    );
    populate1TupleTable($table_name, $rows);
  }

  // Populate table: opportunity_statuses
  function populate_opportunity_statuses() {
    $table_name = "opportunity_statuses";
    $rows = array(
      "Open",
      "Closed",
      "Filled"
    );
    populate1TupleTable($table_name, $rows);
  }

  // Populate table: consultant_rating_areas
  function populate_consultant_rating_areas() {
    $table_name = "consultant_rating_areas";
    $rows = array(
      "partner", "programmer", "DI", "BI", "admin", "grid", "VA", "analytics"
    );
    populate1TupleTable($table_name, $rows);
  }

  // FIXME: REMOVE POPULATE TABLE FUNCTIONS ABOVE THIS POINT

  // Populate tables that do no need to be read from the workbook
  function populateTables() {
    populate_partners();
    populate_partner_strength_ratings();
    populate_partner_technology_ratings();
    populate_partner_solution_ratings();
    populate_partner_misc_ratings();
    populate_partner_vertical_junction();
    populate_partner_region_junction();
    populate_consultants();
    populate_consultant_ratings();
    populate_consultant_partner_junction();
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
