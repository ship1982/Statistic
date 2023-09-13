<?php

use api\HashAuth\HashAuth;
use api\login\LoginAPI;
use EventsAPI\CustomEventsAPI;
use tools\XMLClient;
use ptv\PTV;
use ptv\PTVHelper;
use SequencerAPI\SequencerAPI;
use services\MainService;
use EventsAPI\EventsAPI;
use CampaignsAPI\CampaignsAPI;

$layout = 'default';

function getStatAPISequencer()
{
  common_inc('oauth');

  //Если oAuth авторизация не выполнена, то выполнение срипта будет остановлено
  init_oauth()->access_api();

  //Подключаем файл класса
  common_inc('api/api_sequencer', 'api_sequencer');

  $apiSequencer = new apiSequencer();

  /**
   * Получаем параметры из GET или POST
   */
  //Временной диапазон, испоьзуется для того, чтобы определить в каких таблицах искать данные, так как таблицы шардированы.
  $time = filter_input(INPUT_GET, 'time', FILTER_SANITIZE_STRING);
  $time = (empty($time)) ? filter_input(INPUT_POST, 'time', FILTER_SANITIZE_STRING) : $time;

  //Поля, которые нужно вернуть в результате выборки
  $fields = filter_input(INPUT_GET, 'fields');
  $fields = (empty($fields)) ? filter_input(INPUT_POST, 'fields') : $fields;

  //Условия, накладываемые на выборку данных
  $conditions = filter_input(INPUT_GET, 'conditions');
  $conditions = (empty($conditions)) ? filter_input(INPUT_POST, 'conditions') : $conditions;

  // группировка
  $group = filter_input(INPUT_POST, 'group');

  //Передаём параметры в апи
  $apiSequencer->set_time($time);
  $apiSequencer->set_fields($fields);
  $apiSequencer->set_conditions($conditions);
  $apiSequencer->setGroupBy($group);

  $apiSequencer->get_statistics();
}


function getUserProperty()
{
  common_inc('oauth');

  //Инициируем oAuth
  $oAuth2 = init_oauth();

  //Если нед доступа по токена, то вернём ошибку об этом и остановим выполнение скрипта
  if (!$oAuth2->test_access_api())
  {
    echo json_encode([
        'status' => 'error',
        'description' => 'No token or Error access tokken'
    ], JSON_UNESCAPED_UNICODE);
    die;
  }

  //Подключаем файл класса
  common_inc('api/api_sequencer', 'api_sequencer');

  $apiSequencer = new apiSequencer();

  $uuid = filter_input(INPUT_GET, 'uuid', FILTER_SANITIZE_STRING);
  $uuid = (empty($uuid)) ? filter_input(INPUT_POST, 'uuid', FILTER_SANITIZE_STRING) : $uuid;

  $apiSequencer->get_user_property($uuid);
}

function helpPage()
{
  common_setView('api/help');
}

function getAnswerFromBitrix($input = [])
{
  $d1 = $d2 = '';
  if (!empty($input['time']))
  {
    $d1 = json_decode($input['time'], true);
  }
  if (!empty($input['retrotime']))
  {
    $d2 = $input['retrotime'];
  }

  return json_encode(
      array_merge(
          $d1,
          ['retrotime' => $d2]
      )
  );
}

function getUtmOrder()
{
  if (HashAuth::isEqualHash($_GET))
  {
    common_inc('api/common', 'common');
    $data = getAnswerFromBitrix($_GET);

    // set data in queue
    if (!empty($data))
    {
      $service = new MainService();
      $answer = $service->query('mysqlqueue', [
          'method' => 'mysqliqueue_set',
          'param' => $data,
          'queue' => '4620'
      ]);
    }


    $result = [];
    if (!empty($answer))
    {
      $result = json_decode($answer, true);
    }

    echo common_setValue($result, 'queue');
    exit;
  }
  else
  {
    header('HTTP/1.0 401 Unauthorized');
    exit;
  }
}

/**
 * Отражает вьюху для тестирования API и oAUTH
 */
function testOAuthController()
{
  common_setView('api/test');
}

function getRepeateActions()
{
  common_inc('oauth');
  common_inc('timer');

  $timer = new timerPrint();
  $timer->start('start');

  //Если oAuth авторизация не выполнена, то выполнение срипта будет остановлено
  init_oauth()->access_api();

  common_inc('api/repeate_actions', 'repeate_actions');
  common_inc('api/common', 'common');

  //Получим данные из POST или GET запроса, хранимые в REQUEST
  $data_input = common_api_getInput([
      'time_start',
      'time_end',
      'uuid',
      'ip',
      'type_access_site'
  ]);

  $repeateActions = new repeateActions();
  $repeateActions->setter_time_start(common_getVariable($data_input, ['time_start'], 0));
  $repeateActions->setter_time_end(common_getVariable($data_input, ['time_end'], 0));
  $repeateActions->setter_uuid(common_getVariable($data_input, ['uuid'], ''));
  $repeateActions->setter_ip(common_getVariable($data_input, ['ip'], ''));
  $repeateActions->setter_type_access_site(common_getVariable($data_input, ['type_access_site'], ''));

  $result = [
      'status' => 'ok',
      'description' => '',
      'result' => [
        //'params_input' => $repeateActions->get_params(),
        //'list_shard_tables' => $repeateActions->get_shard_l_sequencer_4_user(),
        'is_bot' => $repeateActions->getter_isbot_percent()
      ]
  ];
  $result['time'] = $timer->stop('start');

  echo json_encode($result);

}

/**
 * Создание нового сегмента
 */
function createSegmentAction()
{
    $tableName = $_GET['table_name'] ?? null;
    $conditions = $_GET['conditions'] ?? null;
    $script = $_GET['script'] ?? null;

    $errors = [];
    if (is_null($tableName)) {
        $errors[] = 'Parameter "table_name" is required';
    }

    if (is_null($conditions)) {
        $errors[] = 'Parameter "conditions" is required';
    }

    if (is_null($script)) {
        $errors[] = 'Parameter "script" is required';
    }

    $tableName = addslashes(trim(strip_tags($tableName)));

    $segmentsAPI = new \SegmentsAPI\SegmentsAPI();
    if ($segmentsAPI->segmentExists($tableName, $conditions)) {
        $errors[] = 'Segment already exists';
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'messages' => $errors]);
        exit;
    }

    if (!$segmentsAPI->createSegment($tableName, $conditions, $script)) {
        $error = $segmentsAPI->getError();
        $segmentsAPI->removeSegment($tableName, md5(json_encode($conditions)));

        echo json_encode(['success' => false, 'messages' => ['Segment create filed', $error]]);
        exit;
    }

    echo json_encode(['success' => true, 'messages' => ['Segment was created']]);
}

/**
 * Поиск сегмента
 */
function findSegment()
{
    $tableName = $_GET['table_name'] ?? null;
    $seance = $_GET['seance'] ?? null;

    $result = ['success' => true, 'messages' => [], 'segment' => null];

    if (is_null($tableName) || empty($tableName)) {
        $result['messages'][] = 'Parameter "table_name" is required';
        $result['success'] = false;
    }

    if (is_null($seance) || empty($tableName)) {
        $result['messages'][] = 'Parameter "seance" is required';
        $result['success'] = false;
    }

    if ($result['success']) {
        $tableName = addslashes(trim(strip_tags($tableName)));
        
        $segmentsAPI = new \SegmentsAPI\SegmentsAPI();
        $uuid = $segmentsAPI->findUserBySeance($tableName, $seance);
        if (is_null($uuid)) {
            $result['messages'][] = 'User not found';
            $result['success'] = false;
        }

        if ($result['success']) {
            $foundedSegment = $segmentsAPI->findSegmentByUser($tableName, $uuid);
            if (is_null($foundedSegment)) {
                $result['messages'][] = 'Segment not found';
                $result['success'] = false;
            } else {
                $result['segment']['script'] = $foundedSegment['script'];
                $result['messages'][] = 'Segment was found';
            }
        }
    }

    echo json_encode($result);
}

/**
 * Обработка запросов API на event_list
 */
function eventsProcess()
{
  $events = new EventsAPI();
  echo $events->toJSON($events->execute());
}

/**
 * Обработка запросов API для формирования отчетов по рекламным кампаниям
 */
function campaignsProcess()
{
  $campaigns = new CampaignsAPI();
  echo $campaigns->toJSON($campaigns->execute());
}

/**
 * Обработка запросов API на user_property
 */
function botProcess()
{
  $bots = new \BotAPI\BotAPI();
  echo $bots->toJSON($bots->execute());
}

/**
 * Обработка запросов API на user_login
 */
function getLogin()
{
  $api = new LoginAPI();
  echo $api->toJSON($api->execute());
}

/**
 * Обработка запросов API на event_list
 */
function sequenceProcess()
{
  $events = new SequencerAPI();
  echo $events->toJSON($events->execute());
}

function customEventsProcess()
{
    $customEvents = new CustomEventsAPI();
    echo $customEvents->toJSON($customEvents->execute());
}

/**
 * Экшен для тестирования вставки данных PTV
 */
function ptvTestInsertAction()
{
    $config = setConfig('ptv/ptv');

    $XMLClient = new XMLClient();
    $ptv = new PTV();
    $PTVHelper = new PTVHelper();
    $ptvRequest = $PTVHelper->prepareRequest('4954684174');
    $ptvResponse = $XMLClient->request($config['host'], $config['user'], $config['password'], $ptvRequest);
    // Коннект к PTV
    $ptvDataArray = $PTVHelper->parseResponse($ptvResponse, true);
    $ptvDataArray['event_list_id'] = 183423662;

    $ptv->insert($ptvDataArray);
    $ptv->execute();

    $ptv->insert($ptvDataArray);
    if (!$ptv->execute()) {
        echo json_encode(['error' => implode('; ', $ptv->error)]);
    } else {
        echo json_encode(['success' => 'PTV data inserted.']);
    }
}

/**
 * Экшент для тестирования получения данных события, обогощенных данными из PTV
 */
function ptvTestSelectDataByEventIdAction()
{
    $testEventId = 183423662;
    $ptv = new PTV();

    $sql = "SELECT * FROM event_list join ptv on ptv.event_list_id = event_list.id WHERE event_list_id = {$testEventId}";
    if (!$ptv->query($sql)) {
        echo json_encode(['error' => implode('; ', $ptv->error)]);
    } else {
        echo json_encode($ptv->fetch());
    }
}