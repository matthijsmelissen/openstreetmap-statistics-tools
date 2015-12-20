<?php
# This script generates a wiki-page that shows how chain shops are tagged in a
# given set of OpenStreetMap-data.
#
# Example output:
# https://wiki.openstreetmap.org/wiki/Shop_tagging_in_The_Netherlands
# https://wiki.openstreetmap.org/wiki/UK_Retail_Chains
#
# Before running this script, you will need to load OSM-data into an
# PostGIS-database with osm2pgsql:
# sudo -u postgres createdb --encoding=UTF8 gis
# psql --dbname=gis -c "CREATE EXTENSION postgis;"
# osm2pgsql --slim -d gis -C 2000 --hstore --number-processes 3 netherlands-latest.osm.pbf
#
# Usage: Run this script locally with 'php shops-wiki.php'.

$db = "gis";

$duplicatesList = array(
  array("3", "3 Store", "Three"),
  array("99p Store", "99p Stores"),
  array("Aldi", "ALDI"),
  array("Asda", "ASDA"),
  array("ATS", "ATS Euromaster"),
  array("Barnardos", "Barnardo's"),
  array("best-one", "Best One", "Best-One"),
  array("Betfred", "BetFred"),
  array("Blockbuster", "Blockbuster Video"),
  array("B&M", "B&M Bargains"),
  array("Bonmarche", "Bon Marche", "Bon Marché"),
  array("B & Q", "B&Q"),
  array("Brantano", "Brantano Footwear"),
  array("Cancer Research", "Cancer Research UK"),
  array("Carpetright", "CarpetRight", "Carpet Right"),
  array("CeX", "CEX"),
  array("co-op", "Coop", "Co-op", "Co-Op", "co-operative", "Cooperative", "Co-operative", "Co-Operative", "Co-operative Food", "Cooperative Food", "Co-Operative Food", "Co-op Food", "The Co-op", "The co-operative", "The Cooperative", "The Co-operative", "The Co-Operative", "The co-operative food", "The Cooperative", "The Co-operative", "The Co-Operative", "The co-operative food", "The Co-operative food", "The Co-operative Food", "The Co-Operative Food "),
  array("Costcutter", "Costcutters"),
  array("Cotswold", "Cotswold Outdoor"),
  array("Farm Foods", "Farmfoods"),
  array("F Hinds", "F. Hinds"),
  array("Greggs", "Gregg's"),
  array("Holland and Barrett", "Holland & Barrett"),
  array("H Samuel", "H. Samuel"),
  array("JD", "JD Sports"),
  array("Jewson", "Jewsons"),
  array("Jones", "Jones Bootmaker"),
  array("Kwik fit", "Kwik Fit"),
  array("Ladbrokes", "Ladbrooks"),
  array("Launderette", "Laundrette"),
  array("Lidl", "LIDL"),
  array("Majestic", "Majestic Wine", "Majestic Wine Warehouse"),
  array("Maplin", "Maplin Electronics", "Maplins"),
  array("Marks and Spencer", "Marks & Spencer", "M&S"),
  array("Marks & Spencer Simply Food", "M&S Simply Food"),
  array("Martins", "Martin's"),
  array("McColls", "McColl's"),
  array("M & Co", "M&Co"),
  array("Morrisons", "Morrison's"),
  array("Nisa", "Nisa Local"),
  array("OneStop", "One Stop", "One-Stop", "One Stop Shop"),
  array("Oxfam Books", "Oxfam Bookshop"),
  array("Pets at Home", "Pets At Home"),
  array("Phones4U", "Phones 4U", "Phones 4 U"),
  array("Poundstretcher", "Pound Stretcher"),
  array("Ryman", "Rymans"),
  array("Sainsbury", "Sainsburys", "Sainsbury's"),
  array("Sainsbury's Local", "Sainsburys Local"),
  array("Spar", "SPAR"),
  array("Sue Ryder", "Sue Ryder Care"),
  array("Tesco Express", "Tesco express"),
  array("Barber Shop", "The Barber Shop"),
  array("Body Shop", "The Body Shop"),
  array("Carphone Warehouse", "The Carphone Warehouse"),
  array("Edinburgh Woollen Mill", "The Edinburgh Woollen Mill"),
  array("Village Stores", "The Village Store"),
  array("Salvation Army", "The Salvation Army"),
  array("Thompson", "Thomson"),
  array("Timpson", "Timpsons"),
  array("T K Maxx", "TK Maxx"),
  array("Waterstones", "Waterstone's"),
  array("WHSmith", "W H Smith", "WH Smith", "WH Smiths"),
  array("Wilkinson", "Wilkinsons", "Wilko"),
  array("We", "WE"),
  array("Hema", "HEMA"),
  array("Plus", "PLUS"),
  array("EkoPlaza", "Ekoplaza"),
  array("Emté", "EMTÉ"),
  array("D-reizen", "D-Reizen"),
  array("Leen Bakker", "Leenbakker"),
  array("Cool Cat", "CoolCat", "Coolcat"),
  array("Aktie Sport", "Aktiesport"),
  array("Media Markt", "Mediamarkt"),
  array("Bakkerij Bart", "Bakker Bart"),
  array("Dirk", "Dirk van den Broek"),
  array("Pearle", "Pearle Opticiens"),
  array("AH to go", "Albert Heijn TOGO"),
);



$dbconn = pg_connect("host=localhost dbname=$db")
    or die('Could not connect: ' . pg_last_error());

echo "Selecting shops...\n";

//Select all shop names that occur more than 10 times
$query = "
SELECT name FROM (
    SELECT name, SUM(cnt) AS cnt FROM (
        SELECT name, COUNT(name) AS cnt FROM planet_osm_point WHERE NOT shop IS NULL AND NOT name IS NULL GROUP BY name
        UNION SELECT name, COUNT(name) AS cnt FROM planet_osm_polygon WHERE NOT shop IS NULL AND NOT name IS NULL GROUP BY name
    ) AS subsubquery
    GROUP BY name
    ORDER BY name
) AS subquery
WHERE cnt > 10";
$result = pg_query($query) or die('Query failed: ' . pg_last_error());

echo "Selected shops.\n";

// Loop through results
while ($name = pg_fetch_array($result, null, PGSQL_ASSOC)) {
    $pgNameQueryPart = "";
    $displayName = "";
    $isDuplicate = false;
    foreach ($duplicatesList as $duplicates) {
        foreach ($duplicates as $i => $duplicate) {
            if ($name['name'] == $duplicate) {
                // Ignore duplicates that are not the first item in the list of duplicates
                if ($i == 0) {
                    // Generate query and name to display consisting of all alternative names
                    foreach ($duplicates as $j => $duplicate2) {
                        if ($j != 0) {
                            $pgNameQueryPart .= " OR ";
                            $displayName .= " / ";
                        }
                        $pgNameQueryPart .= "name = '" . pg_escape_string($duplicate2) . "'";
                        $displayName .= "$duplicate2";
                    }
                }
                else {
                    $isDuplicate = true;
                }
            }
        }
    }
    if ($displayName == "") $displayName = $name['name'];
    if ($pgNameQueryPart == "") $pgNameQueryPart = "name = '" . pg_escape_string($name['name']) . "'";

    if (!$isDuplicate) {
        echo "Getting data for $displayName...\n";
        // Select all shop types for that shop
        $query = "SELECT shop, SUM(cnt) AS cnt FROM (
          SELECT shop, COUNT(*) AS cnt FROM planet_osm_point WHERE NOT shop IS NULL AND NOT name IS NULL AND ($pgNameQueryPart) GROUP BY shop
          UNION SELECT shop, COUNT(*) AS cnt FROM planet_osm_polygon WHERE NOT shop IS NULL AND NOT name IS NULL AND ($pgNameQueryPart) GROUP BY shop
        ) AS subquery
        GROUP BY shop
        ORDER BY SUM(cnt) DESC;";
        $result2 = pg_query($query) or die('Query failed: ' . pg_last_error());
        $i = 0;
        while ($shoptype = pg_fetch_array($result2, null, PGSQL_ASSOC)) {
            if ($i == 0) {
                $type = $shoptype['shop'];
                $shop['name'] = $displayName;
                $shop['cnt'] = $shoptype['cnt'];
                $shop['alternatives'] = array();
            }
            else {
                if ($shoptype['cnt'] >= 5) {
                    $shop['alternatives'][] = $shoptype;
                }
            }
            $i++;
        }
        $data[$type][] = $shop;
        echo "Got data for $displayName.\n";
    }
}

// Sort by shop type
ksort($data);

// Loop through shop types
foreach ($data as $shoptype => $shops) {
    echo "== {{tag|shop|$shoptype}} ==\n";
    foreach ($shops as $shop) {
        $alternativestext = "";
        foreach ($shop['alternatives'] as $alternative) {
            $alternativestext .= "{{tag|shop|{$alternative['shop']}}} ({$alternative['cnt']}x); ";
        }
        if ($alternativestext != "") { $alternativestext = "(Alternatives: $alternativestext)"; }
        echo "* {$shop['name']} ({$shop['cnt']}x) $alternativestext\n";
    }
    echo "\n";
}


?>
