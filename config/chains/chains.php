<?php

return [
    'chains' => [
      'partner' =>  [
        //Идентификатор партнёра
        'number' => 1
      ],
      'utm_labels' => [
        'utm_campaign' => 'utm_compaign',
        'utm_content' => 'utm_content',
        'utm_term' => 'utm_term',
        'utm_medium' => 'utm_medium',
        'utm_source' => 'utm_source',
        'referer_link' => 'Реферер'
      ],
      //События на определённых страницах
      'events_on_certain_pages' => [
        'home' => [
          'checkbox_internet' => 'Чекбокс Интернет',
          'checkbox_tv' => 'Чекбокс Телевидение',
          'checkbox_telephone' => 'Чекбокс Телефон',
          'checkbox_mobile' => 'Чекбокс Мобильная Связь',
          'checkbox_serv_v' => 'Чекбокс Видеонаблюдение',
          'checkbox_serv_s' => 'Чекбокс Охрана',
          'send_order' => 'Конпка Отправить'
        ],
        'home/bundles' => [
          'checkbox_internet' => 'Чекбокс Интернет',
          'checkbox_tv' => 'Чекбокс Телевидение',
          'checkbox_telephone' => 'Чекбокс Телефон',
          'checkbox_mobile' => 'Чекбокс Мобильная Связь',
          'checkbox_serv_v' => 'Чекбокс Видеонаблюдение',
          'checkbox_serv_s' => 'Чекбокс Охрана',
          'slider_internet' => 'Слайдер Интернет',
          'slider_tv' => 'Слайдер Телевидение',
          'slider_mobile' => 'Слайдер мобильная связь',
          'send_order' => 'Конпка Отправить'
        ]
      ],
      //Изменение элементов на страницах
      'change_elements_from_pages' => [
        'home' => [
          'internet' => 'Чекбокс Интернет',
          'tv' => 'Чекбокс Телевидение',
          'telephone' => 'Чекбокс Телефон',
          'mobile' => 'Чекбокс Мобильная Связь',
          'serv_v' => 'Чекбокс Видеонаблюдение',
          'serv_s' => 'Чекбокс Охрана'
        ],
        'home/bundles' => [
          'internet' => 'Чекбокс Интернет',
          'tv' => 'Чекбокс Телевидение',
          'telephone' => 'Чекбокс Телефон',
          'mobile' => 'Чекбокс Мобильная Связь',
          'serv_v' => 'Чекбокс Видеонаблюдение',
          'serv_s' => 'Чекбокс Охрана',
          'internet_rate_num' => 'Слайдер Интернет',
          'tv_rate_num' => 'Слайдер Телевидение',
          'mobile_rate_num' => 'Слайдер мобильная связь'
        ]
      ],
      //Общие события для всех страниц
      'events_on_all_pages' => [
        'view' => 'Просмотр страницы',
        'view2' => 'Просмотр страницы2'
      ],
      //Страницы
      'pages' => [
        'home' => 'Страница заявок',
        'home/bundles' => 'Страница пакетатора'
      ],
      //Список типов сравнения для условий
      'list_conditions_type' => [
        1 => 'Точно соответствует',
        2 => 'Содержит',
        3 => 'Начинается с',
        4 => 'Заканчивается на',
        5 => 'Соответствует регулярному выражению',
        6 => 'Является одним из',
        7 => 'Не является точным соответствием',
        8 => 'Не содержит'
      ],
      //Список типов сравнения для событий на страницах
      'list_conditions_event_for_pages' => [
        1 => 'Точно соответствует'
      ],
      //Поля для отслеживания услуг
      'fields_for_services' => [
        'internet',
        'tv',
        'telephone',
        'mobile',
        'serv_v',
        'serv_s'
      ],
      //Поля для отслеживания услуг расширенный вариант
      'fields_for_services_ext' => [
        'internet',
        'internet_rate_num',
        'internet_price',
        'internet_rate_name',
        'tv',
        'tv_rate_num',
        'tv_price',
        'tv_rate_name',
        'telephone',
        'mobile',
        'mobile_rate_num',
        'mobile_price',
        'mobile_rate_name',
        'serv_v',
        'serv_s'
      ],
      'fields_for_services_ext_tree' => [
        'internet' => [
          'bundle' => 'internet_rate_num',
          'price' => 'internet_price',
          'name' => 'internet_rate_name'
        ],
        'tv' => [
          'bundle' => 'tv_rate_num',
          'price' => 'tv_price',
          'name' => 'tv_rate_name'
        ],
        'telephone' => [],
        'mobile' => [
          'bundle' => 'mobile_rate_num',
          'price' => 'mobile_price',
          'name' => 'mobile_rate_name'
        ],
        'serv_v' => '',
        'serv_s' => ''
      ]
    ]
];