<?php

session_start();

$_SESSION = [];

// キャッシュを削除
foreach(glob('cash/*') as $file){
    if(is_file($file)){
        echo htmlspecialchars($file);
        unlink($file);
    }
}

// ログを削除
unlink("log.txt");

