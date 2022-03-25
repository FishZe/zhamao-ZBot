var page = require('webpage').create();
system = require('system');

var url;
var path;

url = system.args[1];
path = system.args[2];

var width = 15000;
var height = 30000;

page.viewportSize = {width: width, height: height}; 
page.zoomFactor = 5;
page.open(url, function (status) {
    if (status != "success") {
        console.log('FAIL to load the address');
        phantom.exit();
    }
    var length;
    window.setTimeout(function () {
        length = page.evaluate(function () {
           var div = document.getElementsByClassName("card")[0];
            var bc = div.getBoundingClientRect();
            var top = bc.top;
            var left = bc.left;
            var width = bc.width;
            var height = bc.height;
            window.scrollTo(0, 200000);
            return [top, left, width, height];
        });
        page.clipRect = { 
            top: length[0] * 5,
            left: length[1] * 5,
            width: length[2] * 5,
            height: length[3] * 5
        };
    }, 5000);
    window.setTimeout(function () {
        page.render(path, {format:'png',quality:'300'});
        phantom.exit();
    }, 5000 + 500);
});