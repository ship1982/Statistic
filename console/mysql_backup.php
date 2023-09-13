<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 02.09.16
 * Time: 14:57
 */

$root = __DIR__ . '/../../';

if(empty($argv[1]))
{
    echo "\n\nСправка\n\n";
    echo "Укажите название конфигурационного файла (без расширения php), который должен быть расположен по адресу " . realpath($root . '/stat/config/backup/') . ".\n";
    echo "\n";
    exit;
}

/** check configuration file */
if(!file_exists(__DIR__ . '/../config/backup/' . $argv[1] . '.php'))
{
    echo "\nНет такого файла " . realpath(__DIR__ . '/../config/backup/' . $argv[1] . '.php') . "\n\n";
    exit;
}

/** check folder for backup */
if(!is_dir($root . '/stat/backup/mysql'))
{
    echo realpath($root);
    echo "\nНет папки для хранения бэкапа " . realpath($root . '/stat/backup/mysql/') . "\n\n";
    exit;
}

/** @var $files  - contain all files or folders for import */
$files = require (__DIR__ . '/../config/backup/' . $argv[1] . '.php');
foreach ($files as $type => $file) {
    if ($type == 'mysql') {
        if (!is_array($file)) {
            echo "\nКонфигурация должна быть описана в виде массива\n\n";
            exit;
        }
        /** connect to mysql */
        require_once(__DIR__ . '/../lib/common/common.php');
        common_inc('sharding');
        for ($i = 0; $i < $ic = count($file); $i++) {
            $timestamp = false;
            $table = '';
            if(strpos($file[$i], '_') !== false)
            {
                $arData = explode('_', $file[$i]);
                $end = end($arData);
                if(is_int($end))
                    $timestamp = $end;

                for ($k = 0; $k < $ik = count($arData) - 1; $k++)
                    $table .= $arData[$k];
            }
            else
            {
                $table = $file[$i];
                $timestamp = false;
            }
            $connection = sharding_getConfiguration($timestamp, $table);
            system('mysqldump -u' . $connection['db_user'] . ' -p' . $connection['db_pass'] . ' -h' . $connection['db_type'] . ' ' . $connection['db_name'] . ' ' . $file[$i] . ' | gzip > ' . realpath(__DIR__ . '/../backup/mysql') . '/' . $file[$i] . '.sql.gz');
        }
    } else if ($type == 'file') {
        if (!is_array($file)) {
            echo "\nКонфигурация должна быть описана в виде массива\n\n";
            exit;
        }

        $fdescriptor = [];
        $descriptor = [];

        for ($i = 0; $i < $ic = count($file); $i++)
        {
            list($folder, $f) = explode('/', $file[$i]);
            if ($f == '*')
            {
                /** folder backup */
                $fldr = trim($file[$i], '*');
                if(!is_dir($root . '/stat/backup/mysql/' . $fldr))
                    mkdir($root . '/stat/backup/mysql/' . $fldr);

                system('cp -r ' . $root . '/' . $file[$i] . ' ' . $root . '/stat/backup/mysql/' . $fldr);
            }
            else
            {
                if(!array_key_exists($root . '/' . $folder, $fdescriptor))
                {
                    $fdescriptor[] = $root . '/' . $folder;
                    $descriptor[$root . '/' . $folder] = opendir($root . '/' . $folder);
                }

                while (false !== ($entry = readdir($descriptor[$root . '/' . $folder])))
                {
                    $regex = '(^' . str_replace('*', '.*', $f) . '$)';

                    if (preg_match_all($regex, $entry, $matches))
                    {
                        if(!is_dir($root . '/stat/backup/mysql/' . $folder))
                            mkdir($root . '/stat/backup/mysql/' . $folder);

                        copy($root . '/' . $folder . '/' . $entry, $root . '/stat/backup/mysql/' . $folder . '/' . $entry);
                    }
                }
            }
        }
    }
}

/** archive in tar.gz */
echo "\nСоздание архива\n";
system('tar cfz ' . $root . '/stat/backup/dump.tgz -C ' . $root . '/stat/backup/ mysql');

echo "\nУдаление временных файлов.\n";
system('rm -rf ' . $root . '/stat/backup/mysql/*');
echo "\nУдаление временных файлов завершено.\n";

echo "\nСоздание симлинка на загрузку.\n";
if(file_exists($root . '/stat/web/dump.tgz'))
    system('rm ' . $root . '/stat/web/dump.tgz');

system('ln -s ' . $root . '/stat/backup/dump.tgz ' . $root . '/stat/web/dump.tgz');
echo "\nСоздание симлинка на завершено.\n";

echo "\nПолучить бэкап можно по ссылке адрес_сайта/dump.tgz\n\n";
echo "\n\nРабота мастера завершена\n\n";