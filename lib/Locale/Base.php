<?php

namespace Locale;

class Base
{

  private static $tree = array();
  private static $cache = array();

  public static function all()
  {
    $out  = array();
    $lang = \Locale\Config::get('default') ?: 'en';
    $test = \Locale\Config::get('accept') ?: array('en');

    $out[$lang] = 1;

    foreach ((array) $test as $one) {
      $one = explode(';q=', $one);

      if ($lang = trim($one[0])) {//FIX
        $out[$lang] = ! empty($one[1]) ? (float) $one[1] : 1;
      }
    }

    arsort($out, SORT_NUMERIC);

    return $out;
  }

  public static function digest($phrase, $default = '', array $params = array())
  {
    if (preg_match_all('/[%#:](\d+\s[\w.]+|[\w.]+)\b/', $phrase, $matches)) {
      foreach ($matches[0] as $i => $old) {
        $tmp   = array_filter(explode(' ', $matches[1][$i]), 'strlen');
        $tmp []= $params;

        $callback  = strpos($old, ' ') ? 'pluralize' : 'translate';
        $new = call_user_func_array("static::$callback", $tmp);

        $phrase = str_replace($old, $new, $phrase);
      }
    }

    return $phrase;
  }

  public static function pluralize($number, $string, array $params = array())
  {
    $decimal   = 0;// TODO: configure
    $separator = '.';
    $thousands = ' ';

    $string = "$string." . ($number <> 1 ? 'other' : 'one');
    $number = number_format($number, $decimal, $separator, $thousands);

    $string = static::translate($string, $params);
    $string = sprintf($string, $number);

    return $string;
  }

  public static function translate($string, $default = '', array $params = array())
  {
    if (is_array($string)) {
      $params = array_merge($params, $string);
    } elseif ( ! isset($params['string'])) {
      $params['string'] = $string;
    }

    if (is_array($default)) {
      $params = array_merge($default, $params);
    } elseif ( ! isset($params['default'])) {
      $params['default'] = (string) $default;
    }

    $params = array_merge(array(
      'scope'   => '',
      'string'  => '',
      'default' => '',
    ), $params);

    $params['default'] = (array) $params['default'];

    if (is_array($params['default'])) {
      foreach ($params['default'] as $one) {
        if ( ! preg_match('/^[a-z][a-z0-9_.]+$/', $one)) {
          $params['default'] = $one;
          break;
        } else {
          $test = static::translate($one, array('scope' => $params['scope']));

          if ( ! empty($test)) {
            $params['default'] = $test;
            break;
          }
        }
      }
    }

    $from   = static::load_locale();

    $prefix = $params['scope'] ? "$params[scope]." : '';
    $string = static::fetch($from, "$prefix$params[string]", $params['default'] ?: "$prefix$params[string]");

    $string = preg_replace_callback('/%\{(.+?)\}/', function ($match)
      use ($params) {
        return isset($params[$match[1]]) ? $params[$match[1]] : $match[1];
      }, $string);

    return $string;
  }

  public static function load_path($from, $scope = '')
  {
    if (is_array($from)) {
      return array_map('static::load_path', $from);
    }

    $dir = realpath($from);

    if ( ! is_dir($from) OR in_array($dir, static::$cache)) {
      return FALSE;
    }

    static::$cache []= $dir;

    $path = realpath($from);
    $test = preg_split('/[^a-zA-Z]/', \Locale\Config::get('default'));

    foreach (array(
      '.php' => 'array',
      '.csv' => 'csv',
      '.ini' => 'ini',
      '.yaml' => 'yaml',
    ) as $ext => $type) {
      $callback = 'static::load_' . $type;

      foreach (array(
        join(DIRECTORY_SEPARATOR, array($path, join('_', $test).$ext)),
        join(DIRECTORY_SEPARATOR, array($path, $test[0].$ext)),
      ) as $one) {
        if (is_file($one)) {// do not use lambda here
          $lang = call_user_func($callback, $one);
          static::load_locale($lang, $scope);
          break;
        }
      }
    }
  }

  public static function load_locale(array $set = array(), $scope = '')
  {
    if ( ! empty($set)) {
      if ( ! empty($scope)) {
        $old = isset(static::$tree[$scope]) ? static::$tree[$scope] : array();
        $set = array($scope => array_merge($old, $set));
      }
      static::$tree = array_merge(static::$tree, $set);
    }

    return static::$tree;
  }

  public static function load_array($from)
  {
    if ( ! is_file($from)) {
      return FALSE;
    }

    ob_start();
    $out = include $from;
    ob_end_clean();

    if ( ! empty($lang)) {
      $out = $lang;
    }

    return (array) $out;
  }

  public static function load_csv($from, $split = ';')
  {
    if ( ! is_file($from)) {
      return FALSE;
    }

    $out      = array();
    $resource = fopen($from, 'rb');

    fseek($resource, 0);

    while (FALSE !== ($old = fgetcsv($resource, 0, $split, '"'))) {
      if ((substr($old[0], 0, 1) == '#') OR empty($old[1])) {
        continue;
      }

      $out[trim($old[0])] = $old[1];
    }

    fclose($resource);

    return $out;
  }

  public static function load_ini($from)
  {
    if ( ! is_file($from)) {
      return FALSE;
    }

    $out = parse_ini_file($from, FALSE);

    return $out;
  }

  public static function load_yaml($from)
  {
    if ( ! is_file($from)) {
      return FALSE;
    }

    $text = file_get_contents($from);

    return \Symfony\Component\Yaml\Yaml::parse($text);
  }

  private static function fetch(array $set, $key, $default = FALSE)
  {
    $key = strtr($key, array('[' => '.', ']' => ''));
    $parts = explode('.', $key);
    $test = end($parts);

    $idy = join("']['", $parts);
    $idx = strtr(strtolower(join("']['", $parts)), ' ', '_');

    @eval("\$tmp = isset(\$set['$idx']) ? \$set['$idx'] : (isset(\$set['$idy']) ? \$set['$idy'] : \$default);");

    if (preg_match('/^[A-Z]{2,}/', $test)) {
      return strtoupper($tmp); // uppercase
    } elseif (preg_match('/^[A-Z][a-z]/', $test)) {
      return ucwords($tmp); // capitalize
    }

    return $tmp ?: $default;
  }

}
