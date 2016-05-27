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
Open file and read data
"""

# Open file
filename = "partner-spreadsheet-copy.xlsx"
wb = openpyxl.load_workbook(filename)
ws = wb.get_sheet_by_name("");

# Cell ranges

# Read data

# Convert data to JSON
