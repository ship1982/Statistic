<?php

/**
 * @var string $layout - layuout for controller.
 */
$layout = 'filtersequencer';

/**
 * Check user authorization.
 *
 * @return redirect on main or nothing
 */
function MainAuthUser()
{
    common_inc('auth');
    if(!auth_is())
        header('Location: /');
}

/**
 * Get type of user for filter
 *
 * @return array
 */
function getUserType()
{
	$post = $_POST;
	if(empty($post)
		|| empty($post['group_of_user'])
	)
		return [];

	return $post['group_of_user'];
}

/**
 * Set count of user path part.
 *
 * @return bool
 */
function getCountOfPath()
{
	$post = $_POST;
	if(empty($post)
		|| empty($post['count_of_site'])
	)
		return false;

	return true;
}

/**
 * Get type of conversion.
 *
 * @return string
 */
function getConversionType()
{
	$post = $_POST;
	if(empty($post)
		|| empty($post['type_of_conversion'])
	)
		return '';

	return $post['type_of_conversion'];
}

/**
 * Get name of domain by list of domains id.
 *
 * @param array $domain - list of domain's id.
 * @return array
 */
function getDomainLink($domain = [])
{
	common_inc('_database');
	$sql = "SELECT `id`,`name` WHERE `id` IN ('".implode("','", array_unique($domain))."')";
	$rs = query_db(
		1,
		'domain',
		$sql
	);
	$data = [];
	if(!empty($rs)
		&& is_object($rs)
	)
	{
		while ($array = mysqli_fetch_assoc($rs))
			$data[$array['id']] = $array['name'];
	}

	return $data;
}

/**
 * Get name of link by list of links id.
 *
 * @param array $link - list of link's id.
 * @return array
 */
function getLinkLink($link = [])
{
	common_inc('_database');
	$sql = "SELECT `id`,`domain_link` WHERE `id` IN ('".implode("','", array_unique($link))."')";
	$rs = query_db(
		1,
		'link',
		$sql
	);
	$data = [];
	if(!empty($rs)
		&& is_object($rs)
	)
	{
		while ($array = mysqli_fetch_assoc($rs))
			$data[$array['id']] = $array['domain_link'];
	}

	return $data;
}

/**
 * Main handler.
 * Handles json data (path by user)
 * Get list of domain ids
 * Get list of link ids.
 *
 * @param array $model - row from by database (array)
 * @return array
 * 
 * domain
 * link
 * info
 */
function collectInfoByInfo($model = [])
{
	$listOfDomain = [];
	$listOfLink = [];
	$array = [];
	if(!empty($model))
	{
		foreach ($model as $key => $value)
		{
			if(!empty($value['json2sequence'])
				&& is_string($value['json2sequence'])
			)
			{
				$array2Json = json_decode($value['json2sequence'], true);
				for ($i=0; $i < $ic = count($array2Json); $i++)
				{ 
					if(!empty($array2Json[$i]['domain']))
						$listOfDomain[] = $array2Json[$i]['domain'];
					if(!empty($array2Json[$i]['link']))
						$listOfLink[] = $array2Json[$i]['link'];
				}
				
				$array[$key] = [
					'time' => $value['time'],
					'count' => $value['count'],
					'c_bots' => $value['c_bots'],
					'c_ads' => $value['c_ads'],
					'data' => $array2Json
				];
			}
		}
	}

	return [
		'domain' => $listOfDomain,
		'link' => $listOfLink,
		'info' => $array
	];
}

/**
 * Show html for path of user.
 * 
 * @param @see showInfoByPath()
 * @param array $domain - list of domain ids.
 * @param array $link - list of links ids.
 * @return string
 */
function showPathDetail($path = [], $domain = [], $link = [])
{
	$str = '';
	if(!empty($path)
		&& !empty($domain)
		&& !empty($link)
	)
	{
		if(is_array($path))
		{
			for ($i=0; $i < $ic = count($path); $i++)
			{ 
				$str .= '<div>';
				foreach ($path[$i] as $key => $value)
				{
					switch ($key)
					{
						case 'domain':
							$str .= '<div>Домен: ' . $domain[$value] . '</div>';
							break;
						case 'link':
							$str .= '<div>Ссылка: ' . $link[$value] . '</div>';
							break;
						case 'step':
							$str .= '<div>Шаг: ' . $value . '</div>';
							break;
						case 'duration':
							$str .= '<div>Длительность пребывания: ' . $value . '</div>';
							break;
					}
				}
				$str .= '</div><hr>';
			}
		}
	}

	return $str;
}

/**
 * Get htm for result view.
 *
 * @param array $model - row from by database (array)
 * @return string
 */
function showInfoByPath($model = [])
{
	$ids = collectInfoByInfo($model);
	$domains = getDomainLink($ids['domain']);
	$link = getLinkLink($ids['link']);
	$html = '';

	foreach ($ids['info'] as $value)
	{
		$html .= '<tr><td>' . $value['count'] . '</td><td>';
		$html .= showPathDetail($value['data'], $domains, $link);
        $html .= '</td><td>' . (int)$value['c_bots'] . '</td>';
        $html .= '</td><td>' . (int)$value['c_ads'] . '</td>';
	}

	return $html;
}

/**
 * Get time for filter.
 *
 * @return array
 */
function getTime()
{
	$post = $_POST;
	if(!empty($post)
		&& !empty($post['from'])
		&& !empty($post['to'])
	)
	{
		return [
			'from' => strtotime($post['from']),
			'to' => strtotime($post['to'])
		];
	}

	return [];
}

function getLastDomain()
{
	if(!empty($_POST['sequencer_domain']))
		return $_POST['sequencer_domain'];
	else
		return '';
}

/**
 * @constructor
 */
function showStartFormController()
{
	MainAuthUser();

	$userType = getUserType();
	$countOfPath = getCountOfPath();
	$conversion = getConversionType();
	$time = getTime();
	$lastDomain = getLastDomain();

	// подключаем API fetcher
	common_inc('_fetcher');
	$arDomains = []; //  массив с доменами
  $resDomain = fetcher_getDomain(['show' => 1]);
  if(!empty($resDomain))
  {
  	while ($a = mysqli_fetch_assoc($resDomain))
  		$arDomains[$a['id']] = $a['name'];
  }

  // получаем список пользователей
  $userTypes = [];
  if(file_exists(__DIR__ . '/../../config/sequencer/usertypes.php'))
  	$userTypes = require __DIR__ . '/../../config/sequencer/usertypes.php';

  // получаем список пользователей
  $conversionTypes = [];
  if(file_exists(__DIR__ . '/../../config/sequencer/conversionType.php'))
  	$conversionTypes = require __DIR__ . '/../../config/sequencer/conversionType.php';
	
	$answer = '';
	if(!empty($_POST))
	{
		common_inc('services');
		$service = new MainService();
		$answer = $service->query('sequence', [
			'user_type' => $userType,
			'count_of_path' => $countOfPath,
			'conversion' => $conversion,
			'time' => $time,
			'method' => 'getByType',
			'lastDomain' => $lastDomain
		]);
	}

	if(is_string($answer))
		$content = json_decode($answer, true);

	return common_setView('sequence/start', [
		'model' => $content,
		'domains' => $arDomains,
		'usertypes' => $userTypes,
		'conversion' => $conversionTypes
	]);
}