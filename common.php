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
