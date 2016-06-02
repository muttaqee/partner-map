<?php

  /*
  This script creates a database and a set of new tables for use by the
  Partner Map. Most of the created tables are empty.
  It is only intended to be used as a one-time setup.

  !!! NOTE: It first removes any database and tables having the same names
  without any warning.
  Don't execute this without first saving important data!
  */

  /*
  TABLES TO CREATE:

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
      partner_verticals
      partner_geo

    consultants
      consultant_rating_areas
        consultant_ratings

    customers - FIXME: deal with later; may not implement

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
  global $ID_SIZE = 5;
  global $NAME_SIZE = 30;
  global $BOOLEAN_SIZE = 1; // Does this script use this for boolean/tinyint?
  global $NOTE_SIZE = 500;

  // Create table: ratings
  function create_ratings() {
    global $link;
    $table_name = "ratings";
    dropTable($table_name);

    // Construct query
    $sql = "CREATE TABLE $table_name (
      grade ENUM(
        'A+', 'A', 'A-',
        'B+', 'B', 'B-',
        'C+', 'C', 'C-',
        'D+', 'D', 'D-',
        'No rating'
      ) NOT NULL,
      PRIMARY KEY (grade)
    )"; # FIXME: Add E and F to ratings?

    query($sql, "Created $table_name table", false);
  }

  // Create table: ratings_simple
  function create_ratings_simple() {
    global $link;
    $table_name = "ratings_simple";
    dropTable($table_name);

    // Construct query
    $sql = "CREATE TABLE $table_name (
      grade ENUM(
        'A', 'B', 'C', 'D', 'No rating'
      ) NOT NULL,
      PRIMARY KEY (grade)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: partners
  function create_partners() {
    global $link;
    $table_name = "partners";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    global $NOTE_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      official_name VARCHAR($NAME_SIZE) NOT NULL,
      is_partner_plus BOOLEAN,
      notes VARCHAR($NOTE_SIZE)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: partner_strengths
  function create_partner_strengths() {
    global $link;
    $table_name = "partner_strengths";
    dropTable($table_name);

    $sql = "CREATE TABLE $table_name (
      strength ENUM(
        'technical_quality',
        'financial_rate_negotiation',
        'process_and_training',
        'political_SAS-customer',
        'social_responsive'
      ) NOT NULL,
      PRIMARY KEY (strength)
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: partner_strength_ratings
  function create_partner_strength_ratings() {
    global $link;
    $table_name = "partner_strength_ratings";
    dropTable($table_name);

    $sql = "CREATE TABLE $table_name (
      partner_id INT($ID_SIZE) NOT NULL,
      technical_quality ENUM()
    )";

    query($sql, "Created $table_name table", false);
  }

  // Create table: technologies
  function create_technologies() {

  }

  // Create table: partner_technology_ratings
  function create_partner_technology_ratings() {

  }

  // Create table: solutions
  function create_solutions() {

  }

  // Create table: partner_solution_ratings
  function create_partner_solution_ratings() {

  }

  // Create table: misc
  function create_misc() {

  }

  // Create table: partner_misc_ratings
  function create_partner_misc_ratings() {

  }

  // Create table: partner_verticals
  function create_partner_verticals() {

  }

  // Create table: partner_geo
  function create_partner_geo() {

  }

  // Create table: consultants
  function create_consultants() {

  }

  // Create table: consultant_rating_areas
  function create_consultant_rating_areas() {

  }

  // Create table: consultant_ratings
  function create_consultant_ratings() {

  }

  // Create table: customers - FIXME: may implement later
  function create_customers() {

  }

  // Create table: opportunities
  function create_opportunities() {

  }

  // Create table: opportunity_partner_junction
  function create_opportunity_partner_junction() {

  }

  // Create table: opportunity_consultant_junction
  function create_opportunity_consultant_junction() {

  }

  // Create table: consultant_partner_junction
  function create_consultant_partner_junction() {

  }

  // Populate table: ratings
  function populate_ratings() {

  }

  // Populate table: ratings_simple
  function populate_ratings_simple() {

  }

  // Populate table: technologies
  function populate_technologies() {

  }

  // Populate table: solutions
  function populate_solutions() {

  }

  // Populate table: misc
  function populate_misc() {

  }

  // Create all tables
  function createAllTables() {
    // Ratings
    create_ratings();
    create_ratings_simple();
    // Main table group
    create_partners();
    create_partner_strengths();
    create_partner_strength_ratings();
    create_technologies();
    create_partner_technology_ratings();
    create_solutions();
    create_partner_solution_ratings();
    create_misc();
    create_partner_misc_ratings();
    create_partner_verticals();
    create_partner_geo();
    create_consultants();
    create_consultant_rating_areas();
    create_consultant_ratings();
    create_customers();
    create_opportunities();
    // Junctions
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
  }

  // Main function
  function execute() {
    connect();
    createDatabase();
    createAllTables();
    // populateTables();
    disconnect();
  }

  // Main function call
  execute();

  /* Create and connect to database */
  //
  //
  // /* Prepare variables to set up tables */
  //
  // // Table and column names
  // $tables = array(
  //   "consultants",
  //   "contacts",
  //   "partners",
  //   "ratings",
  //   "ratings_simple"
  // );
  //
  // $partners_table = $tables[0];
  // $partners_cols = array(
  //   "id",
  //   "name",
  //   "official_name",
  //   "is_partner_plus",
  //   "notes"
  // );
  //
  // $consultants_table = $tables[1];
  // $consultants_cols = array(
  //   "id",
  //   "first_name",
  //   "last_name",
  //   "rating",
  //   "partner",
  //   "rejected",
  // );
  //
  // $contacts_table = $tables[2];
  // $contacts_cols = array(
  //   "id",
  //   "first_name",
  //   "last_name",
  //   "email",
  //   "partner_organization"
  // );
  //
  // $ratings_table = $tables[3];
  // $ratings_cols = array("grade");
  //
  // $ratings_simple_table = $tables[4];
  // $ratings_simple_cols = array("grade");
  //
  //
  // /* Create tables */
  //
  // // Drop any existing tables with the same names
  // report("DROPPING TABLES HAVING SAME NAMES");
  //
  // $arrayLength = count($tables);
  // for ($i = 0; $i < $arrayLength; $i++) {
  //   $query = "DROP TABLE IF EXISTS $tables[$i]";
  //   if (!mysql_query($query, $link)) {
  //     $error_count++;
  //     echo "Error dropping table $tables[$i]: " . mysql_error() . "\n";
  //   } else {
  //     smallReport("Dropped table if exists: '$tables[$i]'");
  //   }
  // }
  //
  // $MAX_ID_LENGTH = 5;
  // $MAX_NAME_LENGTH = 32;
  // $MAX_EMAIL_LENGTH = 320;
  // $MAX_ORG_NAME_LENGTH = 128;
  // $MAX_NOTES_LENGTH = 500;
  // $ratings = array(
  //   "A+", "A", "A-",
  //   "B+", "B", "B-",
  //   "C+", "C", "C-",
  //   "D+", "D", "D-",
  //   "No rating"
  // );
  // $ratings_simple = array(
  //   'A', 'B', 'C', 'D', "No rating"
  // );
  //
  // report("CREATING TABLES");
  //
  // // Create ratings table
  // $query = "CREATE TABLE $ratings_table (
  //   grade ENUM(
  //     'A+', 'A', 'A-',
  //     'B+', 'B', 'B-',
  //     'C+', 'C', 'C-',
  //     'D+', 'D', 'D-',
  //     'No rating'
  //   ) NOT NULL,
  //   PRIMARY KEY (grade)
  // )";
  // if (!mysql_query($query, $link)) {
  //   $error_count++;
  //   smallReport("Error creating table $ratings_table: " . mysql_error());
  // } else {
  //   smallReport("Created table: '$ratings_table'");
  // }
  //
  // // Create simple ratings table
  // $query = "CREATE TABLE $ratings_simple_table (
  //   grade ENUM(
  //     'A', 'B', 'C', 'D', 'No rating'
  //   ) NOT NULL,
  //   PRIMARY KEY (grade)
  // )";
  // if (!mysql_query($query, $link)) {
  //   $error_count++;
  //   smallReport("Error creating table $ratings_simple_table: " . mysql_error());
  // } else {
  //   smallReport("Created table: '$ratings_simple_table'");
  // }
  //
  // // Create partners table
  // $query = "CREATE TABLE $partners_table (
  //   $partners_cols[0] INT($MAX_ID_LENGTH) NOT NULL AUTO_INCREMENT,
  //   $partners_cols[1] VARCHAR($MAX_ORG_NAME_LENGTH),
  //   $partners_cols[2] VARCHAR($MAX_ORG_NAME_LENGTH) NOT NULL,
  //   $partners_cols[3] BIT(1),
  //   $partners_cols[4] VARCHAR($MAX_NOTES_LENGTH),
  //   PRIMARY KEY ($partners_cols[0])
  // )";
  // if (!mysql_query($query, $link)) {
  //   $error_count++;
  //   smallReport("Error creating table $partners_table: " . mysql_error());
  // } else {
  //   smallReport("Created table: '$partners_table'");
  // }
  //
  // // Create consultants table
  // $query = "CREATE TABLE $consultants_table (
  //   id INT($MAX_ID_LENGTH) NOT NULL AUTO_INCREMENT,
  //   first_name VARCHAR($MAX_NAME_LENGTH),
  //   last_name VARCHAR($MAX_NAME_LENGTH) NOT NULL,
  //   rating ENUM(
  //     'A+', 'A', 'A-',
  //     'B+', 'B', 'B-',
  //     'C+', 'C', 'C-',
  //     'D+', 'D', 'D-'
  //   ),
  //   partner_organization_id INT($MAX_ID_LENGTH),
  //   rejected BIT(1) DEFAULT 0,
  //   PRIMARY KEY (id),
  //   FOREIGN KEY (rating) REFERENCES $ratings_table(grade),
  //   FOREIGN KEY (partner_organization_id) REFERENCES $partners_table(id)
  // )";
  // if (!mysql_query($query, $link)) {
  //   $error_count++;
  //   smallReport("Error creating table $consultants_table: " . mysql_error());
  // } else {
  //   smallReport("Created table: '$consultants_table'");
  // }
  //
  // // Create contacts table
  // $query = "CREATE TABLE $contacts_table (
  //   id INT($MAX_ID_LENGTH) NOT NULL AUTO_INCREMENT,
  //   first_name VARCHAR($MAX_NAME_LENGTH),
  //   last_name VARCHAR($MAX_NAME_LENGTH),
  //   email VARCHAR($MAX_EMAIL_LENGTH) NOT NULL,
  //   partner_organization_id INT($MAX_ID_LENGTH),
  //   PRIMARY KEY (id),
  //   FOREIGN KEY (partner_organization_id) REFERENCES $partners_table(id)
  // )";
  // if (!mysql_query($query, $link)) {
  //   $error_count++;
  //   smallReport("Error creating table $contacts_table: " . mysql_error());
  // } else {
  //   smallReport("Created table: '$contacts_table'");
  // }
  //
  //
  // /* Fill ratings and simple ratings tables */
  // report("ENTERING ROWS");
  //
  // $arrayLength = count($ratings);
  // for ($i = 0; $i < $arrayLength; $i++) {
  //   $query = "INSERT INTO $ratings_table VALUES ('$ratings[$i]')";
  //   if (!mysql_query($query, $link)) {
  //     $error_count++;
  //     smallReport("Error inserting $ratings[$i] into table $ratings_table: " . mysql_error());
  //   } else {
  //     smallReport("Inserted $ratings[$i] into table: '$ratings_table'");
  //   }
  // }
  //
  // $arrayLength = count($ratings_simple);
  // for ($i = 0; $i < $arrayLength; $i++) {
  //   $query = "INSERT INTO $ratings_simple_table VALUES ('$ratings_simple[$i]')";
  //   if (!mysql_query($query, $link)) {
  //     $error_count++;
  //     smallReport("Error inserting $ratings_simple[$i] into table $ratings_simple_table: " . mysql_error());
  //   } else {
  //     smallReport("Inserted $ratings_simple[$i] into table: '$ratings_simple_table'");
  //   }
  // }
  //
  //
  // /* End and close server connection */
  //
  // // Report success or errors
  // if ($error_count == 0) {
  //   report("Success! Database setup complete for $db_name. No errors detected.");
  // } else {
  //   report("Satabase setup terminated for $db_name. $error_count errors encountered.");
  // }

?>
