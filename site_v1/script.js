$(document).ready(function() {

  // Test script connection
  alert("Connected");

  var phpScript = "query.php";

  // INITIALIZATION: Write initial elements to DOM (never read from DOM)

  // Declare DOM elements
  var leftColumn, rightColumn, middleColumn;

  var header;
  var menuBar, findButton, addButton, oppButton;

  var mainContainer;
  var form;

  var filterPane; // Also: input and label elements
  var oppFilters, partnerFilters, consultantFilters;

  var searchPane;
  var searchBar; // Also: input, label, and select/option elements;

  var oppCard, partnerCard, consultantCard;

  // Initialize elements
  leftColumn = $("<div id=\"left_column\" class=\"column\"></div>");
  rightColumn = $("<div id=\"right_column\" class=\"column\"></div>");
  middleColumn = $("<div id=\"middle_column\" class=\"column\"></div>");

  header = $("<div id=\"header\"></div>");
  menuBar = $("<div id=\"menu_bar\"></div>");
  findButton = $("<div id=\"find_button\" class=\"button menu_bar_button\"></div>");
  addButton = $("<div id=\"add_button\" class=\"button menu_bar_button\"></div>");
  oppButton = $("<div id=\"opp_button\" class=\"button menu_bar_button\"></div>");

  mainContainer = $("<div id=\"main_container\" class=\"container\"></div>");
  form = $("<form action=\"" + phpScript + "\" method=\"post\">");

  filterPane = $("<div id=\"filter_pane\" class=\"pane\"></div>");
  oppFilters = $("<div id=\"opp_filters\" class=\"widget\"></div>");
  partnerFilters = $("<div id=\"partner_filters\" class=\"widget\"></div>");
  consultantFilters = $("<div id=\"consultant_filters\" class=\"widget\"></div>");

  searchPane = $("<div id=\"search_pane\" class=\"pane\"></div>");
  searchBar = $("<div id=\"search_bar\" class=\"widget\"></div>");

  oppCard = $("<div id=\"opp_card\" class=\"widget card\">");
  partnerCard = $("<div id=\"partner_card\" class=\"widget card\">");
  consultantCard = $("<div id=\"consultant_card\" class=\"widget card\">");

  // Build some elements (nest some)
  document.append(leftColumn);
  document.append(middleColumn);
  document.append(rightColumn);

  // Write elements to DOM

  // Populate filter pane from query (PHP):

  /*
  OPPORTUNITIES <radio button : expands>

  PARTNERS <radio button : expands>

  - STRENGTHS <expands>
  -- Technical-quality <checkbox> : rating <dropdown>
  -- Financial rate negotiation
  -- Process and training
  --  Political - SAS/CUST
  --- Show results with no rating <checkbox>

  - TECHNOLOGIES <expands>
  -- <type - techn. dropdown> : rating <dropdown>  <-- "Add a technology"
  --- Show results with no rating <checkbox>

  - SOLUTIONS <expands>
  -- <type - soln. dropdown> : rating <dropdown>  <-- "Add a solution"
  --- Show results with no rating <checkbox>

  - MISC <expands>
  -- <dropdown> : <rating>
  --- Show results with no rating <checkbox>

  - VERTICALS <expands>
  -- <checkboxes>
  --- Show results with no vertical <checkbox>

  - GEOGRAPHIC REGIONS <expands>
  -- <checkboxes>
  --- Show results with no region <checkbox>

  CONSULTANTS <radio button : expands>


  *** Save this search? (per user)

  STEPS:
  1. Execute PHP script (queries db), retrieve results
  2. Display to DOM elements
  */

});
