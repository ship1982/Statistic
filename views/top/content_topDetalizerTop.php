<?php

$arLinksId = [];
$arData = [];
$key4Sort = [];
foreach ($params['result'] as $key => $value)
{
	$key4Sort[$key] = $value['c'];
	$arLinksId[] = $value['link'];
}

arsort($key4Sort);
$keys = array_keys($key4Sort);

if(!empty($arLinksId))
{
	common_inc('_fetcher');
	$rsLinkName = fetcher_getLink([
		'IN' => [
			'id' => $arLinksId
		]
	]);
}

$arLinkName = [];
if(!empty($rsLinkName))
{
    while($arLink = mysqli_fetch_assoc($rsLinkName))
        $arLinkName[$arLink['id']] = $arLink['domain_link'];
}

for ($i=0; $i < $ic = count($keys); $i++)
{
    $data = $params['result'][$keys[$i]];
	echo '<tr>
	<td>' . common_setValue($arLinkName, $data['link']) . '</td>
	<td>' . $data['c'] . '</td>
	<td>' . common_setValue($GLOBALS['app']['allDomain'], $data['domain']) . '</td>	
	<td>' . (int)$data['c_bots'] . '</td>	
	<td>' . common_percent_from_number($data['c'], (int)$data['c_bots']) . '</td>
	<td>' . (int)$data['c_ads'] . '</td>
	<td>' . common_percent_from_number($data['c'], (int)$data['c_ads']) . '</td>';
    echo '</tr>';
}