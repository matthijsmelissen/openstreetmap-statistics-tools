#!/usr/bin/python

# This script generates completeness statistics for some chain shops in the
# Netherlands. It can be easily adapted for other shops by modifying the
# shops array.
#
# Make sure to set the total number of shops manually in the "shops" array.
#
# Before running this script, you will need to load OSM-data into an
# PostGIS-database with osm2pgsql:
# sudo -u postgres createdb --encoding=UTF8 gis
# psql --dbname=gis -c "CREATE EXTENSION postgis;"
# psql --dbname=gis -c "CREATE EXTENSION hstore;"
# osm2pgsql --slim -d gis -C 2000 --hstore --number-processes 3 netherlands-latest.osm.pbf
#
# Usage: Simply run './shops-completeness.py'.

from __future__ import division
import psycopg2
import sys

db = "gis"
user = "postgres"

con = None

try:
    # Set here the shop names and the total number of shops
    shops = [
      ("Albert Heijn", 850),
      ("Bijenkorf", 10),
      ("Gamma", 165),
      ("V&D", 62),
      ("C&A", 132),
      ("BCC", 75),
      ("Kwantum", 99),
      ("Mango", 50),
      ("Bruna", 380),
      ("Blokker", 622),
      ("Specsavers", 118),
      ("Witteveen", 100),
      ("Shoeby", 223),
    ]

    con = psycopg2.connect(database=db, user=user)
    cur = con.cursor()

    for shop in shops:
      cur.execute("SELECT COUNT(*) FROM planet_osm_polygon WHERE name LIKE %s AND NOT shop IS NULL;", (shop[0]+'%',))
      totalpolygon = cur.fetchone()[0]
      cur.execute("SELECT COUNT(*) FROM planet_osm_point WHERE name LIKE %s AND NOT shop IS NULL;", (shop[0]+'%',))
      totalpoint = cur.fetchone()[0]
      total = totalpoint + totalpolygon
      percentage = total / shop[1] * 100
      print shop[0], total, shop[1], "%.2f" % percentage + '%'

except psycopg2.DatabaseError, e:
    print 'Error %s' % e
    sys.exit(1)

finally:
    if con:
        con.close()



