<?php

/**
 * @var string $layout - layuout for controller.
 */
$layout = 'statistic';

/**
 * Check user authorization.
 *
 * redirect on main or nothing
 */
function MainAuthUser()
{
    common_inc('auth');
    if(!auth_is())
        header('Location: /');
}

function show(){
  MainAuthUser();
  
  return common_setView(
		'chains/show',
    [
      'utm_labels' => common_getVariable($GLOBALS, [
        'conf',
        'chains',
        'utm_labels'
        ], []
      ),
      
      'events_on_certain_pages' => common_getVariable($GLOBALS, [
        'conf',
        'chains',
        'events_on_certain_pages'
        ], []
      ),
      
      'change_elements_from_pages' => common_getVariable($GLOBALS, [
        'conf',
        'chains',
        'change_elements_from_pages'
        ], []
      ),
      
      'events_on_all_pages' => common_getVariable($GLOBALS, [
        'conf',
        'chains',
        'events_on_all_pages'
        ], []
      ),
      
      'pages' => common_getVariable($GLOBALS, [
        'conf',
        'chains',
        'pages'
        ], []
      ),
      
      'list_conditions_type' => common_getVariable($GLOBALS, [
        'conf',
        'chains',
        'list_conditions_type'
        ], []
      ),
      
      'list_conditions_event_for_pages' => common_getVariable($GLOBALS, [
        'conf',
        'chains',
        'list_conditions_event_for_pages'
        ], []
      ),
      
      'fields_for_services' => common_getVariable($GLOBALS, [
        'conf',
        'chains',
        'fields_for_services'
        ], []
      ),
      
      'fields_for_services_ext' => common_getVariable($GLOBALS, [
        'conf',
        'chains',
        'fields_for_services_ext'
        ], []
      ),
     ]
	);
}

/**
 * Вовзращает массив с POST параметрами, со следующими ключами:
 * ['utm_conditions' => [],'page_conditions' => [], 'event_for_pages' => [], 'param_for_pages' => [],]
 * @return array
 */
function get_filter_params(){
  //Подготавливаем обработку POST данных
  $filters = [
    'utm_conditions' => ['flags' => FILTER_REQUIRE_ARRAY],
    'page_conditions' => ['flags' => FILTER_REQUIRE_ARRAY],
    'event_for_pages' => ['flags' => FILTER_REQUIRE_ARRAY],
    'param_for_pages' => ['flags' => FILTER_REQUIRE_ARRAY],
    'events_on_all_pages' => ['flags' => FILTER_REQUIRE_ARRAY]
  ];

  //Фильтруем массив параметров
  $arr_post = filter_input_array(INPUT_POST, $filters);
  
  //Если какой то массив пустой, то вместо [] устанавливается false, исправим это
  foreach($arr_post as $k => $v){
    $arr_post[$k] = ($v === false || $v == null)?[]:$v;
  }
  
  return $arr_post;
}

/**
 * Получает данные по отчётам, ивозвращает их в JSON формате
 */
function get_data(){
  MainAuthUser();
  
  common_inc('chains');
  $chains = new Chains();
  
  common_inc('timer');
  $timer = new timerPrint();
  $timer->start('start');
  $data_return = [];
  
  //Установим параметры
  $chains->setter_arr_params(get_filter_params());
  $chains->setter_partner_number(filter_input(INPUT_POST, 'partner', FILTER_SANITIZE_NUMBER_INT));
  $chains->setter_dps(filter_input(INPUT_POST, 'dps', FILTER_SANITIZE_NUMBER_INT));
  $chains->setter_dpe(filter_input(INPUT_POST, 'dpe', FILTER_SANITIZE_NUMBER_INT));  
  $chains->setter_report_type(filter_input(INPUT_POST, 'report_type', FILTER_SANITIZE_STRING));
  $chains->setter_interval_type(filter_input(INPUT_POST, 'filter_type', FILTER_SANITIZE_STRING));
  
  //Выполняем запрос на получение отчёта
  list($data_res, $data_summ, $count_bot, $count_ad) = $chains->get_report();
  
  $data_return['status'] = 'ok';
  $data_return['data'] = $data_res;
  $data_return['data_summ'] = $data_summ;
  $data_return['count_bot'] = $count_bot;
  $data_return['count_ad'] = $count_ad;
  $data_return['time'] = $timer->stop('start');
  print(json_encode($data_return, JSON_UNESCAPED_UNICODE));
}