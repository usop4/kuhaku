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
}

if( isset($_GET["lon"]) ){
    $lon = floatval($_GET["lon"]);
    $_SESSION["lng"] = $lon;
}

$base_param = [
    "format"=>"json",
    "input_coordinates_mode"=>2,//世界測地系
    "coordinates_mode"=>2,//世界測地系
    "keyid"=>$acckey
];

// 件数を取得
$total = 0;
$url = $rest_uri."?".http_build_query(array_merge($base_param,[
        "latitude"=>$lat,
        "longitude"=>$lon,
        "range"=>$range
]));
$json = file_get_contents_cache($url);
$obj  = json_decode($json);
if( $obj->{"gnavi"}->{"error"}->{"code"} == 429 ){
    echo "429";
    mydump($obj);
    exit();
}
$total = $obj->total_hit_count;
mydump("total_hit_count");
mydump($total);

// ランクが低い順に取得
$ret = [];
$ret_num = 0;
$hit_per_page = 10;
$hit_per_photo_page = 0;
$zone_count = [];
for( $i = $total - $hit_per_page; $i > $hit_per_page; $i = $i - $hit_per_page ){

    mydump("offset:".$i);

    $url = $rest_uri."?".http_build_query(array_merge($base_param,[
            "offset"=>$i,
            "hit_per_page"=>10,
            //"hit_per_page"=>$hit_per_page,
            "latitude"=>$lat,
            "longitude"=>$lon,
            "range"=>$range
    ]));
    $json = file_get_contents_cache($url);
    $obj  = json_decode($json);

    // 写真を取得するために店舗一覧を作成
    $shop_array = [];
    foreach((array)$obj as $key => $val){
        if(strcmp($key, "rest") == 0){
            foreach((array)$val as $spot){
                array_push($shop_array,$spot->{"id"});
            }
        }
    }
    $shop_query = implode(",",$shop_array);
    mydump("shop_query:".$shop_query);

    // 店舗一覧の口コミがあるか検索
    $photo_array = [];
    $url = $photo_uri."?".http_build_query($base_param)."&shop_id=".$shop_query;
    $json = file_get_contents_cache($url);
    $photo_obj = json_decode($json);
    $data = $photo_obj->{"response"};
    foreach((array)$data as $key => $val){
        if(strcmp($key, "total_hit_count" ) == 0 ){
            $photo_total = $val;
            mydump("photo_total: ".$photo_total);
        }

        if(strcmp($key, "hit_per_page" ) == 0 ){
            $hit_per_photo_page = $val;
            mydump("hit_per_photo_page:".$hit_per_photo_page);
        }

        for($j = 0; $j < $hit_per_photo_page ; $j++){
            if(strcmp($key, $j) == 0){
                $shop_id = $val->{'photo'}->{'shop_id'};
                array_push($photo_array,$shop_id);
            }
        }
    }

    // 写真がない店のみ表示
    foreach((array)$obj as $key => $val){
        if(strcmp($key, "rest") == 0){
            foreach((array)$val as $spot){

                $flag = true;
                $zone = 0;

                mydump($spot->{'id'});

                // 写真がある場合はループを抜ける
                if(checkString($spot->{'image_url'}->{'shop_image1'})){
                    mydump($spot->{'id'}." - shop photo");
                    $flag = false;
                }

                // ユーザ写真がある場合はループを抜ける
                if(in_array($spot->{'id'},$photo_array)){
                    mydump($spot->{'id'}." - user photo");
                    $flag = false;
                }

                // 隣接する場合はループを抜ける
                $atan2 = 180 + 180 * atan2($spot->{'latitude'}-$lat,$spot->{'longitude'}-$lon) / M_PI;
                $zone = round($atan2/10);//360度を10度刻み
                if( isset($zone_count[$zone]) ){
                    mydump($spot->{'id'}." - neighbor - ".$zone);
                    $flag = false;
                }else{
                    $zone_count[$zone] = 1;
                }

                if( $flag == true ){
                    array_push($ret,[
                        'name'=>$spot->{'name'},
                        'offset'=>$i,
                        'zone'=>$zone,
                        'url'=>$spot->{'url'},
                        'lat'=>$spot->{'latitude'},
                        'lng'=>$spot->{'longitude'}
                    ]);
                    $ret_num = $ret_num + 1;
                }
                if( $ret_num >= 10 ){
                    break 3;
                }
            }
        }
    }
}

header("Content-Type: application/json; charset=utf-8");
array_multisort(array_column($ret,'zone'),$ret);
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
