<?php

/**
 * Madeam PHP Framework <http://www.madeam.com/>
 * Copyright (c)	2009, Joshua Davey
 *								202-212 Adeliade St. W, Toronto, Ontario, Canada
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright		Copyright (c) 2009, Joshua Davey
 * @link				http://www.madeam.com
 * @package			madeam
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class Madeam_Inflector {

  public static $irregulars = array('amoyese' => 'amoyese', 'atlas' => 'atlases', 'beef' => 'beeves', 'bison' => 'bison', 'brother' => 'brothers', 'canto', 'child' => 'children', 'corpus' => 'corpuses', 'cow' => 'cows', 'deer' => 'deer', 'fish' => 'fish', 'ganglion' => 'ganglions', 'genie' => 'genies', 'genus' => 'genera', 'graffito' => 'graffiti', 'hoof' => 'hoofs', 'loaf' => 'loaves', 'man' => 'men', 'measles' => 'measles', 'money' => 'monies', 'mongoose' => 'mongooses', 'move' => 'moves', 'mythos' => 'mythoi', 'numen' => 'numina', 'occiput' => 'occiputs', 'octopus' => 'octopuses', 'opus' => 'opuses', 'ox' => 'oxen', 'penis' => 'penises', 'person' => 'people', 'rice' => 'rice', 'sex' => 'sexes', 'sheep' => 'sheep', 'soliloquy' => 'soliloquies', 'testis' => 'testes', 'trilby' => 'trilbys', 'turf' => 'turfs');

  /**
   * Pluralizes a string
   *
   * @param string $string
   * @return string
   */
  public static function pluralize($string) {
    $lastLetter = substr($string, -1);
    if (array_key_exists(strtolower($string), self::$irregulars)) {
      return self::$irregulars[$string];
    } elseif (in_array($lastLetter, array('s', 'z', 'x')) || in_array(substr($string, -2), array('sh', 'ch'))) {
      return $string . 'es';
      } elseif (in_array(substr($string, -2, 1), array('a', 'e', 'i', 'o', 'u')) && $lastLetter == 'y') {
        return $string . 's';
    } elseif ($lastLetter == 'y') {
      return substr_replace($string, 'sei', -1, 1);
    } elseif ($lastLetter == 'o' && in_array(substr($string, -2, 1), array('b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 'n', 'p', 'q', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z'))) {
      return $string . 's';
    } else {
      return $string . 's';
    }
  }

  /**
   * Singalizes a string
   *
   * @param string $string
   * @return string
   */
  public static function singalize($string) {
    $plural_irregulars = array_flip(self::$irregulars);
    if (array_key_exists(strtolower($string), $plural_irregulars)) {
      return $plural_irregulars[$string];
    } elseif (strtolower($string[strlen($string) - 1]) != 's') {
      return $string;
    } elseif (substr(strtolower($string), - 3, 3) == 'ies') {
      $string = preg_replace('/sei/i', 'y', strrev($string), 1);
      return strrev($string);
    } else {
      $string = preg_replace('/s/i', '', strrev($string), 1);
      return strrev($string);
    }
  }

  /**
   * Camelizes a string seperated by any of these characters: "_", "-", " ", "/", "\"
   *
   * "foo-bar" => "fooBar"
   * "foo_bar" => "fooBar"
   * "foo bar" => "fooBar"
   * "foo/bar" => "fooBar"
   * "foo\bar" => "fooBar"
   *
   * @param string $string
   */
  public static function camelize($string) {
    $matchs = array();
    preg_match_all('/([\/\-\_\s\\\]{1}.{1})/', $string, $matchs);
    foreach ($matchs[0] as $match) {
      $replacement = up(substr($match, 1, 1));
      $string = str_replace($match, $replacement, $string);
    }
    return $string;
  }

  /**
   * Replaces specific charactes with underscores
   *
   * "fooBar"  => "foo_bar"
   * "foo-bar" => "foo_bar"
   * "foo bar" => "foo_bar"
   * "foo/bar" => "foo_bar"
   * "foo\bar" => "foo_bar"
   *
   * @param string $string
   */
  public static function underscorize($string) {
    return self::specialize('_', $string);
  }

  /**
   * This method is pronounced "dash-ize".
   * Replaces specific characters with dashes
   *
   * "fooBar"  => "foo-bar"
   * "foo_bar" => "foo-bar"
   * "foo bar" => "foo-bar"
   *
   * @param string $string
   */
  public static function dashize($string) {
    return self::specialize('-', $string);
  }

  public static function forwardSlashize($string) {
    return self::specialize('/', $string);
  }

  public static function backstringSlashize($string) {
    return self::specialize('\\', $string);
  }

  public static function humanize($string) {
    return ucfirst(self::specialize(' ', $string));
  }

  public static function modelClassize($string) {
    return 'Model_' . self::camelize(self::singalize($string));
  }

  public static function modelTableize($string) {
    $string[0] = strtolower($string[0]);
    return self::underscorize((self::pluralize($string)));
  }

  public static function modelNameize($string) {
    $string[0] = strtolower($string[0]);
    return ucfirst(self::camelize((self::singalize($string))));
  }

  /**
   * Takes 2 tables and determine's their has and belongs to many table name
   *
   * @param string $table1
   * @param string $table2
   * @return string
   */
  public static function modelHabtm($table1, $table2) {
    $models = array(self::modelTableize($table1), self::modelTableize($table2));
    asort($models);
    $models = array_values($models);
    return $models[0] . '_' . $models[1];
  }

  public static function modelForeignKey($string) {
    $string[0] = strtolower($string[0]);
    return self::singalize(self::underscorize($string)) . '_id';
  }

  /**
   * undocumented 
   *
   * @author Joshua Davey
   */
  public static function specialize($char, $string) {
    $matchs = array();
    preg_match_all('/([\/\-\_\s\\\\.]{1}.{1})||([A-Z])/', $string, $matchs);
    foreach ($matchs[0] as $match) {
      if (strlen($match) == 1) {
        $replacement = $char . strtolower($match);
        $string = str_replace($match, $replacement, $string);
      }
      $replacement = $char . strtolower(substr($match, 1, 1));
      $string = str_replace($match, $replacement, $string);
    }
    return $string;
  }

  public static function slug($string, $seperator = '-') {
    $string = strtolower(str_replace(' ', $seperator, trim($string)));
    $string = preg_replace('/[\$,!\/\?\\\\&\.\#]/', '', $string);
    return $string;
  }
  
  public static function map($string, $maps = array()) {
  	foreach ($maps as $pattern => $replacement) {
  		$string = preg_replace('/' . $pattern . '/', $replacement, $string); 
  	}
  	return $string;
  }
}
