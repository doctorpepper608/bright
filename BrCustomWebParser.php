<?php

/**
 * Project:     Bright framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Bright Core
 */

class BrWebParserResult extends BrObject {

  function __construct($struct) {

    parent::__construct();

    $this->setAttributes($struct);

  }

  function getTitle() {

    return $this->getAttr('title');

  }

  function getImage() {

    return $this->getAttr('image');

  }

  function getContent() {

    return $this->getAttr('content');

  }

  function getEncoding() {

    return $this->getAttr('encoding', 'utf-8');

  }

  function getPage() {

    $result  = '<html xmlns="http://www.w3.org/1999/xhtml" lang="ru"><head><meta http-equiv="Content-Type" content="text/html; charset=' . $this->getEncoding() . '" /><body>';
    $result .= $this->getContent();
    $result .= '</body></html>';

    return $result;

  }

}

class BrCustomWebParser extends BrObject {

  function parsePage($page) { }
  function parseUrl($url) { }

}
