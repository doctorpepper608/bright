<?php

/**
 * Project:     Bright framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Bright Core
 */

require_once(__DIR__.'/BrGenericCacheProvider.php');

class BrAPCCacheProvider extends BrGenericCacheProvider {

  function __construct($cfg = array()) {

    parent::__construct($cfg);

    if (br($cfg, 'lifeTime')) {
      $this->setCacheLifeTime($cfg['lifeTime']);
    }

  }

  public static function isSupported() {

    return extension_loaded('apc');

  }

  public function reset() {

    return apc_clear_cache('user');

  }

  public function get($name, $default = null, $saveDefault = false) {

    $name = $this->safeName($name);

    $value = apc_fetch($name);
    if ($value === FALSE) {
      $value = $default;
      if ($saveDefault) {
        $this->set($name, $value);
      }
    }

    return $value;

  }

  public function set($name, $value, $cacheLifeTime = null) {

    if (!$cacheLifeTime) { $cacheLifeTime = $this->getCacheLifeTime(); }

    $name = $this->safeName($name);

    return apc_store($name, $value, $cacheLifeTime);

  }

  function remove($name) {

    $name = $this->safeName($name);

    return apc_delete($name);

  }

}

