<?php

/* Queries the database. */

  // Connection variables
$db_host = "localhost:7860";
$db_user = "root";
$db_pass = "password";
$db_name = "partner_map_db";

// Display message
function report($string) {
  echo "<pre>$string</pre>";
}

// Open connection
function connect() {
  global $db_host, $db_user, $db_pass, $db_name, $link;
  $link = mysql_connect($db_host, $db_user, $db_pass);
  if (!$link) {
    die("Could not connect to server: " . mysql_error());
  }
  mysql_select_db($db_name) or die("Could not select database $db_name: " . mysql_error());
}

// Close connection
function disconnect() {
  global $link;
  mysql_close($link);
}

// Submit query
function query($query_string) {
  global $link;
  $result = mysql_query($query_string, $link);
  if ($result) {
    return $result;
  } else {
    report("Error: " . mysql_error());
  }
}

// Construct query
function constructQuery() {
  $query_string = "";
  $SELECT = "*";
  $FROM = "";
  $WHERE = "";
  if (!empty($_POST["SELECT"]) && (strlen(trim($_POST["SELECT"])) > 0)) {
    $SELECT = strtolower(trim($_POST["SELECT"]));
  }
  if (!empty($_POST["FROM"]) && (strlen(trim($_POST["FROM"])) > 0)) {
    $FROM = strtolower(trim($_POST["FROM"]));
    $query_string = "SELECT $SELECT FROM $FROM";
  } else {
    die(); // FIXME: have better error handling
  }
  if (!empty($_POST["WHERE"]) && (strlen(trim($_POST["WHERE"])) > 0)) {
    $WHERE = strtolower(trim($_POST["WHERE"]));
    $query_string .= " WHERE $WHERE";
  }

  // FIXME: THIS IS TEMPORARY: SEND QUERY RESULT TO JS -> JS SENDS TO VIEW
  $query_string .= ";";
  return $query_string;
}

function encode_query_result($result) {
  $rows = array();
  while ($row = mysql_fetch_assoc($result)) {
    $rows[] = $row;
  }
  // echo("<pre>" . print_r($rows, $return = true) . "</pre>"); // FIXME: May remove
  return json_encode($rows);
}

// Program start
function execute() {
  connect();
  echo encode_query_result(query(constructQuery()));
  disconnect();
}

execute(); 
