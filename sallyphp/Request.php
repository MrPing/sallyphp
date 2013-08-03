<?php
/**
 * SallyPHP
 *
 * @link      https://github.com/MrPing/sallyphp
 * @copyright Copyright (c) 2013, Jonathan Amsellem.
 * @license   https://github.com/MrPing/sallyphp#license
 */

class Request
{
  private $_module = false;
  private $_controller = false;
  private $_action = false;
  private $_data = array();
  protected static $_instance = false;

  public function __construct()
  {

  }

  public static function getInstance()
  {
    if (!self::$_instance) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  public function setSegment($name, $value)
  {
    $this->_data[$name] = $value;
  }

  public function getSegment($name)
  {
    if (array_key_exists($name, $this->_data)) {
      return $this->_data[$name];
    } else {
      return null;
    }
  }

  public function getPost($name)
  {
    if (array_key_exists($name, $_POST)) {
      return $_POST[$name];
    } else {
      return null;
    }
  }

  public function setModule($name)
  {
    $this->_module = $name;
  }

  public function setController($name)
  {
    $this->_controller = $name;
  }

  public function setAction($name)
  {
    $this->_action = $name;
  }

  public function getModule()
  {
    return $this->_module;
  }

  public function getController()
  {
    return $this->_controller;
  }

  public function getAction()
  {
    return $this->_action;
  }
}