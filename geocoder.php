<?php

session_start();

require_once("common.php");
require_once("secret.php");

$address = $_GET["address"];

$url = "https://map.yahooapis.jp/geocode/V1/geoCoder?"
    .http_build_query([
        "appid"=>$appid,
        "query"=>$address
    ]);
$contents = file_get_contents($url);

$location = new SimpleXMLElement($contents);

if( $location->attributes()->totalResultsReturned != 0 ){
    $cordinates = $location->Feature[0]->Geometry->Coordinates;
    list($lng,$lat) = explode(",",$cordinates);
    echo json_encode([
        "lat"=>$lat,
        "lng"=>$lng
    ]);
    $_SESSION["lat"] = $lat;
    $_SESSION["lng"] = $lng;
}
