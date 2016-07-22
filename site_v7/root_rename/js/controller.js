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

/* Helper methods */

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

function isNonempty(x) {
  return (x !== undefined && x !== null && x !== "null" && x !== "");
}

// Helper: checks a variable for positive value
// (Safe translation for potentially unclean JSON data)
function isPositive(x) {
  return (isNonempty(x)
  && x !== false && x !== "false" && x !== 0 && x !== "0");
}

// Helper: Executes functions in order (for async tasks).
// Pass functions as args or in array.
// Like this:
// executeInOrder(f1, f2, f3);
// executeInOrder( [f1, f2, f3] );
function executeInOrder() {
  var funcs = Array.prototype.concat.apply([], arguments);
  var func = function(callback) {
    funcs.shift();
    callback();
  };
  func(function() {
    if (funcs.length > 0) {
      executeInOrder.apply(this, funcs);
    }
  });
}

// Helper // FIXME: Left off - write proper executeTasks function; use this
// Helper: Executes functions in order
// Works
function executeTasks() {
  var tasks = Array.prototype.concat.apply([], arguments);
  var task = tasks.shift();
  task(function() {
    if (tasks.length > 0) {
      executeTasks.apply(this, tasks);
    }
  });
}

// Helper: Executes functions in order (functions need not have callback mechanism)
function orderTasks() {
  var tasks = Array.prototype.concat.apply([], arguments);
  var task = function(callback) {
    tasks.shift()();
    callback();
  }
  task(function() {
    if (tasks.length > 0) {
      executeTasks.apply(this, tasks);
    }
  });
}

// // Executes asynchronous functions before returning
// function asyncLoop(iterations, func, callback) {
//
// }
//
// //
// function asyncLoop(iterations, func, callback) {
//   var index = 0;
//   var done = false;
//   var loop = {
//
//     next: function() {
//       if (done) {
//         return;
//       }
//       if (index < iterations) {
//         index++;
//         func(loop);
//       } else {
//         done = true;
//         callback();
//       }
//     },
//
//     iteration: function() {
//       return index - 1;
//     },
//
//     break: function() {
//       done = true;
//       callback();
//     }
//   };
//
//   loop.next();
//   return loop;
// }

/* Object constructors */

// This object reflects the tables_meta relation schema
function Table(id, name, label, type, is_searchable, rating_table) {
  this.id = id;
  this.name = name;
  this.label = isNonempty(label) ? label : name;
  this.type = type;
  this.is_searchable = isPositive(is_searchable);
  this.rating_table = isNonempty(rating_table) ? rating_table : null; // (name)
}

function Lookup(id, type, name) {
  this.id = id;
  this.type = isNonempty(type) ? type : null;
  this.name = name;
  this.label = (this.type === null) ? this.name : this.type + " - " + this.name;
}

function LookupSet(table_id, label, values_set) {
  this.table_id = table_id;
  this.label = isNonempty(label) ? label : null;
  this.set = (typeof values_set === "object") ? values_set : {}; // Stores lookup-id : lookup-obj pairs

  this.addLookup = function(lookup) {
    if (lookup instanceof Lookup) {
      this.set[lookup.id] = lookup;
    }
  }

  this.getLookupsArray = function() {
    var arr = [];
    for (var key in this.set) {
      arr.push(this.set[key]);
    }
    return arr;
  }

  this.getLookupById = function(id) {
    return (this.set.hasOwnProperty(id)) ? this.set[id] : null;
  }
}

function Model(callback) {
  // Context
  var self = this;

  // Database name
  var dbname = "sas_app_db"; // FIXME: Put in global scope

  // Table types: string array ["type 1", "type 2", ...]
  var table_types = [];

  // Tables: {type1: [Table1, Table2, ...],
  //          type2: [Table3, ...],
  //          ...}
  var tables = {};

  // Lookup sets: {table_id: lookupSet, table_id: lookupSet, ...}
  var lookup_sets = {};

  /* Private nitiation functions (can be used to "refresh" variables) */

  var loadTableTypes = function(callback) {
    alert("Loading table types"); // FIXME: Remove
    table_types.push("other");
    var query_str = "SELECT * FROM table_types_meta";
    query(query_str, function(data) {
      var rows = JSON.parse(data);
      for (var i = 0; i < rows.length; i += 1) {
        table_types.push(rows[i]["name"]);
      }

      alert("Table types: " + JSON.stringify(table_types, null, 4));// FIXME: Remove

      if (typeof callback == "function") {
        callback();
      }
    });
  }

  var loadTables = function(callback) {
    // Give the object a key-value pair for each table type (i.e. <type>: [])
    for (var i = 0; i < table_types.length; i += 1) {
      tables[table_types[i]] = [];
    }
    // Load and store tables as Table objects into the object
    var query_str = "SELECT * FROM tables_meta";
    query(query_str, function(data) {
      var rows = JSON.parse(data);
      for (var i = 0; i < rows.length; i += 1) {
        var id = rows[i]["id"];
        var name = rows[i]["name"];
        var label = rows[i]["label"];
        var type = rows[i]["type"];
        var is_searchable = rows[i]["is_searchable"];
        var rating_table = rows[i]["rating_table"];
        var t = new Table(id, name, label, type, is_searchable, rating_table);
        self.addTable(t); // FIXME: Careful - 'this' has to refer to Model m
      }

      if (typeof callback == "function") {
        callback();
      }
    });
  }

  var buildLookupSetFromTable = function(lookup_table, callback) {
    query_str = "SELECT * FROM " + lookup_table.name;
    query(query_str, function(data) {
      var rows = JSON.parse(data);
      var lookup_set = new LookupSet(lookup_table.id, lookup_table.label, {});
      for (var i = 0; i < rows.length; i += 1) {
        var id = rows[i]["id"];
        var type = rows[i]["type"];
        var name = rows[i]["name"];
        var lookup = new Lookup(id, type, name);
        lookup["table_id"] = lookup_table["id"];
        lookup_set.addLookup(lookup);
      }
      callback(lookup_set);
    });
  }

  var loadLookupSets = function(callback) {
    var iterations = tables["lookup"].length;
    var index = 0;
    var tryCallback = function() {
      index += 1;

      var ct = 0;
      for (var key in lookup_sets) {
        ct += 1;
      }
      if (index >= iterations) {
        callback();
      }
    }

    for (var i = 0; i < iterations; i += 1) {
      var t = tables["lookup"][i];
      buildLookupSetFromTable(t, function(set) {
        lookup_sets[set.table_id] = set;
        tryCallback();
      });
    }
  }
    // var loadFunctions = [];
    // var args_array = [];
    // for (var i = 0; i < tables["lookup"].length; i += 1) {
    //   // loadFunctions.push(function() {
    //   //   buildLookupSetFromTable(tables["lookup"][i], function(set) {
    //   //     lookup_sets.push(set);
    //   //   });
    //   // });
    //
    //   var t = tables["lookup"][i];
    //   // Call buildLookupSetFromTable(t, function(set) { lookup_sets[t.id] = set })
    //
    //   // alert("Lookup table " + i + ": " + (typeof t)); // FIXME: Remove [NOTE: DEFINED HERE]
    //   var f = function(callback) {
    //     setTimeout(function() {
    //       buildLookupSetFromTable(t, function(set) {
    //         lookup_sets[t.id] = set;
    //       });
    //       callback();
    //     }, 500);
    //   }
    //   loadFunctions.push(f);

      // var t = tables["lookup"][i];
      // alert("Lookup table " + i + ": " + (typeof t)); // FIXME: Remove [NOTE: DEFINED HERE]
      // var f = function(callback) {
      //   setTimeout(function() {
      //     buildLookupSetFromTable(t, function(set) {
      //       lookup_sets[t.id] = set;
      //     //   alert("Loaded new set - lookup_sets:\n" + JSON.stringify(lookup_sets, null, 4));
      //     //   callback();
      //     });
      //     callback();
      //   }, 500);
      // }
      // loadFunctions.push(f);

      //args_array.push([t, ); // FIXME: Trying this new thing

      // loadFunctions.push(function(callback) {
      //   buildLookupSetFromTable(tables["lookup"][i], function(set) {
      //     lookup_sets[tables["lookup"][i].id] = set;
      //     alert("Loaded new set - lookup_sets:\n" + JSON.stringify(lookup_sets, null, 4));
      //     callback();
      //   });
      // });

      // buildLookupSetFromTable(tables["lookup"][i], function(set) {
      //   lookup_sets[tables["lookup"][i].id] = set;
      //   alert("Loaded new set - lookup_sets:\n" + JSON.stringify(lookup_sets, null, 4));
      // });

    // var f1 = function(callback) {
    //   setTimeout(function() {
    //     alert("task 1");
    //     callback();
    //   }, 500);
    // };
    // var f2 = function(callback) {
    //   setTimeout(function() {
    //     alert("task 2");
    //     callback();
    //   }, 500);
    // };

    //buildLookupSetFromTable.apply(this, ) // FIXME: Trting this new thing

    //executeTasks(loadFunctions, callback);

    // executeTasks(loadFunctions, function(callback) {
    //   alert("Lookup sets: " + JSON.stringify(lookup_sets, null, 4));// FIXME: Remove
    //   if (typeof callback == "function") {
    //     callback();
    //   }
    // });

    //executeInOrder(loadFunctions);
    // var len = 0;
    // while (len < tables["lookup"].length) {
    //   len = 0;
    //   for (var key in lookup_sets) {
    //     len += 1;
    //   }
    // } // Wait
    // alert("Finished loading lookup sets: " + len); // FIXME: Remove



  this.setDatabaseName = function(str) {
    if (typeof str === "string") {
      dbname = str;
    }
  }

  /* Table types */

  this.addTableType = function(str) {
    if (typeof str === "string" && !table_types.hasOwnProperty(str)) {
      table_types.push(str);
      tables[str] = [];
    }
  }

  this.removeTableType = function(str) {
    if (typeof str === "string" && table_types.hasOwnProperty(str)) {
      // Remove type from the table_types object
      table_types.splice(table_types.indexOf(str), 1);
    }
    if (tables.hasOwnProperty(str)) {
      // Migrate all tables of this type to type "other"
      if (tables.hasOwnProperty("other") && str !== "other") {
        for (var table in tables[str]) {
          table.type = "other";
          tables["other"].push(table);
        }
      }
      // Remove type key from tables object
      delete tables[str];
    }
  }

  this.getTableTypes = function() {
    return table_types.slice();
  }

  /* Tables */

  this.addTable = function(t) {
    if (t instanceof Table) {
      for (var key in tables) {
        if (t.type == key) {
          tables[key].push(t);
          return;
        }
      }
      tables["other"].push(t);
    }
  }

  this.getTablesByType = function(type_str) {
    if (typeof type_str === "string") {
      return tables[table_str] ? tables[type_str].splice() : [];
    }
  }

  this.getAllTables = function() {
    var all_tables = [];
    for (var key in tables) {
      all_tables.push.apply(all_tables, tables[key]);
    }
    return all_tables;
  }

  this.getTableById = function(id) {
    var tables = this.getAllTables();
    for (var i = 0; i < tables.length; i += 1) {
      if (tables[i].id == id) {
        return tables[i];
      }
    }
    return null;
  }

  /* Lookups and lookup sets */

  /* Modification functions */

  this.addEntity = function() {
    // FIXME: INSERT PARTNER IF DOES NOT EXIST
  }

  this.editEntity = function(id, column, value) {
    // FIXME: ALTER PARTNER IF EXISTS
  }

  this.removeEntity = function(id) {
    // FIXME: DELETE PARTNER IF EXISTS
  }

  this.addTableColumn = function(table, column) {
    // FIXME: [LATER] ALTER TABLE IF EXISTS
  }

  this.selectEntities = function() {
    // FIXME:
    var query_str = "SELECT * FROM ";
  }

  // Initialize variables
  var initialize = function() {
    alert("Start initialize"); // FIXME: Remove
    executeTasks(loadTableTypes, loadTables, loadLookupSets,
    function(callback) {
      var count = 0;
      for (var key in lookup_sets) {
        count += 1;
      }
      alert("Final task - show lookup_sets:\n" + " ("+ count +") " + JSON.stringify(lookup_sets, null, 4));
      callback();
    });
    //loadTableTypes();
    // loadTables();
    // loadLookupSets();
    //executeInOrder(loadTableTypes, loadTables, loadLookupSets);
    // loadTableTypes(function() {
    //   loadTables(function() {
    //     loadLookupSets();
    //   });
    // });

    //loadTables();
    alert("End initialize"); // FIXME: Remove
    //loadLookupSets();
  }

  initialize();
  callback(null);
}

/* View: stores and manipulates DOM elements as objects */

function View() {
  // FIXME
  // Store base view elements

  // Store resuable view templates (result card, expanded profile cards, forms)

  // Render base view elements

  // Render
}

var m;
var v;

// NOTE: Should be in view
// function populateLookupValues() {
//   var lookup_tables = m.getTables()['lookup'];
//   // for ()
// }

// function loadFKs() {
//   str = "SELECT * FROM table_fk_meta";
// }

// function loadTypes() {
//   str = "SELECT * FROM table_types_meta";
//   query(str, function(data) {
//     var rows = JSON.parse(data);
//     for (var i = 0; i < rows.length; i += 1) {
//       var id = -1, name = "";
//       var label = "";
//       var type = "";
//       var is_searchable = false;
//       var rating_table = "";
//       // id
//       id = rows[i]['id'];
//       // name
//       name = rows[i]['name'];
//       // label
//       if (rows[i]['label'] === undefined || rows[i]['label'] === "") {
//         label = undefined;
//       } else {
//         label = rows[i]['label'];
//       }
//       // type
//       if (rows[i]['type'] === undefined || rows[i]['type'] === "") {
//         type = "other";
//       } else {
//         type = rows[i]['type'];
//       }
//       // is_searchable
//       if (rows[i]['is_searchable'] === undefined || rows[i]['is_searchable'] === ""
//       || rows[i]['is_searchable'] === "0" || rows[i]['is_searchable'] === 0
//       || rows[i]['is_searchable'] === "false" || rows[i]['is_searchable'] === false) {
//         is_searchable = false;
//       } else {
//         is_searchable = true;
//       }
//       // rating_table
//       if (rows[i]['rating_table'] === undefined || rows[i]['rating_table'] === ""
//       || rows[i]['rating_table'] === false) {
//         rating_table = "";
//       } else {
//         rating_table = rows[i]['rating_table'];
//       }
//       var table = new Table(id, name, label, type, is_searchable, rating_table);
//       m.addTable(table);
//     }
//
//     // FIXME: Remove
//     $('body').append('<pre>'+JSON.stringify(m.getTables(), null, 4)+'</pre>');
//     alert("MODEL TABLES: " + JSON.stringify(m.getTables(), null, 4)); // FIXME: Remove
//   });
// }

// function loadTables() {
//
//   // Get rows of tables from tables_meta
//   str = "SELECT * FROM tables_meta";
//   query(str, function(data) {
//     var rows = JSON.parse(data);
//     for (var i = 0; i < rows.length; i += 1) {
//       var id = 0;
//       var name = "";
//       var label = "";
//       var type = "";
//       var is_searchable = false;
//       var rating_table = "";
//       // id
//       id = rows[i]['id'];
//       // name
//       name = rows[i]['name'];
//       // label
//       if (rows[i]['label'] === undefined || rows[i]['label'] === "") {
//         label = undefined;
//       } else {
//         label = rows[i]['label'];
//       }
//       // type
//       if (rows[i]['type'] === undefined || rows[i]['type'] === "") {
//         type = "other";
//       } else {
//         type = rows[i]['type'];
//       }
//       // is_searchable
//       if (rows[i]['is_searchable'] === undefined || rows[i]['is_searchable'] === ""
//       || rows[i]['is_searchable'] === "0" || rows[i]['is_searchable'] === 0
//       || rows[i]['is_searchable'] === "false" || rows[i]['is_searchable'] === false) {
//         is_searchable = false;
//       } else {
//         is_searchable = true;
//       }
//       // rating_table
//       if (rows[i]['rating_table'] === undefined || rows[i]['rating_table'] === ""
//       || rows[i]['rating_table'] === false) {
//         rating_table = "";
//       } else {
//         rating_table = rows[i]['rating_table'];
//       }
//       var table = new Table(id, name, label, type, is_searchable, rating_table);
//       m.addTable(table);
//     }
//
//     // FIXME: Remove
//     $('body').append('<pre>'+JSON.stringify(m.getTables(), null, 4)+'</pre>');
//     alert("MODEL TABLES: " + JSON.stringify(m.getTables(), null, 4)); // FIXME: Remove
//   });
//
// }

// Load lookup values from db: ratings, filters (names, ids)
// NOTE: let the model and view take care of their own loading, respectively.
// function load() {
//   loadTables();
//   loadFKs();
//   populateLookupValues();
//   // FIXME
//     // Load values into model
//   //buildModel();
//
//     // Build view elements
//   //buildView();
//
//     // Render view
//     // v.render();
// }

function testA() {
  var f1 = function(callback) {
    setTimeout(function() {
      alert("task 1");
      callback();
    }, 500);
  };
  var f2 = function(callback) {
    setTimeout(function() {
      alert("task 2");
      callback();
    }, 500);
  };
  var f3 = function(callback) {
    setTimeout(function() {
      alert("task 3");
      callback();
    }, 500);
  };

  var f4 = function(callback) {
    setTimeout(function() {
      alert("task A");
      callback();
    }, 500);
  };
  var f5 = function(callback) {
    setTimeout(function() {
      alert("task B");
      callback();
    }, 500);
  };
  var f6 = function(callback) {
    setTimeout(function() {
      alert("task C");
      callback();
    }, 500);
  };

  var e1 = function(callback) {
    executeTasks(f1, f2, f3, callback);
  };
  var e2 = function(callback) {
    executeTasks(f4, f5, f6, callback);
  };

  executeTasks(e1, e2); // This works: each must have callback
}

function testB() {
  var f1 = function() {
    setTimeout(function() {
      alert("Function 1");
    }, 500);
  };
  var f2 = function() {
    setTimeout(function() {
      alert("Function 2");
    }, 500);
  };
  var f3 = function() {
    setTimeout(function() {
      alert("Function 3");
    }, 500);
  };
  orderTasks(f1, f2, f3);
}

// Test: Using apply with N > 1 arguments per function call
function testC() {
  var fn2 = function(arg1, arg2) {
    alert(arg1 + " squared is " + arg2);
  };

  var fn1 = function(arg) {
    setTimeout(function() {
      alert("Result: " + arg);
    }, 450);
  };

  var fn = function(a, b) {
    alert("a: " + a + ", b: " + b);
  };

  var args_list = [];
  var func_list = [];
  var tasks = [];
  var j;
  for (var i = 2; i < 5; i += 1) {
    j = i * i;
    args_list.push(j);

    // fn(i, j);
    // var f = function() {
    //   fn1(j);
    // };
    func_list.push(fn1);
    tasks.push(function(callback) {
      fn1(arguments[1]);
      callback();
    });
  }

  alert("Size of func list: " + func_list.length);
  for (var k = 0; k < func_list.length; k += 1) {
    alert(typeof func_list[k]);
    func_list[k](args_list[k]); // Works
    fn1(args_list[k]); // Works
  }
  alert("Done with async tasks?"); // FIXME: This has to wait for the functions above to finish.
  // fn1.apply(this, args_list);
}

// Program start
function main() {
  alert("Start main"); // FIXME
  //testA(); // FIXME: works
  //testB(); // FIXME: fails
  //testC();
  m = new Model(function(data) {
    alert("Done initializing model: " + data);
  });
  alert("End main"); // FIXME

  // NOTE: Test (works)
  // var arr = [
  //   alert("Ex5"), alert("Ex6"), alert("Ex7")
  // ];
  // //executeInOrder.apply(this, arr);
  // executeInOrder(arr);

  // executeInOrder(function(cb) {
  //   alert("1");
  //   cb();
  // }, function(cb) {
  //   alert("2");
  //   cb();
  // });

  // setTimeout(function() {
  //   buildView();
  // }, 100);
}

// Program start invocation
main(); // FIXME: Test - does this run? Before or after $.ready(...) ?

// Program ready & event handling
$(document).ready(function() {

  // FIXME: Remove - temporary
  // $(document).click(function() {
  //   alert("Clicked!");
  //   m.selectPartners(function(result) {
  //     alert("Queried");
  //     $("body").html("<pre>" + result + "</pre>");
  //   });
  // });

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
