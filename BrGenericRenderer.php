<?php

/**
 * Project:     Bright framework
 * Author:      Jager Mesh (jagermesh@gmail.com)
 *
 * @version 1.1.0.0
 * @package Bright Core
 */

require_once(dirname(__DIR__) . '/vendor/autoload.php');

class BrGenericRenderer extends BrObject {

  private $templates = array();
  private $vars = array();
  protected $params = array();

  public function render($templateName) {

    $templateFile = br()->atTemplatesPath($templateName);
    if (file_exists($templateFile)) {
      $this->templates[] = array('file' => $templateFile);
    }

  }

  public function getTemplates() {

    return $this->templates;

  }

  public function assign($name, $values) {

    $this->vars[$name] = $values;

  }

  public function getVar($name) {

    return br($this->vars, $name);

  }

  public function unassign($name) {

    unset($this->vars[$name]);

  }

  public function unassignByPattern($pattern) {

    foreach($this->vars as $key => $value) {
      if (preg_match($pattern, $key)) {
        unset($this->vars[$key]);
      }
    }

  }

  public function fetchString($string, $subst= array()) {

  }

  public function display($templateName, $subst = array()) {

  }

  public function fetch($templateName, $subst= array()) {

  }

  public function configure($params = array()) {

    $this->params = $params;

  }

  protected function compile($body, $subst = array()) {

    $localVars = array_merge($this->vars, $subst);

    $localVars['get']    = br()->request()->get();
    $localVars['config'] = br()->config()->get();
    if ($localVars['login']  = br()->auth()->getLogin()) {
      $localVars['authorized'] = true;
    }

    // $m = new Mustache(null, null, null, array('delimiters' => br($this->params, 'delimiters', '[[ ]]')));
    // $body = $m->render($body, $localVars);

    $m = new Mustache_Engine(array('delimiters' => br($this->params, 'delimiters', '[[ ]]')));
    $body = $m->render($body, $localVars);

    // $m = new Handlebars\Handlebars;
    // $body = $m->render($body, $localVars);

    // replace {br} with Bright url
    $body = str_replace('{br}', br()->request()->frameworkUrl(), $body);
    $body = str_replace('[br]', br()->request()->frameworkUrl(), $body);
    // // replace {url} with current url
    // $body = str_replace('{url}', br()->request()->url(), $body);
    // replace {/} with base url
    $body = str_replace('{/}', br()->request()->baseUrl(), $body);
    $body = str_replace('[/]', br()->request()->baseUrl(), $body);
    // replace {request.host} with current host
    $body = str_replace('{request.host}', br()->request()->host(), $body);
    // replace {request.domain} with current url
    $body = str_replace('{request.domain}', br()->request()->domain(), $body);
    // replace {request.url} with current url
    $body = str_replace('{request.url}', br()->request()->url(), $body);
    // replace {cfg:var-name} with config variable value
    while (preg_match('/[{]config.([^}]+)[}]/', $body, $matches)) {
      $body = str_replace($matches[0], br()->config()->get($matches[1]), $body);
    }
    while (preg_match('/\[config.([^]]+)\]/', $body, $matches)) {
      $body = str_replace($matches[0], br()->config()->get($matches[1]), $body);
    }
    while (preg_match('/\[[.]([^]]+)\]/', $body, $matches)) {
      $translation = br()->config()->get('translation');
      $body = str_replace($matches[0], br()->trn($matches[1]), $body);
    }
    //
    // replace {login:var-name} with config variable value
    while (preg_match('/[{]login.([^}]+)[}]/', $body, $matches)) {
      $vars = preg_split('/[ ]/', $matches[1]);
      $value = br()->auth()->getLogin($vars[0], br($vars, 1));
      $body = str_replace($matches[0], $value, $body);
    }
    while (preg_match('/\[login.([^]]+)\]/', $body, $matches)) {
      $vars = preg_split('/[ ]/', $matches[1]);
      $value = br()->auth()->getLogin($vars[0], br($vars, 1));
      $body = str_replace($matches[0], $value, $body);
    }
    // replace {at:path ... }
    if (preg_match_all("/[{](\^?):([^ }\n\r]+)[}](.+?)[{]:[}]/sm", $body, $matches, PREG_SET_ORDER)) {
      foreach($matches as $match) {
        if ($match[2] == '/') {
          $url = br()->request()->host().br()->request()->baseUrl().'($|[?])';
        } else {
          $url = $match[2];
        }
        if ($match[1]) {
          $ok = !br()->request()->isAt($url);
        } else {
          $ok = br()->request()->isAt($url);
        }
        if ($ok) {
          $body = str_replace($match[0], $match[3], $body);
        } else {
          $body = str_replace($match[0], '', $body);
        }
      }
    }
    // process secure/unsecure sections
    if ($login = br()->auth()->getLogin()) {
      if (preg_match_all('/[{][$]([^}]*?)[}](.+?)[{][$][}]/sm', $body, $matches, PREG_SET_ORDER)) {
        foreach($matches as $match) {
          if ($condition = trim($match[1])) {
            if (preg_match('/login[.]([^!= ]+)[ ]?(==|!=|in)(.*)/sm', $condition, $subMatch)) {
              $field = $subMatch[1];
              $condition = $subMatch[2];
              $value = rtrim(ltrim(trim($subMatch[3]), '('), ')');
              switch($condition) {
                case 'in':
                  $values = preg_split('~,~', $value);
                  $ok = false;
                  foreach($values as $value) {
                    $value = trim(trim($value), "'\"");
                    if (br($login, $field) == $value) {
                      $ok = true;
                      break;
                    }
                  }
                  if ($ok) {
                    $body = str_replace($match[0], $match[2], $body);
                  } else {
                    $body = str_replace($match[0], '', $body);
                  }
                  break;
                case '!in':
                  $values = preg_split('~,~', $value);
                  $ok = true;
                  foreach($values as $value) {
                    $value = trim(trim($value), "'\"");
                    if (br($login, $field) == $value) {
                      $ok = false;
                      break;
                    }
                  }
                  if ($ok) {
                    $body = str_replace($match[0], $match[2], $body);
                  } else {
                    $body = str_replace($match[0], '', $body);
                  }
                  break;
                case '==':
                  $value = trim(trim($value, "'"), '"');
                  if (br($login, $field) == $value) {
                    $body = str_replace($match[0], $match[2], $body);
                  } else {
                    $body = str_replace($match[0], '', $body);
                  }
                  break;
                case '!=':
                  $value = trim(trim($value, "'"), '"');
                  if (br($login, $field) != $value) {
                    $body = str_replace($match[0], $match[2], $body);
                  } else {
                    $body = str_replace($match[0], '', $body);
                  }
                  break;
                default:
                  $body = str_replace($match[0], '', $body);
                  break;
              }
            } else {
              $body = str_replace($match[0], $match[2], $body);
            }
          } else {
            $body = str_replace($match[0], $match[2], $body);
          }
        }
      }
      $body = preg_replace('/[{][-][}].+?[{]-[}]/sm', '', $body);
    } else {
      $body = preg_replace('/[{][$][^}]*?[}].+?[{][$][}]/sm', '', $body);
      $body = preg_replace('/[{][-][}]/sm', '', $body);
    }

    return $body;

  }


}
