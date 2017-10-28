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
$div = 4;

$ret = [];
$ret_num = 0;
$hit_per_page = 10;
$hit_per_photo_page = 0;
$zone_count = [];

if( isset($_GET["lat"]) ){
    $lat = floatval($_GET["lat"]);
    $_SESSION["lat"] = $lat;
}

if( isset($_GET["lon"]) ){
    $lon = floatval($_GET["lon"]);
    $_SESSION["lng"] = $lon;
}

if( isset($_GET["range"]) ){
    $range = intval($_GET["range"]);
    $_SESSION["range"] = $range;
}

if( isset($_GET["div"]) ){
    $div = intval($_GET["div"]);
    $_SESSION["div"] = $div;
}else{
    if( isset($_SESSION["div"]) ){
        $div = $_SESSION["div"];
    }
}

for( $i = 0; $i <= $div; $i++ ){
    $zone_count[$i] = 0;
}

mydump("div:".$div);
mydump($zone_count);

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
    for( $i=0; $i<10; $i++ ){
        array_push($ret,[
            'name'=>$desc[$i],
            'lat'=>$lat+rand(-9,9)/2000,
            'lng'=>$lon+rand(-9,9)/2000
        ]);
    }
    echo json_encode($ret);
    exit();
}
$total = $obj->total_hit_count;
mydump("total_hit_count");
mydump($total);

// ランクが低い順に取得
for( $i = $total - $hit_per_page; $i > $hit_per_page; $i = $i - $hit_per_page ){

    mydump("offset:".$i);

    $url = $rest_uri."?".http_build_query(array_merge($base_param,[
            "offset"=>$i,
            "hit_per_page"=>$hit_per_page,
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

                // 隣接する（同一zoneにある）場合はループを抜ける
                $deg = 180 + rad2deg( atan2($spot->{'latitude'}-$lat,$spot->{'longitude'}-$lon) );
                $zone = round($deg/(360/$div));
                mydump("zone:".$zone);
                mydump("min:".min($zone_count));
                mydump("zone_count:".$zone_count[$zone]);
                if( isset($zone_count[$zone]) ){
                    if( $zone_count[$zone] < min($zone_count) + 3 ){
                        $zone_count[$zone] = $zone_count[$zone] + 1;
                    }else{
                        mydump($spot->{'id'}." - neighbor - ".$zone);
                        $flag = false;
                    }
                }
                /*
                if( $zone_count[$zone] == min($zone_count) ){
                    $zone_count[$zone] = $zone_count[$zone] + 1;
                }else{
                    mydump($spot->{'id'}." - neighbor - ".$zone);
                    //$flag = false;
                }
                */

                if( $flag == true ){
                    mydump($spot->{'id'}." d".round($deg)." z".$zone);
                    array_push($ret,[
                        'name'=>$spot->{'name'},
                        'id'=>$spot->{'id'},
                        'offset'=>$i,
                        'deg'=>round($deg),
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

mydump($zone_count);

header("Content-Type: application/json; charset=utf-8");
array_multisort(array_column($ret,'deg'),$ret);
$ret = json_encode($ret);
echo $ret;
