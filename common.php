<?php

// file_get_contentsのキャッシュ付き
function file_get_contents_cash($url){
    $cash_path = "cash/".md5($url);
    if( file_exists($cash_path) ){
        $contents = file_get_contents($cash_path);
        mydump("hit : ".$cash_path);
    }else{
        $contents = file_get_contents($url);
        file_put_contents($cash_path,$contents);
        mydump($cash_path);
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
