<?php

/**
 * @var string $layout - layuout for controller.
 */

use services\MainService;

$layout = 'statistic';

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

function eventainer_setCode($data = [])
{
	$code = '0-0-0-1';
	if(!empty($data['id'])
		|| !empty($data['right'])
		|| !empty($data['left'])
	)
		$code = ($data['left']) . '-'
			. ($data['right']) . '-'
			. ($data['level'] + 1);

	return $code;
}

function eventainer_setCodeUpdate($data = '')
{
	$code = '0-0-0-1';
	if(!empty($data['id'])
		|| !empty($data['right'])
		|| !empty($data['left'])
	)
		$code = ($data['left']) . '-'
			. ($data['right']) . '-'
			. ($data['level']);

	return $code;
}

function eventainer_getPosition()
{
	$left = '';
	$right = '';
	$level = '';
	if(!empty($_GET['id']))
		list($left, $right, $level) = explode('-', $_GET['id']);
	
	return [
		'left' => $left,
		'right' => $right,
		'level' => $level
	];
}

function eventainer_getBreadcrumbs()
{
	$position = eventainer_getPosition();
	$data = [];
	if(!empty($position['left'])
		&& !empty($position['right'])
	)
	{
		common_inc('_database');
		$sql = "SELECT
				`name`,
				`label`,
				`left`,
				`right`,
				`level`
			WHERE `left`<=$position[left]
				AND `right`>=$position[right]
			ORDER BY `left`";
		$o = query_db(
			1,
			'event_eventainer',
			$sql
		);

		if(!empty($o))
		{
			while($a = mysqli_fetch_assoc($o))
				$data[] = $a;			
		}
	}
	return $data;
}

function eventainer_getQueryParams()
{
	if(empty($_GET['id'])) return [];
	$left = '';
	$right = '';
	$level = '';
	if(strpos($_GET['id'], '-') !== false)
		list($left, $right, $level) = explode('-', $_GET['id']);

	return [
		'left' => $left,
		'right' => $right,
		'level' => $level
	];
}

function eventainer_getOldFiled()
{
	$data = [];
	if(empty($_POST)) return $data;
	foreach ($_POST as $key => $value)
	{
		if(strpos($key, 'old_') !== false)
			$data[$key] = $value;
	}

	return $data;
}

/**
 * Get type of user for filter
 *
 * @return array
 */
function showStartPage()
{
	MainAuthUser();
	$service = new MainService();
	$breadcrumbs = eventainer_getBreadcrumbs();
	$answer = $service->query(
		'events',
		array_merge([
			'action' => 'eventainerList',
			'method' => 'eventRun'
			], eventainer_getQueryParams()
		)
	);
	if(is_string($answer))
		$result = json_decode($answer, JSON_UNESCAPED_UNICODE);

	return common_setView(
		'events/startPage',
		array_merge(
			$result,
			['breadcrumbs' => $breadcrumbs]
		)
	);
}

function eventainer_showBreadcrumbs($breadcrumbs = [])
{
	$str = '';
	if(!empty($breadcrumbs))
	{
		$cnt = count($breadcrumbs);
		for ($i=0; $i < $ic = $cnt; $i++)
		{ 
			$id = '';
			if(!empty($breadcrumbs[$i]['right'])
				&& !empty($breadcrumbs[$i]['left'])
				&& !empty($breadcrumbs[$i]['level'])
			)
				$id = "?id=".$breadcrumbs[$i]['left'].'-'.$breadcrumbs[$i]['right'].'-'.($breadcrumbs[$i]['level'] + 1);

			if($cnt - 1 == $i)
				$str.= '<li>'.$breadcrumbs[$i]['name'].'</li>';
			else
				$str .= '<li><a href="/events/'.$id.'" title="'.$breadcrumbs[$i]['name'].'">'.$breadcrumbs[$i]['name'] . '</a></li>';
		}
	}

	return $str;
}

function showAddEventForm()
{
	MainAuthUser();
	$result = [];
	if(!empty($_POST))
	{
		$service = new MainService();
		$answer = $service->query('events', [
			'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
			'category' => filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING),
			'event' => filter_input(INPUT_POST, 'event', FILTER_SANITIZE_STRING),
			'label' => filter_input(INPUT_POST, 'label', FILTER_SANITIZE_STRING),
			'value' => filter_input(INPUT_POST, 'value', FILTER_SANITIZE_STRING),
			'id' => filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING),
			'action' => 'eventainerAdd',
			'method' => 'eventRun'
		]);

		if(is_string($answer))
			$result = json_decode($answer, JSON_UNESCAPED_UNICODE);
	}

	return common_setView(
		'events/element',
		$result
	);
}

function showUpdateEventForm()
{
	MainAuthUser();
	$result = [];

	$service = new MainService();

	if(!empty($_POST))
	{
		$answer = $service->query('events', [
			'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
			'category' => filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING),
			'event' => filter_input(INPUT_POST, 'event', FILTER_SANITIZE_STRING),
			'label' => filter_input(INPUT_POST, 'label', FILTER_SANITIZE_STRING),
			'value' => filter_input(INPUT_POST, 'value', FILTER_SANITIZE_STRING),
			'id' => filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING),
			'action' => 'eventainerAdd',
			'method' => 'eventRun',
			'old' => eventainer_getOldFiled(),
			'update' => true
		]);
	}
	else
	{
		$urlPart = explode('/', $_SERVER['REQUEST_URI']);
		$urlWithQuery = urldecode(end($urlPart));
		$urlWithoutQuery = strtok($urlWithQuery, '?');
		$answer = $service->query('events', [
			'action' => 'eventainerList',
			'label' => $urlWithoutQuery,
			'method' => 'eventRun'
		]);
	}

	if(is_string($answer))
		$result = json_decode($answer, JSON_UNESCAPED_UNICODE);

	// if record is empty
	if(empty($result['error']))
	{
		if(empty($result['header']))
		{
			header("Location: /events/");
			exit;
		}
	}

	if(empty($result['error']))
		$_POST = (!empty($result['items'][0]) ? $result['items'][0] : $result['list']);


	return common_setView(
		'events/element',
		array_merge(
			$result,
			['update' => 'Y']
		)
	);
}

function showDeleteEventForm()
{
	MainAuthUser();
	$result = [];
	if(!empty($_GET['id']))
	{
		$service = new MainService();
		$answer = $service->query('events', [
			'id' => filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING),
			'action' => 'eventainerDelete',
			'method' => 'eventRun'
		]);

		if(is_string($answer))
			$result = json_decode($answer, JSON_UNESCAPED_UNICODE);
	}

	if($result)
	{
		header('Location: /events/');
		exit;
	}
}