#!/usr/bin/env python3
# Download from https://dev.maxmind.com/geoip/geoip2/geolite2/
# You only need GeoLite2-City-Blocks-IPv4.csv from GeoLite2 City
# license of the DB is CC-BY-SA 4.0
#
# This product includes GeoLite2 data created by MaxMind, available from
# http://www.maxmind.com

# usage: add_ip_mapping.py > ip.csv
# in mysql terminal:
# LOAD DATA INFILE "`path to ip.csv`" INTO TABLE v3_ip_mapping COLUMNS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' ESCAPED BY '"' LINES TERMINATED BY '\n';

# for query by ip:
# SELECT * FROM v3_ip_mapping WHERE `ipstart` <= ip-in-decimal AND `ipend` >= ip-in-decimal ORDER BY `ipstart` DESC LIMIT 1;
import socket
import struct
import csv

with open('GeoLite2-City-Blocks-IPv4.csv', 'r') as csvfile:
    iplist = csv.reader(csvfile, delimiter=',', quotechar='"')
    # Skip header
    next(iplist)
    for row in iplist:
        if row[7] is "" or row[8] is "":
            continue
        ip, net_bits = row[0].split('/')
        ipstart = (struct.unpack("!I", socket.inet_aton(ip)))[0]
        ipend = ipstart + ((1 << (32 - int(net_bits))) - 1)
        latitude = float(row[7])
        longitude = float(row[8])
        print('%d,%d,%f,%f' % (ipstart, ipend, latitude, longitude))
