var filterSequencer = {
  checkUserType: function (el) {
    var list = el.val();
    if(list) {
      $.each(list, function (key, value) {
        if(value == 1 || value == 2)
          filterSequencer.hideConversion();
        else
          filterSequencer.showConversion();
      });
    }
  },
  showConversion: function () {
    $('#type_of_conversion_fieldset').show();
  },
  hideConversion: function () {
    $('#type_of_conversion_fieldset').hide();
  }
};