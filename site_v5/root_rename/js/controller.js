/*jslint browser: true*/
/*global $, jQuery, alert*/

// FIME: Pull file paths from config.php file instead
var selectScript = "js/../servercode/select.php";
var insertScript = "js/../servercode/insert.php"; // FIXME: script needs to be written
var deleteScript = "js/../servercode/delete.php"; // FIXME: script needs to be written

//------------------------------------------------------------------------------
// MVC Abstraction // FIXME: Remove?
//------------------------------------------------------------------------------

var v = function view() {
  function render(object, to) {
    // FIXME: Remove? Continue?
  }
};

var m = {
  // FIXME: Remove? Continue?
  rating_filters: [],
  rating_filter_settings: [],
  checkbox_filters: [],

  addFilter: function(f) {
    if (f instanceof ratingFilter) {
      this.rating_filters.push(f);
    } else if (f instanceof ratingFilterSetting) {
      this.rating_filter_settings.push(f);
    } else if (f instanceof checkboxFilter) {
      this.checkbox_filters.push(f);
    }
  },

  removeFilter: function(f) {
    if (f instanceof ratingFilter) {
      this.rating_filters.splice(this.rating_filters.indexOf(f), 1);
    } else if (f instanceof ratingFilterSetting) {
      this.rating_filter_settings.splice(this.rating_filter_settings.indexOf(f), 1);
    } else if (f instanceof checkboxFilter) {
      this.checkbox_filters.splice(this.checkbox_filters.indexOf(f), 1);
    }
  },

  findFilterById: function(id) {
    for (var i = this.rating_filter_settings.length-1; i >= 0; i -= 1) {
      if (this.rating_filter_settings[i].id === id) {
        return this.rating_filter_settings[i];
      }
    }
    for (var i = this.rating_filters.length-1; i >= 0; i -= 1) {
      if (this.rating_filters[i].id === id) {
        return this.rating_filters[i];
      }
    }
    for (var i = this.checkbox_filters.length-1; i >= 0; i -= 1) {
      if (this.checkbox_filters[i].id === id) {
        return this.checkbox_filters[i];
      }
    }
    return null;
  }
};

//------------------------------------------------------------------------------
// HELPER/UTILITY FUNCTIONS
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
  return string.trim().replace(/ /g, "_").toLowerCase();
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

// Helper: Convert rows to an array in the form:
// ["row0coll0< del >row0col1< del >...", ..., "rowNcol0< del >rowNcol1< del >..."]
// where < del > is a delimiter (e.g. comma or other string)
// EXAMPLE:
// If there are M rows, N columns, and the delimiter is ", "
// array[0]: "<row 0 col 0>, <row 0 col 1>, ..., <row 0 col N>"
// array[1]: "<row 1 col 0>, <row 1 col 1>, ..., <row 1 col N>"
// ...
// array[M]: "<row M col 0>, <row M col 1>, ..., <row M col N>"
function rowsToStringArray(results, delimiter) {
  var obj = JSON.parse(results);
  var length = obj.length;
  var array = [];
  for (var i = 0; i < length; i++) {
    array[i] = objectToString(obj[i], delimiter);
  }
  return array;
}

//------------------------------------------------------------------------------
// DOM element builders
//------------------------------------------------------------------------------

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
    selectQuery("*", table_name, "", function(result) {
      var rows = JSON.parse(result);
      var $option;
      var optionString, delimiter = " - ";

      var length = rows.length;
      for (var i = 0; i < length; i += 1) {
        $option = $("<option></option>");
        optionString = "";
        for (var col in rows[i]) {
          if (rows[i].hasOwnProperty(col)) {
            if (col === "id") {
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

  // Build a filter setting from a rating filter item // FIXME: reword comment
  function buildRatingFilterSetting(id_str, $ratingFilterItem) {
    var main_string = $("#" + $ratingFilterItem.attr("id") + " .main option:selected").html();
    var rating_string = $("#" + $ratingFilterItem.attr("id") + " .rating option:selected").html();

    var $setting = $("<div></div>");
    $setting.attr("id", id_str);
    $setting.attr("class", $ratingFilterItem.attr("class") + "_setting");

    // "Clear filter" button
    var $clearButton = $("<input type=\"button\" class=\"clear_filter\" value=\"X\"></input>");
    $setting.append($clearButton);

    var main_string = $("#" + $ratingFilterItem.attr("id") + " .main option:selected").html();
    var rating_string = $("#" + $ratingFilterItem.attr("id") + " .rating option:selected").html();
    var label_string = main_string + " (" + rating_string + ")";
    var $label = $("<label>" + label_string + "</label>");
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
  function buildRatingFilterItem(id_str, options_table, is_simple_rating, trigger_class) {
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
    var $addButton = $("<input type=\"button\" value=\"+\"></input>");
    $addButton.attr("class", trigger_class);
    $filterItem.append($addButton);

    return $filterItem;
  }

  // Create a checkbox and label filter element (e.g. for VERTICALS and REGIONS)
  function buildCheckboxFilterItem(id_str, value_str) {
    var $filterItem = $("<div class='checkbox_filter_item'></div>");
    $filterItem.attr("id", id_str);

    var $checkbox = $("<input type='checkbox'></input>");
    $checkbox.attr("name", id_str);
    $checkbox.attr("value", value_str);
    $checkbox.attr("class", "filter_checkbox"); // FIXME: Remove or change?
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

//------------------------------------------------------------------------------
// OBJECTS (DOM elements [use templates?], results [each entity], ...)
//------------------------------------------------------------------------------

// Generic view object // FIXME: might remove
function viewObject(id_str, class_str, value_str, header_str, parent_obj) {
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
function checkboxFilter(id_str, value_str, table_str) {
  this.id = id_str;
  this.value = value_str;
  this.table = table_str;

  this.domCheckbox;
  this.is_checked = false;
  this.domElement = buildCheckboxFilterItem(this.id, this.value);
  this.domCheckbox = this.domElement.find("input[type='checkbox']");

  this.setChecked = function(boolean) {
    this.is_checked = boolean;
    this.domCheckbox.prop("checked", this.is_checked);
  };

  this.toggle = function() {
    this.is_checked = !this.is_checked;
    this.domCheckbox.prop("checked", this.is_checked);
  };
}
checkboxFilter.prototype.class = "checkbox_filter_item";
checkboxFilter.prototype.checkbox_class = "filter_checkbox";

// Rating filter
function ratingFilter(id_str, value_table, is_simple_bool) {
  this.id = id_str;
  // this.value = value_str; // FIXME LEFT OFF HERE W/ PARAMS
  this.is_simple = is_simple_bool;
  this.domElement = buildRatingFilterItem(this.id, value_table, this.is_simple, this.trigger_class);
}
ratingFilter.prototype.class = "rating_filter_item";
ratingFilter.prototype.trigger_class = "rating_filter_trigger";

// Rating filter setting (created from an  existing rating filter object)
function ratingFilterSetting(ratingFilter_obj) {
  this.id = ratingFilter_obj.id + "_setting";
  this.class = "rating_filter_item_setting";

  this.value = ratingFilter_obj.domElement.find(".main option:selected").html();
  this.rating = ratingFilter_obj.domElement.find(".rating option:selected").html();

  this.domElement = buildRatingFilterSetting(this.id, ratingFilter_obj.domElement); // FIXME: change this ctor & params - need to pass rating directly from here, not rely on domElement
}

// Filter category: contains a set of filters
function filterCategory(id_str, name_str) {
  this.id = id_str;
  this.name = name_str;
  this.domElement = buildFilterCategory(this.id, this.name);

  this.checkboxFilters = [];
  this.ratingFilters = [];
  this.ratingFilterSettings = [];

  this.addFilter = function(f) {
    if (f instanceof checkboxFilter) {
      this.checkboxFilters.push(f);
      this.domElement.append(f.domElement);
    } else if (f instanceof ratingFilter) {
      this.ratingFilters.push(f);
      this.domElement.append(f.domElement);
    } else if (f instanceof ratingFilterSetting) {
      this.ratingFilterSettings.push(f);
      this.domElement.append(f.domElement);
    }
  };

  this.removeFilterSetting = function(f) {
    if (f instanceof ratingFilterSetting) {
      f.domElement.remove(); // FIXME: This is a VIEW thing
      this.ratingFilterSettings.splice(this.ratingFilterSettings.indexOf(f), 1);
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

// Filter box: contains filter categories
function filterBox(id_str, label_str) {
  this.id = id_str;
  this.class = "filter_box";
  this.domElement = buildFilterBox(this.id, label_str);

  this.filterCategories = [];

  this.addFilterCategory = function(fc) {
    if (fc instanceof filterCategory) {
      this.filterCategories.push(fc);
      this.domElement.append(fc.domElement);
    }
  };
}

//------------------------------------------------------------------------------
// BUILD (DOM elements)
//------------------------------------------------------------------------------

// Construct objects and render to View
function build() {

  var filterBoxes = {
    partners: new filterBox("partner_filter_box", "Partners"),
    consultants: new filterBox("consultant_filter_box", "Consultants"),
    opportunities: new filterBox("opp_filter_box", "Opportunities")
  };

  var par_categories = {
    strengths: {
      object: new filterCategory("strength_filter_category", "Strengths"),
      assoc_table: "partner_strengths"
    },
    technologies: {
      object: new filterCategory("technology_filter_category", "Technologies"),
      assoc_table: "technologies"
    },
    solutions: {
      object: new filterCategory("solution_filter_category", "Solutions"),
      assoc_table: "solutions"
    },
    misc: {
      object: new filterCategory("misc_filter_category", "Misc"),
      assoc_table: "misc"
    },
    verticals: {
      object: new filterCategory("vertical_filter_category", "Verticals"),
      assoc_table: "verticals"
    },
    regions: {
      object: new filterCategory("region_filter_category", "Regions"),
      assoc_table: "geographical_regions" // FIXME: Update to "regions" once db similarly updated
    }
  };

  // Ratings filters

  var f;

  f = new ratingFilter("str_filter", par_categories.strengths.assoc_table, true);
  par_categories.strengths.object.addFilter(f);
  m.addFilter(f);

  f = new ratingFilter("tech_filter", par_categories.technologies.assoc_table, true);
  par_categories.technologies.object.addFilter(f);
  m.addFilter(f);

  f = new ratingFilter("sol_filter", par_categories.solutions.assoc_table, true);
  par_categories.solutions.object.addFilter(f);
  m.addFilter(f);

  f = new ratingFilter("misc_filter", par_categories.misc.assoc_table, true);
  par_categories.misc.object.addFilter(f);
  m.addFilter(f);

  // Verticals checkbox filters
  var tableV = "verticals";
  selectQuery("*", tableV, "", function(data) {
    var rows = JSON.parse(data);
    for (var i = 0; i < rows.length; i += 1) {
      var id, val;
      for (var col in rows[i]) {
        id = makeId(rows[i][col] + "_vert_checkbox_filter");
        val = rows[i][col];
      }
      var f = new checkboxFilter(id, val, tableV);
      par_categories.verticals.object.addFilter(f);
      m.addFilter(f);
    }
  });

  // Regions checkbox filters // FIXME: Change table name with db
  var tableR = "geographical_regions";
  selectQuery("*", tableR, "", function(data) {
    var rows = JSON.parse(data);
    for (var i = 0; i < rows.length; i += 1) {
      var id, val;
      for (var col in rows[i]) {
        id = makeId(rows[i][col] + "_reg_checkbox_filter");
        val = rows[i][col];
      }
      var f = new checkboxFilter(id, val, tableR);
      par_categories.regions.object.addFilter(f);
      m.addFilter(f);
    }
  });

  // Put filter categories into partner filter box
  for (var cat in par_categories) {
    filterBoxes.partners.addFilterCategory(par_categories[cat].object);
  }

  // var consultantFilterCategories =
  // var opportunityFilterCategories =

/* FIXME: later - move this to a View object/module or something */

  var $body = $("body");
  var $form = $("<form></form>"); // FIXME: If using >1 form, change this (more specific)
  $form.appendTo($body);

  var $filterPanel = $("<div></div>");
  $filterPanel.attr("id", "filter_panel");
  $filterPanel.appendTo($form);

  var $resultPanel = $("<div></div>");
  $resultPanel.attr("id", "result_panel");
  $resultPanel.appendTo($form);

  filterBoxes.partners.domElement.appendTo($filterPanel);
}

//------------------------------------------------------------------------------
// RENDER (to View/DOM)
//------------------------------------------------------------------------------

//------------------------------------------------------------------------------
// EVENT HANDLING
//------------------------------------------------------------------------------

$(document).ready(function() {

  /* *** FIXME: BEGIN TEST AREA *** */

//  // FIXME: TEST
//  function testQuery() {
//    var $body = $("body");
//    var result = selectQuery("*", "technologies", "", function(d) {
//      $body.append("<p>"+d+"</p>")
//
//      d2 = JSON.parse(d);
//      for (var i = d2.length - 1; i >= 0; i -= 1) {
//        for (var col in d2[i]) {
//          $body.prepend($("<p>" + d2[i][col] + "</p>"));
//        }
//      }
//      // $body.append("<pre>"+d3+"</pre>")
//    });
//  }
//
//  // FIXME: Test. Create options where id stored in value
//  function testBuildSelect(options_table_name) {
//    var $body = $("body");
//    var $select = $("<select id=\"dropdown\"></select>");
//    var result = selectQuery("*", options_table_name, "", function(result) {
//      var rows = JSON.parse(result);
//      var $option;
//      var optionString, delimiter = " - ";
//
//      var length = rows.length;
//      for (var i = 0; i < length; i += 1) {
//        $option = $("<option></option>");
//        optionString = "";
//        for (var col in rows[i]) {
//          if (rows[i].hasOwnProperty(col)) {
//            if (col == "id") {
//              $option.attr("value", rows[i][col]);
//            } else {
//              optionString += rows[i][col] + delimiter;
//            }
//          }
//        }
//        $option.html(optionString.slice(0, -1 * delimiter.length));
//        $select.append($option);
//      }
//
//      $body.append($select);
//    });
//  }
//
//  // FIXME: test - Remove
//  $("body").on("change", "#dropdown", function() {
//    $("body").append("<p>"+$(this).val()+"<p>");
//  });

  /* *** FIXME: END TEST AREA *** */

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
      true,
      "rating_filter_trigger"
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

  // function buildView() {
  //   /* Declare some view elements */
  //
  //   var $body;
  //
  //   var $form, $filterPanel, $resultPanel;
  //
  //   var $oppFilterBox, $consultantFilterBox;
  //
  //   /* Instantiate some view elements */
  //
  //   $body = $("body");
  //
  //   $form = $("<form></form>"); // FIXME: If using >1 form, change this (more specific)
  //   $form.appendTo($body);
  //
  //   $filterPanel = $("<div></div>");
  //   $filterPanel.attr("id", "filter_panel");
  //   $filterPanel.appendTo($form);
  //
  //   $resultPanel = $("<div></div>");
  //   $resultPanel.attr("id", "result_panel");
  //   $resultPanel.appendTo($form);
  //
  //   // $oppFilterBox = buildOpportunityFilterBox();
  //   // $oppFilterBox.attr("id", "opp_filter_box");
  //   // $oppFilterBox.appendTo($filterPanel);
  //
  //   $consultantFilterBox = buildConsultantFilterBox();
  //   $consultantFilterBox.appendTo($filterPanel);
  //
  //   $filterPanel.appendTo($form);
  //   $resultPanel.appendTo($form);
  //   $form.appendTo($body);
  // }

  /* *** LISTENERS *** */

  var $body = $("body");

  // Rating filter add button
  $body.on("click", "." + ratingFilter.prototype.trigger_class, function() {
    var $trigger_element = $(this).parent();

    // FIXME: Remove
    var trig_filt = m.findFilterById($trigger_element.attr("id"));
    var setting = new ratingFilterSetting(trig_filt);
    m.addFilter(setting);
    trig_filt.domElement.before(setting.domElement);

    // FIXME: Remove
    alert(m.rating_filter_settings[0].value + ", " + m.rating_filter_settings[0].rating);
  });

  // Checkbox filters
  $body.on("change", "." + checkboxFilter.prototype.checkbox_class, function() {
    var id = $(this).attr("name"); // FIXME: Change this to "id"
    var trig_filt = m.findFilterById(id);

    // Toggle filter value
    trig_filt.toggle();

    if (trig_filt.value === "All" && trig_filt.is_checked) {
      var filter;
      for (var i = m.checkbox_filters.length - 1; i >= 0; i -= 1) {
        filter = m.checkbox_filters[i];
        if (filter.table === trig_filt.table) {
          filter.setChecked(true);
        }
      }
    } else if (!(trig_filt.is_checked)) { // FIXME: Find by id instead? (still need to match table)
      var filter;
      for (var i = m.checkbox_filters.length - 1; i >= 0; i -= 1) {
        filter = m.checkbox_filters[i];
        if (filter.value === "All" && filter.table === trig_filt.table) {
          filter.setChecked(false);
        }
      }
    }

    // FIXME: Remove below
//    var s = "", t = "";
//    for (var i = m.checkbox_filters.length-1; i >= 0; i -= 1) {
//      if (m.checkbox_filters[i].is_checked) {
//        s = s + m.checkbox_filters[i].id + " : " + m.checkbox_filters[i].value + " : " + m.checkbox_filters[i].table + "\n";
//      } else {
//        t = t + m.checkbox_filters[i].id + " : " + m.checkbox_filters[i].value + " : " + m.checkbox_filters[i].table + "\n";
//      }
//    }
//    alert("CHECKED (id:value):\n" + s + "\nUNCHECKED (id:value):\n" + t);
  });

  // Filter form change listener: triggers query; displays results
  // Should draw filters/query details from some model, not the DOM
  // $body.on(); // FIXME: left off here II

  // Program start
  function execute() {
    alert("Executing...");
    //buildView(); // FIXME: Should be build() -> builds model and renders
    build();
    // Listen for view changes
  }

  // Program start
  execute();
});
