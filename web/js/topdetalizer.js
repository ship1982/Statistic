var topDetalizer = {
  checkValue: function(event, id, prefix) {
    var selectStart = $('#day_' + id + '_s'),
      selectEnd = $('#day_' + id + '_e');

    if(prefix == 's') {
      if(selectEnd.val() == 'all')
        selectEnd.val('00');

      if(selectStart.val() == 'all')
        selectEnd.val('all');
    } else if(prefix == 'e') {         
      if(selectStart.val() == 'all')
        selectStart.val('00');
      
      if(selectEnd.val() == 'all')
        selectStart.val('all');
    }

    topDetalizer.crossBox();
  },
  crossBox: function () {
    var a = [1,2,3,4,5,6,7],
      d = false;
    for (var i = a.length; i >= 1; i--) {
      var b = $('#day_' + i + '_e').val(),
        c = $('#day_' + i + '_s').val();
      if(b != 'all' || c != 'all')
        d = true;
    }

    if(d) {
      var cc = $('#diff-checkbox');
      cc.prop('checked', false);
      cc.attr('checked', false);
      $('#cross-checkbox').hide();
    } else
      $('#cross-checkbox').show();
  }
};