function debug(m, d) {
  "use strict";
  if (!d) {
    return console.log(m);
  }
  return console.dir(m);
}

var common = {
  showSum: function () {
    "use strict";
    if ($('#diff-checkbox').prop('checked') == true) {
      $('#sum-checkbox').css('display', 'none');
    } else {
      $('#sum-checkbox').css('display', 'block');
    }
  },
  inputActive: function () {
    "use strict";
    var o = $('.md-input.animated');
    $.each(o, function (k) {
      $(this).on('focus', function () {
        $(this).parent('.md-input-group').addClass('md-input-animated');
      });
      $(this).on('blur', function () {
        $(this).parent('.md-input-group').removeClass('md-input-animated');
      });
    });
  },
  init: function () {
    "use strict";
    this.inputActive();
    common.showSum();
  },
  setting: {
    delete: function (id) {
      location.href = '/groupfilter/delete/' + id;
    },
    update: function (id) {
      location.href = '/groupfilter/update/' + id;
    }
  },
  conditions: {
    delete: function (id) {
      location.href = '/condition_user_property/delete/' + id;
    },
    update: function (id) {
      location.href = '/condition_user_property/update/' + id;
    }
  },
  events: {
    update: function (label, position, event) {
      event.stopPropagation();
      var search = '';
      if (position) {
        search = '?id=' + position
      }
      location.href = '/events/update/' + label + search;
    },
    delete: function (label, position, event) {
      event.stopPropagation();
      if (confirm("Вы действительно хотите удалить запись " + label)) {
        var search = '';
        if (position) {
          search = '?id=' + position
        }

        location.href = '/events/delete/' + label + search;
      }
    }
  },
  partners: {
    update: function (label, event) {
      event.stopPropagation();
      location.href = '/partners/update/' + label;
    },
    delete: function (label, event) {
      event.stopPropagation();
      if (confirm("Вы действительно хотите удалить запись " + label)) {
        location.href = '/partners/delete/' + label;
      }
    }
  },
  model: {
    update: function (label, event) {
      "use strict";
      event.preventDefault();
      location.href = label;
    },
    delete: function (label, event) {
      "use strict";
      event.stopPropagation();
      if (confirm("Вы действительно хотите удалить запись " + label)) {
        location.href = label;
      }
    }
  }
};

$(document).ready(function () {
  "use strict";
  common.init();
  if (window.filter) {
    filter.init();
  }

  if (window.topDetalizer) {
    topDetalizer.crossBox();
  }

  if (window.filterSequencer) {
    filterSequencer.checkUserType($('#group_of_user'));
  }

  var nowTemp = new Date();

  if ($('*').is('.datetimepicker')) {
    $('.datetimepicker').datetimepicker({
      'dayOfWeekStart': 1,
      lang: 'ru',
      format: 'd.m.Y H:i:s'
    });
  }

  var defaultData = [
    {
      text: 'Parent 1',
      href: '#parent1',
      tags: ['4'],
      nodes: [
        {
          text: 'Child 1',
          href: '#child1',
          tags: ['2'],
          nodes: [
            {
              text: 'Grandchild 1',
              href: '#grandchild1',
              tags: ['0']
            },
            {
              text: 'Grandchild 2',
              href: '#grandchild2',
              tags: ['0']
            }
          ]
        },
        {
          text: 'Child 2',
          href: '#child2',
          tags: ['0']
        }
      ]
    },
    {
      text: 'Parent 2',
      href: '#parent2',
      tags: ['0']
    },
    {
      text: 'Parent 3',
      href: '#parent3',
      tags: ['0']
    },
    {
      text: 'Parent 4',
      href: '#parent4',
      tags: ['0']
    },
    {
      text: 'Parent 5',
      href: '#parent5',
      tags: ['0']
    }
  ];


  if ($('*').is('.datetimepicker')) {
    $('.treeview').treeview({
      //expandIcon: "glyphicon glyphicon-plus",
      //collapseIcon: "glyphicon glyphicon-minus",
      //    nodeIcon: "glyphicon glyphicon-user",
      color: "#428bca",
      //    backColor: "purple",
      //    onhoverColor: "orange",
      //    borderColor: "red",
      //    showBorder: false,
      showTags: true,
      //    highlightSelected: true,
      //selectedColor: "yellow",
      //selectedBackColor: "darkorange",
      data: defaultData
    });
  }

  if (typeof(conditionsUserProperty) !== "undefined") {
    conditionsUserProperty.init();
    $.datetimepicker.setLocale('ru');
    $('.datetimepicker').datetimepicker({
      'dayOfWeekStart': 1,
      lang: 'ru'
    });


    var dp_start = $('.dps').datepicker({
      weekStart: 1,
      format: 'dd.mm.yyyy'
    }).on('changeDate', function (ev) {
      dp_start.hide();
    }).data('datepicker');
    var dp_end = $('.dpe').datepicker({
      weekStart: 1,
      format: 'dd.mm.yyyy'
    }).on('changeDate', function (ev) {
      dp_end.hide();
    }).data('datepicker');
  }

  if (typeof(conditionsUserProperty) !== "undefined") {
    conditionsUserProperty.init();
  }

  if ($('*').is('#dp1')) {
    var checkin = $('#dp1').datepicker({
      weekStart: 1,
      format: 'dd.mm.yyyy'
    }).on('changeDate', function (ev) {
      if (ev.date.valueOf() > checkout.date.valueOf()) {
        var newDate = new Date(ev.date);
        newDate.setDate(newDate.getDate() + 1);
        checkout.setValue(newDate);
      }
      checkin.hide();
      $('#dp2')[0].focus();
    }).data('datepicker');
  }
  if ($('*').is('#dp2')) {
    var checkout = $('#dp2').datepicker({
      weekStart: 1,
      format: 'dd.mm.yyyy'
    }).on('changeDate', function (ev) {
      checkout.hide();
    }).data('datepicker');
  }
});


var banner = {
  obj: $('#field_content'),
  changeType: function (el) {
    var v = el.val();
    switch (v) {
      case '2':
        banner.setText();
        break;
      case '1':
        banner.setFile();
        break;
    }
  },
  setText: function (value) {
    var v = value || '';
    var html = '<textarea class="form-control ci-input" name="content" placeholder="Содержимое рекламы">' + v + '</textarea>';
    this.obj.html(html);
  },
  setFile: function (value) {
    var v = value || '';
    var html = '<a class="file-input-wrapper btn btn-default "><input name="content" type="file"></a>';
    // выводим картинку баннера
    if(v) {
      html += '<div class="image_block_banner">' +
        '<img width="200" height="auto" src="/ad/' + v + '" />' +
        '</div>';
    }
    this.obj.html(html);
  }
};

$('#filed_content_type').on('change', function () {
  banner.changeType($(this));
});
