# CSV import documentation

Open **Doctors → CSV Import** and upload a CSV file.

Minimum supported header row:

```csv
First Name,Last Name,Speciality,Group Name,Profile Link
```

Also supported:

```csv
First Name,Last Name,Specialty,Group Name,Profile Link,Image URL,Designation,Clinic,Department,Display Order
```

Headers are normalized for BOMs and flexible whitespace. Empty rows are ignored. Missing terms are created automatically. The import log can be downloaded after each import.
