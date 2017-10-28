<?php

session_start();

require_once("common.php");

$lat   = 35.7192805;
$lon   = 139.6509221;

if( isset($_GET["lat"]) ){
    $lat = floatval($_GET["lat"]);
    $_SESSION["lat"] = $lat;
}

if( isset($_GET["lon"]) ){
    $lon = floatval($_GET["lon"]);
    $_SESSION["lon"] = $lon;
}

$desc = [
    "APIの利用上限に達したため",
    "サービスをご利用いただけません。",
    "またのご利用をお待ちしております。",
    "",
    "",
    "",
    "",
    "",
    "",
    ""
];
$ret = [];
$ret_num = 0;
$hit_per_page = 10;
$range = 2000;
for( $i=0; $i<10; $i++ ){
    array_push($ret,[
        //'name'=>"dummy".sprintf("%d",$i+1),
        'name'=>$desc[$i],
        'offset'=>$i,
        'dist'=>$dist,
        'url'=>"",
        'lat'=>$lat+rand(-9,9)/$range,
        'lng'=>$lon+rand(-9,9)/$range
    ]);
}

header("Content-Type: application/json; charset=utf-8");
$ret = json_encode($ret);
echo $ret;
