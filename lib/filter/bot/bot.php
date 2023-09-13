<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 20.09.16
 * Time: 12:40
 */

/**
 * Get list of useful bots.
 *
 * @return array
 */
function bot_getUsefulList()
{
    if(file_exists(__DIR__ . '/../../../config/searchBots/searchBots.php'))
        return require (__DIR__ . '/../../../config/searchBots/searchBots.php');
    else
        return [];
}

/**
 * Set name of bot. If name is empty, return unknown.
 *
 * @param string $userAgent
 * @return string
 */
function bot_setName($userAgent = '')
{
    if(empty($userAgent)) return 'unknown';
    $botList = bot_getUsefulList();
    foreach ($botList as $code => $name)
    {
        if(strpos($userAgent, $code) !== false)
        {
          return $name;
        }
    }

    return NULL;
}