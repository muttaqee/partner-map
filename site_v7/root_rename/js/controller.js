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

function Rating()

function Model() {
  // Primary entities
  var partners = [];
  var consultants = [];
  var opportunities = [];

  // Lookup entities
  var ratings = []; // "id":"A+"
  var ratings_simple = []; // "id":"A"

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

/* View */

function View() {

}

var m = new Model();
var v = new View();

$(document).ready(function() {
  // Listeners

  // FIXME: Remove - temporary
  $(document).click(function() {
    alert("Clicked!");
    m.selectPartners(function(result) {
      alert("Queried");
      $("body").html("<pre>" + result + "</pre>");
    });
  })

  // Program start
  function execute() {
    alert("Executing...");
    // load();
    // setTimeout(function() {
    //   build();
    // }, 100);
  }

  // Program start
  execute();
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
