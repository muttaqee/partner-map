#!/usr/bin/env python

"""
Read data for partner_strengths table, pass 1/2:
- Read rows from "Map" worksheet.
- Encode rows in JSON. In this form:
  {
    ...,
    "Piper Enterprise Solutions": {
        "Financial Rate Negotiation": "B",
        "Political - SAS/Customer": "B",
        "Process & Training": "B",
        "Social - Responsive": "B",
        "Technical - Quality": "C"
    },
    ...
  }
- Output rows for use by PHP script.

06-06-2016

FIXME: Update header comments here
"""

import openpyxl, json
from openpyxl.cell import column_index_from_string

# Command-line arguments # FIXME: Use this if script is general-purpose
# [0] File name
# [1] Workbook name
# [2] Worksheet name
# [3] col_first
# [4] row_first
# [5] column names list
# [6] Number of rows

# Open file
wb_name = "partner-spreadsheet-copy.xlsx"
try:
    wb = openpyxl.load_workbook(wb_name)
except:
    print("Error: Could not load workbook.")

# Official_Names sheet
ws_name = "Map"
try:
    ws = wb.get_sheet_by_name(ws_name)
except:
    print("Error: Could not load worksheet.")

# Column and rows to remember
columns = ['Technical - Quality', 'Financial Rate Negotiation', 'Process & Training', 'Political - SAS/Customer', 'Social - Responsive']
x_first = column_index_from_string('H')
x_last = column_index_from_string('L')
y_first = 4
y_last = 106
width = len(columns)
height = y_last - y_first + 1 # Number of rows

# Create JSON name-value pair. NOTE: Only name parameter is encoded as string
def getNameValuePair(name, value):
    return "\"" + name + "\"" + ":" + value

# For a given row, build and return an object in JSON
def getRow(row_number):
    json_row = ""

    for i in range(0, width-1):
        x = x_first + i
        if ws.cell(row = row_number, column = x).value:
            # name-value pair
            cell_value = str.strip(ws.cell(row = row_number, column = x).value)
            json_row += getNameValuePair(name = columns[i], value = "\"" + cell_value + "\"") + ","
        else:
            # name-null pair
            json_row += getNameValuePair(name = columns[i], value = "null") + ","
    # Last name-value pair has no comma
    if ws.cell(row = row_number, column = x_first + width - 1).value:
        # name-value pair
        cell_value = str.strip(ws.cell(row = row_number, column = x_first + width - 1).value)
        json_row += getNameValuePair(name = columns[width-1], value = "\"" + cell_value + "\"")
    else:
        # name-null pair
        json_row += getNameValuePair(name = columns[width-1], value = "null")

    return "{" + json_row + "}"

# Read data from each row - watch for null cells
def getAllRows(begin, end):
    json_collection = ""
    id = 1
    for y in range(begin, end):
        partner_name = str.strip(ws.cell(row = y, column = 2).value)
        json_collection += getNameValuePair(name = partner_name, value = getRow(y)) + ","
        id += 1
    # Last name-value pair has no comma:
    partner_name = str.strip(ws.cell(row = end, column = 2).value)
    json_collection += getNameValuePair(name = partner_name, value = getRow(end))
    return "{" + json_collection + "}"

# Read data from spreadsheet, convert to JSON, and output:
try:
    parsed_json = json.loads(getAllRows(y_first, y_last))
except:
    print("Error: Could not load data from spreadsheet.")
try:
    #print(json.dumps(parsed_json))
    # Human-readable version:
    print(json.dumps(parsed_json, indent = 4, sort_keys = True))
except:
    print("Error: Could not output spreadsheet data in JSON.")
