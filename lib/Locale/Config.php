<?php

namespace Locale;

class Config
{

  private static $bag = array(
                    'default' => 'en',
                    'accept' => array('en'),
                  );

  public static function set($key, $value = NULL)
  {
    static::$bag[$key] = $value;
  }

  public static function get($key, $default = FALSE)
  {
    return isset(static::$bag[$key]) ? static::$bag[$key] : $default;
  }

}
