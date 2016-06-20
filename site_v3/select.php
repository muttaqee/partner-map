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

// Construct SELECT query // FIXME
function selectQuery() {
  // $query = "SELECT ";
  // $columns =
  // $tables =
  // return $query;
}

// Construct INSERT query // FIXME
function insertQuery() {
  // $query = "INSERT INTO " . $_POST["table"];
}

// Construct query
function constructQuery() {
  $query_string = "";
  // $operation = $_POST["operation"];
  // switch ($operation) {
  //   case "SELECT":
  //   $query_string .= "SELECT "
  //   break;
  //   case "INSERT":
  //   break;
  //   default:
  //   break;
  // }
  // FIXME: THIS IS TEMPORARY: SEND QUERY RESULT TO JS -> JS SENDS TO VIEW
  $query_string .= "
  SELECT * FROM " . $_POST["table"] . ";
  ";
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

 ?>
