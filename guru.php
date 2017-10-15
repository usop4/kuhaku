<?php

require_once("secret.php");

// APIリファレンス http://api.gnavi.co.jp/api/manual/
$rest_uri = "https://api.gnavi.co.jp/RestSearchAPI/20150630/";
$photo_uri = "https://api.gnavi.co.jp/PhotoSearchAPI/20150630/";
$lat   = 35.7192805;
$lon   = 139.6509221;
$hit_per_page = 50;
$range = 3;//1:300m、2:500m、3:1000m、4:2000m、5:3000m

if( isset($_GET["lat"]) ){
    $lat = floatval($_GET["lat"]);
}

if( isset($_GET["lon"]) ){
    $lon = floatval($_GET["lon"]);
}

$base_param = [
    "format"=>"json",
    "keyid"=>$acckey,
    "input_coordinates_mode"=>2,//世界測地系
    "coordinates_mode"=>2,//世界測地系
    "latitude"=>$lat,
    "longitude"=>$lon,
    "hit_per_page"=>$hit_per_page,
    "range"=>$range
];

// 写真を取得
$shop_array = [];
$photo_total = 0;
$json = file_get_contents_cash($photo_uri."?".http_build_query(array_merge($base_param,[
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
for( $i = $total - $hit_per_page; $i > $hit_per_page; $i = $i - $hit_per_page ){

    $json = file_get_contents_cash($rest_uri."?".http_build_query(array_merge($base_param,[
            "offset"=>$i
    ])));
    $obj  = json_decode($json);

    foreach((array)$obj as $key => $val){
        if(strcmp($key, "rest") == 0){
            foreach((array)$val as $spot){
                // 写真がある場合はループを抜ける
                if(checkString($spot->{'image_url'}->{'shop_image1'})){
                    break;
                }
                // ユーザ写真がある場合はループを抜ける
                if(in_array($spot->{'name'},$shop_array)){
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

// file_get_contentsのキャッシュ付き
function file_get_contents_cash($url){
    $cash_path = "cash/".md5($url);
    if( file_exists($cash_path) ){
        $contents = file_get_contents($cash_path);
        mydump("cash hit : ".$url);
    }else{
        $contents = file_get_contents($url);
        file_put_contents($cash_path,$contents);
        mydump($contents);
    }
    return $contents;
}

// ログ出力
function mydump($data, $fname = "log.txt", $overwrite = TRUE ){
    ob_start();
    var_dump($data);
    $out = ob_get_contents();
    ob_end_clean();
    if( $overwrite == TRUE ){
        file_put_contents($fname,date(DATE_RFC2822)." ".$out.PHP_EOL,FILE_APPEND);
    }else{
        file_put_contents($fname,date(DATE_RFC2822)." ".$out.PHP_EOL);
    }
}
