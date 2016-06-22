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

  // Helper: replace " " with "_" (for creating element IDs)
  function makeId(string) {
    return string.trim().replace(/ /g, "_");
  }

  function buildRatingFilterItem(id_str, list_name, options_array, is_simple_rating) {
    var $filterItem = $("<div class='rating_filter_item'></div>");
    $filterItem.attr("id", id_str);

    var $categorySelect = $("<select></select>");

    var $ratingSelect = $("<select></select>");
    $ratingSelect.append$("<option value='all_ratings'>All ratings</option>")
    if (is_simple_rating) {
      // Get simple ratings from db
    } else {
      // Get ratings from db
    }
  }

  // Create a checkbox and label filter element (e.g. for VERTICALS and REGIONS)
  function buildCheckboxFilterItem(id_str, value_str, label_str) {
    var $filterItem = $("<div class='checkbox_filter_item'></div>");
    $filterItem.attr("id", id_str);

    var $checkbox = $("<input type='checkbox'></input>");
    $checkbox.attr("name", id_str);
    $checkbox.attr("value", value_str);
    $checkbox.appendTo($filterItem);

    var $label = $("<label for='" + id_str + "'>" + value_str + "</label>");
    $label.appendTo($filterItem);

    return $filterItem;
  }

  function buildPartnerFilterBox() {
    $partnerFilterBox = $("<div></div>");
    $partnerFilterBox.attr("id", "partner_filter_box");
    $partnerFilterBox.append(buildCategoryFilterItem);
    return $partnerFilterBox;
  }

  function buildView() {
    var $body;

    // Declare view elements
    var $form, $searchPanel, $resultPanel;

    var $oppFilterBox, $partnerFilterBox, $consultantFilterBox;

    var $partnerStrenFilters;
    var $partnerTechFilters;
    var $partnerSolFilters;
    var $partnerMiscFilters;
    var $partnerVertFilters;
    var $partnerRegFilters;

    /* FIXME: ALSO NEED oppFilters AND consultantFilters */

    // Instantiate view elements
    $body = $("body");

    $form = $("<form></form>"); // FIXME: If using >1 form, change this (more specific)
    // $form.appendTo($body);

    $searchPanel = $("<div></div>");
    $searchPanel.attr("id", "search_panel");
    // $searchPanel.appendTo($form);

    $resultPanel = $("<div></div>");
    $resultPanel.attr("id", "result_panel");
    // $resultPanel.appendTo($form);

    $oppFilterBox = $("<div></div>");
    $oppFilterBox.attr("id", "opp_filter_box");
    // $oppFilterBox.appendTo($searchPanel);

    $partnerFilterBox = $("<div></div>");
    $partnerFilterBox.attr("id", "partner_filter_box");
    // $partnerFilterBox.appendTo($searchPanel);

    $consultantFilterBox = $("<div></div>");
    $consultantFilterBox.attr("id", "consultant_filter_box");

    $partnerStrenFilters = $("<div></div>");
    $partnerStrenFilters.attr("id", "partner_stren_filters");

    $partnerTechFilters = $("<div></div>");
    $partnerTechFilters.attr("id", "partner_tech_filters");

    $partnerSolFilters = $("<div></div>");
    $partnerSolFilters.attr("id", "partner_sol_filters");

    $partnerMiscFilters = $("<div></div>");
    $partnerMiscFilters.attr("id", "partner_misc_filters");

    $partnerVertFilters = $("<div></div>");
    $partnerVertFilters.attr("id", "partner_vert_filters");

    $partnerRegFilters = $("<div></div>");
    $partnerRegFilters.attr("id", "partner_reg_filters");

    // Build (nest) view elements
    partnerFilters = [
      $partnerStrenFilters,
      $partnerTechFilters,
      $partnerSolFilters,
      $partnerMiscFilters,
      $partnerVertFilters,
      $partnerRegFilters
    ];
    var length = partnerFilters.length;
    for (var i = 0; i < length; i++) {
      partnerFilters[i].appendTo($partnerFilterBox);
    }

    filterBoxes = [$oppFilterBox, $partnerFilterBox, $consultantFilterBox];
    length = filterBoxes.length;
    for (var i = 0; i < length; i++) {
      filterBoxes[i].appendTo($searchPanel);
    }

    $searchPanel.appendTo($form);
    $resultPanel.appendTo($form);
    $form.appendTo($body);
  }

  // Program start
  function execute() {
    alert("Executing...");
    buildView();

    // Listen for view changes
  }

  // Program start
  execute();
});
