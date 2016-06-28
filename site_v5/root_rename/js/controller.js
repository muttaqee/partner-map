/*jslint browser: true*/
/*global $, jQuery, alert*/
var selectScript = "../servercode/select.php";
var insertScript = "../servercode/insert.php"; // FIXME: script needs to be written
var deleteScript = "../servercode/delete.php"; // FIXME: script needs to be written

//------------------------------------------------------------------------------
// HELPER FUNCTIONS
//------------------------------------------------------------------------------

// Helper: query db with SELECT <...> FROM <...> WHERE <...>
function selectQuery(select_str, from_str, where_str, callback) {
  $.post(
    selectScript,
    {
      SELECT: select_str,
      FROM: from_str,
      WHERE: where_str
    },
    function (data, status) {
      callback(data);
    }
  );
}

// Helper: replace " " with "_" (for creating element IDs) // FIXME: Remove - not needed?
function makeId(string) {
  return string.trim().replace(/ /g, "_");
}

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
    if (obj.hasOwnProperty(key)) {
      str += obj[key] + delimiter;
    }
  }
  return str.slice(0, -1 * delimiter.length);
}

//------------------------------------------------------------------------------
// OBJECTS (DOM elements [use templates?], results [each entity], ...)
//------------------------------------------------------------------------------

// Generic view object // FIXME: might remove
function view(id_str, class_str, value_str, header_str, parent_obj) {
  this.id = id_str;
  this.class = class_str;
  this.value = value_str;
  this.parent = parent_obj;
  this.children = new Array();
  
  this.addChild = function(child) {
    this.children.push(child);
  };
  
  this.getParent = function() {
    return parent_obj;
  };
}

// Checkbox filter
function checkboxFilter(id_str, value_str, label_str) {
  this.id = id_str;
  this.class = "checkbox_filter_item";
  this.value = value_str;
  this.domElement = buildCheckboxFilterItem(id_str, value_str, label_str);
}

// Rating filter
function ratingFilter(id_str, value_table, rating_str, is_simple_bool) {
  this.id = id_str;
  this.class = "rating_filter_item";
  // this.value = value_str; // FIXME LEFT OFF HERE W/ PARAMS
  this.is_simple = is_simple_bool;
  this.rating = rating_str;
  this.domElement = buildRatingFilterItem(id_str, value_table, is_simple_bool);
}

// Rating filter setting (created from an  existing rating filter object)
function ratingFilterSetting(id_str, ratingFilter_obj) {
  this.id = ratingFilter_obj.id + "_setting";
  this.class = "rating_filter_item_setting";
  this.value = ratingFilter_obj.value;
  this.rating = ratingFilter_obj.rating;
  this.domElement = buildRatingFilterSetting(id_str, ratingFilter_obj.domElement); // FIXME: change this ctor & params
}

// Filter category: contains a group of filters
function filterCategory(id_str, name_str) {
  this.id = id_str;
  this.name = name_str;
  this.domElement = buildFilterCategory(id_str, name_str);

  this.checkboxFilters = [];
  this.ratingFilters = [];
  this.ratingFilterSettings = [];

  this.addFilter = function(filter) {
    if (filter instanceof checkboxFilter) {
      checkboxFilters.push(filter);
    } else if (filter instanceof ratingFilter) {
      ratingFilters.push(filter);
    } else if (filter instanceof ratingFilterSetting) {
      ratingFilterSettings.push(filter);
    }
  };

  this.getCheckboxFilters = function() {
    return this.checkboxFilters;
  };

  this.getRatingFilters = function() {
    return this.ratingFilters;
  };

  this.getRatingFilterSettings = function() {
    return this.ratingFilterSettings;
  };
}

//------------------------------------------------------------------------------
// BUILD
//------------------------------------------------------------------------------

//------------------------------------------------------------------------------
// RENDER (to View/DOM)
//------------------------------------------------------------------------------

//------------------------------------------------------------------------------
// EVENT HANDLING
//------------------------------------------------------------------------------

var partnerFilterCategories = []; // Push arrays of filter objects into this

$(document).ready(function() {

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
          if (rows[i].hasOwnProperty(col)) {
            if (col == "id") {
              $option.attr("value", rows[i][col]);
            } else {
              optionString += rows[i][col] + delimiter;
            }
          }
        }
        $option.html(optionString.slice(0, -1 * delimiter.length));
        $select.append($option);
      }

      $body.append($select);
    });
  }

  // FIXME: test - Remove
  $("body").on("change", "#dropdown", function() {
    $("body").append("<p>"+$(this).val()+"<p>");
  });

  /* *** FIXME: END TEST AREA *** */

  // Builds a select drop-down list
  //
  // Each list option corresponds to a row in the table
  // Each option's value is set to the "id" field, if it exists
  // The remaining fields are strung together to appear in the option content
  //
  // Example outcome:
  //
  // <select>
  //   <option value="1">name - age - DOB</option>
  //   ...
  // </select>
  function buildDropDownListFromTable(class_string, table_name) {
    var $select = $("<select class=\"" + class_string + "\"></select>");
    var result = selectQuery("*", table_name, "", function(result) {
      var rows = JSON.parse(result);
      var $option;
      var optionString, delimiter = " - ";

      var length = rows.length;
      for (var i = 0; i < length; i += 1) {
        $option = $("<option></option>");
        optionString = "";
        for (var col in rows[i]) {
          if (rows[i].hasOwnProperty(col)) {
            if (col == "id") {
              $option.attr("value", rows[i][col]);
            } else {
              optionString += rows[i][col] + delimiter;
            }
          }
        }
        $option.html(optionString.slice(0, -1 * delimiter.length));
        $select.append($option);
      }
    });
    return $select;
  }

  function buildRatingFilterSetting($ratingFilterItem) {
    var main_string = $("#" + $ratingFilterItem.attr("id") + " .main option:selected").html();
    var rating_string = $("#" + $ratingFilterItem.attr("id") + " .rating option:selected").html();

    var $setting = $("<div></div>");
    $setting.attr("class", $ratingFilterItem.attr("class") + "_setting");
    // FIXME: Need to set id?

    // "Clear filter" button
    $clearButton = $("<input type=\"button\" class=\"clear_filter\" value=\"X\"></input>");
    $setting.append($clearButton);

    var main_string = $("#" + $ratingFilterItem.attr("id") + " .main option:selected").html();
    var rating_string = $("#" + $ratingFilterItem.attr("id") + " .rating option:selected").html();
    label_string = main_string + " (" + rating_string + ")";
    $label = $("<label>" + label_string + "</label>");
    $setting.append($label);

    return $setting;
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
  function buildRatingFilterItem(id_str, options_table, is_simple_rating) {
    var $filterItem = $("<div class='rating_filter_item'></div>");
    $filterItem.attr("id", id_str);

    // Main drop-down list
    var $optionSelect = buildDropDownListFromTable("main", options_table);
    $filterItem.append($optionSelect);

    // Ratings drop-down list
    var ratings_table = is_simple_rating ? "ratings_simple" : "ratings";
    var $ratingSelect = buildDropDownListFromTable("rating", ratings_table);
    $ratingSelect.prepend($("<option value=\"all_ratings\">All ratings</option>"));
    $filterItem.append($ratingSelect);

    // "Add filter" button
    $addButton = $("<input type=\"button\" class=\"add_filter\" value=\"+\"></input>");
    $filterItem.append($addButton);

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

  // Filter categories group like filter items (e.g. technologies or solutions)
  // Child of: filter box
  // Parent of: filter items (both checkbox and rating types)
  function buildFilterCategory(id_str, name_str) {
    var $category, $header;
    $category = $("<div></div>");
    $category.attr("id", id_str);
    $category.attr("class", "filter_category");

    $header = $("<div></div>");
    $header.attr("class", "filter_category_header");
    $header.html(name_str);
    $header.appendTo($category);

    return $category;
  }

  // Filter boxes contain groups of filter items associated with one kind of
  // resource (partners, consultants, or opportunities)
  // Child of: filterPanel
  // Parent of: filter categories
  function buildFilterBox(id_str, label_str) {
    var $filterBox, $header, $radioInput, $label;

    $filterBox = $("<div></div>");
    $filterBox.attr("id", id_str);
    $filterBox.attr("class", "filter_box");

    $header = $("<div></div>");
    $header.attr("class", "filter_box_header");

    var radioId = makeId(label_str);
    $radioInput = $("<input type=\"radio\"></input>");
    $radioInput.attr("name", "resource_select");
    $radioInput.attr("value", label_str.toLowerCase());
    $radioInput.attr("id", radioId);

    $label = $("<label for=\"" + radioId + "\">" + label_str + "</label>");

    $header.append($radioInput);
    $header.append($label);
    $filterBox.append($header);

    return $filterBox;
  }

  function buildConsultantFilterBox() {
    var $filterBox;

    var $ratingAreaFilterCategory;
    var $partnerFilterCategory;

    var rtg_fc_id = "rating_filter_category";
    var ptr_fc_id = "partner_filter_category";

    $filterBox = buildFilterBox("consultant_filter_box", "Consultants");

    $ratingAreaFilterCategory = buildFilterCategory(rtg_fc_id, "Rating Areas");
    $partnerFilterCategory = buildFilterCategory(ptr_fc_id, "Associated Partners");

    $ratingAreaFilterCategory.append(buildRatingFilterItem(
      "rating_filter_item",
      "consultant_rating_areas",
      true
    ));

    // Associated partners filter category // FIXME: LEFT OFF HERE 6/24

    // selectQuery(
    //   "partners.official_name AS name, partners.id AS id",
    //   "partners, consultant_partner_junction",
    //   "partners.id = consultant_partner_junction.partner_id GROUP BY partners.official_name",
    //   function(result) {
    //     var $select = $("<select class=\"main\"></select>");
    //     var rows = JSON.parse(result);
    //     var $filterItem, id_str, val_str, label_str;
    //     for (var i = 0; i < rows.length; i += 1) {
    //       id_str = val_str = rows[i]["id"];
    //       label_str = rows[i]["name"];
    //       $filterItem = build // FIXME: can't pass build...() the right input?
    //     }
    //   }
    // );

    // $partnerFilterCategory.append(buildRatingFilterItem(
    //   "partner_filter_item",
    //   "consultant_partner_junction",
    //   true
    // ));

    $filterBox.append($ratingAreaFilterCategory);
    //$filterBox.append($partnerFilterCategory);

    return $filterBox;
  }

  function buildPartnerFilterBox() {
    var $filterBox;

    var $strengthFilterCategory;
    var $technologyFilterCategory;
    var $solutionFilterCategory;
    var $miscFilterCategory;
    var $verticalFilterCategory;
    var $regionFilterCategory;

    var str_fc_id = "strength_filter_category";
    var tech_fc_id = "technology_filter_category";
    var sol_fc_id = "solution_filter_category";
    var misc_fc_id = "misc_filter_category";
    var vert_fc_id = "vertical_filter_category";
    var reg_fc_id = "region_filter_category";

    $filterBox = buildFilterBox("partner_filter_box", "Partners");

    $strengthFilterCategory = buildFilterCategory(str_fc_id, "Strengths");
    $technologyFilterCategory = buildFilterCategory(tech_fc_id, "Technologies");
    $solutionFilterCategory = buildFilterCategory(sol_fc_id, "Solutions");
    $miscFilterCategory = buildFilterCategory(misc_fc_id, "Miscellaneous");
    $verticalFilterCategory = buildFilterCategory(vert_fc_id, "Verticals");
    $regionFilterCategory = buildFilterCategory(reg_fc_id, "Regions");

    $strengthFilterCategory.append(buildRatingFilterItem(
      "strength_filter_item",
      "partner_strengths",
      true
    ));
    $technologyFilterCategory.append(buildRatingFilterItem(
      "technology_filter_item",
      "technologies",
      true
    ));
    $solutionFilterCategory.append(buildRatingFilterItem(
      "solution_filter_item",
      "solutions",
      true
    ));
    $miscFilterCategory.append(buildRatingFilterItem(
      "misc_filter_item",
      "misc",
      true
    ));

    // Vertical filter category
    selectQuery("*", "verticals", "", function(result) {
      var rows = JSON.parse(result);

      for (var i = 0; i < rows.length; i += 1) {
        var $filterItem;
        var id_str, val_str, label_str;
        for (var col in rows[i]) {
          id_str = makeId(rows[i][col]) + "_filter_item";
          val_str = label_str = rows[i][col];
        }
        $filterItem = buildCheckboxFilterItem(id_str, val_str, label_str);
        $verticalFilterCategory.append($filterItem);
      }
    });

    // Region filter category
    selectQuery("*", "geographical_regions", "", function(result) {
      var rows = JSON.parse(result);

      for (var i = 0; i < rows.length; i += 1) {
        var $filterItem;
        var id_str, val_str, label_str;
        for (var col in rows[i]) {
          id_str = makeId(rows[i][col]) + "_filter_item";
          val_str = label_str = rows[i][col];
        }
        $filterItem = buildCheckboxFilterItem(id_str, val_str, label_str);
        $regionFilterCategory.append($filterItem);
      }
    });

    $filterBox.append($strengthFilterCategory);
    $filterBox.append($technologyFilterCategory);
    $filterBox.append($solutionFilterCategory);
    $filterBox.append($miscFilterCategory);
    $filterBox.append($verticalFilterCategory);
    $filterBox.append($regionFilterCategory);

    return $filterBox;
  }

  function buildView() {
    var $body;

    // Declare view elements
    var $form, $filterPanel, $resultPanel;

    var $oppFilterBox, $partnerFilterBox, $consultantFilterBox;

    // Instantiate view elements
    $body = $("body");

    $form = $("<form></form>"); // FIXME: If using >1 form, change this (more specific)
    $form.appendTo($body);

    $filterPanel = $("<div></div>");
    $filterPanel.attr("id", "filter_panel");
    $filterPanel.appendTo($form);

    $resultPanel = $("<div></div>");
    $resultPanel.attr("id", "result_panel");
    $resultPanel.appendTo($form);

    // $oppFilterBox = buildOpportunityFilterBox();
    // $oppFilterBox.attr("id", "opp_filter_box");
    // $oppFilterBox.appendTo($filterPanel);

    $partnerFilterBox = buildPartnerFilterBox();
    $partnerFilterBox.appendTo($filterPanel);

    $consultantFilterBox = buildConsultantFilterBox();
    $consultantFilterBox.appendTo($filterPanel);

    $filterPanel.appendTo($form);
    $resultPanel.appendTo($form);
    $form.appendTo($body);
  }

  /* *** LISTENERS *** */

  var $body = $("body");

  // Rating filter item listener
  $body.on("click", ".rating_filter_item .add_filter", function() {
    // FIXME: check if filter already set (check data structure here, in controller)
    // FIXME: This should affect model
    $filterItem = $(this).parent();
    $filterItem.before(buildRatingFilterSetting($filterItem));
  });

  // Filter form change listener: triggers query; displays results
  // Should draw filters/query details from some model, not the DOM
  // $body.on(); // FIXME: left off here II

  // Program start
  function execute() {
    alert("Executing...");
    buildView();
    // testQuery();
    // Listen for view changes
  }

  // Program start
  execute();
});
