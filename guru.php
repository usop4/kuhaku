<?php

session_start();

require_once("common.php");
require_once("secret.php");

mydump("guru.php",false);

// APIリファレンス http://api.gnavi.co.jp/api/manual/
$rest_uri = "https://api.gnavi.co.jp/RestSearchAPI/20150630/";
$photo_uri = "https://api.gnavi.co.jp/PhotoSearchAPI/20150630/";
$lat   = 35.7192805;
$lon   = 139.6509221;
$range = 2;//1:300m、2:500m、3:1000m、4:2000m、5:3000m

if( isset($_GET["lat"]) ){
    $lat = floatval($_GET["lat"]);
    $_SESSION["lat"] = $lat;
}else{
    if( isset($_SESSION["lat"]) ){
        $lat = floatval($_SESSION["lat"]);
    }
}

if( isset($_GET["lng"]) ){
    $lng = floatval($_GET["lng"]);
    $_SESSION["lng"] = $lng;
}else{
    if( isset($_SESSION["lng"]) ){
        $lng = floatval($_SESSION["lng"]);
    }
}

$base_param = [
    "format"=>"json",
    "keyid"=>$acckey,
    "input_coordinates_mode"=>2,//世界測地系
    "coordinates_mode"=>2,//世界測地系
    "latitude"=>$lat,
    "longitude"=>$lon,
    "range"=>$range
];

// 写真を取得
$shop_array = [];
$photo_total = 0;
$hit_per_page = 50;
$json = file_get_contents_cash($photo_uri."?".http_build_query(array_merge($base_param,[
        "hit_per_page"=>$hit_per_page,
        "order"=>"distance",
        "sort"=>1
])));
$obj  = json_decode($json);
if( $obj->{"gnavi"}->{"error"}->{"code"} == 429 ){
    echo "429";
    mydump($obj);
    exit();
}
$data = $obj->{"response"};
foreach((array)$data as $key => $val){
    if(strcmp($key, "total_hit_count" ) == 0 ){
        $photo_total = $val;
        mydump("photo_total: ".$photo_total);
    }
    if(strcmp($key, "hit_per_page" ) == 0 ){
        $hit_per_page = $val;
    }
    for($i = 0; $i < $hit_per_page ; $i++){
        if(strcmp($key, $i) == 0){
            $restArray = $val->{'photo'};
            $shop_id = $restArray->{'shop_id'};
            array_push($shop_array,$shop_id);
        }
    }
}

// 件数を取得
$total = 0;
$json = file_get_contents_cash($rest_uri."?".http_build_query($base_param));
$obj  = json_decode($json);
foreach((array)$obj as $key => $val){
    if(strcmp($key, "total_hit_count" ) == 0 ){
        $total = $val;
    }
}

// ランクが低い順に取得
$ret = [];
$ret_num = 0;
$hit_per_page = 10;
for( $i = $total - $hit_per_page; $i > $hit_per_page; $i = $i - $hit_per_page ){

    $json = file_get_contents_cash($rest_uri."?".http_build_query(array_merge($base_param,[
            "hit_per_page"=>$hit_per_page,
            "offset"=>$i
    ])));
    $obj  = json_decode($json);

    foreach((array)$obj as $key => $val){
        if(strcmp($key, "rest") == 0){
            foreach((array)$val as $spot){
                mydump($spot->{'id'});

                // 写真がある場合はループを抜ける
                if(checkString($spot->{'image_url'}->{'shop_image1'})){
                    mydump("shop_photo: ".$spot->{'name'});
                    break;
                }

                // ユーザ写真がある場合はループを抜ける
                if(in_array($spot->{'id'},$shop_array)){
                    mydump("user_photo: ".$spot->{'name'});
                    break;
                }

                $dist = compute_dist($lat,$lon,$spot->{'latitude'},$spot->{'longitude'});

                array_push($ret,[
                    'name'=>$spot->{'name'},
                    'offset'=>$i,
                    'dist'=>$dist,
                    'url'=>$spot->{'url'},
                    'lat'=>$spot->{'latitude'},
                    'lng'=>$spot->{'longitude'}
                ]);
                $ret_num = $ret_num + 1;
                if( $ret_num >= 10 ){
                    break 3;
                }
            }
        }
    }
}

header("Content-Type: application/json; charset=utf-8");
$ret = json_encode($ret);
echo $ret;

//文字列であるかをチェック
function checkString($input)
{
    if(isset($input) && is_string($input)) {
        return true;
    }else{
        return false;
    }
}

// 距離を計算
function compute_dist($lat1, $lng1, $lat2, $lng2){
    $GRS80_A = 6377397.155;
    $GRS80_E2 = 0.00667436061028297;
    $GRS80_MNUM = 6334832.10663254;

    $mu_y = deg2rad($lat1 + $lat2)/2;
    $W = sqrt(1-$GRS80_E2*pow(sin($mu_y),2));
    $W3 = $W*$W*$W;
    $M = $GRS80_MNUM/$W3;
    $N = $GRS80_A/$W;
    $dx = deg2rad($lng1 - $lng2);
    $dy = deg2rad($lat1 - $lat2);

    $dist = sqrt(pow($dy*$M,2) + pow($dx*$N*cos($mu_y),2)) / 1000;

    return $dist;

}
