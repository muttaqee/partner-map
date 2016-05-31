#!/usr/bin/env python

"""
NOTE to self:

Refer to...
read-save-xls-file.py --- to open and read from partner-spreadsheet-copy.xls
io-json-send.py --- to encode data in JSON and output

then refer to...
io-json-receive.php --- to receive JSON as associative array

and store data into tables (write and run table-filling script after running
wipe-init-db.php script)

5/26/2016

FIXME: Update header comments here
"""

import openpyxl

"""
Open file and read data: PARTNERS
"""

# Column-merge algorithm (specific to partner names?)
# Determined required input vars
# def columnMerge()

# Open file
filename = "partner-spreadsheet-copy.xlsx"
wb = openpyxl.load_workbook(filename)

# Official_Names sheet
on_sheet = wb.get_sheet_by_name("Official_Names");
# Sheet cell ranges
on_xBegin = 1;
on_xEnd = 3;
on_yBegin = 2;
on_yEnd = 117;

# Map sheet_ranges
# Use get_column_letter(index_variable) or coliumn_index-by_string
map_sheet = wb.get_sheet_by_name("Map")
map_xBegin = 2
map_xEnd = coliumn_index-by_string(2)
map_yBegin = 4
map_yEnd = 107

id = 1; # Use str(id) to populate id fields in JSON.

# Read data - watch for null cells



# Convert data to JSON
