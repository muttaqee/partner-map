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
  }

  // Close server connection
  function disconnect() {
    global $link;
    mysql_close($link);
  }

  // Create and select database
  function createDatabase() {
    global $db_name, $link;

    // If database already exists, remove it
    $query = "DROP DATABASE IF EXISTS $db_name";
    if (!mysql_query($query, $link)) {
      die("Could not drop database $db_name: " . mysql_error());
    } else {
      report("Dropped database if exists: $db_name");
    }

    // Create database
    $query = "CREATE DATABASE $db_name";
    if (!mysql_query($query, $link)) {
      die("Could not create database $db_name: " . mysql_error());
    } else {
      report("Created database: '$db_name'");
    }

    // Select database
    mysql_select_db($db_name) or die("Could not select database $db_name: " . mysql_error());
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

  // Drop table if it exists
  function dropTable($table_name) {
    $sql = "DROP TABLE IF EXISTS $table_name";
    query($sql, NULL, false);
  }

  // Field data sizes
  $ID_SIZE = 5;
  $RATING_SIZE = 10; // FIXME: May change to 2 or 1 (for ratings_simple)
  $NAME_SIZE = 30;
  $BOOLEAN_SIZE = 1; // Does this script use this for boolean/tinyint?
  $NOTE_SIZE = 500;
  $CURRENCY_SIZE = 15;

  // Create table: ratings
  function create_ratings() {
    $table_name = "ratings";
    dropTable($table_name);

    // Construct query
    global $RATING_SIZE;
    $sql = "CREATE TABLE $table_name (
      grade VARCHAR($RATING_SIZE) NOT NULL,
      PRIMARY KEY (grade)
    )"; # FIXME: Add E and F to ratings?

    query($sql, "Created $table_name table", false);
  }

  // Create table: ratings_simple
  function create_ratings_simple() {
    $table_name = "ratings_simple";
    dropTable($table_name);

    // Construct query
    global $RATING_SIZE;
    $sql = "CREATE TABLE $table_name (
      grade VARCHAR($RATING_SIZE) NOT NULL,
      PRIMARY KEY (grade)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: partners
  function create_partners() {
    $table_name = "partners";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    global $BOOLEAN_SIZE;
    global $NOTE_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      official_name VARCHAR($NAME_SIZE) NOT NULL,
      is_partner_plus BIT($BOOLEAN_SIZE),
      notes VARCHAR($NOTE_SIZE),
      PRIMARY KEY (id)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: partner_strengths
  function create_partner_strengths() {
    $table_name = "partner_strengths";
    dropTable($table_name);

    // Construct query
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      strength VARCHAR($NAME_SIZE) NOT NULL,
      PRIMARY KEY (strength)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: partner_strength_ratings
  function create_partner_strength_ratings() {
    $table_name = "partner_strength_ratings";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    global $RATING_SIZE;
    $sql = "CREATE TABLE $table_name (
      partner_id INT($ID_SIZE) NOT NULL,
      strength VARCHAR($NAME_SIZE) NOT NULL,
      rating VARCHAR($RATING_SIZE) NOT NULL,
      CONSTRAINT pk PRIMARY KEY (partner_id, strength),
      FOREIGN KEY (partner_id) REFERENCES partners (id),
      FOREIGN KEY (strength) REFERENCES partner_strengths (strength),
      FOREIGN KEY (rating) REFERENCES ratings_simple (grade)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: technologies
  function create_technologies() {
    $table_name = "technologies";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      technology_type VARCHAR($NAME_SIZE) NOT NULL,
      technology VARCHAR($NAME_SIZE) NOT NULL,
      PRIMARY KEY (id),
      CONSTRAINT UNIQUE (technology_type, technology)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: partner_technology_ratings
  function create_partner_technology_ratings() {
    $table_name = "partner_technology_ratings";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $RATING_SIZE;
    $sql = "CREATE TABLE $table_name (
      partner_id INT($ID_SIZE) NOT NULL,
      technology_id INT($ID_SIZE) NOT NULL,
      rating VARCHAR($RATING_SIZE) NOT NULL,
      CONSTRAINT pk PRIMARY KEY (partner_id, technology_id),
      FOREIGN KEY (partner_id) REFERENCES partners(id),
      FOREIGN KEY (technology_id) REFERENCES technologies(id),
      FOREIGN KEY (rating) REFERENCES ratings_simple(grade)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: solutions
  function create_solutions() {
    $table_name = "solutions";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      solution_type VARCHAR($NAME_SIZE) NOT NULL,
      solution VARCHAR($NAME_SIZE) NOT NULL,
      PRIMARY KEY (id),
      CONSTRAINT UNIQUE (solution_type, solution)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: partner_solution_ratings
  function create_partner_solution_ratings() {
    $table_name = "partner_solution_ratings";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $RATING_SIZE;
    $sql = "CREATE TABLE $table_name (
      partner_id INT($ID_SIZE) NOT NULL,
      solution_id INT($ID_SIZE) NOT NULL,
      rating VARCHAR($RATING_SIZE) NOT NULL,
      CONSTRAINT pk PRIMARY KEY (partner_id, solution_id),
      FOREIGN KEY (partner_id) REFERENCES partners(id),
      FOREIGN KEY (solution_id) REFERENCES solutions(id),
      FOREIGN KEY (rating) REFERENCES ratings_simple(grade)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: misc
  function create_misc() {
    $table_name = "misc";
    dropTable($table_name);

    // Construct query
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      type VARCHAR($NAME_SIZE) NOT NULL,
      PRIMARY KEY (type)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: partner_misc_ratings
  function create_partner_misc_ratings() {
    $table_name = "partner_misc_ratings";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    global $RATING_SIZE;
    $sql = "CREATE TABLE $table_name (
      partner_id INT($ID_SIZE) NOT NULL,
      misc_type VARCHAR($NAME_SIZE) NOT NULL,
      rating VARCHAR($RATING_SIZE) NOT NULL,
      CONSTRAINT pk PRIMARY KEY (partner_id, misc_type),
      FOREIGN KEY (partner_id) REFERENCES partners(id),
      FOREIGN KEY (misc_type) REFERENCES misc(type),
      FOREIGN KEY (rating) REFERENCES ratings_simple(grade)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: verticals
  function create_verticals() {
    $table_name = "verticals";
    dropTable($table_name);

    // Construct query
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      vertical VARCHAR($NAME_SIZE) NOT NULL,
      PRIMARY KEY (vertical)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: partner_vertical_junction
  function create_partner_vertical_junction() {
    $table_name = "partner_verticals_junction";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      partner_id INT($ID_SIZE) NOT NULL,
      vertical VARCHAR($NAME_SIZE) NOT NULL,
      CONSTRAINT pk PRIMARY KEY (partner_id, vertical),
      FOREIGN KEY (partner_id) REFERENCES partners(id),
      FOREIGN KEY (vertical) REFERENCES verticals(vertical)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: geographical_regions
  function create_geographical_regions() {
    $table_name = "geographical_regions";
    dropTable($table_name);

    // Construct query
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      region VARCHAR($NAME_SIZE) NOT NULL,
      PRIMARY KEY (region)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: partner_region_junction
  function create_partner_region_junction() {
    $table_name = "partner_region_junction";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      partner_id INT($ID_SIZE) NOT NULL,
      region VARCHAR($NAME_SIZE) NOT NULL,
      CONSTRAINT pk PRIMARY KEY (partner_id, region),
      FOREIGN KEY (partner_id) REFERENCES partners(id),
      FOREIGN KEY (region) REFERENCES geographical_regions(region)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: consultants
  function create_consultants() {
    $table_name = "consultants";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    global $BOOLEAN_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      first_name VARCHAR($NAME_SIZE),
      last_name VARCHAR($NAME_SIZE) NOT NULL,
      is_rejected BIT($BOOLEAN_SIZE) NOT NULL,
      PRIMARY KEY (id)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: consultant_rating_areas
  function create_consultant_rating_areas() {
    $table_name = "consultant_rating_areas";
    dropTable($table_name);

    // Construct query
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      area VARCHAR($NAME_SIZE) NOT NULL,
      PRIMARY KEY (area)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: consultant_ratings
  function create_consultant_ratings() {
    $table_name = "consultant_ratings";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    global $RATING_SIZE;
    $sql = "CREATE TABLE $table_name (
      consultant_id INT($ID_SIZE) NOT NULL,
      area VARCHAR($NAME_SIZE) NOT NULL,
      rating VARCHAR($RATING_SIZE) NOT NULL,
      CONSTRAINT pk PRIMARY KEY (consultant_id, area),
      FOREIGN KEY (consultant_id) REFERENCES consultants(id),
      FOREIGN KEY (area) REFERENCES consultant_rating_areas(area),
      FOREIGN KEY (rating) REFERENCES ratings_simple(grade)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: customers - FIXME: may implement later
  function create_customers() {
  }

  // Create table: opportunity_statuses
  function create_opportunity_statuses() {
    $table_name = "opportunity_statuses";
    dropTable($table_name);

    // Construct query
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      status VARCHAR($NAME_SIZE) NOT NULL,
      PRIMARY KEY (status)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: opportunities
  function create_opportunities() {
    $table_name = "opportunities";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    global $CURRENCY_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      customer VARCHAR($NAME_SIZE) NOT NULL, # FIXME: when customer table is implemented, change to: customer_id INT($ID_SIZE) NOT NULL,
      customer_rate FLOAT($CURRENCY_SIZE, 2), # FIXME: Make DEFAULT 0?
      status VARCHAR($NAME_SIZE), # FIXME: Make this NOT NULL?
      date_start DATE,
      date_end DATE,
      PRIMARY KEY (id),
      FOREIGN KEY (status) REFERENCES opportunity_statuses(status)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: opportunity_partner_junction
  function create_opportunity_partner_junction() {
    $table_name = "opportunity_partner_junction";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $CURRENCY_SIZE;
    $sql = "CREATE TABLE $table_name (
      opportunity_id INT($ID_SIZE) NOT NULL,
      partner_id INT($ID_SIZE) NOT NULL,
      partner_rate FLOAT($CURRENCY_SIZE, 2), # FIXME: Make DEFAULT 0?
      CONSTRAINT pk PRIMARY KEY (opportunity_id, partner_id),
      FOREIGN KEY (opportunity_id) REFERENCES opportunities(id),
      FOREIGN KEY (partner_id) REFERENCES partners(id)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: opportunity_consultant_junction
  function create_opportunity_consultant_junction() {
    $table_name = "opportunity_consultant_junction";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $CURRENCY_SIZE;
    $sql = "CREATE TABLE $table_name (
      opportunity_id INT($ID_SIZE) NOT NULL,
      consultant_id INT($ID_SIZE) NOT NULL,
      consultant_rate FLOAT($CURRENCY_SIZE, 2), # FIXME: Make DEFAULT 0?
      CONSTRAINT pk PRIMARY KEY (opportunity_id, consultant_id),
      FOREIGN KEY (opportunity_id) REFERENCES opportunities(id),
      FOREIGN KEY (consultant_id) REFERENCES consultants(id)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: consultant_partner_junction
  function create_consultant_partner_junction() {
    $table_name = "consultant_partner_junction";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    $sql = "CREATE TABLE $table_name (
      consultant_id INT($ID_SIZE) NOT NULL,
      partner_id INT($ID_SIZE) NOT NULL,
      CONSTRAINT pk PRIMARY KEY (consultant_id, partner_id),
      FOREIGN KEY (consultant_id) REFERENCES consultants(id),
      FOREIGN KEY (partner_id) REFERENCES partners(id)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Helper: inserts single value into a one-column table
  function populateTable($table_name, $value_array) {
    foreach ($value_array as $value) {
      // Construct and submit query
      $sql = "INSERT INTO $table_name VALUES (\"$value\")";
      query($sql, "Populated $table_name table: $sql", false);
    }
  }

  // Populate table: ratings
  function populate_ratings() {
    $table_name = "ratings";
    $rows = array(
      "A+", "A", "A-",
      "B+", "B", "B-",
      "C+", "C", "C-",
      "D+", "D", "D-"
    );
    populateTable($table_name, $rows);
  }

  // Populate table: ratings_simple
  function populate_ratings_simple() {
    $table_name = "ratings_simple";
    $rows = array("A", "B", "C", "D");
    populateTable($table_name, $rows);
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
    populateTable($table_name, $rows);
  }

  // Populate table: technologies
  function populate_technologies() {
    $table_name = "technologies";
    $col1 = "technology_type";
    $col2 = "technology";
    $rows = array(
      // Each row is in the form: "col1 name"=>"value1", "col2 name"=> value2
      array($col1=>"Hadoop", $col2=>"Data Loader for Hadoop"),
      array($col1=>"Analytics", $col2=>"Enterprise Miner"),
      array($col1=>"Analytics", $col2=>"Workbench for SAP HANA"),
      array($col1=>"Analytics", $col2=>"Text Miner"),
      array($col1=>"Analytics", $col2=>"Visual Statistics"),
      array($col1=>"Analytics", $col2=>"Forecast Studio"),
      array($col1=>"Analytics", $col2=>"OR"),
      array($col1=>"Analytics", $col2=>"ETS"),
      array($col1=>"Analytics", $col2=>"Sentiment Analysis"),
      array($col1=>"Analytics", $col2=>"Decision Manager"),
      array($col1=>"Analytics", $col2=>"Model Manager"),
      array($col1=>"Analytics", $col2=>"Business Rules Manager"),
      array($col1=>"Analytics", $col2=>"Scoring Accelerator"),
      array($col1=>"Analytics", $col2=>"Analytic Technologies"),
      array($col1=>"BI", $col2=>"BI Server"),
      array($col1=>"BI", $col2=>"Enterprise BI Server"),
      array($col1=>"BI", $col2=>"Visual Analytics"),
      array($col1=>"BI", $col2=>"BI Technologies"),
      array($col1=>"DI", $col2=>"Data Management with DI/DM Studio"),
      array($col1=>"DI", $col2=>"Data Surveyor for SAP"),
      array($col1=>"DI", $col2=>"Event Stream Processing"),
      array($col1=>"DI", $col2=>"Federation Server"),
      array($col1=>"DI", $col2=>"DI Architects"),
      array($col1=>"DQ", $col2=>"Master Data Management"),
      array($col1=>"DQ", $col2=>"Data Governance"),
      array($col1=>"DQ", $col2=>"Data Quality Standard-Advanced / DataFlux"),
      array($col1=>"Security", $col2=>"Fraud Management"),
      array($col1=>"Security", $col2=>"AML"),
      array($col1=>"Security", $col2=>"Enterprise Case Management"),
      array($col1=>"IPA/GRID", $col2=>"Grid Manager"),
      array($col1=>"IPA/GRID", $col2=>"SAS HPA"),
      array($col1=>"IPA/GRID", $col2=>"HPA/Grid")
    );
    foreach ($rows as $row) {
      $size = count($row);
      $columns = "";
      $values = "";
      $i = 0;
      foreach ($row as $column_name => $value) {
        $columns

      }
      $sql = "INSERT INTO $table_name ($columns) VALUES ($values)";
    }
  }

  // Populate table: solutions
  function populate_solutions() {

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
    populateTable($table_name, $rows);
  }

  // Populate table: verticals
  function populate_verticals() {
    $table_name = "verticals";
    $rows = array(
      "All", "FS", "COM", "HLS", "FED", "RCCM", "SLG", "EN/MFG", "UTL"
    );
    populateTable($table_name, $rows);
  }

  // Populate table: geographical_regions
  function populate_geographical_regions() {
    $table_name = "geographical_regions";
    $rows = array(
      "All", "NE", "SE", "MW", "NW", "SW", "Other"
    );
    populateTable($table_name, $rows);
  }

  // Populate table: oppourtunity_statuses
  function populate_opportunity_statuses() {
    $table_name = "opportunity_statuses";
    $rows = array(
      "Open",
      "Closed",
      "Filled"
    );
    populateTable($table_name, $rows);
  }

  // Create all tables
  function createAllTables() {
    create_ratings();
    create_ratings_simple();

    create_partners();

    create_partner_strengths();
    create_partner_strength_ratings();

    create_technologies();
    create_partner_technology_ratings();

    create_solutions();
    create_partner_solution_ratings();

    create_misc();
    create_partner_misc_ratings();

    create_verticals();
    create_partner_vertical_junction();

    create_geographical_regions();
    create_partner_region_junction();

    create_consultants();
    create_consultant_rating_areas();
    create_consultant_ratings();

    create_customers();

    create_opportunity_statuses();
    create_opportunities();
    create_opportunity_partner_junction();
    create_opportunity_consultant_junction();

    create_consultant_partner_junction();
  }

  // Populate tables that do no need to be read from the workbook
  function populateTables() {
    populate_ratings();
    populate_ratings_simple();
    populate_partner_strengths();
    populate_technologies();
    populate_solutions();
    populate_misc();
    populate_verticals();
    populate_geographical_regions();
    populate_opportunity_statuses();
  }

  // Main function
  function execute() {
    connect();
    createDatabase();
    createAllTables();
    populateTables();
    disconnect();
  }

  // Main function call
  execute();
?>
