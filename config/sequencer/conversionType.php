<?php

/**
 * Типы конверсии.
 * 
 * при первом взаимодействии - конверсия на первом и втором шаге
 * при срединном взаимодействии - конверсия на шаге, отличном от первого и второго, но не последнем
 * на последнем шаге - конверсия на последнем шаге пути пользователя
 */
return [
	0 => 'не учитывать',
	1 => 'при первом взаимодействии',
	2 => 'при срединном взаимодействии',
	3 => 'на последнем шаге'
];