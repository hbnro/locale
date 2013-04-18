<?php

namespace Locale;

class Datetime
{

  private static $num_days = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

  private static $fmt_expr = '/(?<!%)%([dDjlNSwzWFmMntLoYyaABgGhHisueIOPTZcrU])/';

  private static $fmt_set = array(
                    '/\bDDDD\b/' => '%l',
                    '/\bDDD\b/' => '%D',
                    '/\bDD\b/' => '%d',
                    '/\bMMMM\b/' => '%F',
                    '/\bMMM\b/' => '%M',
                    '/\bMM\b/' => '%m',
                    '/\bYYYY\b/' => '%Y',
                    '/\bYY\b/' => '%y',
                    '/\bHH\b/' => '%H',
                    '/\bhh\b/' => '%g',
                    '/\bii\b/' => '%i',
                    '/\bss\b/' => '%s',
                  );

  private static $time_periods = array(
                    'Years' => 31556926,
                    'Months' => 2629743,
                    'Weeks' => 604800,
                    'Days' => 86400,
                    'hours' => 3600,
                    'minutes' => 60,
                    'seconds' => 1,
                  );

  public static function format($with, $of = 0)
  {
    $with = preg_replace_callback(static::$fmt_expr, function ($match)
      use ($of) {
        $test = date($match[1], $of ? ( ! is_numeric($of) ? strtotime($of) : $of) : time());
        $test = is_numeric($test) ? $test : \Locale\Base::translate("date.$test");

        return $test;
      }, $with);

    return $with;
  }

  public static function simple($to, $from = 0)
  {
    $to = preg_replace(array_keys(static::$fmt_set), static::$fmt_set, $to);
    $to = static::format($to, $from);

    return $to;
  }

  public static function distance($since, $to = 0, $or = '%F %Y')
  {
    if ( ! is_numeric($since)) {
      $since = strtotime($since);
    }

    if ($to <= 0) {
      $to = time();
    }

    $diff = $to - $since;

    if (($diff >= 0) && ($diff <= 4)) {
      return \Locale\Base::translate('date.now');
    } elseif ($diff > 0) {
      $day_diff = floor($diff / 86400);

      if ($day_diff == 0) {
        if ($diff < 120) {
          return \Locale\Base::translate('date.less_than_minute');
        }

        if ($diff < 3600) {
          return \Locale\Base::pluralize(floor($diff / 60), 'date.minutes_ago');
        }

        if ($diff < 7200) {
          return \Locale\Base::translate('date.hour_ago');
        }

        if ($diff < 86400) {
          return \Locale\Base::pluralize(floor($diff / 3600), 'date.hours_ago');
        }
      }

      if ($day_diff == 1) {
        return \Locale\Base::translate('date.yesterday');
      }

      if ($day_diff < 7) {
        return \Locale\Base::pluralize($day_diff, 'date.days_ago');
      }

      if ($day_diff < 31) {
        return \Locale\Base::pluralize(ceil($day_diff / 7), 'date.weeks_ago');
      }

      if ($day_diff < 60) {
        return \Locale\Base::translate('date.last_month');
      }

      return static::format($or, $since);
    } else {
      $diff     = abs($diff);
      $day_diff = floor($diff / 86400);

      if ($day_diff == 0) {
        if ($diff < 120) {
          return \Locale\Base::translate('date.in_a_minute');
        }

        if ($diff < 3600) {
          return \Locale\Base::pluralize(floor($diff / 60), 'date.in_minutes');
        }

        if ($diff < 7200) {
          return \Locale\Base::translate('date.in_a_hour');
        }

        if ($diff < 86400) {
          return \Locale\Base::pluralize(floor($diff / 3600), 'date.in_hours');
        }
      }

      if ($day_diff == 1) {
        return \Locale\Base::translate('date.tomorrow');
      }

      if ($day_diff < 4) {
        return static::format('%l', $since);
      }

      if ($day_diff < (7 + (7 - date('w')))) {
        return \Locale\Base::translate('date.next_week');
      }

      if (ceil($day_diff / 7) < 4) {
        return \Locale\Base::pluralize(ceil($day_diff / 7), 'date.in_weeks');
      }

      if ((date('n', $since) == (date('n') + 1)) && (date('y', $since) == date('Y'))) {
        return \Locale\Base::translate('date.next_month');
      }

      return static::format($or, $since);
    }
  }

  public static function duration($secs, $used = 'hms', $zero = FALSE)
  {
    $out   =
    $parts = array();
    $secs  = (float) $secs;

    foreach (static::$time_periods as $key => $value) {
      if ( ! empty($used) && (strpos($used, substr($key, 0, 1)) === FALSE)) {
        continue;
      }

      $count = $secs / $value;

      if (floor($count) == 0 && ! $zero) {
        continue;
      }

      $secs       %= $value;
      $parts[$key] = abs($count);
    }

    foreach ($parts as $key => $value) {
      $out []= \Locale\Base::pluralize((int) $value, 'date.' . strtolower($key));
    }

    return $out;
  }

  public static function secs($from, $or = 'YMWD', $glue = ' ')
  {
    if ($from >= 86400) {
      return join($glue, \Sauce\I18N\Date::duration($from, $or));
    }

    $hours = floor($from / 3600);
    $mins  = floor($from % 3600 /60);
    $out   = sprintf('%d:%02d:%02d', $hours, $mins, $from % 60);
    $out   = preg_replace('/^0+:/', '', $out);

    return $out;
  }

  public static function days($month, $from = 1970)
  {
    if (($month < 1) OR ($month > 12)) {
      return FALSE;
    } elseif ( ! is_numeric($from) OR (strlen($from) <> 4)) {
      $from = date('Y');
    }

    if ($month == 2) {
      if ((($from % 400) == 0) OR ((($from % 4) == 0) AND (($from % 100) <> 0))) {
        return 29;
      }
    }

    return static::$num_days[$month - 1];
  }

  public static function gmt($from = 0)
  {
    $from = $from > 0 ? $from : time();

    $out  = gmdate('D M ', $from);
    $out .= sprintf('%2d ', (int) gmdate('d', $from));
    $out .= gmdate('H:i:s Y', $from);

    return strtotime($out);
  }

}
