<?php

/* Queries the database. */

  // Connection variables
  $config = include('../config/config.php');
  $db_host = $config["host"];
  $db_user = $config["username"];
  $db_pass = $config["password"];
  $db_name = $config["database"];

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
  return $_POST["query"];
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
