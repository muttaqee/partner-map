$(document).ready(function() {
  var $postButton = $("#post_button");
  var $resultArea = $("#result_area");
  $postButton.html("Fetch"); // FIXME: Test

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

  $postButton.click(function() {
    var $tableName = $("#table_input").val();
    $.post(
      "select.php",
      {table: $tableName},
      function(data, status) {
        fill(data);
      }
    );
  });
});
