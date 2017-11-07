$(function() {

    map = new Y.Map("map");
    map.addControl(new Y.CenterMarkControl({
        visibleButton: true ,
        visible      : true
    }));
    map.addControl(new Y.ZoomControl());
    map.addControl(new Y.LayerSetControl());
    map.addControl(new Y.ScaleControl());
    map.drawMap(new Y.LatLng(lat,lng), zoom, Y.LayerSetId.NORMAL);

    map.bind('moveend',function(){
        $("#message").html("再検索する場合は更新ボタンを押してください");
        $("#message").fadeIn(800);
        $("#message").fadeOut(800);
    });

    for(var i=0;i<10;i++){
        icon[i] = new Y.Icon('icon/'+i+'.gif');
    }

    loadSpots(lat,lng);

    $("#reload").click(function(){
        var latlng = map.getCenter();
        console.log(latlng);
        loadSpots(latlng.Lat,latlng.Lon,true);
    });

    $("#location").click(function(){
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    var lat = pos.coords.latitude;
                    var lng = pos.coords.longitude
                    var latlng = new Y.LatLng(lat,lng);
                    map.setZoom(16,true,latlng);
                    loadSpots(lat,lng,true);
                })
        }
    });

    $("#geocode").click(function(){
        var address = $("[name=address]").val();
        if( address != "" ){
            $.ajax({
                url: "geocoder.php?address="+address,
                dataType:"json",
                success: function(json){
                    var lat = json["lat"];
                    var lng = json["lng"]
                    map.setZoom(16,true,new Y.LatLng(lat,lng));
                    loadSpots(lat,lng,true);
                }
            });
        }
    });

});

function mode2url(mode){
    switch(mode){
        case "dummy":
            return "dummy.php?";
        case "mode1":
            return "guru.php?range=2&div=1";
        case "mode2":
            return "guru.php?range=2&div=16";
        case "mode3":
            return "guru.php?range=3&div=1";
    }
}

function loadSpots(lat,lng,push_flag){
    var url = mode2url(plugin)+"&lat="+lat+"&lon="+lng;

    for(var i=0;i<10;i++){
        map.removeFeature(marker[i]);
        $("#"+i).html('<img src="icon/loading.gif">');
    }

    $.ajax({
        url: url,
        dataType:"json",
        success: function(spots){
            for(var i=0;i<10;i++){
                map.removeFeature(marker[i]);
                $("#"+i).html('');
            }
            console.log(spots);
            for(var i in spots){
                //console.log(spots[i]["offset"]+" "+spots[i]["name"]);
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

