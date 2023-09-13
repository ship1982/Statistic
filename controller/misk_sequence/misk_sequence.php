<?php

$layout = 'statistic';

function contGetDomain() {
    //Фильтруем
    $ids = filter_input(INPUT_GET, 'ids', FILTER_SANITIZE_STRING);
    $ids = (!$ids) ? filter_input(INPUT_POST, 'ids', FILTER_SANITIZE_STRING) : $ids;

    common_inc('misk_sequence');    
    echo getDomain($ids);
}

function contGetListCity(string $string_like = '') {
    //Фильтруем
    $string_like = filter_input(INPUT_GET, 'string_like', FILTER_SANITIZE_STRING);
    $string_like = (empty($string_like)) ? filter_input(INPUT_POST, 'string_like', FILTER_SANITIZE_STRING) : $string_like;

    common_inc('misk_sequence');
    echo getListCity($string_like);
}

function contGetListIPS(string $string_like = '') {
    //Фильтруем
    $string_like = filter_input(INPUT_GET, 'string_like', FILTER_SANITIZE_STRING);
    $string_like = (empty($string_like)) ? filter_input(INPUT_POST, 'string_like', FILTER_SANITIZE_STRING) : $string_like;

    common_inc('misk_sequence');
    echo getListIPS($string_like);
}

function contGetListIPDIAP(string $string_like = '') {
    //Фильтруем
    $string_like = filter_input(INPUT_GET, 'string_like', FILTER_SANITIZE_STRING);
    $string_like = (empty($string_like)) ? filter_input(INPUT_POST, 'string_like', FILTER_SANITIZE_STRING) : $string_like;

    common_inc('misk_sequence');
    echo getListIpDiap($string_like);
}

function contGetUserPropertyCond(){
    common_inc('auth');
    if (!auth_is()){
        header('Location: /');
    }
    
    $result = [];
    
    common_inc('misk_sequence');
    
    common_setView('misk_sequence/cond_user_property/get', $result);
}

function contChangeUserPropertyCondAdd(){
  common_inc('auth');
    if (!auth_is()){
        header('Location: /');
    }
    
    common_inc('misk_sequence');
    
    changePropertyCond(false);
}

function contChangeUserPropertyCondUpd(){
  common_inc('auth');
    if (!auth_is()){
        header('Location: /');
    }
    
    common_inc('misk_sequence');
    
    changePropertyCond(true);
}

function contDelUserPropertyCond(){
    common_inc('auth');
    if (!auth_is()){
        header('Location: /');
    }
    
    common_inc('misk_sequence');
    
    delUserPropertyCond();
    
    header('Location: /condition_user_property/');
}