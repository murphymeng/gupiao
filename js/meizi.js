$(document).ready(function(){

  for(var i = 0; i < arr.length; i++) {
    if(arr[i].like == 1) {
      $('#' + arr[i]['uid']).attr('src', 'img/like.png');
    }
  }

  $('.like').click(function(e) {

    var like = 0;
    if($(this).attr('src') == 'img/unlike.png') {
        $(this).attr('src', 'img/like.png');
        like = 1;
    } else {
        $(this).attr('src', 'img/unlike.png');
    }
    var uid = $(this).attr('id');
    for(var i = 0; i < arr.length; i++) {
       if(arr[i].uid == uid) {
            arr[i].like = like;
        }
	}
  });


  $(window).keypress(function(e) {
       var key = e.which;
       if(key == 113) {
          showGirls();
       }
   });
});

showGirls = function() {
    $.ajax({
      type: "POST",
      url: "./update",
      data: {'result': arr},
      dataType: 'json',
      success: function(data) {
        location.reload();
      },
      error: function() {
        alert('error');
      }
    });
};

