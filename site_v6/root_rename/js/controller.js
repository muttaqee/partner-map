/*jslint browser: true*/
/*global $, jQuery, alert*/

// FIME: Pull file paths from config.php file instead
var selectScript = "js/../servercode/select.php";
var insertScript = "js/../servercode/insert.php"; // FIXME: script needs to be written
var deleteScript = "js/../servercode/delete.php"; // FIXME: script needs to be written

var tables; // Array of lookup tables (populated in load() function)
var partner_cards;
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

  filter_boxes: [],

  partner_cards: [],
  consultant_cards: [],
  opportunity_cards: [],

  filter_categories: {
    partners: [],
    opportunities: [],
    consultants: []
  },

  rating_filters: [],
  rating_filter_settings: [],
  checkbox_filters: [],

  active_filters: {
    partner_strengths: [],
    technologies: [],
    solutions: [],
    misc: [],
    verticals: [],
    geographical_regions: []
  },

  addFilterBox: function(b) {
    if (b instanceof filterBox) {
      this.filter_boxes.push(b);
    }
  },

  removeFilterBox: function(b) {
    if (b instanceof filterBox) {
      // FIXME: Implement or remove?
    }
  },

  expandFilterBox: function(b) {
    if (b instanceof filterBox) {
      // FIXME: Implement
    }
  },

  collapseFilterBox: function(b) {
    if (b instanceof filterBox) {
      // FIXME: Implement
    }
  },

  addFilterCategory: function(c) {
    // FIXME: Implement
  },

  addFilter: function(f) {
    if (f instanceof ratingFilter) {
      this.rating_filters.push(f);
    } else if (f instanceof ratingFilterSetting) {
      this.rating_filter_settings.push(f);
      this.active_filters[f.table].push(f);
    } else if (f instanceof checkboxFilter) {
      this.checkbox_filters.push(f);
    }
  },

  removeFilter: function(f) {
    if (f instanceof ratingFilter) {
      this.rating_filters.splice(this.rating_filters.indexOf(f), 1);
    } else if (f instanceof ratingFilterSetting) {
      this.rating_filter_settings.splice(this.rating_filter_settings.indexOf(f), 1);
      this.active_filters[f.table].splice(this.active_filters[f.table].indexOf(f), 1);
    } else if (f instanceof checkboxFilter) {
      this.checkbox_filters.splice(this.checkbox_filters.indexOf(f), 1);
      this.active_filters[f.table].splice(this.active_filters[f.table].indexOf(f), 1);
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

  function buildDropDownList(class_string, table_name) {
    var $select = $("<select class=\"" + class_string + "\"></select>");
    var $option;

    var table = tables[table_name];
    var id, val;
    for (var row in table) {
      id = table[row]["id"];
      val = table[row]["val"];

      $option = $("<option></option>");
      $option.attr("id", id);
      $option.html(val);
      $select.append($option);
    }
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
    var $optionSelect = buildDropDownList("main", options_table);
    $filterItem.append($optionSelect);

    // Ratings drop-down list
    var ratings_table = is_simple_rating ? "ratings_simple" : "ratings";
    var $ratingSelect = buildDropDownList("rating", ratings_table);
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
function checkboxFilter(table_id_str, dom_id_str, value_str, table_str) {
  this.table_id = table_id_str;
  this.id = dom_id_str;
  this.value = value_str;
  this.table = table_str;

  this.domCheckbox;
  this.is_checked = false;
  this.domElement = buildCheckboxFilterItem(this.id, this.value);
  this.domCheckbox = this.domElement.find("input[type='checkbox']");

  this.setChecked = function(boolean) {
    if (!(this.is_checked) && boolean) {
      m.active_filters[this.table].push(this);
    } else if (this.is_checked && !boolean) {
      m.active_filters[this.table].splice(m.active_filters[this.table].indexOf(this), 1);
    }
    //alert(JSON.stringify(m.active_filters[this.table].length)); // FIXME: Remove
    this.is_checked = boolean;
    this.domCheckbox.prop("checked", this.is_checked);
  };

  this.toggle = function() {
    this.setChecked(!this.is_checked);
  };
}
checkboxFilter.prototype.class = "checkbox_filter_item";
checkboxFilter.prototype.checkbox_class = "filter_checkbox";

// Rating filter
function ratingFilter(id_str, value_table, is_simple_bool) {
  this.id = id_str;
  this.is_simple = is_simple_bool;
  this.table = value_table;
  this.domElement = buildRatingFilterItem(this.id, value_table, this.is_simple, this.trigger_class);
}
ratingFilter.prototype.class = "rating_filter_item";
ratingFilter.prototype.trigger_class = "rating_filter_trigger"; // Inject dependency here

// Rating filter setting (created from an  existing rating filter object)
function ratingFilterSetting(ratingFilter_obj) {
  this.value = ratingFilter_obj.domElement.find(".main option:selected").html();
  this.rating = ratingFilter_obj.domElement.find(".rating option:selected").html();

  this.id = ratingFilter_obj.id + "_" + makeId(this.value) + "_" + makeId(this.rating) + "_setting";
  this.table_id = ratingFilter_obj.domElement.find(".main option:selected").attr("id");
  this.rating_id = ratingFilter_obj.domElement.find(".rating option:selected").attr("id");
  this.table = ratingFilter_obj.table;
  this.is_simple = ratingFilter_obj.is_simple;

  this.domElement = buildRatingFilterSetting(this.id, ratingFilter_obj.domElement); // FIXME: change this ctor & params - need to pass rating directly from here, not rely on domElement
}
ratingFilterSetting.prototype.class = "rating_filter_item_setting";
ratingFilterSetting.prototype.clear_class = "clear_filter";

// Filter category: contains a set of filters
function filterCategory(id_str, name_str, table_str) {
  this.id = id_str;
  this.name = name_str;
  this.table = table_str;
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

function partnerCard() {
  this.name = null;
  this.partner_id = null;

  this.verticals = [];
  this.regions = [];

  this.partner_strength_ratings = [];
  this.technology_ratings = [];
  this.solution_ratings = [];
  this.misc_ratings = [];

  this.notes = "";

  this.domElement = null;
}

function consultantCard() {

}

function opportunityCard() {

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
  for (var box in filterBoxes) {
    m.addFilterBox(filterBoxes[box]);
  }

  var par_categories = {
    strengths: {
      assoc_table: "partner_strengths",
      object: new filterCategory("strength_filter_category", "Strengths", "partner_strengths")
    },
    technologies: {
      assoc_table: "technologies",
      object: new filterCategory("technology_filter_category", "Technologies", "technologies")
    },
    solutions: {
      assoc_table: "solutions",
      object: new filterCategory("solution_filter_category", "Solutions", "solutions")
    },
    misc: {
      assoc_table: "misc",
      object: new filterCategory("misc_filter_category", "Misc", "misc")
    },
    verticals: {
      assoc_table: "verticals",
      object: new filterCategory("vertical_filter_category", "Verticals", "verticals")
    },
    regions: {
      assoc_table: "geographical_regions", // FIXME: Update to "regions" once db similarly updated
      object: new filterCategory("region_filter_category", "Regions", "geographical_regions")
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
      var table_id, dom_id, val;
      for (var col in rows[i]) {
        table_id = rows[i][col];
        dom_id = makeId(rows[i][col] + "_vert_checkbox_filter");
        val = rows[i][col];
      }
      var f = new checkboxFilter(table_id, dom_id, val, tableV);
      par_categories.verticals.object.addFilter(f);
      m.addFilter(f);
    }
  });

  // Regions checkbox filters // FIXME: Change table name with db
  var tableR = "geographical_regions";
  selectQuery("*", tableR, "", function(data) {
    var rows = JSON.parse(data);
    for (var i = 0; i < rows.length; i += 1) {
      var table_id, dom_id, val;
      for (var col in rows[i]) {
        table_id = rows[i][col];
        dom_id = makeId(rows[i][col] + "_reg_checkbox_filter");
        val = rows[i][col];
      }
      var f = new checkboxFilter(table_id, dom_id, val, tableR);
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
// Search
//------------------------------------------------------------------------------

// Return partner rows (filtered)
function searchPartners() {
  var select_str = "DISTINCT *";
  var from_str = "partners";
  var where_str = ""; // Will contain UNIONS // FIXME: temporary

  // Use m.active_filters[table_name] // FIXME: LEFT OFF HERE
  var f = m.active_filters;
  var t = "partner_strengths";
  var length = f[t].length;
  if (length > 0) {
    from_str += t + ", "; // FIXME: remove last comma
    where_str += t + ".rating" + " = " + "\"" + f[t][0].rating + "\", ";
    for (var i = 1; i < length; i += 1) {
      where_str += ", " +
    }
  }

  where_str = "(" + where_str + ")";

  // for (var table in f) {
  //   if (f[table].length > 0) {
  //     from_str += ", " + table;
  //
  //   }
  // }

  // Gather all filter values (setting and checkbox)
  var settings = m.rating_filter_settings;
  var checkboxes = m.checkbox_filters;



  selectQuery(select_str, from_str, where_str, function(data) {
    var rows = JSON.parse(data);
    // FIXME: Drop data into cards as buckets, creating new as needed (modify model m)
  });
}

//function dropIntoPartnerCard(id, )

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


    var trig_filt = m.findFilterById($trigger_element.attr("id"));
    var setting = new ratingFilterSetting(trig_filt);
    if (m.findFilterById(setting.id) == null) {
      m.addFilter(setting);
      // setting.domElement.slideDown();
      // trig_filt.domElement.before(setting.domElement);

      setting.domElement.insertBefore(trig_filt.domElement).hide().slideDown("fast");
    }

    // FIXME: Remove
    // var arr = m.rating_filter_settings;
    // var s = "";
    // for (var i = 0; i < arr.length; i += 1) {
    //   s += arr[i].id + ": " + arr[i].value + " (" + arr[i].rating + ")\n";
    // }
    // alert("Settings: " + s + "(" + arr.length + " total)");
  });

  $body.on("click", "." + ratingFilterSetting.prototype.clear_class, function() {
    var $setting = $(this).parent();
    m.removeFilter(m.findFilterById($setting.attr("id")));
    $setting.slideUp("fast");
    setTimeout(function() {
      $setting.remove();
    }, 1000);

    // FIXME: Remove
    var arr = m.rating_filter_settings;
    var s = "";
    for (var i = arr.length-1; i >= 0; i -= 1) {
      s += arr[i].value + ", " + arr[i].rating + "\n";
    }
    //alert("Settings:\n" + s + " (" + arr.length + " total)");
  });

  // Checkbox filters
  $body.on("change", "." + checkboxFilter.prototype.checkbox_class, function() {
    var id = $(this).attr("name"); // FIXME: Change this to "id"
    var trig_filt = m.findFilterById(id);

    // Toggle filter value
    trig_filt.toggle();

    // Checking "All" checks all boxes in the same category
    if (trig_filt.value === "All" && trig_filt.is_checked) {
      var filter;
      for (var i = m.checkbox_filters.length - 1; i >= 0; i -= 1) {
        filter = m.checkbox_filters[i];
        if (filter.table === trig_filt.table) {
          filter.setChecked(true);
        }
      }
    }
    // Unchecking any box also unchecks "All" in the same category
    else if (!(trig_filt.is_checked)) { // FIXME: Find by id instead? (still need to match table)
      var filter;
      for (var i = m.checkbox_filters.length - 1; i >= 0; i -= 1) {
        filter = m.checkbox_filters[i];
        if (filter.value === "All" && filter.table === trig_filt.table) {
          filter.setChecked(false);
        }
      }
    }

    // FIXME: Remove below
    alert(trig_filt.table_id + " in " + trig_filt.table + ", is_checked: " + trig_filt.is_checked);
   var s = "", t = "";
   for (var i = m.checkbox_filters.length-1; i >= 0; i -= 1) {
     if (m.checkbox_filters[i].is_checked) {
       s = s + m.checkbox_filters[i].id + " : " + m.checkbox_filters[i].value + " : " + m.checkbox_filters[i].table + "\n";
     } else {
       t = t + m.checkbox_filters[i].id + " : " + m.checkbox_filters[i].value + " : " + m.checkbox_filters[i].table + "\n";
     }
   }
   //alert("CHECKED (id:value):\n" + s + "\nUNCHECKED (id:value):\n" + t);
  });

  // Filter form change listener: triggers query; displays results
  // Should draw filters/query details from some model, not the DOM
  // $body.on(); // FIXME: left off here II

  // Use to db to populate DOM elements
  function load() {
    var ratings_simple = []; // [{id: "1", val: "A"}, ...]
    var ratings = [];

    var partner_strengths = [];
    var technologies = [];
    var solutions = [];
    var misc = [];

    var verticals = [];
    var geographical_regions = [];

    tables = {
      ratings_simple: ratings_simple,
      ratings: ratings,
      partner_strengths: partner_strengths,
      technologies: technologies,
      solutions: solutions,
      misc: misc,
      verticals: verticals,
      geographical_regions: geographical_regions
    }

    var count = 0;

    selectQuery("*", "ratings_simple", "", function(data) {
      var rows = JSON.parse(data);
      var length = rows.length;
      var tmp;
      for (var i = 0; i < length; i += 1) {
        tmp = {
          id: rows[i]["grade"],
          val: rows[i]["grade"],
          grade: rows[i]["grade"]
        };
        ratings_simple.push(tmp);
      }
      tables["ratings_simple"] = ratings_simple;
      count += 1;
    });

    selectQuery("*", "ratings", "", function(data) {
      var rows = JSON.parse(data);
      var length = rows.length;
      var tmp;
      for (var i = 0; i < length; i += 1) {
        tmp = {
          id: rows[i]["grade"],
          val: rows[i]["grade"],
          grade: rows[i]["grade"]
        };
        ratings.push(tmp);
      }
      //alert(JSON.stringify(ratings)); // Remove
      count += 1;
    });

    selectQuery("*", "partner_strengths", "", function(data) {
      var rows = JSON.parse(data);
      var length = rows.length;
      var tmp;
      for (var i = 0; i < length; i += 1) {
        tmp = {
          id: rows[i]["strength"],
          val: rows[i]["strength"],
          strength: rows[i]["strength"]
        };
        partner_strengths.push(tmp);
      }
      //alert(JSON.stringify(partner_strengths)); // Remove
      count += 1;
    });

    selectQuery("*", "technologies", "", function(data) {
      var rows = JSON.parse(data);
      var length = rows.length;
      var tmp;
      for (var i = 0; i < length; i += 1) {
        tmp = {
          id: rows[i]["id"],
          val: rows[i]["technology_type"] + " - " + rows[i]["technology"],
          technology_type: rows[i]["technology_type"],
          technology: rows[i]["technology"]
        };
        technologies.push(tmp);
      }
      //alert(JSON.stringify(technologies)); // Remove
      count += 1;
    });

    selectQuery("*", "solutions", "", function(data) {
      var rows = JSON.parse(data);
      var length = rows.length;
      var tmp;
      for (var i = 0; i < length; i += 1) {
        tmp = {
          id: rows[i]["id"],
          val: rows[i]["solution_type"] + " - " + rows[i]["solution"],
          technology_type: rows[i]["solution_type"],
          technology: rows[i]["solution"]
        };
        solutions.push(tmp);
      }
      //alert(JSON.stringify(solutions)); // Remove
      count += 1;
    });

    selectQuery("*", "misc", "", function(data) {
      var rows = JSON.parse(data);
      var length = rows.length;
      var tmp;
      for (var i = 0; i < length; i += 1) {
        tmp = {
          id: rows[i]["type"],
          val: rows[i]["type"],
          type: rows[i]["type"]
        };
        misc.push(tmp);
      }
      //alert(JSON.stringify(misc)); // Remove
      count += 1;
    });

    selectQuery("*", "verticals", "", function(data) {
      var rows = JSON.parse(data);
      var length = rows.length;
      var tmp;
      for (var i = 0; i < length; i += 1) {
        tmp = {
          id: rows[i]["vertical"],
          val: rows[i]["vertical"],
          vertical: rows[i]["vertical"]
        };
        verticals.push(tmp);
      }
      //alert(JSON.stringify(verticals)); // Remove
      count += 1;
    });

    selectQuery("*", "geographical_regions", "", function(data) {
      var rows = JSON.parse(data);
      var length = rows.length;
      var tmp;
      for (var i = 0; i < length; i += 1) {
        tmp = {
          id: rows[i]["region"],
          val: rows[i]["region"],
          region: rows[i]["region"]
        };
        geographical_regions.push(tmp);
      }
      //alert(JSON.stringify(geographical_regions)); // Remove
      tables["geographical_regions"] = geographical_regions;

      alert('tables... ' + JSON.stringify(tables)); // Remove
      count += 1;
    });

    // alert("CALL BACK"); // FIXME: Remove
    // if (typeof(callback) == "function") {
    //   callback();
    // }

    while (count < tables.length) {

    }
    return count;
  }

  // Program start
  function execute() {
    alert("Executing...");
    // load(); // Load table info from db
    // //buildView(); // FIXME: Should be build() -> builds model and renders
    load();
    setTimeout(function() {
      build();
    }, 100); // FIXME: Use more reliable function ordering
    // Listen for view changes
  }

  // Program start
  execute();
});
