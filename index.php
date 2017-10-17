<?php
$debug = false;

require_once("secret.php");

$lat=35.71507331660124;
$lng=139.6873127755067;

session_start();

if( isset($_GET["lat"]) ){
    $lat = floatval($_GET["lat"]);
}else{
    if( isset($_SESSION["lat"]) ){
        $lat = floatval($_SESSION["lat"]);
    }
}

if( isset($_GET["lng"]) ){
    $lng = floatval($_GET["lng"]);
}else{
    if( isset($_SESSION["lng"]) ){
        $lng = floatval($_SESSION["lng"]);
    }
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>ku-haku</title>

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
                <a class="nav-link dropdown-toggle" href="http://example.com" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">このサイトは？</a>
                <div class="dropdown-menu" aria-labelledby="dropdown01">
                    <a class="dropdown-item" href="#">ぐるなびの情報を元に</a>
                    <a class="dropdown-item" href="#">１ｋｍ程度の距離にある</a>
                    <a class="dropdown-item" href="#">まだ写真が登録されていない</a>
                    <a class="dropdown-item" href="#">お店を発見するサービスです</a>
                </div>
            </li>
        </ul>
        <form action="geocoder.php" class="form-inline my-2 my-lg-0">
            <input name="address" class="form-control mr-sm-2" type="text" placeholder="Search" aria-label="Search">
            <button class="btn btn-outline-success my-2 my-sm-0" type="submit">ken-saku</button>
        </form>
    </div>

</nav>

<div class="container">

    <div class="row">
        <div class="col-sm-6">
            <div><img src="icon/0.gif"><span id="0"></span><a class="link0">[i]</a></div>
            <div><img src="icon/1.gif"><span id="1"></span><a class="link1">[i]</a></div>
            <div><img src="icon/2.gif"><span id="2"></span><a class="link2">[i]</a></div>
            <div><img src="icon/3.gif"><span id="3"></span><a class="link3">[i]</a></div>
            <div><img src="icon/4.gif"><span id="4"></span><a class="link4">[i]</a></div>
            <div><img src="icon/5.gif"><span id="5"></span><a class="link5">[i]</a></div>
            <div><img src="icon/6.gif"><span id="6"></span><a class="link6">[i]</a></div>
            <div><img src="icon/7.gif"><span id="7"></span><a class="link7">[i]</a></div>
            <div><img src="icon/8.gif"><span id="8"></span><a class="link8">[i]</a></div>
            <div><img src="icon/9.gif"><span id="9"></span><a class="link9">[i]</a></div>

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


</div><!-- /.container -->


<!-- Bootstrap core JavaScript
================================================== -->
<!-- Placed at the end of the document so the pages load faster -->
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
<script src="js/ie10-viewport-bug-workaround.js"></script>
<script type="text/javascript" charset="utf-8" src="http://js.api.olp.yahooapis.jp/OpenLocalPlatform/V1/jsapi?appid=<?php echo $appid;?>"></script>
<script>

    var map;
    var marker = [];
    var icon = [];

    $(function() {

        var lat = <?php echo $lat;?>;
        var lng = <?php echo $lng;?>;
        var zoom = 16;

        // Yahoo Map
        map = new Y.Map("map");
        map.addControl(new Y.CenterMarkControl({
            visibleButton: true ,
            visible      : true
        }));
        map.addControl(new Y.SliderZoomControl());
        map.drawMap(new Y.LatLng(lat,lng), zoom, Y.LayerSetId.NORMAL);

        map.bind("moveend", function(){
            loadSpots(true);
        });

        for(var i=0;i<10;i++){
            icon[i] = new Y.Icon('icon/'+i+'.gif');
        }

        loadSpots(false);

    });

    function loadSpots(push_flag){
        var latlng = map.getCenter();
        var zoom = map.getZoom();
        var lat = latlng.lat();
        var lng = latlng.lng();
        var url = "guru.php?lat="+lat+"&lon="+lng;

        for(var i=0;i<10;i++){
            map.removeFeature(marker[i]);
            $("#"+i).html("");
        }

        $.ajax({
            url: url,
            dataType:"json",
            success: function(spots){
                console.log(spots);
                for(var i in spots){
                    console.log(spots[i]["offset"]+" "+spots[i]["name"]);
                    $("#"+i).html(spots[i]["name"]);
                    $(".link"+i).attr("href",spots[i]["url"]);
                    $(".link"+i).attr("target","_blank");
                    marker[i] = new Y.Marker(
                        new Y.LatLng(spots[i]["lat"],spots[i]["lng"]),
                        {icon: icon[i]}
                    );
                    map.addFeature(marker[i]);
                }
            }
        });
        console.log(url);
        if( push_flag == true ){
            window.history.pushState(null,null,"?lat="+lat+"&lng="+lng);
        }
    }


</script>
</body>
</html>
