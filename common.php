<?php

// file_get_contentsのキャッシュ付き
function file_get_contents_cache($url){
    $cache = false;
    $cache_path = "cache/".md5($url);
    if( file_exists($cache_path) ){
        if( mktime() - filemtime("test.php") < 60 * 60 * 24 ){
            $cache = true;
        }
    }
    if( $cache == true ){
        $contents = file_get_contents($cache_path);
        mydump($cache_path." hit!");
    }else{
        $contents = file_get_contents($url);
        file_put_contents($cache_path,$contents);
        mydump($cache_path);
    }

    return $contents;
}

// ログ出力
function mydump($data, $overwrite = TRUE ){
    $fname = "log.txt";
    ob_start();
    var_dump($data);
    $out = ob_get_contents();
    ob_end_clean();
    if( $overwrite == TRUE ){
        file_put_contents($fname,date(DATE_RFC2822)." ".$out,FILE_APPEND);
    }else{
        file_put_contents($fname,date(DATE_RFC2822)." ".$out);
    }
}

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
