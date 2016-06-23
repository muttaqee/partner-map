/*jslint browser: true*/
/*global $, jQuery, alert*/

$(document).ready(function() {

  var dbname = "partner_map_db";
  var selectScript = "select.php";

  // Helper: Turn array into string of values with delimiter
  function arrayToString(arr, delimiter) {
    var str = "";
    var length = arr.length;
    for (var i = 0; i < length; i++) {
      str += arr[i] + delimiter;
    }
    return str.slice(0, -1 * delimiter.length);
  }

  // Helper: Turn object into string of property values with delimiter
  function objectToString(obj, delimiter) {
    str = "";
    for (var key in obj) {
      if (obj.hasOwnPropertty(key)) {
        str += obj[key] + delimiter;
      }
    }
    return str.slice(0, -1 * delimiter.length);
  }

  // Convert rows to an array in the form:
  // ["row0coll0< del >row0col1< del >...", ..., "rowNcol0< del >rowNcol1< del >..."]
  // where < del > is a delimiter (e.g. comma or other string)
  // EXAMPLE:
  // If there are M rows, N columns, and the delimiter is ", "
  // array[0]: "<row 0 col 0>, <row 0 col 1>, ..., <row 0 col N>"
  // array[1]: "<row 1 col 0>, <row 1 col 1>, ..., <row 1 col N>"
  // ...
  // array[M]: "<row M col 0>, <row M col 1>, ..., <row M col N>"
  function queryResultsToStringArray(results, delimiter) {
    var obj = JSON.parse(results);
    var length = obj.length;
    var array = [];
    for (var i = 0; i < length; i++) {
      array[i] = objectToString(obj[i], delimiter);
    }
    return array;
  }


  /* *** FIXME: BEGIN TEST AREA *** */

  // FIXME: TEST
  function testQuery() {
    var $body = $("body");
    var result = selectQuery("*", "technologies", "", function(d) {
      $body.append("<p>"+d+"</p>")

      d2 = JSON.parse(d);
      for (var i = d2.length - 1; i >= 0; i -= 1) {
        for (var col in d2[i]) {
          $body.prepend($("<p>" + d2[i][col] + "</p>"));
        }
      }
      // $body.append("<pre>"+d3+"</pre>")
    });
  }

  // FIXME: Test. Create options where id stored in value
  function testBuildSelect(options_table_name) {
    var $body = $("body");
    var $select = $("<select id=\"dropdown\"></select>");
    var result = selectQuery("*", options_table_name, "", function(result) {
      var rows = JSON.parse(result);
      var $option;
      var optionString, delimiter = " - ";

      var length = rows.length;
      for (var i = 0; i < length; i += 1) {
        $option = $("<option></option>");
        optionString = "";
        for (var col in rows[i]) {
          if (col == "id") {
            $option.attr("value", rows[i][col]);
          } else {
            optionString += rows[i][col] + delimiter;
          }
        }
        $option.html(optionString.slice(0, -1 * delimiter.length));
        $select.append($option);
      }

      $body.append($select);
    });
  }

  // FIXME: test - Remove
  $("body").on("change", "select", function() {
    $("body").append("<p>"+$(this).val()+"<p>");
  });

  /* *** FIXME: END TEST AREA *** */


  // Helper: query db with SELECT <...> FROM <...> WHERE <...>
  function selectQuery(select_str, from_str, where_str, callback) {
    $.post(
      selectScript,
      {
        SELECT: select_str,
        FROM: from_str,
        WHERE: where_str
      },
      function(data, status) {
        callback(data);
      }
    );
  }

  // Helper: replace " " with "_" (for creating element IDs) // FIXME: Remove - not needed?
  function makeId(string) {
    return string.trim().replace(/ /g, "_");
  }

  // Build an element containing these three elements:
  // <Dropdown menu of options> <Dropdown menu of ratings> <Add filter button>
  //
  // PARAMETERS:
  // id_str            id of this filter item (should be "<name>_filter_item")
  // options_table     name of table to draw options from
  // id_col_name       name of the id column for an option, if exists
  // option_col_names array of name(s) of columns to build options from
  // is_simple_rating  determines which table ratings are drawn from
  function buildRatingFilterItem(id_str, options_table, id_col_name, option_col_names, is_simple_rating) {
    var $filterItem = $("<div class='rating_filter_item'></div>");
    $filterItem.attr("id", id_str);

    // Select (drop-down menu) input: categories from db (must match db values)
    /*
    NOTE: Regardless of what is displayed to the user, the value stored and
    checked against should be the technology id
    */
    var $optionSelect = $("<select></select>");
    // Get rows from db
    var options;
    var select_str = arrayToString(options_cols, ", ");
    options = queryResultsToStringArray(selectQuery(select_str, options_table, ""), " - "); // FIXME: ORDER BY technology_type?
    //ids =
    // Put rows into select option elements
    var length = options.length;
    for (var i = 0; i < length; i++) {
      $option = $("<option value=\"" + /* ID */ + "\">" + options[i] + "</option>");
      $optionSelect.append($option);
    }
    // Put options into select element
    // Put select into filterItem element
    $filterItem.append($optionSelect);

    // Select (drop-down menu) input: ratings from db (must match db values)
    var $ratingSelect = $("<select></select>");
    $ratingSelect.append($("<option value='all_ratings'>All ratings</option>"));
    var rTable = is_simple_rating ? "ratings_simple" : "ratings";
    var ratings = queryResultsToStringArray(selectQuery("*", rTable, ""), " - ");
    var length = ratings.length;
    for (var i = 0; i < length; i++) {
      $ratingsSelect.append($("<option>" + ratings[0] + "</option>"));
    }
    $filterItem.append($ratingsSelect);

    // "Add filter" button
    $button = $("<input type=\"button\" value=\"add_filter\">+</input>");
    $filterItem.append($button);

    return $filterItem;
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
    $partnerFilterBox.append(buildCategoryFilterItem()); // FIXME name of innermost function called
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
    //buildView(); // FIXME: Uncomment
    // testQuery();
    testBuildSelect("technologies");
    // Listen for view changes
  }

  // Program start
  execute();
});
