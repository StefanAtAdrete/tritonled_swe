#!/usr/bin/env python3
import csv
import sys

input_file = '/Users/steffes/Projekt/tritonled/data/import/max-ip20.csv'
output_file = '/Users/steffes/Projekt/tritonled/data/import/max-ip20-new.csv'

with open(input_file, 'r', encoding='utf-8') as infile, \
     open(output_file, 'w', encoding='utf-8', newline='') as outfile:

    reader = csv.DictReader(infile)
    fieldnames = reader.fieldnames + ['field_certifications']

    writer = csv.DictWriter(outfile, fieldnames=fieldnames)
    writer.writeheader()

    for row in reader:
        ip = row.get('attribute_ip_rating', '20')
        if str(ip) == '43':
            row['field_certifications'] = 'CE|RoHS|B2L ready|Dimmable|Flicker Free|IK06'
        else:
            row['field_certifications'] = 'CE|RoHS|B2L ready|Dimmable|Flicker Free'
        writer.writerow(row)

print(f'Done. Output: {output_file}')
