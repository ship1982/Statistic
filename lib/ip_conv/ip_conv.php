<?php

/**
 * Возвращает номер версии IP протокола, если проверка успешна
 * @param string $ip - IP адрес
 * @return boolean|int
 */
function ip_conv_get_version_from_ip(string $ip) {
	if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
		return 4;
	} elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
		return 6;
	} else {
		return false;
	}
}

/**
 * Возвращает номер версии IP протокола, если проверка успешна
 * @param string $cidr - CIDR подсети
 * @return boolean|int
 */
function ip_conv_get_version_from_cidr(string $cidr) {
	//Попробуем получить первый элемент для ipv4
	list($ipv4, $prefixlen) = explode("/", $cidr);

	//Попробуем получить первый элемент для ipv6
	list($ipv6, $prefixlen) = explode('/', $cidr);

	if (filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
		return 4;
	} elseif (filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
		return 6;
	} else {
		return false;
	}
}

/**
 * Фнукция разбирает CIDR IPv4 и  IPv6 адресов и возвращает массив, где 0-й элемент, это начало,
 * а 1-й - конец интервала адресов, предварительно определив версию IP.
 * Если IP версия не определена, то вернёт false.
 * @param string $cidr CIDR маска IP
 * @return boolean|array
 */
function ip_conv_cidr_to_range(string $cidr) {
	if (ip_conv_get_version_from_cidr($cidr) == 4) {
		return ip_conv_ipv4_cidr_to_range($cidr);
	} elseif (ip_conv_get_version_from_cidr($cidr) == 6) {
		return ip_conv_ipv6_cidr_to_range($cidr);
	} else {
		return ['',''];
	}
}

/**
 * Фнукция разбирает CIDR IPv4 адресов и возвращает массив, где 0-й элемент, это начало, а 1-й - конец интервала адресов.
 * @param string $cidr - строковое представление диапазаона адреса
 * @return array
 */
function ip_conv_ipv4_cidr_to_range(string $cidr) {
  if (ip_conv_get_version_from_cidr($cidr) == 4) {
    // Assign IP / mask
    list($ip, $mask) = explode("/", $cidr);

    // Sanitize IP
    $ip1 = preg_replace('_(\d+\.\d+\.\d+\.\d+).*$_', '$1', "$ip.0.0.0");

    // Calculate range
    $ip2 = long2ip(ip2long($ip1) - 1 + ( 1 << ( 32 - $mask) ));
    return [$ip1, $ip2];
  }else{
    return ['',''];
  }
}

/**
 * Фнукция разбирает CIDR IPv6 адресов и возвращает массив, где 0-й элемент, это начало, а 1-й - конец интервала адресов.
 * @param string $cidr - строковое представление диапазаона адреса
 * @return array
 */
function ip_conv_ipv6_cidr_to_range(string $cidr) {
  if (ip_conv_get_version_from_cidr($cidr) == 6) {
    // An example prefix
    //$prefix = '2001:db8:abc:1400::/54';
    $prefix = $cidr;

    // Split in address and prefix length
    list($firstaddrstr, $prefixlen) = explode('/', $prefix);

    // Parse the address into a binary string
    $firstaddrbin = inet_pton($firstaddrstr);

    // Convert the binary string to a string with hexadecimal characters
    # unpack() can be replaced with bin2hex()
    # unpack() is used for symmetry with pack() below
    $firstaddrhex = reset(unpack('H*', $firstaddrbin));

    // Overwriting first address string to make sure notation is optimal
    $firstaddrstr = inet_ntop($firstaddrbin);

    // Calculate the number of 'flexible' bits
    $flexbits = 128 - $prefixlen;

    // Build the hexadecimal string of the last address
    $lastaddrhex = $firstaddrhex;

    // We start at the end of the string (which is always 32 characters long)
    $pos = 31;
    while ($flexbits > 0) {
      // Get the character at this position
      $orig = substr($lastaddrhex, $pos, 1);

      // Convert it to an integer
      $origval = hexdec($orig);

      // OR it with (2^flexbits)-1, with flexbits limited to 4 at a time
      $newval = $origval | (pow(2, min(4, $flexbits)) - 1);

      // Convert it back to a hexadecimal character
      $new = dechex($newval);

      // And put that character back in the string
      $lastaddrhex = substr_replace($lastaddrhex, $new, $pos, 1);

      // We processed one nibble, move to previous position
      $flexbits -= 4;
      $pos -= 1;
    }

    // Convert the hexadecimal string to a binary string
    # Using pack() here
    # Newer PHP version can use hex2bin()
    $lastaddrbin = pack('H*', $lastaddrhex);

    // And create an IPv6 address from the binary string
    $lastaddrstr = inet_ntop($lastaddrbin);
    return [$firstaddrstr, $lastaddrstr];
  }else{
    return ['',''];
  }
}

function ip_conv_ip_to_int(string $ip){
	if (ip_conv_get_version_from_ip($ip) == 4) {
		return ip_conv_ipv4_addr_to_int($ip);
	} elseif (ip_conv_get_version_from_ip($ip) == 6) {
		return 0;
	} else {
		return 0;
	}
}

/**
 * Фнукция преобразует строку адреса IPv4 в целочисленное значение
 * @param string $ip - ip адрес формата 192.168.0.1
 * @return boolean|int
 */
function ip_conv_ipv4_addr_to_int(string $ip) {
	if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
		return ip2long($ip);
	} else {
		return false;
	}
}

/**
 * Фнукция преобразует целочисленное значение в строку адреса IPv4
 * @param int $num - целочисленное представление адреса IPv4
 * @return string
 */
function ip_conv_ipv4_int_to_addr(int $num) {
	return long2ip($num);
}

/**
 * Фнукция вызывает нужную функцию, в соответствии с версией IP, и возвращает бинарное представление адреса.
 * @param string $ip - строковое представление адреса
 * @return string
 */
function ip_conv_ip_to_binary_32(string $ip){
  if (ip_conv_get_version_from_ip($ip) == 4) {
    return ip_conv_ipv4_to_binary_32($ip);
  } elseif (ip_conv_get_version_from_ip($ip) == 6) {
		return ip_conv_ipv6_to_binary_32($ip);
	} else {
		return '';
	}
}

/**
 * Фнукция преобразует строку адреса IPv4 в бинарное представление в формате 000000000000000000000000FFFFFFFF
 * @param string $ip - ip адрес формата 192.168.0.1
 * @return string
 */
function ip_conv_ipv4_to_binary_32(string $ip){
  if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
		return str_pad(mb_convert_case(dechex(ip2long($ip)), MB_CASE_UPPER, "UTF-8"), 32, '0', STR_PAD_LEFT);
	} else {
		return '';
	}
}

/**
 * Фнукция преобразует строку адреса IPv6 в бинарное представление в формате 2a001370000000000000000000000000
 * @param string $ip - ip адрес формата 2002:c0a8:0000:0000:0000:0000:0000:0000
 * @return string
 */
function ip_conv_ipv6_to_binary_32(string $ip){
  if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
    $addr_bin = inet_pton($ip);
    return mb_convert_case(implode('',str_split(bin2hex($addr_bin), 4)), MB_CASE_UPPER, "UTF-8");
	} else {
		return '';
	}
}

/**
 * Функция преобразует бинарное значение адреса формата: 2a001370000000000000000000000000
 * или 000000000000000000000000C0A80001 в человеко понятный IP адрес IPv4 или IPv6.
 * @param string $string - строка, содержащая бинарное значение адреса
 * @return string
 */
function ip_conv_binary_32_to_ip(string $string){
  $ip = @inet_ntop(@hex2bin($string));
  if (filter_var($ip, FILTER_VALIDATE_IP)){
    //Попробуем выделить IPv4
    $ltrim_ip = ltrim($ip, ':');
    //Если это адрес IPv4
    if (filter_var($ltrim_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
      return $ltrim_ip;
    }else{
      return $ip;
    }
  }else{
    return '';
  }
}