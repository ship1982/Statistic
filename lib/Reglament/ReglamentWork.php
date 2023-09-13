<?php

namespace Reglament;

use model\Model;
use queryBuilder\mysql\QueryBuilder;

/**
 * Скрипт по созданию шардированных таблиц на месяц.
 * 
 * Раз в месяц для шардированых таблиц необходимо создавать новую таблицу.
 * Данный класс реализует все методы для создания таких таблиц.
 * Алгоритм работы следующий:
 * 1) получаем конфигурационный файл со списком шардов
 * 2) получаем список всех таблиц
 * 3) выбираем таблицы, которые нужны нам (передаются в конструктор) и timestamp начала месяца. Если не передать время, то будет взят следующий месяц
 * 4) получаем запросы на создание таблиц последних что есть (самые свежие)
 * 5) добавляем новые таблицы в конфиг файл
 * 6) сохраняем конфигурационный файл с таблицами
 */

class ReglamentWork
{
	/**
	 * @var int - timestamp месяца, от которого будут браться таблицы.
	 */
	public $currentMonth = 0;

	/**
	 * @var array - массив c ключом к старой таблицы и значением новой.
	 */
	protected $crossTables = [];

	/**
	 * @var array - массив с ключом, названием таблицы и запрсом на создание таблицы.
	 */
	protected $createTables = [];

	/**
	 * @var array - список таблиц для шардирования.
	 */
	protected $tables = [];

	/**
	 * @var string - email для отправки после генерации
	 */
	public $email = "hva@zionec.ru";

	/**
	 * @var array - конфигурационный массив со всеми таблицами и параметрами для подключения
	 */
	protected $configFile = [];

	function __construct($tables = [], $current = 0)
	{
		if(empty($tables)) exit("\nНе задан список таблиц для поиска.\n");
		$this->tables = $tables;
		if(empty($current))
			$this->currentMonth = time();
		else
			$this->currentMonth = strtotime('-1 month', $current);
	}

	/**
	 * Получает список таблиц для шардинга.
	 * 
	 * @param array $allTable - список всех таблиц
	 * @return array
	 */
	public function getNeededTable($allTable = [])
	{
		$neededTbales = [];
		if(empty($allTable)) exit("\nНе получилось получить список таблиц.\n");
		for ($i=0; $i < $ic = count($allTable); $i++)
		{
			// разбиваем название таблицы на части
			preg_match_all('/([a-z_A-Z0-9]+)_(\d{10,10})/', $allTable[$i]['Tables_in_stat'], $matches);
			
			if(!empty($matches[1][0])
				&& !empty($matches[2][0])
			)
			{
				// находим максимальное значение времени
				if(in_array($matches[1][0], $this->tables))
				{
					if(empty($neededTbales[$matches[1][0]]))
						$neededTbales[$matches[1][0]] = 0;

					if($neededTbales[$matches[1][0]] < $matches[2][0])
						$neededTbales[$matches[1][0]] = $matches[2][0];
				}
			}
		}

		return $neededTbales;
	}

	/**
	 * Получает список всех таблиц в БД.
	 * 
	 * @return array
	 */
	public function getTables()
	{
		$db = new QueryBuilder(1);
		$db->query("SHOW TABLES");
		$data = $db->fetch();
		return $this->getNeededTable($data);
	}

	/**
	 * Собирает массив со старыми ключами и новыми для замены в запросе по добавлению новых таблиц.
	 * 
	 * @param string $oldName - старое имя
	 * @param string $newName - новое имя
	 * @return void
	 */
	public function storeTablesOldAndNewName($oldName = '', $newName = '')
	{
		if(!empty($oldName)
			&& !empty($newName)
		)
			$this->crossTables[$oldName] = $newName;
	}

	/**
	 * Получаем ключ для новой таблицы.
	 * 
	 * @return string
	 */
	public function getStartMonthDate()
	{
		return strtotime(date('Y-m-1', strtotime('+1 month', $this->currentMonth)));
	}

	/**
	 * Получает массив с запрсоом на вставку и дополнительнеы данные для новых таблиц.
	 * 
	 * @param array $list - список необходимых таблиц @see $this->getNeededTable()
	 * @return void
	 */
	public function getCreateString($list = [])
	{
		if(empty($list)) exit("\nНет таблиц для SHOW CREATE TABLE\n");
		$postfix = $this->getStartMonthDate();
		foreach ($list as $key => $value)
		{
			$db = new Model([$value], $key);
			$tableName = $db->getTable();
			$this->storeTablesOldAndNewName($tableName, $key . '_' . $postfix);
			$db->query("SHOW CREATE TABLE " . $db->getTable());
			$data = $db->fetch()[0]['Create Table'];
			$this->createTables[$tableName] = [
				'shard' => $value,
				'table' => $key,
				'query' => $data
			];
		}

		$this->changeTableNames();
	}

	/**
	 * Заменяет в запросе на вставку таблиц данные.
	 * 
	 * @return void
	 */
	public function changeTableNames()
	{
		if(!empty($this->createTables)
			&& !empty($this->crossTables)
		)
		{
			foreach ($this->createTables as $key => $str)
			{
				if(!empty($this->crossTables[$key]))
					$this->createTables[$key] = str_replace(
						$key,
						$this->crossTables[$key],
						$str
					);
			}
		}
	}

	/**
	 * Создает таблицы в БД.
	 * 
	 * @return void
	 */
	public function createTables()
	{
		if(!empty($this->createTables))
		{
			foreach ($this->createTables as $key => $params)
			{
				if(!empty($params['table'])
					&& !empty($params['shard'])
					&& !empty($params['query'])
				)
				{
					$db = new Model([$params['shard']], $params['table']);
					$db->query($params['query']);
				}
			}
		}
	}

	/**
	 * Получает конфигурационный файл.
	 * 
	 * @return void
	 */
	public function getConfigFile()
	{
		if(file_exists(__DIR__ . '/../../config/sharding/list.php'))
			$this->configFile = require __DIR__ . '/../../config/sharding/list.php';
		else
			exit("\nНет файла конфигурации для шардинга\n");
	}

	/**
	 * Формирует новый массив для конфигурационного файла.
	 * 
	 * @param array $tables
	 * @return void
	 */
	public function getConfigRow4Table($tables = [])
	{
		if(!empty($this->configFile)
			&& !empty($tables)
		)
		{
			$time = $this->getStartMonthDate();
			foreach ($tables as $table => $postfix)
			{
				$params = $this->configFile[$table][$postfix];
				$params['db_key'] = $time;
				$this->configFile[$table][$time] = $params;
			}
		}
	}

	/**
	 * Обновляет конфигурационный файл.
	 * 
	 * @return void
	 */
	public function updateConfigFile()
	{
		if(!empty($this->configFile))
			file_put_contents(
				__DIR__ . '/../../config/sharding/list.php',
				'<?php return ' . var_export($this->configFile, true ) . ";\n"
			);
	}

	/**
	 * Отправка на почту сообщений при успешной генерации.
	 * 
	 * @return void
	 */
	public function notify()
	{
		// получаем текст запросов
		$text = '';
		if(!empty($this->createTables))
		{
			foreach ($this->createTables as $key => $params)
			{
				if(!empty($params['query']))
					$text .= "\n" . $params['query'] . "\n\n";
			}
		}
		mail(
			$this->email,
			"Create sharding tables on" . date("d-m-Y", $this->getStartMonthDate()),
			$text
		);
	}

	/**
	 * Выполяет основную работу.
	 * 
	 * @return void
	 */
	public function run()
	{
		$this->getConfigFile();
		$tables = $this->getTables();
		$this->getCreateString($tables);
		$this->createTables();
		$this->getConfigRow4Table($tables);
		$this->updateConfigFile();
		$this->notify();
	}
}