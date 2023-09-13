<?php

function ip_to_binary_32(){
  common_inc('ip_conv');
  
  //Фильтруем
  $ip = filter_input(INPUT_GET, 'ip', FILTER_SANITIZE_STRING);
  $ip = (!$ip) ? filter_input(INPUT_POST, 'ip', FILTER_SANITIZE_STRING) : $ip;
  
  $ip_binary_32 = ip_conv_ip_to_binary_32((string)$ip);
  $res = [
      'status' => 'Ok',
      'ip' => (string)$ip,
      'ip_binary_32' => $ip_binary_32,
      'binary_32_ip' => ip_conv_binary_32_to_ip((string)$ip_binary_32)
  ];
  
  echo json_encode($res, JSON_UNESCAPED_UNICODE);
}