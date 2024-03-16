# apis
A simple API that will return the corresponding cM value for a segment or segments based on the chromosome number, start and end positions.

Pass either of the following via GET:
- chromosome, start position and end position
- or an encoded string containing multiple rows of tab-delimited data (in the same order)

Each segment will be returned as an object.

This repository contains the files needed to:
- create a MySQL table
- populate this table with data from https://github.com/williamslab/min_map/blob/main/hapmap_min_map.zip
- return segments with an approximated cM value via a PHP script that queries the database
