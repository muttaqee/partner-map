/*jslint browser: true*/
/*global $, jQuery, alert*/

// FIXME: Pull file paths from config.php file instead
var queryScript = "js/../servercode/query.php";
var selectScript = "js/../servercode/select.php";
var insertScript = "js/../servercode/insert.php"; // FIXME: script needs to be written
var alterScript = "js/../servercode/alter.php"; // FIXME: script needs to be written
var deleteScript = "js/../servercode/delete.php"; // FIXME: script needs to be written

var tables; // Array of lookup tables (populated in load() function)
var partner_cards;

//------------------------------------------------------------------------------
// NEW CONTROLLER // FIXME: Remove?
//------------------------------------------------------------------------------

/*
FUNCTIONALITY NEEDED:

Add/INSERT partner, consultnat, opp (adds to assoc primary and junction tables)
Edit/ALTER partner, consultant, opp (alters assoc. primary and junction tables)
Remove/DELETE partner, consul, opp (deletes assoc. primary and junction tables)
Later:
Add rating/str/tech/sol/misc/vert/reg (to lookup table -- affects model & view)
Rem " (affected rows must be fixed first)
*/

/*
Plan: make model here - (1) db entities (primary & lookup), (2) view entities
Make UI.
Style, document.
*/

/* Model */

/*
Idea:
-- Upon prompt to query, load entities of interest from db to this model
-- Write from this model to view as necessary
*/

function Rating(id, name) {
  this.id = id;
  this.name = name;
}

function RatingSimple(id, name) {
  this.id = id;
  this.name = name;
}

function LookupEntity(id, name) {
  this.id = id;
  this.name = name;

  this.table = "";
}

function RatingPair(entity, rating) {
  this.entity = entity; // Has own id and table
  this.rating = rating; // Has own id
}

// Encapsulates a set of entity-rating pairs (e.g. partner technology ratings)
function RatingSet(name) {
  this.name = name; // Name of area or topic of ratings (e.g. from technologies)
  this.set = []; // Set of entity-rating pairs

  this.add = function(rating_pair) {
    if (rating_pair instanceof RatingPair) {
      this.set.push(rating_pair);
    }
  }
}

// Use to fill content
function Partner(id, name) {
  this.id = id;
  this.name = name;

  this.is_partner_plus = false;
  this.notes = "";

  // Contains rating sets, each of which contain rating pairs
  this.ratings = []; // E.g. technologies, solutions, misc
  this.features = []; // E.g. verticals, regions

  this.opportunities = []; // (Associated)
  this.consultants = []; // (Associated)

  this.addRatingSet = function(rating_set) {
    if (rating_set instanceof RatingSet) {
      this.ratings.push(rating_set);
    }
  }

  this.addRatingPair = function(rating_set, rating_pair) {
    if (rating_pair instanceof RatingPair) {
      if (rating_set instanceof RatingSet) {
        this.ratings[rating_set.name].push(rating_pair);
      } else if (typeof rating_set === "string") {
        this.ratings[rating_set].push(rating_pair);
      }
    }
  }
}

// FIXME: Remove?
// function Table(name, columns) {
//   this.name = name;
//   this.columns = columns;
//
//   this.addColumns = function(columns) {
//     if (typeof columns === "object") {
//       // Push each column to the end of the columns array
//       this.columns.push.apply(this.columns, columns);
//     }
//   }
// }

//------------------------------------------------------------------------------
// Begin here // FIXME
//------------------------------------------------------------------------------

function Table(id, name, label, type, is_searchable, rating_table) {
  this.id = id;
  this.name = name;
  this.label = label;
  this.type = type;
  this.is_searchable = is_searchable;
  this.rating_table = rating_table; // Name, not table object
}

function LookupSet(table, value_set) {
  this.table = table;
  this.value_set = value_set;
}

function Model() {
  // Database and table names
  var dbname = "sas_app_db"; // FIXME: Put in global scope
  var tables = {
    primary: [],
    secondary: [],
    tertiary: [],
    lookup: [],
    junction: [],
    ratings: [],
    primary_primary_junction: [],
    primary_secondary_junction: [],
    primary_lookup_junction: [],
    primary_junction_junction: [],
    other: []
  };

  // Lookup entities
  this.ratings = {}; // "id":"A+"
  this.ratings_simple = {}; // "id":"A"

  // Primary entities
  var partners = []; // Loaded from db
  var consultants = []; // Loaded from db
  var opportunities = []; // Loaded from db

  var partner_strengths = []; // "id":"name"
  var technologies = [];
  var solutions = [];
  var misc = [];
  var verticals = [];
  var regions = [];

  var partnerProperties = {
    partner_strength_ratings: [],
    partner_technology_ratings: [],
    partner_solution_ratings: [],

  };

  // FIXME: Draw from table_types_meta
  this.addTable = function(table) {
    // table must be Table object
    if (table.type === "primary"
    || table.type === "secondary"
    || table.type === "tertiary"
    || table.type === "lookup"
    || table.type === "junction"
    || table.type === "ratings"
    || table.type === "primary_primary_junction"
    || table.type === "primary_secondary_junction"
    || table.type === "primary_lookup_junction"
    || table.type === "primary_junction_junction") {
      tables[table.type].push(table);
    } else {
      tables["other"].push(table);
    }
  }

  this.getTables = function() {
    return tables;
  }

  // this.addTable = function(table_name) {
  //   if (typeof table_name == "string") {
  //     if (table_name.endsWith("_junction") || table_name.endsWith("_ratings")) {
  //       //tables.junction // FIXME: Left off - EOB 7/14
  //     }
  //   }
  // }

  this.addPartner = function() {
    // FIXME: INSERT PARTNER IF DOES NOT EXIST
  }

  this.editPartner = function(id, column, value) {
    // FIXME: ALTER PARTNER IF EXISTS
  }

  this.removePartner = function(id) {
    // FIXME: DELETE PARTNER IF EXISTS
  }

  this.addConsultant = function() {
    // FIXME: INSERT CONSULTANT IF DOES NOT EXIS
  }

  this.editConsultant = function(id, column, value) {
    // FIXME: ALTER ALTER PARTNER ROW IF EXISTS
  }

  this.removeConsultant = function(id) {
    // FIXME: DELETE CONSULTANT IF EXISTS
  }

  this.addOpportunity = function() {
    // FIXME: INSERT OPPORTUNITY IF DOES NOT EXIST
  }

  this.editOpportunity = function(id, column, value) {
    // FIXME: ALTER OPPORTUNITY IF EXISTS
  }

  this.addTableColumn = function(table, column) {
    // FIXME: [LATER] ALTER TABLE IF EXISTS
  }

  this.selectPartners = function(callback) {
    // FIXME:
    var query_str = "SELECT * FROM misc;";
    $.post(
      queryScript,
      {
        query: query_str
      },
      function (data, status) {
        callback(data);
      }
    );
  }

  this.selectConsultants = function() {
    // FIXME
  }

  this.selectOpportunities = function() {
    // FIXME
  }
}

/* View: stores and manipulates DOM elements as objects */

function View() {
  // FIXME
  // Store base view elements

  // Store resuable view templates (result card, expanded profile cards, forms)

  // Render base view elements

  // Render
}

var m = new Model();
var v;

function buildModel() {
  // FIXME
  var query_str;

  // ratings and ratings_simple tables
  query_str = "SELECT * FROM ratings;";
  query(query_str, function(data) {

  });
  query_str = "SELECT * FROM ratings_simple;";
  query(query_str, function(data) {

  });

  // Lookup tables
}

function buildView() {
  // FIXME
}

function loadLookupValues() {
  var lookup_tables = m.getTables()['lookup'];
  // for ()
}

function loadFKs() {
  str = "SELECT * FROM table_fk_meta";
}

// Generic table load function
function loadTable(table_name) {
  query_str = "SELECT * FROM " + table_name;
  query(query_str, function(data) {
    var rows = JSON.parse(data);
  });
}

function loadTables() {

  // Get rows of tables from tables_meta
  str = "SELECT * FROM tables_meta";
  query(str, function(data) {
    var rows = JSON.parse(data);
    for (var i = 0; i < rows.length; i += 1) {
      var id = 0;
      var name = "";
      var label = "";
      var type = "";
      var is_searchable = false;
      var rating_table = "";
      // id
      id = rows[i]['id'];
      // name
      name = rows[i]['name'];
      // label
      if (rows[i]['label'] === undefined || rows[i]['label'] === "") {
        label = undefined;
      } else {
        label = rows[i]['label'];
      }
      // type
      if (rows[i]['type'] === undefined || rows[i]['type'] === "") {
        type = "other";
      } else {
        type = rows[i]['type'];
      }
      // is_searchable
      if (rows[i]['is_searchable'] === undefined || rows[i]['is_searchable'] === ""
      || rows[i]['is_searchable'] === "0" || rows[i]['is_searchable'] === 0
      || rows[i]['is_searchable'] === "false" || rows[i]['is_searchable'] === false) {
        is_searchable = false;
      } else {
        is_searchable = true;
      }
      // rating_table
      if (rows[i]['rating_table'] === undefined || rows[i]['rating_table'] === ""
      || rows[i]['rating_table'] === false) {
        rating_table = "";
      } else {
        rating_table = rows[i]['rating_table'];
      }
      var table = new Table(id, name, label, type, is_searchable, rating_table);
      m.addTable(table);
    }

    // FIXME: Remove
    $('body').append('<pre>'+JSON.stringify(m.getTables(), null, 4)+'</pre>');
    alert("MODEL TABLES: " + JSON.stringify(m.getTables(), null, 4)); // FIXME: Remove
  });

}

// Load lookup values from db: ratings, filters (names, ids)
function load() {
  loadTables();
  loadFKs();
  loadLookupValues();
  // FIXME
    // Load values into model
  //buildModel();

    // Build view elements
  //buildView();

    // Render view
    // v.render();
}

// Program start
function main() {
  alert("Ex"); // FIXME
  load();
  setTimeout(function() {
    buildView();
  }, 100);
}

// Program start invocation
main(); // FIXME: Test - does this run? Before or after $.ready(...) ?

// Program ready & event handling
$(document).ready(function() {

  // FIXME: Remove - temporary
  $(document).click(function() {
    alert("Clicked!");
    m.selectPartners(function(result) {
      alert("Queried");
      $("body").html("<pre>" + result + "</pre>");
    });
  });

});

//------------------------------------------------------------------------------
// OLD CONTROLLER // FIXME: Remove
//------------------------------------------------------------------------------

//------------------------------------------------------------------------------
// MVC Abstraction // FIXME: Remove?
//------------------------------------------------------------------------------

//------------------------------------------------------------------------------
// HELPER/UTILITY FUNCTIONS
//------------------------------------------------------------------------------

// Helper: all-purpose query
function query(query_str, callback) {
  $.post(
    queryScript,
    {query: query_str},
    function(data, status) {
      callback(data);
    }
  );
}

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
