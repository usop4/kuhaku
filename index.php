<?php
if (empty($_SERVER['HTTPS'])) {
    header("Location: https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
    exit;
}

$debug = false;
if( substr_count( $_SERVER["SCRIPT_NAME"], "sandbox") ){
    $debug = "true";
}

require_once("secret.php");

$lat = 35.69384330;
$lng = 139.70355740;
$plugin = "mode1";

session_start();

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

if( isset($_GET["range"]) ){
    $range = intval($_GET["range"]);
    $_SESSION["range"] = $range;
}else{
    if( isset($_SESSION["range"]) ){
        $range = intval($_SESSION["range"]);
    }
}

if( isset($_GET["div"]) ){
    $div = intval($_GET["div"]);
    $_SESSION["div"] = $div;
}else{
    if( isset($_SESSION["div"]) ){
        $range = intval($_SESSION["div"]);
    }
}

if( isset($_GET["plugin"]) ){
    $plugin = strval($_GET["plugin"]);
    $_SESSION["plugin"] = $plugin;
}else{
    if( isset($_SESSION["plugin"]) ){
        $plugin = strval($_SESSION["plugin"]);
    }
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <title>ku-haku</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <meta property="fb:app_id" content="171184180129440" />
    <meta property="og:url" content="https://barcelona-prototype.com/kuhaku/" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="ku-haku - 自転車と一緒に「空白」を探す" />
    <meta property="og:description" content="ぐるなびの情報を元に、まだ写真や口コミが登録されていない店を発見するサービスです。" />
    <meta property="og:image" content="https://barcelona-prototype.com/kuhaku/landing/img/og.png" />

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/starter-template.css" rel="stylesheet">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.0.0/jquery.min.js"></script>

</head>
<body>

<nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
    <a class="navbar-brand" href="#">ku-haku</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarsExampleDefault">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">このサイトは？</a>
                <div class="dropdown-menu" aria-labelledby="dropdown01">
                    <a class="dropdown-item" href="landing">ぐるなびの情報を元に</a>
                    <a class="dropdown-item" href="landing">１ｋｍ程度の距離にある</a>
                    <a class="dropdown-item" href="landing">まだ写真が登録されていない</a>
                    <a class="dropdown-item" href="landing">お店を発見するサービスです</a>
                </div>
            </li>
        </ul>
    </div>

</nav>

<div class="container">

    <div class="row">
        <div class="col-sm-6">

            <form>
                <div class="form-group">
                    <select class="form-control form-control-sm" id="plugin">
                        <option value="mode1">ぐるなび500m圏内、口コミなし、方位分散なし</option>
                        <option value="mode2">ぐるなび500m圏内、口コミなし、16方位分散</option>
                        <option value="mode3">ぐるなび1km圏内、口コミなし、16方位分散</option>
                        <option value="dummy">テスト表示（プラグイン募集中）</option>
                    </select>
                </div>
            </form>

            <div><img src="icon/0.gif"><span id="0"><img src="icon/loading.gif"></span><a class="link0">[i]</a></div>
            <div><img src="icon/1.gif"><span id="1"><img src="icon/loading.gif"></span><a class="link1">[i]</a></div>
            <div><img src="icon/2.gif"><span id="2"><img src="icon/loading.gif"></span><a class="link2">[i]</a></div>
            <div><img src="icon/3.gif"><span id="3"><img src="icon/loading.gif"></span><a class="link3">[i]</a></div>
            <div><img src="icon/4.gif"><span id="4"><img src="icon/loading.gif"></span><a class="link4">[i]</a></div>
            <div><img src="icon/5.gif"><span id="5"><img src="icon/loading.gif"></span><a class="link5">[i]</a></div>
            <div><img src="icon/6.gif"><span id="6"><img src="icon/loading.gif"></span><a class="link6">[i]</a></div>
            <div><img src="icon/7.gif"><span id="7"><img src="icon/loading.gif"></span><a class="link7">[i]</a></div>
            <div><img src="icon/8.gif"><span id="8"><img src="icon/loading.gif"></span><a class="link8">[i]</a></div>
            <div><img src="icon/9.gif"><span id="9"><img src="icon/loading.gif"></span><a class="link9">[i]</a></div>

            <?php if(!$debug){echo "<!--";}?>
            <button id="test" class="btn btn-outline-primary" type="button">test</button>
            <?php if(!$debug){echo "-->";}?>
            <?php
            if( $debug == true ){
                var_dump($_SESSION);
            }
            ?>

        </div>
        <div class="col-sm-6">
            <div id="map" style="height:400px"></div>
        </div>
    </div>

    <form>
        <div class="form-row align-items-center">
            <div class="col-auto">
                <button id="reload" type="button" class="btn btn-outline-primary">更新</button>
            </div>
            <div class="col-auto">
                <button id="location" type="button" class="btn btn-outline-primary">現在地</button>
            </div>
            <div class="col-auto">
                <label class="sr-only" for="inlineFormInput">Name</label>
                <input name="address" type="text" class="form-control mb-2 mb-sm-0" id="inlineFormInput" value="東京都新宿区">
            </div>
            <div class="col-auto">
                <button id="geocode" type="button" class="btn btn-outline-primary">検索</button>
            </div>
        </div>
    </form>

</div><!-- /.container -->


<!-- Bootstrap core JavaScript
================================================== -->
<!-- Placed at the end of the document so the pages load faster -->
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
<script src="js/ie10-viewport-bug-workaround.js"></script>
<script type="text/javascript" charset="utf-8" src="https://map.yahooapis.jp/js/V1/jsapi?appid=<?php echo $appid;?>"></script>
<script type="text/javascript" charset="utf-8" src="kuhaku.js"></script>
<script>

    var map;
    var marker = [];
    var icon = [];
    var plugin = "<?php echo $plugin;?>";
    var lat = <?php echo $lat;?>;
    var lng = <?php echo $lng;?>;
    var zoom = 16;

    $(function() {
        $("#plugin").val("<?php echo $plugin;?>");

        $("#plugin").change(function(){
            location.href = "index.php?plugin="+$(this).val();
        });
    });

</script>
<!-- Global site tag (gtag.js) - Google Analytics -->
<?php if(!$debug){echo "<!--";}?>
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-55877107-6"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'UA-55877107-6');
</script>
<?php if(!$debug){echo "-->";}?>
</body>
</html>
