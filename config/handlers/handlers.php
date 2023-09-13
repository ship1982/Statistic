<?php

/**
 * Массив со списком перехватчиков, которые выполянются до загрузки основной странице и указываются в роутере.
 */
return [
    'oauth' => __DIR__ . '/../../handlers/oAuthHandlers.php',
    'auth' => __DIR__ . '/../../handlers/AuthHandler.php'
];