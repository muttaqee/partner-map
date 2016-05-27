<?php

  /*
  This script creates a database and a set of new empty tables for use by the
  Partner Map.
  It is only intended to be used as a one-time setup.

  !!! NOTE: It first removes any database and tables having the same names
  without any warning.
  Don't execute this without first saving important data!
  */

  // Use this to report errors before closing
  $error_count = 0;


  /* Create and connect to database */

  // Database connection variables
  $dbhost = "localhost:7860";
  $dbuser = "root";
  $dbpass = "password";
  $dbname = "partner_map_db";

  // Connect to MySQL server
  $link = mysql_connect($dbhost, $dbuser, $dbpass);
  if (!$link) {
    die("Could not connect to server: " . mysql_error());
  } else {
    report("Connected to $dbhost");
  }

  // If database already exists, remove it
  $query = "DROP DATABASE IF EXISTS $dbname";
  if (!mysql_query($query, $link)) {
    die("Could not drop database $dbname: " . mysql_error());
  } else {
    report("Dropped database if exists: $dbname");
  }

  // Create database
  $query = "CREATE DATABASE $dbname";
  if (!mysql_query($query, $link)) {
    die("Could not create database $dbname: " . mysql_error());
  } else {
    report("Created database: '$dbname'");
  }

  // Select database
  mysql_select_db($dbname) or die("Could not select database $dbname: " . mysql_error());


  /* Prepare variables to set up tables */

  // Table and column names
  $tables = array(
    "partners",
    "consultants",
    "contacts",
    "ratings",
    "ratings_simple"
  );

  $partners_table = $tables[0];
  $partners_cols = array(
    "id",
    "name",
    "official_name",
    "is_partner_plus",
    "notes"
  );

  $consultants_table = $tables[1];
  $consultants_cols = array(
    "id",
    "first_name",
    "last_name",
    "rating",
    "partner",
    "rejected",
  );

  $contacts_table = $tables[2];
  $contacts_cols = array(
    "id",
    "first_name",
    "last_name",
    "email",
    "partner_organization"
  );

  $ratings_table = $tables[3];
  $ratings_cols = array("code");

  $ratings_simple_table = $tables[4];
  $ratings_simple_cols = array("code");


  /* Create tables */

  // Drop any existing tables with the same names
  report("DROPPING TABLES HAVING SAME NAMES");

  $arrayLength = count($tables);
  for ($i = 0; $i < $arrayLength; $i++) {
    $query = "DROP TABLE IF EXISTS $tables[$i]";
    if (!mysql_query($query, $link)) {
      $error_count++;
      echo "Error dropping table $tables[$i]: " . mysql_error() . "\n";
    } else {
      smallReport("Dropped table if exists: '$tables[$i]'");
    }
  }

  $MAX_ID_LENGTH = 5;
  $MAX_NAME_LENGTH = 32;
  $MAX_EMAIL_LENGTH = 320;
  $MAX_ORG_NAME_LENGTH = 128;
  $MAX_NOTES_LENGTH = 500;
  $ratings = array(
    "A+", "A", "A-",
    "B+", "B", "B-",
    "C+", "C", "C-",
    "D+", "D", "D-",
    "No rating"
  );
  $ratings_simple = array(
    'A', 'B', 'C', 'D', "No rating"
  );

  report("CREATING TABLES");

  // Create ratings table
  $query = "CREATE TABLE $ratings_table (
    code ENUM(
      'A+', 'A', 'A-',
      'B+', 'B', 'B-',
      'C+', 'C', 'C-',
      'D+', 'D', 'D-',
      'No rating'
    ) NOT NULL,
    PRIMARY KEY (code)
  )";
  if (!mysql_query($query, $link)) {
    $error_count++;
    smallReport("Error creating table $ratings_table: " . mysql_error());
  } else {
    smallReport("Created table: '$ratings_table'");
  }

  // Create simple ratings table
  $query = "CREATE TABLE $ratings_simple_table (
    code ENUM(
      'A', 'B', 'C', 'D', 'No rating'
    ) NOT NULL,
    PRIMARY KEY (code)
  )";
  if (!mysql_query($query, $link)) {
    $error_count++;
    smallReport("Error creating table $ratings_simple_table: " . mysql_error());
  } else {
    smallReport("Created table: '$ratings_simple_table'");
  }

  // Create partners table
  $query = "CREATE TABLE $partners_table (
    $partners_cols[0] INT($MAX_ID_LENGTH) NOT NULL AUTO_INCREMENT,
    $partners_cols[1] VARCHAR($MAX_ORG_NAME_LENGTH),
    $partners_cols[2] VARCHAR($MAX_ORG_NAME_LENGTH) NOT NULL,
    $partners_cols[3] BIT(1),
    $partners_cols[4] VARCHAR($MAX_NOTES_LENGTH),
    PRIMARY KEY ($partners_cols[0])
  )";
  if (!mysql_query($query, $link)) {
    $error_count++;
    smallReport("Error creating table $partners_table: " . mysql_error());
  } else {
    smallReport("Created table: '$partners_table'");
  }

  // Create consultants table
  $query = "CREATE TABLE $consultants_table (
    id INT($MAX_ID_LENGTH) NOT NULL AUTO_INCREMENT,
    first_name VARCHAR($MAX_NAME_LENGTH),
    last_name VARCHAR($MAX_NAME_LENGTH) NOT NULL,
    rating ENUM(
      'A+', 'A', 'A-',
      'B+', 'B', 'B-',
      'C+', 'C', 'C-',
      'D+', 'D', 'D-'
    ),
    partner_organization_id INT($MAX_ID_LENGTH),
    rejected BIT(1) DEFAULT 0,
    PRIMARY KEY (id),
    FOREIGN KEY (rating) REFERENCES $ratings_table(code),
    FOREIGN KEY (partner_organization_id) REFERENCES $partners_table(id)
  )";
  if (!mysql_query($query, $link)) {
    $error_count++;
    smallReport("Error creating table $consultants_table: " . mysql_error());
  } else {
    smallReport("Created table: '$consultants_table'");
  }

  // Create contacts table
  $query = "CREATE TABLE $contacts_table (
    id INT($MAX_ID_LENGTH) NOT NULL AUTO_INCREMENT,
    first_name VARCHAR($MAX_NAME_LENGTH),
    last_name VARCHAR($MAX_NAME_LENGTH),
    email VARCHAR($MAX_EMAIL_LENGTH) NOT NULL,
    partner_organization_id INT($MAX_ID_LENGTH),
    PRIMARY KEY (id),
    FOREIGN KEY (partner_organization_id) REFERENCES $partners_table(id)
  )";
  if (!mysql_query($query, $link)) {
    $error_count++;
    smallReport("Error creating table $contacts_table: " . mysql_error());
  } else {
    smallReport("Created table: '$contacts_table'");
  }


  /* Fill tables */
  report("ENTERING ROWS");

  $arrayLength = count($ratings);
  for ($i = 0; $i < $arrayLength; $i++) {
    $query = "INSERT INTO $ratings_table VALUES ('$ratings[$i]')";
    if (!mysql_query($query, $link)) {
      $error_count++;
      smallReport("Error inserting $ratings[$i] into table $ratings_table: " . mysql_error());
    } else {
      smallReport("Inserted $ratings[$i] into table: '$ratings_table'");
    }
  }

  $arrayLength = count($ratings_simple);
  for ($i = 0; $i < $arrayLength; $i++) {
    $query = "INSERT INTO $ratings_simple_table VALUES ('$ratings_simple[$i]')";
    if (!mysql_query($query, $link)) {
      $error_count++;
      smallReport("Error inserting $ratings_simple[$i] into table $ratings_simple_table: " . mysql_error());
    } else {
      smallReport("Inserted $ratings_simple[$i] into table: '$ratings_simple_table'");
    }
  }

  // FIXME: Output isn't pretty - should instead write output as html tables
  /* Describe tables */
  report("DESCRIBING TABLES");
  $arrayLength = count($tables);
  for ($i = 0; $i < $arrayLength; $i++) {
    report("TABLE: $tables[$i]");
    //$query = "DESCRIBE $tables[$i]";
    $query = "SHOW COLUMNS FROM $tables[$i]";
    $resource = mysql_query($query, $link);

    $html = "<table border='1'>";
    while ($row = mysql_fetch_array($resource)) {
      // smallReport(implode("|", $row));
      $html .= "<tr>";
      $rowSize = count($row);
      for ($j = 0; $j < $rowSize; $j++) {
        if (!is_null($row[$j])) {
          $html .= "<td>" . "$row[$j]" . "</td>";
        }
      }
      $html .= "</tr>";

      // FIXME: Attempt to remove odd empty last cell for "consultants"
      // if (!is_null($row)) {
      //   $html .= "<tr>";
      //   $rowSize = count($row);
      //   for ($j = 0; $j < $rowSize; $j++) {
      //     if (!is_null($row[$j])) {
      //       $html .= "<td>" . "$row[$j]" . "</td>";
      //     }
      //   }
      //   $html .= "</tr>";
      // }
    }
    $html .= "</table>";
    echo $html;
  }


  /* End and close server connection */

  // Report success or errors
  if ($error_count == 0) {
    report("Success! Database setup complete for $dbname. No errors detected.");
  } else {
    report("Satabase setup terminated for $dbname. $error_count errors encountered.");
  }

  // FIXME: FIX REPORTING/ECHOING THROUGHOUT THIS
  // For reporting to DOM (webpage)
  function report($string) {
    echo "<pre>" . $string . "</pre>";
  }
  function smallReport($string) {
    report("-- " . $string);
  }

  // Close server connection
  mysql_close($link);

?>
