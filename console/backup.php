<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 02.09.16
 * Time: 0:08
 */

$root = __DIR__ . '/../';

if(empty($argv[1]))
{
    echo "\n\nСправка\n\n";
    echo "Укажите название конфигурационного файла (без расширения php), который должен быть расположен по адресу " . realpath($root . '/config/backup/') . ".\n";
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
if(!is_dir($root . '/backup/stat'))
{
    echo realpath($root . '/backup/stat');
    echo "\nНет папки для хранения бэкапа " . realpath($root . '/backup/stat/') . "\n\n";
    exit;
}

/** @var $files  - contain all files or folders for import */
$files = require (__DIR__ . '/../config/backup/' . $argv[1] . '.php');
foreach ($files as $file)
{
    $newFolder = $root . '/backup/stat/';
    $folders = explode('/', $file);
    $ic = count($folders) - 1;
    for ($i = 0; $i < $ic; $i++)
    {
        $newFolder .=  $folders[$i] . '/';
        if(!is_dir($newFolder))
            mkdir($newFolder);
    }
    /** do copy for archive */
    if(is_dir($root . '/' . $file))
        system('cp -r ' . $root . '/' . $file . '/* ' . $root . '/backup/stat/' . $file);
    else
        copy($root . '/' . $file, $root . '/backup/stat/' . $file);
}

/** archive in tar.gz */
echo "\nСоздание архива\n";
system('tar cfz ' . realpath($root) . '/backup/www.tgz -C ' . realpath($root) . '/backup/ stat');

echo "\nУдаление временных файлов.\n";
system('rm -rf ' . realpath($root) . '/backup/stat/*');
echo "\nУдаление временных файлов завершено.\n";

echo "\nСоздание симлинка на загрузку.\n";
if(file_exists($root . '/web/www.tgz'))
    system('rm ' . realpath($root) . '/web/www.tgz');

system('ln -s ' . realpath($root) . '/backup/www.tgz ' . realpath($root) . '/web/www.tgz');
echo "\nСоздание симлинка на завершено.\n";

echo "\nПолучить бэкап можно по ссылке адрес_сайта/www.tgz\n\n";
echo "\n\nРабота мастера завершена\n\n";