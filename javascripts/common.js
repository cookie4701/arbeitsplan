function timeToInt(valTime) {
        var pos = valTime.indexOf(":");
        if (pos === -1) {
                return 0;
        }

        var h = intval( valTime.substr(0,pos));
        var m = intval( valTime.substr(pos+1, valTime.length-1));

        return (h*60)+m;
},

function intToTime(valTime) {
        var h = intval(valTime / 60);

        var m = valTime - h*60;

        if (m < 10) {
                return h + ":0" + m;
        } else {
                return h + ":" + m;
        }
}

module.exports = timeToInt, intToTime;

