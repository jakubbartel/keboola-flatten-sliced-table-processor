# Keboola Flatten Sliced Table Processor

Make multiple csv files from a single sliced file.

Simply loops over all directories with manifest in /data/in/tables/, other directories or files will not appear in the
output.

Output structure is flattened, e.g. `file.csv/slice1` is transformed to `file-slice1.csv`. To prevent duplications
names of output files will contain escaped character `-` by `--`, e.g. `file-1.csv/slice1` to `file--1-slice1.csv`.

Output csv files will contain header row taken from source csv's manifest.

## Configuration

Example processor configuration - there are no explicit parameters:
```
{
    "definition": {
        "component": "jakub-bartel.processor-split-by-value"
    }
}
```

### Input

```
file: weather.csv.manifest
--------------------------
{
    "columns": [
        "city",
        "date",
        "precipitation"
    ]
}
```

```
file: weather.csv/Prague (columns: city, date, precipitation)
------------------------
"Prague","2018-09-20","25.7"
"Prague","2018-09-21","1.8"
"Prague","2018-09-22","13.9"
```

```
file: weather.csv/Berlin (columns: city, date, precipitation)
------------------------
"Berlin","2018-09-20","25.7"
"Berlin","2018-09-21","25.7"
```

### Output

```
file: weather-Prague.csv
------------------------
"city","date","precipitation"
"Prague","2018-09-20","25.7"
"Prague","2018-09-21","1.8"
"Prague","2018-09-22","13.9"
```

```
file: weather-Berlin.csv
------------------------
"city","date","precipitation"
"Berlin","2018-09-20","25.7"
"Berlin","2018-09-21","25.7"
```
