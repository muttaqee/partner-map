<?php

echo "<pre>PHP script executing...</pre>";

// Prepare shell command
$prog = "C:\Python34\python";
$script = "C:\\xampp\htdocs\muttaqee-projects\\partner-map\\read-partners-pass1.py";
$cmd = $prog . " " . $script;

// Execute shell command and decode the result as an associative array
$result = json_decode(shell_exec($cmd), true);
#$result = shell_exec($cmd);

// NOTE: This is for testing.
echo("<pre>" . print_r($result, $return = true) . "</pre>");
# echo $result[48]['official_name']; # Accessing id 48, "official_name" data

// Connect to and select the database
$db_host = "localhost:7860";
$db_user = "root";
$db_pass = "password";
$db_name = "partner_map_db";
$link = mysql_connect($db_host, $db_user, $db_pass);
if (!$link) {
  die("<pre>Could not connect to server: </pre>" . mysql_error());
} else {
  echo("<pre>Connected to server $db_name.</pre>");
}
mysql_select_db($db_name) or die("Could not select the database $db_name: " . mysql_error());

// Store each row to the database
foreach ($result as $key => $row) {
  # FIXME. Remove this. Printing row id.
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
  $query = "INSERT INTO partners (";
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

  // Send query
  if (!mysql_query($query, $link)) {
    echo("<pre>FAILURE. Could not store a row to the database: " . mysql_error() . "\n</pre>");
  } else {
    echo("<pre>SUCCESS!\n</pre>");
  }
}

echo("<pre>PHP script execution complete.</pre>");

?>
