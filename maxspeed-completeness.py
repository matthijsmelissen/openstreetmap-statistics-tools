#!/usr/bin/python

# This script generates completeness statistics for maxspeed tags based on a
# given set of OpenStreetMap-data.
#
# Before running this script, you will need to load OSM-data into an
# PostGIS-database with osm2pgsql:
# sudo -u postgres createdb --encoding=UTF8 gis    
# psql --dbname=gis -c "CREATE EXTENSION postgis;"
# psql --dbname=gis -c "CREATE EXTENSION hstore;"
# osm2pgsql --slim -d gis -C 2000 --hstore --number-processes 3 netherlands-latest.osm.pbf
#
# Usage: Simply run './maxspeed-completeness.py'.

from __future__ import division
import psycopg2

db = "gis"
user = "gis"

con = None

try:
    road_types = [
      "motorway",
      "motorway_link",
      "trunk",
      "trunk_link",
      "primary",
      "primary_link",
      "secondary",
      "secondary_link",
      "tertiary",
      "tertiary_link",
      "residential",
      "unclassified"]
  
    con = psycopg2.connect(database=db, user=user) 
    cur = con.cursor() 

    # calculate total
    cur.execute("SELECT COUNT(*) FROM planet_osm_line WHERE highway IN ('motorway', 'motorway_link', 'trunk', 'trunk_link', 'primary', 'primary_link', 'secondary', 'secondary_link', 'tertiary', 'tertiary_link', 'residential', 'unclassified') AND NOT (tags->'maxspeed') is null;")
    withmaxspeed = cur.fetchone()[0]
    cur.execute("SELECT COUNT(*) FROM planet_osm_line WHERE highway IN ('motorway', 'motorway_link', 'trunk', 'trunk_link', 'primary', 'primary_link', 'secondary', 'secondary_link', 'tertiary', 'tertiary_link', 'residential', 'unclassified');")
    total = cur.fetchone()[0]
    percentage = withmaxspeed / total * 100
    print 'TOTAL', withmaxspeed, total, "%.2f" % percentage + '%'

    #  calculate for all road types
    for road_type in road_types:
      cur.execute("SELECT COUNT(*) FROM planet_osm_line WHERE highway = %s AND NOT (tags->'maxspeed') is null;", (road_type,))
      withmaxspeed = cur.fetchone()[0]
      cur.execute("SELECT COUNT(*) FROM planet_osm_line WHERE highway = %s;", (road_type,))
      total = cur.fetchone()[0]
      percentage = withmaxspeed / total * 100
      print road_type, withmaxspeed, total, "%.2f" % percentage + '%'

except psycopg2.DatabaseError, e:
    print 'Error %s' % e    
    sys.exit(1)
    
finally:
    if con:
        con.close()



