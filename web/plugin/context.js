/**
 * Основной объект для работы с рекламой.
 * Алгоритм работы:
 * 1) инициализация кода происходит так:
 * <script id="mstat_9847fdb7973f01f2c07714ccb516a123">
 *  (function (d,b,c,o) {
 *       b[c] = b[c] || function () {
 *           (b[c].queue = b[c].queue || []).push(arguments);
 *         };
 *       b[c].p = b[c].p || 1;
 *       if(1 === b[c].p) {
 *         var a = d.createElement("script");
 *         a.src = o;
 *         a.async = 1;
 *         var m = d.getElementsByTagName('script')[0];
 *         m.parentNode.insertBefore(a, m);
 *       }
 *       b[c].p++;
 *     })(document, window, 'mstatContext', '//stat.mgts.zionec.ru/plugin/context.js');
 * mstatContext(2, 30, 'mstat_9847fdb7973f01f2c07714ccb516a123');
 * </script>
 *
 * Id скрипта формируется автоматически в интерфейсе добавления рекламы и является обязательным
 * 1) инициализируется очередь через функцию mstatContext (mstatContext.queue)
 * 2) подгружаем скрипт работы с рекламой (этот скрипт)
 * 3) инициализуируем функцию из шага 1 с параметрами, где
 * 2 - id партнера
 * 30 - id рекламы
 * mstat_9847fdb7973f01f2c07714ccb516a123 - id скрипта (нужен для определния родителя (контейнера))
 *
 * Алгоритм работы скрипта рекламы:
 * 1) разгребаем очередь из шага 1 выше
 * 2) подключаем рекламный скрипт (для конкретной рекламы)
 * 3) подгружаем скрипт статистки основной (пиксель)
 * 4) исполняем рекламный скрипт с подгрузкой рекламы
 * @type {{errorload: boolean, maxCount: number, tid: number, scriptId: string, loadMstat: __mstatContext.loadMstat, addAd: __mstatContext.addAd, doMainJob: __mstatContext.doMainJob, init: __mstatContext.init}}
 * @private
 */
var __mstatContext = {
  /**
   * признак того, что не передан партнер и что загрузку нужно прервать
   */
  errorload: false,
  /**
   * максимальное кол-во попыток проверки загрузки скрипта пикселя
   */
  maxCount: 20,
  /**
   * идентификатор интервала.
   * При наличии нескольких скриптов рекламы на странице нужно останавливать интервалы.
   * Для этого храним каждый интервал в массиве и останавливаем все сразу
   */
  tid: [],
  /**
   * идентификатор скрипта, с которого вызывается реклама.
   * Храним в массиве, так как может быть вызвано несколько рекламных скриптов на станице
   */
  scriptId: [],
  /**
   * Хранит массив функций, которые необходимо вызвать как колбэки для исполнения рекламы.
   */
  params: [],
  /**
   * Кол-во попыток загрузки основного пикселя
   */
  count: 0,
  /**
   * Скрипт загрузки пикселя.
   *
   * @param partner
   */
  loadMstat: function (partner) {
    "use strict";
    // если не передан партнер, то работу нужно останавливать
    if (!partner) {
      __mstatContext.errorload = true;
    }
    // если не инициализирован счетчик сновной, то мы его инициализируем
    if (!window.mgtsstat) {
      var a = document.createElement("script");
      a.src = '<!--pixel-->//stat.mgts.zionec.ru/stat.js<!--pixel-->';
      a.async = 1;
      var m = document.getElementsByTagName('script')[0];
      m.parentNode.insertBefore(a, m);
      window.mgtsstat = {pin: partner};
      window.mstat = window.mstat || function () {
          (window.mstat.q = window.mstat.q || []).push(arguments);
        };
    }
  },
  /**
   * Основная функция загрузки рекламы
   */
  addAd: function () {
    "use strict";
    // вызываем нужные функции
    for (var i = 0; i < __mstatContext.params.length; i++) {
      eval(__mstatContext.params[i])();
    }

  },
  /**
   * основная работа по загрузки скрипта рекламы
   */
  doMainJob: function () {
    "use strict";
    if (window.stat) {
      // сбрасываем все интервалы
      for (var i =0; i < __mstatContext.tid.length; i++) {
        clearInterval(__mstatContext.tid[i]);
      }
      __mstatContext.addAd();
      return false;
    }
    __mstatContext.count++;
    if (__mstatContext.count > __mstatContext.maxCount) {
      for (var i =0; i < __mstatContext.tid.length; i++) {
        clearInterval(__mstatContext.tid[i]);
      }
    }
  },
  /**
   * Инициализация.
   * Разгребание очереди и подгрузка рекламы
   */
  init: function () {
    "use strict";
    // если есть объект, то забираем с него данные
    if (mstatContext.queue && Object.prototype.toString.call(mstatContext.queue) === '[object Array]') {
      for (var a = mstatContext.queue || [], b = 0; b < a.length; b++) {
        // если реклама заполнена правильно
        if (a[b] && a[b][0] && a[b][1]) {
          // запоминаем параметры для вызова
          __mstatContext.params.push('__mstatContext.createDivWithBanner' + a[b][1]);
          // запоминаем id скрипта
          __mstatContext.scriptId[a[b][1]] = a[b][2];
          // погружаем массив параметров
          var r = document.createElement("script"),
            p = mstatContext.queue[b][1];
          r.src = '//<!--plugin_url-->stat.mgts.zionec.ru<!--plugin_url-->/plugin/' + p + '.js';
          r.onload = function () {
            __mstatContext.loadMstat(a[0][0]);
            var tid = setInterval(__mstatContext.doMainJob, 50);
            __mstatContext.tid.push(tid);
          };
          r.async = 1;
          var m = document.getElementsByTagName('script')[0];
          m.parentNode.insertBefore(r, m);
        }
      }
    }
  }
};
(function () {
  "use strict";
  __mstatContext.init();
})(window);