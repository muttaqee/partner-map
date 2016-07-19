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
    regions
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
  $config = include('config\config.php');
  $db_host = $config["host"];
  $db_user = $config["username"];
  $db_pass = $config["password"];
  $db_name = $config["database"];

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
  $ID_SIZE = 10;
  $RATING_SIZE = 10; // FIXME: May change to 2 (or 1, for ratings_simple)
  $NAME_SIZE = 50;
  $BOOLEAN_SIZE = 1; // Does this script use this for boolean/tinyint?
  $NOTE_SIZE = 500;
  $CURRENCY_SIZE = 15;

  /* Meta table types: name end in _meta */

  // Create table: table_types_meta
  function create_table_types_meta() {
    $table_name = "table_types_meta";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      name VARCHAR($NAME_SIZE) NOT NULL UNIQUE,
      PRIMARY KEY (id)
    ) COMMENT 'Table types'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: tables_meta
  // (Stores table id, name, type)
  function create_tables_meta() {
    $table_name = "tables_meta";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      name VARCHAR($NAME_SIZE) NOT NULL UNIQUE,   /* Name in db */
      label VARCHAR($NAME_SIZE),                  /* Name to display in UI */
      type VARCHAR($NAME_SIZE),                   /* Functional category */
      is_searchable BIT($BOOLEAN_SIZE) DEFAULT 0, /* Tables entities searchable from main UI (useful for blocking out uninteresting _primary tables) */
      rating_table VARCHAR($NAME_SIZE),           /* Accompanying ratings table, if there is one (useful if there is more than one rating lookup table) */
      PRIMARY KEY (id),
      FOREIGN KEY (type) REFERENCES table_types_meta(name)
    ) COMMENT 'Tables'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: table_fk_meta
  // (junction table storing fk reference pairs of interest)
  // Idea: to find all data related to entity, controller may loop through all
  // tables referencing the entity's table (_junction, then _ratings)
  function create_table_fk_meta() {
    $table_name = "table_fk_meta";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      table_id INT($ID_SIZE) NOT NULL,
      reference_table_id INT($ID_SIZE) NOT NULL,
      referenced_column VARCHAR($NAME_SIZE) NOT NULL,
      CONSTRAINT pk PRIMARY KEY (table_id, reference_table_id)
    ) COMMENT 'Table references'";

    query($sql, "Created $table_name table", false);
  }

  /* Other table types: _primary, _secondary, _lookup, _rating_ and _junction */

  // Create table: ratings
  function create_ratings() {
    $table_name = "ratings_lookup";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $RATING_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      name VARCHAR($RATING_SIZE) NOT NULL UNIQUE COMMENT 'Rating',
      PRIMARY KEY (id)
    ) COMMENT 'Ratings'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: ratings_simple
  function create_ratings_simple() {
    $table_name = "ratings_simple_lookup";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $RATING_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      name VARCHAR($RATING_SIZE) NOT NULL UNIQUE COMMENT 'Rating',
      PRIMARY KEY (id)
    ) COMMENT 'Ratings'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: partners
  function create_partners() {
    $table_name = "partners_primary";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    global $BOOLEAN_SIZE;
    global $NOTE_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      name VARCHAR($NAME_SIZE) NOT NULL COMMENT 'Name',
      is_partner_plus BIT($BOOLEAN_SIZE) COMMENT 'Partner Plus', /* FIXME: Remove and save for opportunity_partner_junction? */
      notes VARCHAR($NOTE_SIZE) COMMENT 'Notes',
      PRIMARY KEY (id)
    ) COMMENT 'Partners'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: partner_strengths
  function create_partner_strengths() {
    $table_name = "partner_strengths_lookup";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      name VARCHAR($NAME_SIZE) NOT NULL UNIQUE COMMENT 'Strength',
      PRIMARY KEY (id)
    ) COMMENT 'Partner strengths'";

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
      primary_id INT($ID_SIZE) NOT NULL COMMENT 'Partner',
      lookup_id INT($ID_SIZE) NOT NULL COMMENT 'Strength',
      rating_id INT($ID_SIZE) NOT NULL COMMENT 'Rating',
      CONSTRAINT pk PRIMARY KEY (primary_id, lookup_id),
      FOREIGN KEY (primary_id) REFERENCES partners_primary(id),
      FOREIGN KEY (lookup_id) REFERENCES partner_strengths_lookup(id),
      FOREIGN KEY (rating_id) REFERENCES ratings_simple_lookup(id)
    ) COMMENT 'Partner strength ratings'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: technologies
  function create_technologies() {
    $table_name = "technologies_lookup";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      type VARCHAR($NAME_SIZE) NOT NULL COMMENT 'Category',
      name VARCHAR($NAME_SIZE) NOT NULL COMMENT 'Technology',
      PRIMARY KEY (id),
      CONSTRAINT UNIQUE (type, name)
    ) COMMENT 'Technologies'";

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
      primary_id INT($ID_SIZE) NOT NULL COMMENT 'Partner',
      lookup_id INT($ID_SIZE) NOT NULL COMMENT 'Technology',
      rating_id INT($ID_SIZE) NOT NULL COMMENT 'Rating',
      CONSTRAINT pk PRIMARY KEY (primary_id, lookup_id),
      FOREIGN KEY (primary_id) REFERENCES partners_primary(id),
      FOREIGN KEY (lookup_id) REFERENCES technologies_lookup(id),
      FOREIGN KEY (rating_id) REFERENCES ratings_simple_lookup(id)
    ) COMMENT 'Partner technology ratings'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: solutions
  function create_solutions() {
    $table_name = "solutions_lookup";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      type VARCHAR($NAME_SIZE) NOT NULL COMMENT 'Category',
      name VARCHAR($NAME_SIZE) NOT NULL COMMENT 'Solution',
      PRIMARY KEY (id),
      CONSTRAINT UNIQUE (type, name)
    ) COMMENT 'Solutions'";

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
      primary_id INT($ID_SIZE) NOT NULL COMMENT 'Partner',
      lookup_id INT($ID_SIZE) NOT NULL COMMENT 'Solution',
      rating_id INT($ID_SIZE) NOT NULL COMMENT 'Rating',
      CONSTRAINT pk PRIMARY KEY (primary_id, lookup_id),
      FOREIGN KEY (primary_id) REFERENCES partners_primary(id),
      FOREIGN KEY (lookup_id) REFERENCES solutions_lookup(id),
      FOREIGN KEY (rating_id) REFERENCES ratings_simple_lookup(id)
    ) COMMENT 'Partner solution ratings'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: misc
  function create_misc() {
    $table_name = "misc_lookup";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      name VARCHAR($NAME_SIZE) NOT NULL UNIQUE COMMENT 'Misc',
      PRIMARY KEY (id)
    ) COMMENT 'Misc'";

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
      primary_id INT($ID_SIZE) NOT NULL COMMENT 'Partner',
      lookup_id INT($ID_SIZE) NOT NULL COMMENT 'Misc',
      rating_id INT($ID_SIZE) NOT NULL COMMENT 'Rating',
      CONSTRAINT pk PRIMARY KEY (primary_id, lookup_id),
      FOREIGN KEY (primary_id) REFERENCES partners_primary(id),
      FOREIGN KEY (lookup_id) REFERENCES misc_lookup(id),
      FOREIGN KEY (rating_id) REFERENCES ratings_simple_lookup(id)
    ) COMMENT 'Partner misc ratings'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: verticals
  function create_verticals() {
    $table_name = "verticals_lookup";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      name VARCHAR($NAME_SIZE) NOT NULL UNIQUE COMMENT 'Vertical',
      PRIMARY KEY (id)
    ) COMMENT 'Verticals'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: partner_vertical_junction
  function create_partner_vertical_junction() {
    $table_name = "partner_vertical_junction";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    $sql = "CREATE TABLE $table_name (
      primary_id INT($ID_SIZE) NOT NULL COMMENT 'Partner',
      lookup_id INT($ID_SIZE) NOT NULL COMMENT 'Vertical',
      CONSTRAINT pk PRIMARY KEY (primary_id, lookup_id),
      FOREIGN KEY (primary_id) REFERENCES partners_primary(id),
      FOREIGN KEY (lookup_id) REFERENCES verticals_lookup(id)
    ) COMMENT 'Partner-vertical junction'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: regions
  function create_regions() {
    $table_name = "regions_lookup";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      name VARCHAR($NAME_SIZE) NOT NULL UNIQUE COMMENT 'Region',
      PRIMARY KEY (id)
    ) COMMENT 'Regions'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: partner_region_junction
  function create_partner_region_junction() {
    $table_name = "partner_region_junction";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    $sql = "CREATE TABLE $table_name (
      primary_id INT($ID_SIZE) NOT NULL COMMENT 'Partner',
      lookup_id INT($ID_SIZE) NOT NULL COMMENT 'Region',
      CONSTRAINT pk PRIMARY KEY (primary_id, lookup_id),
      FOREIGN KEY (primary_id) REFERENCES partners_primary(id),
      FOREIGN KEY (lookup_id) REFERENCES regions_lookup(id)
    ) COMMENT 'Partner-region junction'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: consultants
  function create_consultants() {
    $table_name = "consultants_primary";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    global $RATING_SIZE;
    global $BOOLEAN_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      name VARCHAR($ID_SIZE) COMMENT 'Name', /* FIXME: FOR NORMAL FORMS' SAKE. And make NOT NULL. */
      first_name VARCHAR($NAME_SIZE) COMMENT 'First name',
      last_name VARCHAR($NAME_SIZE) NOT NULL COMMENT 'Last name',
      rating_id int($ID_SIZE) COMMENT 'Overall rating', # FIXME: Added 6-6-16. Alter this?
      is_rejected BIT($BOOLEAN_SIZE) NOT NULL COMMENT 'Rejected',
      PRIMARY KEY (id),
      FOREIGN KEY (rating_id) REFERENCES ratings_lookup(id)
    ) COMMENT 'Consultants'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: consultant_rating_areas
  function create_consultant_rating_areas() {
    $table_name = "consultant_rating_areas_lookup";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      name VARCHAR($NAME_SIZE) NOT NULL UNIQUE COMMENT 'Area',
      PRIMARY KEY (id)
    ) COMMENT 'Consultant rating areas'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: consultant_ratings
  function create_consultant_ratings() {
    $table_name = "consultant_ratings";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    $sql = "CREATE TABLE $table_name (
      primary_id INT($ID_SIZE) NOT NULL COMMENT 'Consultant',
      lookup_id INT($ID_SIZE) NOT NULL COMMENT 'Area',
      rating_id INT($ID_SIZE) NOT NULL COMMENT 'Rating',
      CONSTRAINT pk PRIMARY KEY (primary_id, lookup_id),
      FOREIGN KEY (primary_id) REFERENCES consultants(id),
      FOREIGN KEY (lookup_id) REFERENCES consultant_rating_areas_lookup(id),
      FOREIGN KEY (rating_id) REFERENCES ratings_simple(id)
    ) COMMENT 'Consultant ratings'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: customers - FIXME: may implement later
  function create_customers() {
    $table_name = "customers_primary";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    global $BOOLEAN_SIZE;
    global $NOTE_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      name VARCHAR($NAME_SIZE) NOT NULL COMMENT 'Name',
      website VARCHAR($NAME_SIZE) COMMENT 'Website',
      notes VARCHAR($NOTE_SIZE) COMMENT 'Notes',
      PRIMARY KEY (id)
    ) COMMENT 'Customers'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: opportunity_statuses
  function create_opportunity_statuses() {
    $table_name = "opportunity_statuses_lookup";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      name VARCHAR($NAME_SIZE) NOT NULL UNIQUE COMMENT 'Status',
      PRIMARY KEY (id)
    ) COMMENT 'Opportunity statuses'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: opportunities
  function create_opportunities() {
    $table_name = "opportunities_primary";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    global $CURRENCY_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      customer VARCHAR($NAME_SIZE) NOT NULL COMMENT 'Customer', # FIXME: when customer table is implemented, change to: customer_id INT($ID_SIZE) NOT NULL,
      customer_rate FLOAT($CURRENCY_SIZE, 2) COMMENT 'Charge rate', # FIXME: Make DEFAULT 0?
      status_id INT($ID_SIZE) COMMENT 'Status', # FIXME: Make this NOT NULL?
      date_created DATE COMMENT 'Date created', # i.e. date this opp was opened/created (not the duration, which is stored in the junctions referencing this table)
      PRIMARY KEY (id),
      FOREIGN KEY (status_id) REFERENCES opportunity_statuses(id)
    ) COMMENT 'Opportunities'";

    query($sql, "Created $table_name table", false);
  }

  // FIXME: Remove these old junctions

  // // Create table: opportunity_technology_junction
  // function create_opportunity_technology_junction() {
  //   $table_name = "opportunity_technology_junction";
  //   dropTable($table_name);
  //
  //   // Construct query
  //   global $ID_SIZE;
  //   $sql = "CREATE TABLE $table_name (
  //     primary_id INT($ID_SIZE) NOT NULL COMMENT 'Opportunity',
  //     lookup_id INT($ID_SIZE) NOT NULL COMMENT 'Technology',
  //     UNIQUE KEY (primary_id, lookup_id),
  //     FOREIGN KEY (primary_id) REFERENCES opportunities_primary(id),
  //     FOREIGN KEY (lookup_id) REFERENCES technologies_lookup(id)
  //   ) COMMENT = 'Opportunity-technology junction'";
  //
  //   query($sql, "Created $table_name table", false);
  // }
  //
  // // Create table: opportunity_solution_junction
  // function create_opportunity_solution_junction() {
  //   $table_name = "opportunity_solution_junction";
  //   dropTable($table_name);
  //
  //   // Construct query
  //   global $ID_SIZE;
  //   $sql = "CREATE TABLE $table_name (
  //     primary_id INT($ID_SIZE) NOT NULL COMMENT 'Opportunity',
  //     lookup_id INT($ID_SIZE) NOT NULL COMMENT 'Solution',
  //     UNIQUE KEY (primary_id, lookup_id),
  //     FOREIGN KEY (primary_id) REFERENCES opportunities_primary(id),
  //     FOREIGN KEY (lookup_id) REFERENCES solutions_lookup(id)
  //   ) COMMENT = 'Opportunity-solution junction'";
  //
  //   query($sql, "Created $table_name table", false);
  // }
  //
  // // Create table: opportunity_misc_junction
  // function create_opportunity_misc_junction() {
  //   $table_name = "opportunity_misc_junction";
  //   dropTable($table_name);
  //
  //   // Construct query
  //   global $ID_SIZE;
  //   $sql = "CREATE TABLE $table_name (
  //     primary_id INT($ID_SIZE) NOT NULL COMMENT 'Opportunity',
  //     lookup_id INT($ID_SIZE) NOT NULL COMMENT 'Misc',
  //     UNIQUE KEY (primary_id, lookup_id),
  //     FOREIGN KEY (primary_id) REFERENCES opportunities_primary(id),
  //     FOREIGN KEY (lookup_id) REFERENCES misc_lookup(id)
  //   ) COMMENT = 'Opportunity-misc junction'";
  //
  //   query($sql, "Created $table_name table", false);
  // }

  // Create table: opportunity_partner_junction
  function create_opportunity_partner_junction() {
    $table_name = "opportunity_partner_junction";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $CURRENCY_SIZE;
    $sql = "CREATE TABLE $table_name (
      opportunity_id INT($ID_SIZE) NOT NULL COMMENT 'Opportunity',
      partner_id INT($ID_SIZE) NOT NULL COMMENT 'Partner',
      partner_rate FLOAT($CURRENCY_SIZE, 2) COMMENT 'Rate', # FIXME: Make DEFAULT 0?
      CONSTRAINT pk PRIMARY KEY (opportunity_id, partner_id),
      FOREIGN KEY (opportunity_id) REFERENCES opportunities_primary(id),
      FOREIGN KEY (partner_id) REFERENCES partners_primary(id)
    ) COMMENT = 'Opportunity-partner junction'";

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
      opportunity_id INT($ID_SIZE) NOT NULL COMMENT 'Opportunity',
      consultant_id INT($ID_SIZE) NOT NULL COMMENT 'Consultant',
      consultant_rate FLOAT($CURRENCY_SIZE, 2) COMMENT 'Rate', # FIXME: Make DEFAULT 0?
      CONSTRAINT pk PRIMARY KEY (opportunity_id, consultant_id),
      FOREIGN KEY (opportunity_id) REFERENCES opportunities_primary(id),
      FOREIGN KEY (consultant_id) REFERENCES consultants_primary(id)
    ) COMMENT = 'Opportunity-consultant junction'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: projects
  // An opportunity is composed of one or more projects (think "tasks") for a
  // customer.
  // Each project describes some service/product/technology/solution to be
  // provided to a customer.
  // Projects may be assigned a number of partners or consultants from those
  // assigned to the opportunity (i.e. those appearing in
  // opporunity_partner_junction or opportunity_consultant_junction)
  // Partners or consultants are assigned by adding rows to the appropriate
  // junction table: project_consultant_junction or project_partner_junction
  function create_projects() {
    $table_name = "projects_primary";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $NAME_SIZE;
    global $NOTE_SIZE;
    $sql = "CREATE TABLE $table_name (
      id INT($ID_SIZE) NOT NULL AUTO_INCREMENT,
      name VARCHAR($NAME_SIZE) NOT NULL COMMENT 'Name',
      opportunity_id INT($ID_SIZE) NOT NULL COMMENT 'Opportunity',
      notes VARCHAR($NOTE_SIZE) COMMENT 'Notes',
      PRIMARY KEY (id),
      FOREIGN KEY (opportunity_id) REFERENCES opportunities_primary(id)
    ) COMMENT 'Projects'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: project_technology_junction
  function create_project_technology_junction() {
    $table_name = "project_technology_junction";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    $sql = "CREATE TABLE $table_name (
      primary_id INT($ID_SIZE) NOT NULL COMMENT 'Project',
      lookup_id INT($ID_SIZE) NOT NULL COMMENT 'Technology',
      UNIQUE KEY (primary_id, lookup_id),
      FOREIGN KEY (primary_id) REFERENCES projects_primary(id),
      FOREIGN KEY (lookup_id) REFERENCES technologies_lookup(id)
    ) COMMENT = 'Project-technology junction'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: project_solution_junction
  function create_project_solution_junction() {
    $table_name = "project_solution_junction";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    $sql = "CREATE TABLE $table_name (
      primary_id INT($ID_SIZE) NOT NULL COMMENT 'Project',
      lookup_id INT($ID_SIZE) NOT NULL COMMENT 'Solution',
      UNIQUE KEY (primary_id, lookup_id),
      FOREIGN KEY (primary_id) REFERENCES projects_primary(id),
      FOREIGN KEY (lookup_id) REFERENCES solutions_lookup(id)
    ) COMMENT = 'Project-solution junction'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: project_misc_junction
  function create_project_misc_junction() {
    $table_name = "project_misc_junction";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    $sql = "CREATE TABLE $table_name (
      primary_id INT($ID_SIZE) NOT NULL COMMENT 'Project',
      lookup_id INT($ID_SIZE) NOT NULL COMMENT 'Misc',
      UNIQUE KEY (primary_id, lookup_id),
      FOREIGN KEY (primary_id) REFERENCES projects_primary(id),
      FOREIGN KEY (lookup_id) REFERENCES misc_lookup(id)
    ) COMMENT = 'Project-misc junction'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: project_partner_junction
  function create_project_partner_junction() {
    $table_name = "project_partner_junction";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    $sql = "CREATE TABLE $table_name (
      project_id INT($ID_SIZE) NOT NULL COMMENT 'Project',
      partner_id INT($ID_SIZE) NOT NULL COMMENT 'Partner',
      UNIQUE KEY (project_id, partner_id),
      FOREIGN KEY (project_id) REFERENCES projects_primary(id),
      FOREIGN KEY (partner_id) REFERENCES opportunity_partner_junction(consultant_id)
    ) COMMENT = 'Project-partner junction'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: project_consultant_junction
  function create_project_consultant_junction() {
    $table_name = "project_consultant_junction";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    $sql = "CREATE TABLE $table_name (
      project_id INT($ID_SIZE) NOT NULL COMMENT 'Project',
      consultant_id INT($ID_SIZE) NOT NULL COMMENT 'Consultant',
      UNIQUE KEY (project_id, consultant_id),
      FOREIGN KEY (project_id) REFERENCES projects_primary(id),
      FOREIGN KEY (consultant_id) REFERENCES opportunity_consultant_junction(consultant_id)
    ) COMMENT = 'Project-consultant junction'";

    query($sql, "Created $table_name table", false);
  }

  // Create table: consultant_partner_junction
  function create_consultant_partner_junction() {
    $table_name = "consultant_partner_junction";
    dropTable($table_name);

    // Construct query
    global $ID_SIZE;
    global $BOOLEAN_SIZE;
    $sql = "CREATE TABLE $table_name (
      consultant_id INT($ID_SIZE) NOT NULL COMMENT 'Consultant',
      partner_id INT($ID_SIZE) NOT NULL COMMENT 'Partner',
      is_current BIT($BOOLEAN_SIZE) COMMENT 'Currently employed', # FIXME: Adjust/reinterpret?
      CONSTRAINT pk PRIMARY KEY (consultant_id, partner_id),
      FOREIGN KEY (consultant_id) REFERENCES consultants_primary(id),
      FOREIGN KEY (partner_id) REFERENCES partners_primary(id)
    ) COMMENT = 'Consultant-partner junction'";

    query($sql, "Created $table_name table", false);
  }

  // Helper: inserts single value into a one-column table
  function populate1TupleTable($table_name, $value_array) {
    report("Populating $table_name...");
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
    report("Populating $table_name...");
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

  // Stores table name => table id pairs
  global $id = 1;
  global $tables = array(
    'ratings_lookup'=>$id++,
    'ratings_simple_lookup'=>$id++,
    'partners_primary'=>$id++,
    'partner_strengths_lookup'=>$id++,
    'partner_strength_ratings'=>$id++,
    'technologies_lookup'=>$id++,
    'partner_technology_ratings'=>$id++,
    'solutions_lookup'=>$id++,
    'partner_solution_ratings'=>$id++,
    'misc_lookup'=>$id++,
    'partner_misc_ratings'=>$id++,
    'verticals_lookup'=>$id++,
    'partner_vertical_junction'=>$id++,
    'regions_lookup'=>$id++,
    'partner_region_junction'=>$id++,
    'consultants_primary'=>$id++,
    'consultant_rating_areas_lookup'=>$id++,
    'consultant_ratings'=>$id++,
    'customers_primary'=>$id++,
    'opportunity_statuses_lookup'=>$id++,
    'opportunities_primary'=>$id++,
    'opportunity_partner_junction'=>$id++,
    'opportunity_consultant_junction'=>$id++,
    'projects_primary'=>$id++,
    'project_technology_junction'=>$id++,
    'project_solution_junction'=>$id++,
    'project_misc_junction'=>$id++,
    'project_partner_junction'=>$id++,
    'project_consultant_junction'=>$id++,
    'consultant_partner_junction'=>$id++
  );
  // Helper: get table id from name
  function getTableId($table_name) {
    return $tables[$table_name];
  }

  // Populate table: table types
  function populate_table_types_meta() {
    $table_name = "table_types_meta";
    $columns = array("name");
    $values = array(
      "primary", "secondary", "tertiary", "lookup", "junction", "ratings",
      "primary-primary-junction",
      "primary-secondary junction",
      "primary-lookup-junction",
      "primary-junction-junction"
    );
    $rows = array();
    foreach ($values as $value) {
      array_push($rows, array($columns[0]=>$value));
    }
    populateTable($table_name, $columns, $rows);
  }

  // Populate table: tables_meta
  function populate_tables_meta() {
    global $tables;
    $table_name = "tables_meta";
    $columns = array("name", "label", "type", "is_searchable", "rating_table", "id");
    $rows = array(
      array(
        $columns[0]=>"ratings_lookup",
        $columns[1]=>"Ratings",
        $columns[2]=>"lookup",
        $columns[3]=>0,
        $columns[4]=>"",
        $columns[5]=>$tables['ratings_lookup']
      ),
      array(
        $columns[0]=>"ratings_simple_lookup",
        $columns[1]=>"Ratings",
        $columns[2]=>"lookup",
        $columns[3]=>0,
        $columns[4]=>"",
        $columns[5]=>$tables['ratings_simple_lookup']
      ),
      array(
        $columns[0]=>"partners_primary",
        $columns[1]=>"Partners",
        $columns[2]=>"primary",
        $columns[3]=>1,
        $columns[4]=>"",
        $columns[5]=>$tables['partners_primary']
      ),
      array(
        $columns[0]=>"partner_strengths_lookup",
        $columns[1]=>"Partner strengths",
        $columns[2]=>"lookup",
        $columns[3]=>0,
        $columns[4]=>"ratings_simple_lookup",
        $columns[5]=>$tables['partner_strengths_lookup']
      ),
      array(
        $columns[0]=>"partner_strength_ratings",
        $columns[1]=>"Partner strength ratings",
        $columns[2]=>"ratings",
        $columns[3]=>0,
        $columns[4]=>"ratings_simple_lookup",
        $columns[5]=>$tables['partner_strength_ratings']
      ),
      array(
        $columns[0]=>"technologies_lookup",
        $columns[1]=>"Technologies",
        $columns[2]=>"lookup",
        $columns[3]=>0,
        $columns[4]=>"ratings_simple_lookup",
        $columns[5]=>$tables['technologies_lookup']
      ),
      array(
        $columns[0]=>"partner_technology_ratings",
        $columns[1]=>"Partner technology ratings",
        $columns[2]=>"ratings",
        $columns[3]=>0,
        $columns[4]=>"ratings_simple_lookup",
        $columns[5]=>$tables['partner_technology_ratings']
      ),
      array(
        $columns[0]=>"solutions_lookup",
        $columns[1]=>"Solutions",
        $columns[2]=>"lookup",
        $columns[3]=>0,
        $columns[4]=>"ratings_simple_lookup",
        $columns[5]=>$tables['solutions_lookup']
      ),
      array(
        $columns[0]=>"partner_solution_ratings",
        $columns[1]=>"Partner solution ratings",
        $columns[2]=>"ratings",
        $columns[3]=>0,
        $columns[4]=>"ratings_simple_lookup",
        $columns[5]=>$tables['partner_solution_ratings']
      ),
      array(
        $columns[0]=>"misc_lookup",
        $columns[1]=>"Misc",
        $columns[2]=>"lookup",
        $columns[3]=>0,
        $columns[4]=>"ratings_simple_lookup",
        $columns[5]=>$tables['misc_lookup']
      ),
      array(
        $columns[0]=>"partner_misc_ratings",
        $columns[1]=>"Partner misc ratings",
        $columns[2]=>"ratings",
        $columns[3]=>0,
        $columns[4]=>"ratings_simple_lookup",
        $columns[5]=>$tables['partner_misc_ratings']
      ),
      array(
        $columns[0]=>"verticals_lookup",
        $columns[1]=>"Verticals",
        $columns[2]=>"lookup",
        $columns[3]=>0,
        $columns[4]=>"",
        $columns[5]=>$tables['verticals_lookup']
      ),
      array(
        $columns[0]=>"partner_vertical_junction",
        $columns[1]=>"Partner-vertical junction",
        $columns[2]=>"primary-lookup-junction",
        $columns[3]=>0,
        $columns[4]=>"",
        $columns[5]=>$tables['partner_vertical_junction']
      ),
      array(
        $columns[0]=>"regions_lookup",
        $columns[1]=>"Regions",
        $columns[2]=>"lookup",
        $columns[3]=>0,
        $columns[4]=>"",
        $columns[5]=>$tables['regions_lookup']
      ),
      array(
        $columns[0]=>"partner_region_junction",
        $columns[1]=>"Partner-region junction",
        $columns[2]=>"primary-lookup-junction",
        $columns[3]=>0,
        $columns[4]=>"",
        $columns[5]=>$tables['partner_region_junction']
      ),
      array(
        $columns[0]=>"consultants_primary",
        $columns[1]=>"Consultants",
        $columns[2]=>"primary",
        $columns[3]=>1,
        $columns[4]=>"ratings_lookup",
        $columns[5]=>$tables['consultants_primary']
      ),
      array(
        $columns[0]=>"consultant_rating_areas",
        $columns[1]=>"Consultant rating areas",
        $columns[2]=>"lookup",
        $columns[3]=>0,
        $columns[4]=>"ratings_lookup",
        $columns[5]=>$tables['consultant_rating_areas']
      ),
      array(
        $columns[0]=>"consultant_ratings",
        $columns[1]=>"Consultant ratings",
        $columns[2]=>"ratings",
        $columns[3]=>0,
        $columns[4]=>"ratings_simple_lookup",
        $columns[5]=>$tables['consultant_ratings']
      ),
      array(
        $columns[0]=>"customers_primary",
        $columns[1]=>"Customers",
        $columns[2]=>"primary",
        $columns[3]=>1,
        $columns[4]=>"",
        $columns[5]=>$tables['customers_primary']
      ),
      array(
        $columns[0]=>"opportunity_statuses_lookup",
        $columns[1]=>"Opportunity statuses",
        $columns[2]=>"lookup",
        $columns[3]=>0,
        $columns[4]=>"",
        $columns[5]=>$tables['opportunity_statuses_lookup']
      ),
      array(
        $columns[0]=>"opportunities_primary",
        $columns[1]=>"Opportunities",
        $columns[2]=>"primary",
        $columns[3]=>1,
        $columns[4]=>"",
        $columns[5]=>$tables['opportunities_primary']
      ),
      array(
        $columns[0]=>"opportunity_partner_junction",
        $columns[1]=>"Opportunity-partner junction",
        $columns[2]=>"primary-primary-junction",
        $columns[3]=>0,
        $columns[4]=>"",
        $columns[5]=>$tables['opportunity_partner_junction']
      ),
      array(
        $columns[0]=>"opportunity_consultant_junction",
        $columns[1]=>"Opportunity-consultant junction",
        $columns[2]=>"primary-primary-junction",
        $columns[3]=>0,
        $columns[4]=>"",
        $columns[5]=>$tables['opportunity_consultant_junction']
      ),
      array(
        $columns[0]=>"projects_primary",
        $columns[1]=>"Projects",
        $columns[2]=>"primary",
        $columns[3]=>0,
        $columns[4]=>"",
        $columns[5]=>$tables['projects_primary']
      ),
      array(
        $columns[0]=>"project_technology_junction",
        $columns[1]=>"Project-technology junction",
        $columns[2]=>"primary-lookup-junction",
        $columns[3]=>0,
        $columns[4]=>"",
        $columns[5]=>$tables['project_technology_junction']
      ),
      array(
        $columns[0]=>"project_solution_junction",
        $columns[1]=>"Project-solution junction",
        $columns[2]=>"primary-lookup-junction",
        $columns[3]=>0,
        $columns[4]=>"",
        $columns[5]=>$tables['project_solution_junction']
      ),
      array(
        $columns[0]=>"project_misc_junction",
        $columns[1]=>"Project-misc junction",
        $columns[2]=>"primary-lookup-junction",
        $columns[3]=>0,
        $columns[4]=>"",
        $columns[5]=>$tables['project_misc_junction']
      ),
      array(
        $columns[0]=>"project_partner_junction",
        $columns[1]=>"Project-partner junction",
        $columns[2]=>"primary-junction-junction",
        $columns[3]=>0,
        $columns[4]=>"",
        $columns[5]=>$tables['project_partner_junction']
      ),
      array(
        $columns[0]=>"project_consultant_junction",
        $columns[1]=>"Project-consultant junction",
        $columns[2]=>"primary-junction-junction",
        $columns[3]=>0,
        $columns[4]=>"",
        $columns[5]=>$tables['project_consultant_junction']
      ),
      array(
        $columns[0]=>"consultant_partner_junction",
        $columns[1]=>"Consultant-partner juction",
        $columns[2]=>"primary-primary-junction",
        $columns[3]=>0,
        $columns[4]=>"",
        $columns[5]=>$tables['consultant_partner_junction']
      )
    );
    populateTable($table_name, $columns, $rows);
  }

  // Populate table: table_fk_meta
  function populate_table_fk_meta() {
    $table_name = "table_fk_meta";
    $columns = array("table_id", "reference_table_id", "fk_column");
    $rows = array(
      array(
        $columns[0]=>getTableId('partner_strength_ratings'),
        $columns[1]=>getTableId('partners_primary'),
        $columns[2]=>"primary_id"
      ),
      array(
        $columns[0]=>getTableId('partner_strength_ratings'),
        $columns[1]=>getTableId('partner_strengths_lookup'),
        $columns[2]=>"lookup_id"
      ),
      array(
        $columns[0]=>getTableId('partner_strength_ratings'),
        $columns[1]=>getTableId('ratings_simple_lookup'),
        $columns[2]=>"rating_id"
      ),
      array(
        $columns[0]=>getTableId('partner_technology_ratings'),
        $columns[1]=>getTableId('partners_primary'),
        $columns[2]=>"primary_id"
      ),
      array(
        $columns[0]=>getTableId('partner_technology_ratings'),
        $columns[1]=>getTableId('technologies_lookup'),
        $columns[2]=>"lookup_id"
      ),
      array(
        $columns[0]=>getTableId('partner_technology_ratings'),
        $columns[1]=>getTableId('ratings_simple_lookup'),
        $columns[2]=>"rating_id"
      ),
      array(
        $columns[0]=>getTableId('partner_solution_ratings'),
        $columns[1]=>getTableId('partners_primary'),
        $columns[2]=>"primary_id"
      ),
      array(
        $columns[0]=>getTableId('partner_solution_ratings'),
        $columns[1]=>getTableId('solutions_lookup'),
        $columns[2]=>"lookup_id"
      ),
      array(
        $columns[0]=>getTableId('partner_solution_ratings'),
        $columns[1]=>getTableId('ratings_simple_lookup'),
        $columns[2]=>"rating_id"
      ),
      array(
        $columns[0]=>getTableId('partner_misc_ratings'),
        $columns[1]=>getTableId('partners_primary'),
        $columns[2]=>"primary_id"
      ),
      array(
        $columns[0]=>getTableId('partner_misc_ratings'),
        $columns[1]=>getTableId('misc_lookup'),
        $columns[2]=>"lookup_id"
      ),
      array(
        $columns[0]=>getTableId('partner_misc_ratings'),
        $columns[1]=>getTableId('ratings_simple_lookup'),
        $columns[2]=>"rating_id"
      ),
      array(
        $columns[0]=>getTableId('partner_vertical_junction'),
        $columns[1]=>getTableId('partners_primary'),
        $columns[2]=>"primary_id"
      ),
      array(
        $columns[0]=>getTableId('partner_vertical_junction'),
        $columns[1]=>getTableId('verticals_lookup'),
        $columns[2]=>"lookup_id"
      ),
      array(
        $columns[0]=>getTableId('partner_region_junction'),
        $columns[1]=>getTableId('partners_primary'),
        $columns[2]=>"primary_id"
      ),
      array(
        $columns[0]=>getTableId('partner_region_junction'),
        $columns[1]=>getTableId('regions_lookup'),
        $columns[2]=>"lookup_id"
      ),
      array(
        $columns[0]=>getTableId('consultants_primary'),
        $columns[1]=>getTableId('ratings_lookup'),
        $columns[2]=>"rating_id"
      ),
      array(
        $columns[0]=>getTableId('consultant_ratings'),
        $columns[1]=>getTableId('consultants'),
        $columns[2]=>"primary_id"
      ),
      array(
        $columns[0]=>getTableId('consultant_ratings'),
        $columns[1]=>getTableId('consultant_rating_areas_lookup'),
        $columns[2]=>"lookup_id"
      ),
      array(
        $columns[0]=>getTableId('consultant_ratings'),
        $columns[1]=>getTableId('ratings_simple'),
        $columns[2]=>"rating_id"
      ),
      array(
        $columns[0]=>getTableId('opportunities_primary'),
        $columns[1]=>getTableId('opportunity_statuses'),
        $columns[2]=>"status_id"
      ),
      array(
        $columns[0]=>getTableId('opportunity_partner_junction'),
        $columns[1]=>getTableId('opportunities_primary'),
        $columns[2]=>"opportunity_id"
      ),
      array(
        $columns[0]=>getTableId('opportunity_partner_junction'),
        $columns[1]=>getTableId('partners_primary'),
        $columns[2]=>"partner_id"
      ),
      array(
        $columns[0]=>getTableId('opportunity_consultant_junction'),
        $columns[1]=>getTableId('opportunities_primary'),
        $columns[2]=>"opportunity_id"
      ),
      array(
        $columns[0]=>getTableId('opportunity_consultant_junction'),
        $columns[1]=>getTableId('consultants_primary'),
        $columns[2]=>"consultant_id"
      ),
      array(
        $columns[0]=>getTableId('projects_primary'),
        $columns[1]=>getTableId('opportunities_primary'),
        $columns[2]=>"opportunity_id"
      ),
      array(
        $columns[0]=>getTableId('project_technology_junction'),
        $columns[1]=>getTableId('projects_primary'),
        $columns[2]=>"primary_id"
      ),
      array(
        $columns[0]=>getTableId('project_technology_junction'),
        $columns[1]=>getTableId('technologies_lookup'),
        $columns[2]=>"lookup_id"
      ),
      array(
        $columns[0]=>getTableId('project_solution_junction'),
        $columns[1]=>getTableId('projects_primary'),
        $columns[2]=>"primary_id"
      ),
      array(
        $columns[0]=>getTableId('project_solution_junction'),
        $columns[1]=>getTableId('solutions_lookup'),
        $columns[2]=>"lookup_id"
      ),
      array(
        $columns[0]=>getTableId('project_misc_junction'),
        $columns[1]=>getTableId('projects_primary'),
        $columns[2]=>"primary_id"
      ),
      array(
        $columns[0]=>getTableId('project_misc_junction'),
        $columns[1]=>getTableId('misc_lookup'),
        $columns[2]=>"lookup_id"
      ),
      array(
        $columns[0]=>getTableId('project_partner_junction'),
        $columns[1]=>getTableId('projects_primary'),
        $columns[2]=>"project_id"
      ),
      array(
        $columns[0]=>getTableId('project_partner_junction'),
        $columns[1]=>getTableId('opportunity_partner_junction'),
        $columns[2]=>"partner_id"
      ),
      array(
        $columns[0]=>getTableId('project_consultant_junction'),
        $columns[1]=>getTableId('projects_primary'),
        $columns[2]=>"project_id"
      ),
      array(
        $columns[0]=>getTableId('project_consultant_junction'),
        $columns[1]=>getTableId('opportunity_consultant_junction'),
        $columns[2]=>"consultant_id"
      ),
      array(
        $columns[0]=>getTableId('consultant_partner_junction'),
        $columns[1]=>getTableId('consultants_primary'),
        $columns[2]=>"consultant_id"
      ),
      array(
        $columns[0]=>getTableId('consultant_partner_junction'),
        $columns[1]=>getTableId('partners_primary'),
        $columns[2]=>"partner_id"
      )
    );
    populateTable($table_name, $columns, $rows);
  }

  // Populate table: ratings
  function populate_ratings() {
    $table_name = "ratings";
    $columns = array("name");
    $values = array(
      "A+", "A", "A-",
      "B+", "B", "B-",
      "C+", "C", "C-",
      "D+", "D", "D-",
      "F", "No rating"
    );
    $rows = array();
    foreach ($values as $value) {
      array_push($rows, array($columns[0]=>$value));
    }
    populateTable($table_name, $columns, $rows);
  }

  // Populate table: ratings_simple
  function populate_ratings_simple() {
    $table_name = "ratings_simple";
    $columns = array("name");
    $values = array("A", "B", "C", "D", "F", "No rating");
    $rows = array();
    foreach ($values as $value) {
      array_push($rows, array($columns[0]=>$value));
    }
    populateTable($table_name, $columns, $rows);
  }

  // Populate table: partner_strengths
  function populate_partner_strengths() {
    $table_name = "partner_strengths";
    $columns = array("name");
    $values = array(
      "Technical - Quality",
      "Financial Rate Negotiation",
      "Process & Training",
      "Political - SAS/Customer",
      "Social - Responsive"
    );
    $rows = array();
    foreach ($values as $value) {
      array_push($rows, array($columns[0]=>$value));
    }
    populateTable($table_name, $columns, $rows);
  }

  // Populate table: technologies
  function populate_technologies() {
    $table_name = "technologies";
    $columns = array("type", "name");
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
    $columns = array("type", "name");
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
    $columns = array("name");
    $values = array(
      "Platform Administration",
      "Base SAS Programming",
      "Migration",
      "Validation (IQ/OQ/PQ)", # FIXME: Rename these?
      "Certified Installers",
      "Grid Administration"
    );
    $rows = array();
    foreach ($values as $value) {
      array_push($rows, array($columns[0]=>$value));
    }
    populateTable($table_name, $columns, $rows);
  }

  // Populate table: verticals
  function populate_verticals() {
    $table_name = "verticals";
    $columns = array("name");
    $values = array(
      "All", "FS", "COM", "HLS", "FED", "RCCM", "SLG", "EN/MFG", "UTL"
    );
    $rows = array();
    foreach ($values as $value) {
      array_push($rows, array($columns[0]=>$value));
    }
    populateTable($table_name, $columns, $rows);
  }

  // Populate table: regions
  function populate_regions() {
    $table_name = "regions";
    $columns = array("name");
    $values = array(
      "All", "NE", "SE", "MW", "NW", "SW", "Other"
    );
    $rows = array();
    foreach ($values as $value) {
      array_push($rows, array($columns[0]=>$value));
    }
    populateTable($table_name, $columns, $rows);
  }

  // Populate table: opportunity_statuses
  function populate_opportunity_statuses() {
    $table_name = "opportunity_statuses";
    $columns = array("name");
    $values = array(
      "Open",
      "Closed",
      "Filled"
    );
    $rows = array();
    foreach ($values as $value) {
      array_push($rows, array($columns[0]=>$value));
    }
    populateTable($table_name, $columns, $rows);
  }

  // Populate table: consultant_rating_areas
  function populate_consultant_rating_areas() {
    $table_name = "consultant_rating_areas";
    $columns = array("name");
    $values = array(
      "partner", "programmer", "DI", "BI", "admin", "grid", "VA", "analytics"
    );
    $rows = array();
    foreach ($values as $value) {
      array_push($rows, array($columns[0]=>$value));
    }
    populateTable($table_name, $columns, $rows);
  }

  // Create all tables
  function createAllTables() {
    create_table_types_meta();
    create_tables_meta();
    create_table_fk_meta();

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

    create_regions();
    create_partner_region_junction();

    create_consultants();
    create_consultant_rating_areas();
    create_consultant_ratings();

    create_customers();

    create_opportunity_statuses();
    create_opportunities();
    create_opportunity_partner_junction();
    create_opportunity_consultant_junction();

    # FIXME: May modify to include opp-project junction,
    # then project-( partner | consultant ) junctions
    // create_opportunity_technology_junction();
    // create_opportunity_solution_junction();
    // create_opportunity_misc_junction();

    create_projects();
    create_project_technology_junction();
    create_project_solution_junction();
    create_project_misc_junction();
    create_project_partner_junction();
    create_project_consultant_junction();

    create_consultant_partner_junction();
  }

  // Populate tables that do no need to be read from the workbook
  function populateTables() {
    populate_table_types_meta();
    populate_tables_meta();
    populate_table_fk_meta();

    populate_ratings();
    populate_ratings_simple();

    populate_partner_strengths();
    populate_technologies();
    populate_solutions();
    populate_misc();
    populate_verticals();
    populate_regions();
    populate_opportunity_statuses();
    populate_consultant_rating_areas();
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
