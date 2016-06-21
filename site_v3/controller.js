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

  // Program start
  var execute = function() {
    // FIXME: MOVE A LOT OF THIS STUFF INTO OWN FUNCTIONS

    // Declare view elements
    var $form, $searchPanel, $resultPanel;
    var $oppFilterBox, $partnerFilterBox, $consultantFilterBox;

    var $partnerStrenFilters;
    var $partnerTechFilters;
    var $partnerSolFilters;
    var $partnerMiscFilters;
    var $partnerVertFilters;
    var $partnerRegFilters;

    // Instantiate view elements
    $form = $("<form></form>"); // FIXME: If using >1 form, change this (more specific)

    $searchPanel = $("<div></div>");
    $searchPanel.attr("id", "search_panel");

    $resultPanel = $("<div></div>");
    $resultPanel.attr("id", "result_panel");

    $oppFilterBox = $("<div></div>");
    $oppFilterBox.attr("id", "opp_filter_box");

    $partnerFilterBox = $("<div></div>");
    $partnerFilterBox.attr("id", "partner_filter_box");

    $consultantFilterBox = $("<div></div>");
    $consultantFilterBox.attr("id", "consultant_filter_box");

    $partnerStrenFilters = $("<div></div>");
    $partnerStrenFilters.attr("id", "partner_stren_filters");

    $partnerTechFilters = $("#partner_tech_filters");
    $partnerSolFilters = $("#partner_sol_filters");
    $partnerMiscFilters = $("#partner_misc_filters");
    $partnerVertFilters = $("#partner_vert_filters");
    $partnerRegFilters = $("#partner_reg_filters");

    // Build (nest) view elements
    // Listen for view changes
  }

  // Program start
  execute();
});
