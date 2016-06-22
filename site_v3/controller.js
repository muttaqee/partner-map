$(document).ready(function() {
  var dbname = "partner_map_db";

  var $postButton = $("#post_button");
  var $resultArea = $("#result_area");
  var $tableSelect = $("input[name='table_select']");

  // Populate checkbox options when table is selected
  $tableSelect.click(function() {
    $.post(
      "select.php",
      {
        SELECT: "column_name",
        FROM: "information_schema.columns",
        WHERE: "table_name = '" + $(this).val() + "' AND table_schema = '" + dbname + "'"
      },
      function(data, status) {
        fill(data);
      }
    );
  });

  // Display query results
  var fill = function(data) {
    var obj = JSON.parse(data);
    var $list = $("<ul></ul>");
    for (i = 0; i < obj.length; i++) {
      var $li = $("<li></li>");
      for (col in obj[i]) {
        $li.append(col + ": " + obj[i][col] + ", ");
      }
      $list.append($li);
    }
    $resultArea.html($list);
  }

  // Listener: post button
  $postButton.click(function() {
    var tableName = $("#table_input").val();
    $.post(
      "select.php",
      {FROM: tableName},
      function(data, status) {
        fill(data);
      }
    );
  });
});
