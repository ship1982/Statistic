<?php

use model\Model;
use oAuthClient\oAuthClient;
use oAuthRule\oAuthRule;
use oAuthUser\oAuthUser;

include_once __DIR__ . '/../../lib/autoload.php';

/**
 * Class AddOauthUser
 * Класс для генерации нового пользователя для API.
 * Список параметров для передачи скрипту:
 * - первый параметр (обязательный) - ссылка для редиректа (домен)
 * - второй параметр (не обязательный) - имя пользователя
 * - третий параметр (не обязательный) - Имя пользователя (Иван)
 * - четвернтый параметр (не обязательный) - Фамилия пользователя
 * Результат работы:
 * - Текст с параметрами для авторизации по API
 * - Текст с параметрами для авторизации на счетчике
 */
class AddOauthUser extends Model
{
  /**
   * @var - поле секретного ключа
   */
  protected $client_secret;

  /**
   * @var - поле id клиента
   */
  protected $client_id;

  /**
   * @var - поле логина
   */
  protected $user;

  /**
   * @var - поле пароля
   */
  protected $password;

  /**
   * @var - поле имени пользователя
   */
  protected $first_name;

  /**
   * @var - поле фамилии пользователя
   */
  protected $last_name;

  /**
   * @var - поле ссылки, куда должен быть перенаправлен пользователь после успешной авторизации
   */
  protected $redirect_uri;

  /**
   * Генерация данных для авторизации по API.
   *
   * @return array
   */
  function generateClientsData()
  {
    $this->client_id = sha1(time() . uniqid());
    $this->client_secret = sha1(time() . $this->client_id);

    return [
        'client_id' => $this->client_id,
        'client_secret' => $this->client_secret,
        'scope' => 'read'
    ];
  }

  /**
   * Добавление в таблицу данных для авторизации по API
   */
  function addClients()
  {
    $data = $this->generateClientsData();
    $client = new oAuthClient();
    $client->save($data, []);
  }

  /**
   *  Генерация нового пользователя.
   *
   * @param string $redirect_uri
   * @param string $user
   * @param string $first_name
   * @param string $second_name
   *
   * @internal param string $last_name
   */
  function generateUser($redirect_uri = '', $user = '', $first_name = '', $second_name = '')
  {
    if (empty($user))
    {
      $this->user = md5(time() . uniqid());
    }
    else
    {
      $this->user = $user;
    }
    $this->password = uniqid();
    if (empty($first_name))
    {
      $this->first_name = '';
    }
    else
    {
      $this->first_name = $first_name;
    }
    if (empty($second_name))
    {
      $this->last_name = '';
    }
    else
    {
      $this->last_name = $second_name;
    }
    if (empty($redirect_uri))
    {
      exit("\nПоле редиректа не должно быть пустым\n");
    }
    $this->redirect_uri = $redirect_uri;
  }

  /**
   * Обновляем файл конфигурации.
   *
   * @param string $hash
   */
  function changeConfig($hash = '')
  {
    $file = __DIR__ . '/../../config/user/user.php';
    if (file_exists($file))
    {
      $users = require $file;
    }
    else
    {
      $users = [];
    }

    if (!empty($users)
        && !empty($hash)
    )
    {
      $users['user'][$this->user] = [
          'pass' => $this->password,
          'hash' => $hash
      ];

      file_put_contents($file, '<?php return ' . var_export($users, 1) . '; ?>');
    }
  }

  /**
   * Добавление нового пользователя.
   *
   * @param string $redirect_uri
   * @param string $user
   * @param string $first_name
   * @param string $second_name
   */
  function addUser($redirect_uri = '', $user = '', $first_name = '', $second_name = '')
  {
    $this->generateUser($redirect_uri, $user, $first_name, $second_name);
    $user = new oAuthUser();
    $password = sha1($this->password);
    $user->save([
        'username' => $this->user,
        'password' => $password,
        'first_name' => $this->first_name,
        'last_name' => $this->last_name,
        'redirect_uri' => $this->redirect_uri
    ], []);
    $this->changeConfig($password);
  }

  /**
   * Показ данных на вновь созданного пользователя.
   *
   * @return string
   */
  function showRegData()
  {
    return "Данные для API:\nclient_id: $this->client_id\nclient_secret: $this->client_secret\nredirect_uri: $this->redirect_uri\n
    Данные для авторизации:\nlogin: $this->user\npassword: $this->password\n\n";
  }

  /**
   * Добавляет правило
   */
  function addRule()
  {
    $rule = new oAuthRule();
    $rule->save([
        'client_id' => $this->client_id,
        'username' => $this->user,
        'scope' => 'read'
    ], []);
  }

  /**
   * Функция по обработке нового пользователя.
   *
   * @param string $redirect_uri
   * @param string $user
   * @param string $first_name
   * @param string $second_name
   */
  function run($redirect_uri = '', $user = '', $first_name = '', $second_name = '')
  {
    $this->addClients();
    $this->addUser($redirect_uri, $user, $first_name, $second_name);
    $this->addRule();
    echo $this->showRegData();
    return;
  }

  /**
   * Показ основной справки.
   *
   * @return string
   */
  function showHelp()
  {
    return "Скрипту необходимо передать:\n
    - первый параметр (обязательный) - ссылка для редиректа (домен)\n
    - второй параметр (не обязательный) - имя пользователя\n
    - третий параметр (не обязательный) - Имя пользователя (Иван)\n
    - четвернтый параметр (не обязательный) - Фамилия пользователя\n";
  }

  /**
   * Получение параметр по скриптам.
   *
   * @param array $data
   */
  function getParameters($data = [])
  {
    $redirect_uri = $user = $first_name = $second_name = '';
    if (!empty($data[1]))
    {
      $redirect_uri = $data[1];
    }
    else
    {
      echo $this->showHelp();
      exit;
    }

    if (!empty($data[2]))
    {
      $user = $data[2];
    }

    if (!empty($data[3]))
    {
      $first_name = $data[3];
    }

    if (!empty($data[4]))
    {
      $second_name = $data[4];
    }

    $this->run($redirect_uri, $user, $first_name, $second_name);
  }
}

$object = new AddOauthUser();
$object->getParameters($argv);