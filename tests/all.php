<?php

date_default_timezone_set('AMerica/Mexico_City');

require dirname(__DIR__).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';


Locale\Config::set('default', 'es_MX');
Locale\Base::load_path(__DIR__.DIRECTORY_SEPARATOR.'locale');

$r = rand(0, 5);

var_dump(Locale\Base::digest('%Hi :yo!'), Locale\Base::digest("#$r Coin, #YO!"), Locale\Base::translate('Posts'));

var_dump(Locale\Datetime::format('%Y %d %M'));
var_dump(Locale\Datetime::simple('DDD DD, MMMM YYYY'));
var_dump(Locale\Datetime::distance('-4 weeks'));
var_dump(Locale\Datetime::duration(132));
var_dump(Locale\Datetime::secs(2345));
var_dump(Locale\Datetime::days(2));
var_dump(Locale\Datetime::gmt());
