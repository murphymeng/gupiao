var colorRender = function(val, d, obj) {
    var color = '';
    if (val > 0) {
        color = 'red';
    } else if (val < 0) {
        color = 'green';
    } else if (val === 'null') {
        return '';
    }
    return "<span class='"+color+"'>" + val + "%</span>";
}