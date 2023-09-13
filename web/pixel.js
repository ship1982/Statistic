// murmurHash3.js v2.1.2 |  http://github.com/karanlyons/murmurHash.js | MIT Licensed
(function (y, z) {
  function l(a, c) {
    return (a & 65535) * c + (((a >>> 16) * c & 65535) << 16)
  }

  function r(a, c) {
    return a << c | a >>> 32 - c
  }

  function x(a) {
    a = l(a ^ a >>> 16, 2246822507);
    a ^= a >>> 13;
    a = l(a, 3266489909);
    return a ^= a >>> 16
  }

  function v(a, c) {
    a = [a[0] >>> 16, a[0] & 65535, a[1] >>> 16, a[1] & 65535];
    c = [c[0] >>> 16, c[0] & 65535, c[1] >>> 16, c[1] & 65535];
    var b = [0, 0, 0, 0];
    b[3] += a[3] + c[3];
    b[2] += b[3] >>> 16;
    b[3] &= 65535;
    b[2] += a[2] + c[2];
    b[1] += b[2] >>> 16;
    b[2] &= 65535;
    b[1] += a[1] + c[1];
    b[0] += b[1] >>> 16;
    b[1] &= 65535;
    b[0] += a[0] + c[0];
    b[0] &= 65535;
    return [b[0] << 16 | b[1], b[2] << 16 | b[3]]
  }

  function u(a, c) {
    a = [a[0] >>> 16, a[0] & 65535, a[1] >>> 16, a[1] & 65535];
    c = [c[0] >>> 16, c[0] & 65535, c[1] >>> 16, c[1] & 65535];
    var b = [0, 0, 0, 0];
    b[3] += a[3] * c[3];
    b[2] += b[3] >>> 16;
    b[3] &= 65535;
    b[2] += a[2] * c[3];
    b[1] += b[2] >>> 16;
    b[2] &= 65535;
    b[2] += a[3] * c[2];
    b[1] += b[2] >>> 16;
    b[2] &= 65535;
    b[1] += a[1] * c[3];
    b[0] += b[1] >>> 16;
    b[1] &= 65535;
    b[1] += a[2] * c[2];
    b[0] += b[1] >>> 16;
    b[1] &= 65535;
    b[1] += a[3] * c[1];
    b[0] += b[1] >>> 16;
    b[1] &= 65535;
    b[0] += a[0] * c[3] + a[1] * c[2] + a[2] * c[1] + a[3] * c[0];
    b[0] &= 65535;
    return [b[0] << 16 | b[1], b[2] << 16 | b[3]]
  }

  function w(a, c) {
    c %= 64;
    if (32 === c) {
      return [a[1], a[0]];
    }
    if (32 > c) {
      return [a[0] << c | a[1] >>> 32 - c, a[1] << c | a[0] >>> 32 - c];
    }
    c -= 32;
    return [a[1] << c | a[0] >>> 32 - c, a[0] << c | a[1] >>> 32 - c]
  }

  function s(a, c) {
    c %= 64;
    return 0 === c ? a : 32 > c ? [a[0] << c | a[1] >>> 32 - c, a[1] << c] : [a[1] << c - 32, 0]
  }

  function p(a, c) {
    return [a[0] ^ c[0], a[1] ^ c[1]]
  }

  function A(a) {
    a = p(a, [0, a[0] >>> 1]);
    a = u(a, [4283543511, 3981806797]);
    a = p(a, [0, a[0] >>> 1]);
    a = u(a, [3301882366, 444984403]);
    return a = p(a, [0, a[0] >>> 1])
  }

  var t = {version: "2.1.2", x86: {}, x64: {}};
  t.x86.hash32 = function (a, c) {
    a = a || "";
    for (var b = a.length % 4, p = a.length - b, d = c || 0, e = 0, f = 0; f < p; f += 4)e = a.charCodeAt(f) & 255 | (a.charCodeAt(f + 1) & 255) << 8 | (a.charCodeAt(f + 2) & 255) << 16 | (a.charCodeAt(f + 3) & 255) << 24, e = l(e, 3432918353), e = r(e, 15), e = l(e, 461845907), d ^= e, d = r(d, 13), d = l(d, 5) + 3864292196;
    e = 0;
    switch (b) {
      case 3:
        e ^= (a.charCodeAt(f + 2) & 255) << 16;
      case 2:
        e ^= (a.charCodeAt(f + 1) & 255) << 8;
      case 1:
        e ^= a.charCodeAt(f) & 255, e = l(e, 3432918353), e = r(e, 15), e = l(e, 461845907), d ^= e
    }
    d ^= a.length;
    d = x(d);
    return d >>> 0
  };
  t.x86.hash128 = function (a, c) {
    a = a || "";
    c = c || 0;
    for (var b = a.length % 16, p = a.length - b, d = c, e = c, f = c, h = c, m = 0, n = 0, g = 0, q = 0, k = 0; k < p; k += 16)m = a.charCodeAt(k) & 255 | (a.charCodeAt(k + 1) & 255) << 8 | (a.charCodeAt(k + 2) & 255) << 16 | (a.charCodeAt(k + 3) & 255) << 24, n = a.charCodeAt(k + 4) & 255 | (a.charCodeAt(k + 5) & 255) << 8 | (a.charCodeAt(k + 6) & 255) << 16 | (a.charCodeAt(k + 7) & 255) << 24, g = a.charCodeAt(k + 8) & 255 | (a.charCodeAt(k + 9) & 255) << 8 | (a.charCodeAt(k + 10) & 255) << 16 | (a.charCodeAt(k + 11) & 255) << 24, q = a.charCodeAt(k + 12) & 255 | (a.charCodeAt(k + 13) & 255) << 8 | (a.charCodeAt(k + 14) & 255) << 16 | (a.charCodeAt(k + 15) & 255) << 24, m = l(m, 597399067), m = r(m, 15), m = l(m, 2869860233), d ^= m, d = r(d, 19), d += e, d = l(d, 5) + 1444728091, n = l(n, 2869860233), n = r(n, 16), n = l(n, 951274213), e ^= n, e = r(e, 17), e += f, e = l(e, 5) + 197830471, g = l(g, 951274213), g = r(g, 17), g = l(g, 2716044179), f ^= g, f = r(f, 15), f += h, f = l(f, 5) + 2530024501, q = l(q, 2716044179), q = r(q, 18), q = l(q, 597399067), h ^= q, h = r(h, 13), h += d, h = l(h, 5) + 850148119;
    q = g = n = m = 0;
    switch (b) {
      case 15:
        q ^= a.charCodeAt(k + 14) << 16;
      case 14:
        q ^= a.charCodeAt(k + 13) << 8;
      case 13:
        q ^= a.charCodeAt(k + 12), q = l(q, 2716044179), q = r(q, 18), q = l(q, 597399067), h ^= q;
      case 12:
        g ^= a.charCodeAt(k + 11) << 24;
      case 11:
        g ^= a.charCodeAt(k + 10) << 16;
      case 10:
        g ^= a.charCodeAt(k + 9) << 8;
      case 9:
        g ^= a.charCodeAt(k + 8), g = l(g, 951274213), g = r(g, 17), g = l(g, 2716044179), f ^= g;
      case 8:
        n ^= a.charCodeAt(k + 7) << 24;
      case 7:
        n ^= a.charCodeAt(k + 6) << 16;
      case 6:
        n ^= a.charCodeAt(k + 5) << 8;
      case 5:
        n ^= a.charCodeAt(k + 4), n = l(n, 2869860233), n = r(n, 16), n = l(n, 951274213), e ^= n;
      case 4:
        m ^= a.charCodeAt(k + 3) << 24;
      case 3:
        m ^= a.charCodeAt(k + 2) << 16;
      case 2:
        m ^= a.charCodeAt(k + 1) << 8;
      case 1:
        m ^= a.charCodeAt(k), m = l(m, 597399067), m = r(m, 15), m = l(m, 2869860233), d ^= m
    }
    d ^= a.length;
    e ^= a.length;
    f ^= a.length;
    h ^= a.length;
    d = d + e + f;
    d += h;
    e += d;
    f += d;
    h += d;
    d = x(d);
    e = x(e);
    f = x(f);
    h = x(h);
    d += e;
    d += f;
    d += h;
    e += d;
    f += d;
    h += d;
    return ("00000000" + (d >>> 0).toString(16)).slice(-8) + ("00000000" + (e >>> 0).toString(16)).slice(-8) + ("00000000" + (f >>> 0).toString(16)).slice(-8) + ("00000000" + (h >>> 0).toString(16)).slice(-8)
  };
  t.x64.hash128 = function (a, c) {
    a = a || "";
    c = c || 0;
    for (var b = a.length % 16, l = a.length - b, d = [0, c], e = [0, c], f = [0, 0], h = [0, 0], m = [2277735313, 289559509], n = [1291169091, 658871167], g = 0; g < l; g += 16)f = [a.charCodeAt(g + 4) & 255 | (a.charCodeAt(g + 5) & 255) << 8 | (a.charCodeAt(g + 6) & 255) << 16 | (a.charCodeAt(g + 7) & 255) << 24, a.charCodeAt(g) & 255 | (a.charCodeAt(g + 1) & 255) << 8 | (a.charCodeAt(g + 2) & 255) << 16 | (a.charCodeAt(g + 3) & 255) << 24], h = [a.charCodeAt(g + 12) & 255 | (a.charCodeAt(g + 13) & 255) << 8 | (a.charCodeAt(g + 14) & 255) << 16 | (a.charCodeAt(g + 15) & 255) << 24, a.charCodeAt(g + 8) & 255 | (a.charCodeAt(g + 9) & 255) << 8 | (a.charCodeAt(g + 10) & 255) << 16 | (a.charCodeAt(g + 11) & 255) << 24], f = u(f, m), f = w(f, 31), f = u(f, n), d = p(d, f), d = w(d, 27), d = v(d, e), d = v(u(d, [0, 5]), [0, 1390208809]), h = u(h, n), h = w(h, 33), h = u(h, m), e = p(e, h), e = w(e, 31), e = v(e, d), e = v(u(e, [0, 5]), [0, 944331445]);
    f = [0, 0];
    h = [0, 0];
    switch (b) {
      case 15:
        h = p(h, s([0, a.charCodeAt(g + 14)], 48));
      case 14:
        h = p(h, s([0, a.charCodeAt(g + 13)], 40));
      case 13:
        h = p(h, s([0, a.charCodeAt(g + 12)], 32));
      case 12:
        h = p(h, s([0, a.charCodeAt(g + 11)], 24));
      case 11:
        h = p(h, s([0, a.charCodeAt(g + 10)], 16));
      case 10:
        h = p(h, s([0, a.charCodeAt(g + 9)], 8));
      case 9:
        h = p(h, [0, a.charCodeAt(g + 8)]), h = u(h, n), h = w(h, 33), h = u(h, m), e = p(e, h);
      case 8:
        f = p(f, s([0, a.charCodeAt(g + 7)], 56));
      case 7:
        f = p(f, s([0, a.charCodeAt(g + 6)], 48));
      case 6:
        f = p(f, s([0, a.charCodeAt(g + 5)], 40));
      case 5:
        f = p(f, s([0, a.charCodeAt(g + 4)], 32));
      case 4:
        f = p(f, s([0, a.charCodeAt(g + 3)], 24));
      case 3:
        f = p(f, s([0, a.charCodeAt(g + 2)], 16));
      case 2:
        f = p(f, s([0, a.charCodeAt(g + 1)], 8));
      case 1:
        f = p(f, [0, a.charCodeAt(g)]), f = u(f, m), f = w(f, 31), f = u(f, n), d = p(d, f)
    }
    d = p(d, [0, a.length]);
    e = p(e, [0, a.length]);
    d = v(d, e);
    e = v(e, d);
    d = A(d);
    e = A(e);
    d = v(d, e);
    e = v(e, d);
    return ("00000000" + (d[0] >>> 0).toString(16)).slice(-8) + ("00000000" + (d[1] >>> 0).toString(16)).slice(-8) + ("00000000" + (e[0] >>> 0).toString(16)).slice(-8) + ("00000000" + (e[1] >>> 0).toString(16)).slice(-8)
  };
  "undefined" !== typeof exports ? ("undefined" !== typeof module && module.exports && (exports = module.exports = t), exports.murmurHash3 = t) : "function" === typeof define && define.amd ? define([], function () {
    return t
  }) : (t._murmurHash3 = y.murmurHash3, t.noConflict = function () {
    y.murmurHash3 = t._murmurHash3;
    t._murmurHash3 = z;
    t.noConflict = z;
    return t
  }, y.murmurHash3 = t)
})(this);

(function () {
  /**
   * Проверяет, число ли функция или нет.
   *
   * @param n
   * @returns {boolean}
   */
  function isNumeric(n) {
    return !isNaN(parseFloat(n)) && isFinite(n);
  }

  /**
   * implements console.log()
   * @param msg - string or object for console.log
   */
  function debug(msg) {
    if (typeof console === "undefined" || typeof console.log === "undefined") {
      return false;
    }
    console.log(msg);
  }

  /**
   * Check object for empty
   * @param o - object for checking
   */
  function isEmpty(o) {
    if (Object.prototype.toString.call(o) !== '[object Object]') {
      return false;
    }
    for (var i in o) {
      if (o.hasOwnProperty(i)) {
        return false;
      }
    }
    return true;
  }

  /**
   * Check function or not
   * @param obj o - object for checking
   */
  function isFunction(obj) {
    return Object.prototype.toString.call(obj) === '[object Function]';
  }

  /**
   * Расширяет объект другим объектом.

   * @return obj
   */
  function extend() {
    var a = arguments, target = a[0] || {}, i = 1, l = a.length, deep = false, options;

    if (typeof target === 'boolean') {
      deep = target;
      target = a[1] || {};
      i = 2;
    }

    if (typeof target !== 'object' && !isFunction(target)) {
      target = {};
    }

    for (; i < l; ++i) {
      if ((options = a[i]) !== null) {
        for (var name in options) {
          var src = target[name], copy = options[name];

          if (target === copy) {
            continue;
          }

          if (deep && copy && typeof copy === 'object' && !copy.nodeType) {
            target[name] = extend(deep, src || (copy.length != null ? [] : {}), copy);
          } else if (copy !== undefined) {
            target[name] = copy;
          }
        }
      }
    }

    return target;
  }

  /**
   * Получает элемент по его id
   *
   * @param el - id элемента
   */
  function ge(el) {
    return (typeof el === 'string' || typeof el === 'number') ? document.getElementById(el) : el;
  }

  /**
   * Получаем элемент по тэгу
   */
  function geByTag(searchTag, node) {
    node = ge(node) || document;
    return node.getElementsByTagName(searchTag);
  }

  /**
   * Определяет положение элемента.
   *
   * @param elem
   * @returns {{top: number, left: number}}
   */
  function getOffsetRect(elem) {
    // (1)
    var box = elem.getBoundingClientRect();

    var body = document.body;
    var docEl = document.documentElement;

    // (2)
    var scrollTop = window.pageYOffset || docEl.scrollTop || body.scrollTop;
    var scrollLeft = window.pageXOffset || docEl.scrollLeft || body.scrollLeft;

    // (3)
    var clientTop = docEl.clientTop || body.clientTop || 0;
    var clientLeft = docEl.clientLeft || body.clientLeft || 0;

    // (4)
    var top = box.top + scrollTop - clientTop;
    var left = box.left + scrollLeft - clientLeft;

    return {
      top: top,
      left: left
    };
  }

  /**
   * Добавляет событие на элемент.
   *
   * @param event
   * @param el
   * @param func
   */
  function addEvent(event, el, func) {
    var param = event.split(','), i = 0, e = 0;
    if (!el) {
      return;
    }
    if (el.addEventListener) {
      el.addEventListener(event, func, false);
    } else if (el.attachEvent) {
      el.attachEvent('on' + event, func);
    }
  }

  /**
   * Сделаем отдельный класс,
   * который будет проверять состояние adBlock,
   * и при этом будет инкапуслирован.
   * @param {string} id_div_pixel_load - идентификатор дива,
   * в который можно загружать элемент изображения
   * @param {string} id_img_pixel_load - идентификатор элемента img,
   * которое будет присвоено при загрузке изображения
   * @param {string} callback_onload_name - название функции, которую следует выполнить при успешной загрузке изображения
   * @param {string} callback_onerror_name - название функции, которую следует выполнить при неудачной загрузке изображения
   * @returns {testAdblock}
   */
  function TestAdblockClass() {
    this.id_div_pixel_load = 'id_div_pixel_load';
    this.id_img_pixel_load = 'id_img_pixel_load';
    this.id_img_src = '<!--protocol-->http<!--protocol-->://<!--plugin_url-->stat.mgts.zionec.ru<!--plugin_url-->/ads.png?rnd=' + Math.random();
    /**
     * Загружает изображение, в div элемент.
     */
    this.loadImg = function () {
      var div_pixel_load = document.getElementById(this.id_div_pixel_load) || null;
      //Если не удалось найти div, для загрузки изображения, то создадим его в теле документа
      if (div_pixel_load === null) {
        div_pixel_load = document.createElement('div');
        div_pixel_load.setAttribute('id', this.id_div_pixel_load);
        document.body.appendChild(div_pixel_load);
      }
      //Очистим блок
      div_pixel_load.innerHTML = '';
      //Вставим изображение
      var img_pixel_load = document.createElement('img');
      img_pixel_load.setAttribute('id', this.id_img_pixel_load);
      img_pixel_load.setAttribute('style', 'display: none');
      img_pixel_load.setAttribute('src', this.id_img_src);
      div_pixel_load.appendChild(img_pixel_load);
      addEvent('load', img_pixel_load, function () {
        stat.init(false);
      });
      addEvent('error', img_pixel_load, function () {
        stat.init(true);
      });
    };
  }

  /**
   * Основной объект, отвечающий за события.
   * Поддерживает их запоминание в очереди
   * Отправляет все события в обычный лог, только помечаются как события.
   */
  var sender = {
    /**
     * счетчик событий со страницы
     */
    c: 0,
    /**
     * идентификатор метода для ститистики
     */
    m: 'send',
    /**
     * массив дополнительно собираемых данных
     */
    q: {},
    /**
     * количество элементов для показа
     */
    b: 0,
    /**
     * строка для запроса
     */
    d: '',
    /**
     * список, по которому будут учитываться показы элементов списка.
     */
    s: [],
    /**
     * получем метатеги title, description, keywords
     */
    s1: function () {
      var metas = document.getElementsByTagName('meta'), a = ['title', 'description', 'keywords'];
      for (var i = 0; i < metas.length; i++) {
        if (!isEmpty(a)) {
          for (var j = a.length - 1; j >= 0; j--) {
            if (metas[i].getAttribute("name") == a[j]) {
              if (!sender.q.meta) {
                sender.q.meta = {};
              }
              if (!sender.q.meta.hasOwnProperty(a[j])) {
                sender.q.meta[a[j]] = '';
              }
              sender.q.meta[a[j]] = metas[i].getAttribute("content");
            }
          }
        }
      }
    },
    /**
     * запускает все дополнительные функции по сбору статистики
     */
    s2: function () {
      sender.s1();
      sender.s5();
      sender.s10();
      sender.s7();
      sender.s9();
    },
    /**
     * кодируем объект в url
     */
    s6: function (obj, prefix) {
      var str = [], p;
      for (p in obj) {
        if (obj.hasOwnProperty(p)) {
          var k = prefix ? prefix + "[" + p + "]" : p, v = obj[p];
          str.push((v !== null && typeof v === "object") ?
            sender.s6(v, k) :
            encodeURIComponent(k) + "=" + encodeURIComponent(v));
        }
      }
      return str.join(";");
    },
    /**
     * кодируем для url параметры массива
     */
    s3: function () {
      if (isEmpty(sender.q)) {
        return '';
      }
      var a = sender.s6(sender.q);
      return stat.a5(a);
    },
    /**
     * запоминаем uid пользователя
     */
    s5: function () {
      stat.a9();
      extend(sender.q, {'uid': stat.uid});
    },
    /**
     * использует картинку для отправки статистики
     */
    s4: function () {
      sender.c++;
      var i = new Image;
      i.src = sender.s11();
      i.style.display = 'none';
      sender.q = {};
      var coord = document.getElementById('mgtsstat');
      if (coord) {
        coord.insertBefore(i, null);
      } else {
        document.body.appendChild(i);
      }
    },
    /**
     * расширеет объект очереди идентификатором партнера
     */
    s7: function () {
      if (window.mgtsstat.hasOwnProperty('pin')) {
        extend(sender.q, {pin: window.mgtsstat.pin});
      }
      extend(sender.q, {ad: ((stat.ad) ? 1 : 0)});
    },
    /**
     * Запоминаем реферера.
     */
    s9: function () {
      extend(sender.q, {referrer: document.referrer});
    },
    /**
     * Возвращает строку для запроса пикселя.
     *
     * @returns {string}
     */
    s11: function () {
      return '<!--protocol-->http<!--protocol-->://<!--gif_url-->stat.mgts.zionec.ru/pixel.gif<!--gif_url-->?_c=' + sender.c + '&_r=' + Math.random() + '&_t=' + sender.m + '&_mstats=' + stat._s + '&data=' + sender.s3();
    },
    /**
     * Отправляет событие при его возникновении со всеми необходимыми данными.
     */
    send: function (a) {
      sender.s2();
      if (!isEmpty(sender.q)) {
        extend(sender.q, a);
      }
      sender.s4();
    },
    /**
     * Делает тоже самое, что и send, только не отправляет событие, а возвращает для его отпарвки строку.
     *
     * @param a
     */
    debug: function (a) {
      sender.s2();
      if (!isEmpty(sender.q)) {
        extend(sender.q, a);
      }
      sender.d = sender.s11();
      return sender.sd();
    },
    /**
     * Выводит сохраненную переменную.
     *
     * @returns {*}
     */
    sd: function () {
      return sender.d;
    },
    /**
     * отслеживание CTR
     */
    s8: function (list) {
      // добавляем элементы в очередь
      for (var i = list.length - 1; i >= 0; i--) {
        sender.s.push(list[i]);
      }
      sender.b += list.length; // увеличиваем количество элементов в очереди
      // выполнять после загрузки страницы
      window.onscroll = function () {
        var scrollTop = window.pageYOffset || document.scrollTop,
          windowHeight = window.innerHeight || document.clientHeight;

        // приведение типов
        if (undefined === scrollTop) {
          scrollTop = 0;
        }

        // цикл по объектам
        for (var i = sender.b - 1; i >= 0; i--) {
          // цикл по элементам
          if (sender.b > 0) {
            for (var o in sender.s[i]) {
              // если передали ссылку
              var el = {};
              if ('#' === o[0]) {
                var a = geByTag('a'),
                  tag = o.substring(1); // искомая ссылка
                for (var p = 0; p < a.length; p++) {
                  if (a[p].href.indexOf(tag) !== -1) {
                    el = a[p];
                  }
                }
              } else {
                el = ge(o);
              }
              var heigh = el.clientHeight || 1,
                box = {top: 0}; // начальный отступ

              if (typeof el.getBoundingClientRect !== "undefined") {
                box = getOffsetRect(el);
              }
              // отступ сверху
              var top = box.top;
              // если элемент в видимой области, то отправляем запрос
              if (scrollTop <= top && (heigh + top) < (scrollTop + windowHeight)) {
                sender.s2();
                extend(sender.q, sender.s[i][o]);
                sender.s4();
                // если показали элемент, то он нам более не нужен
                sender.b--;
              }
            }
          }
        }
      };
    },
    /** ссылка **/
    s10: function () {
      extend(sender.q, {link: window.location.href});
    }
  };

  /**
   * Main object for collection statistic
   */
  var stat = {
    /**
     * this object extended another statistic information.
     * This object must be fill with scpeshial function.
     * For example, if you want bring a login from login form,
     * you must made a function, which recieve needed data and
     * final method of this function must be a extend(stat.b, r),
     * where r - is object, which contain a needed information (key, value)
     */
    b: {},
    /**
     * contain last method from pixel.
     */
    t: '',
    /**
     * if value > 0, then this is a new session
     */
    _s: 0,
    /**
     * user id. This property filled by a9()
     */
    uid: undefined,
    /**
     * @param {boolean} ad - Состояние Адблока
     */
    ad: undefined,
    q: [],
    /**
     * link for plugins.
     */
    link: '<!--protocol-->http<!--protocol-->://<!--plugin_url-->stat.mgts.zionec.ru<!--plugin_url-->/plugin/',
    /**
     * Create a pixel image, and add GET parameter data with it.
     * This pixel added on the end of body element
     */
    a1: function () {
      debug('insert...');
      stat.c++;
      var i = new Image;
      i.src = '<!--protocol-->http<!--protocol-->://<!--gif_url-->stat.mgts.zionec.ru/pixel.gif<!--gif_url-->?_c=' + stat.c + '&_r=' + Math.random() + '&_t=' + stat.t + '&_mstats=' + stat._s + '&data=' + stat.a3();
      i.style.display = 'none';
      stat.t = '';
      var coord = document.getElementById('mgtsstat');
      if (coord) {
        coord.insertBefore(i, null);
      } else {
        document.body.appendChild(i);
      }
    },
    /**
     * Test function, which must be add in a4()
     */
    a2: function () {
      debug('a2...');
      extend(stat.b, {link: window.location.href});
    },
    /**
     * prepare stat.b for a url encoded view
     */
    a3: function () {
      debug('prepare...');
      if (isEmpty(stat.b)) {
        return '';
      }
      var a = '';
      for (var i in stat.b) {
        a += i + '=' + encodeURIComponent(stat.b[i]) + ';';
      }
      return stat.a5(a);
    },
    /**
     * do a base decoded string
     */
    a5: function (a) {
      if (isEmpty(a)) {
        return '';
      }
      return b64.encode(a);
    },
    /**
     * Contain all function, which give a statistic information.
     * All function, which get a statistics must be added there.
     */
    a4: function () {
      debug('collect...');
      stat.a2();
      stat.a10();
      stat.a18();
      stat.a11();
      stat.a12();
      stat.a19();
    },
    /**
     *
     * @constructor
     */
    init: function (st_ad_block) {
      stat.ad = st_ad_block;
      if (window.mgtsstat && window.mgtsstat.hasOwnProperty("pin")) {
        var a = window.mstat || [];
        stat.c = 0;
        stat.set = function (a) {
          if (a && !isFunction(a)) {
            for (var i = a.length - 1; i >= 0; i--) {
              void 0 !== a && stat.q.push(a[i]);
            }
          }
        };
        stat.set(a.q);
        stat.a16();
        stat.a13();
        stat.a9();
        stat.a4();
        // переопределяем функцию
        window.mstat = function () {
          if (undefined !== arguments) {
            stat.q.push(arguments);
          }
          return stat.a14();
        };
        window.mstat();
        stat.a1();
      }
    },
    /**
     * generate a uid
     */
    a6: function () {
      stat.uid = murmurHash3.x64.hash128(stat.a17() + Math.random());
    },
    /**
     * get cookie
     * @param name - name of a cookie
     * @return undefined|string
     */
    a7: function (name) {
      var matches = document.cookie.match(new RegExp("(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"));
      return matches ? decodeURIComponent(matches[1]) : undefined;
    },
    /**
     * set cookie
     * @param name - name of a cookie
     * @param value - value of the cookie
     * @param options - contain:
     * domain - this is a domain for a cookie
     * path - contain a path for a cookie
     * secure - If true, then send the cookie only on secure connection.
     */
    a8: function (name, value, options) {
      options = options || {};
      var __expires = options.expires || 3600 * 24 * 365 * 10;
      if (typeof __expires === "number" && __expires) {
        var expires = new Date();
        expires.setMilliseconds(expires.getMilliseconds() + __expires * 1000);
        options.expires = expires;
      }
      if (__expires) {
        options.expires = options.expires.toUTCString();
      }
      value = encodeURIComponent(value);
      var updatedCookie = name + "=" + value;
      for (var propName in options) {
        updatedCookie += "; " + propName;
        var propValue = options[propName];
        if (propValue !== true) {
          updatedCookie += "=" + propValue;
        }
      }
      document.cookie = updatedCookie;
    },
    /**
     * check cookie and set it, if not.
     * If cookie value is numeric (old uid),
     * then save it and replace on new id.
     */
    a9: function () {
      debug('identification...');
      stat.uid = stat.a7('uid');
      if (isNumeric(stat.uid)) {
        extend(stat.b, {ouid: stat.uid});
        stat.a6();
        stat.a8('uid', stat.uid, {
          path: '/',
          domain: window.location.hostname,
        });
      }
      if (!stat.uid) {
        stat.a6();
        debug('identification execute...');
        stat.a8('uid', stat.uid, {
          path: '/',
          domain: window.location.hostname
        });
      }
      extend(stat.b, {uid: stat.uid});
    },
    /**
     * add in stat.b a query string
     */
    a10: function () {
      debug('query params...');
      var a = window.location.search.substring(1),
        b = a ? a.split('&') : {},
        r = {};
      if (isEmpty(b)) {
        return;
      }
      for (var i = 0; i < b.length; i++) {
        var p = b[i].split('=');
        r[p[0]] = p[1];
      }
      extend(stat.b, r);
    },
    /**
     * add in stat.b a referrer
     */
    a11: function () {
      debug('referrer get...');
      extend(stat.b, {referrer: document.referrer});
    },
    /**
     * set a first referrer
     */
    a12: function () {
      var a = stat.a7('referrer');
      if (!a) {
        debug('save referrer...');
        stat.a8('referrer', document.referrer, {path: '/', domain: window.location.hostname, expires: 86400});
        extend(stat.b, {start_referrer: document.referrer});
      }
    },
    /**
     * first referrer stat
     */
    a13: function () {
      if (window.mgtsstat.hasOwnProperty('pin')) {
        extend(stat.b, {pin: window.mgtsstat.pin});
      }
      extend(stat.b, {ad: ((stat.ad) ? 1 : 0)});
    },
    /**
     * Choose plugins and do actions for it.
     */
    a14: function () {
      if (isEmpty(stat.q) || stat.q[0] === undefined) {
        return;
      }
      var q = [], ic = 0, i = 0;
      for (var a = stat.q || [], b = 0; 1 <= a.length; b++) {
        switch (a[0][0]) {
          case "require":
            stat.t = a[0][0];
            stat.a15(a[0][1]);
            stat.t = '';
            break;
          case 'send':
            ic = a[0].length;
            for (; ic > i; i++) {
              q.push(a[0][i]);
            }
            new sender.send(q);
            break;
          case 'debug':
            ic = a[0].length;
            for (; ic > i; i++) {
              q.push(a[0][i]);
            }
            return sender.debug(q);
            break;
          case 'show':
            ic = a[0].length;
            for (; ic > i; i++) {
              q.push(a[0][i]);
            }
            new sender.s8(q);
            break;
        }
        // убираем из очереди данные
        stat.q.shift();
      }
    },
    /**
     * Create js in page on header.
     * @param name - name of plugin
     */
    a15: function (name) {
      var a = document.createElement("script");
      a.src = stat.link + name + '.js';
      a.async = 1;
      var m = document.getElementsByTagName('script')[0];
      m.parentNode.insertBefore(a, m);
    },
    /**
     * Start session and send variable stat._s with random string.
     * If mgtso is, then session is start, else start a new session id.
     */
    a16: function () {
      var o = stat.a7('mgtso');
      if (o) {
        stat._s = o;
      } else {
        stat._s = Math.round((new Date).getTime() / 1E3);
        stat._s = stat._s + "" + stat.a17() + "" + stat.a17();
      }
      stat.a8('mgtso', stat._s, {
        path: '/',
        domain: window.location.hostname,
        expires: 1800
      });
    },
    /**
     * Generate random number
     * @param a
     * @param b
     * @returns {*}
     */
    a17: function (a, b) {
      2 > arguments.length && (b = a, a = 0);
      1 > arguments.length && (b = 1073741824);
      return Math.floor(Math.random() * (b - a)) + a
    },
    /**
     * Get user prop
     */
    a18: function () {
      var sr = screen.width + 'x' + screen.height;
      extend(stat.b, {sr: sr});
    },
    /**
     * Получение шага пользователя или его установка.
     */
    a19: function () {
      var o = stat.a7('_st');
      if (isNumeric(o)) {
        o++;
      } else {
        o = 1;
      }
      stat.a8('_st', o, {
        path: '/',
        domain: window.location.hostname,
        expires: (60 * 15)
      });
      extend(stat.b, {_st: o});
    },
    /**
     * Отправка разницы времени на сервер
     */
    a20: function () {
      var o = stat.a7('_d');
      if (!isNumeric(o)) {
        o++;
      } else {
        o = 1;
      }
      stat.a8('_d', o, {
        path: '/',
        domain: window.location.hostname,
        expires: (60 * 15)
      });
    }
  };

  /**
   * base64 encode|decode analog in php.
   *
   * @type {{_keyStr: string, encode: encode, decode: decode, _utf8_encode: _utf8_encode, _utf8_decode: _utf8_decode}}
   */
  var b64 = {
    _keyStr: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
    encode: function (input) {
      var output = "";
      var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
      var i = 0;
      input = b64._utf8_encode(input);
      while (i < input.length) {
        chr1 = input.charCodeAt(i++);
        chr2 = input.charCodeAt(i++);
        chr3 = input.charCodeAt(i++);
        enc1 = chr1 >> 2;
        enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
        enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
        enc4 = chr3 & 63;
        if (isNaN(chr2)) {
          enc3 = enc4 = 64;
        } else if (isNaN(chr3)) {
          enc4 = 64;
        }
        output = output +
          b64._keyStr.charAt(enc1) + b64._keyStr.charAt(enc2) +
          b64._keyStr.charAt(enc3) + b64._keyStr.charAt(enc4);
      }
      return output;
    },
    decode: function (input) {
      var output = "";
      var chr1, chr2, chr3;
      var enc1, enc2, enc3, enc4;
      var i = 0;
      input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
      while (i < input.length) {
        enc1 = b64._keyStr.indexOf(input.charAt(i++));
        enc2 = b64._keyStr.indexOf(input.charAt(i++));
        enc3 = b64._keyStr.indexOf(input.charAt(i++));
        enc4 = b64._keyStr.indexOf(input.charAt(i++));

        chr1 = (enc1 << 2) | (enc2 >> 4);
        chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
        chr3 = ((enc3 & 3) << 6) | enc4;

        output = output + String.fromCharCode(chr1);

        if (enc3 != 64) {
          output = output + String.fromCharCode(chr2);
        }
        if (enc4 != 64) {
          output = output + String.fromCharCode(chr3);
        }
      }
      output = b64._utf8_decode(output);
      return output;
    },
    _utf8_encode: function (string) {
      string = string.replace(/\r\n/g, "\n");
      var utftext = "";
      for (var n = 0; n < string.length; n++) {
        var c = string.charCodeAt(n);
        if (c < 128) {
          utftext += String.fromCharCode(c);
        } else if ((c > 127) && (c < 2048)) {
          utftext += String.fromCharCode((c >> 6) | 192);
          utftext += String.fromCharCode((c & 63) | 128);
        } else {
          utftext += String.fromCharCode((c >> 12) | 224);
          utftext += String.fromCharCode(((c >> 6) & 63) | 128);
          utftext += String.fromCharCode((c & 63) | 128);
        }
      }
      return utftext;
    },
    _utf8_decode: function (utftext) {
      var string = "";
      var i = 0;
      var c = c1 = c2 = 0;
      while (i < utftext.length) {
        c = utftext.charCodeAt(i);
        if (c < 128) {
          string += String.fromCharCode(c);
          i++;
        } else if ((c > 191) && (c < 224)) {
          c2 = utftext.charCodeAt(i + 1);
          string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
          i += 2;
        } else {
          c2 = utftext.charCodeAt(i + 1);
          c3 = utftext.charCodeAt(i + 2);
          string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
          i += 3;
        }
      }
      return string;
    }
  };

  /** init */
  debug('statistic script load...');
  var mgtsStat = new TestAdblockClass();
  mgtsStat.loadImg();
})(window);