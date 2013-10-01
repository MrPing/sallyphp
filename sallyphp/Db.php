<?php
/**
 * SallyPHP
 *
 * @link      https://github.com/MrPing/sallyphp
 * @copyright Copyright (c) 2013, Jonathan Amsellem.
 * @license   https://github.com/MrPing/sallyphp#license
 */

namespace sally;

/**
 * Sally Db
*/
class Db
{
  /**
   * @var array
  */
  private $_connection = array();

  /**
   * @var mixed
  */
  protected static $_instance = false;

  /**
   * Db instance
   * @return object
  */
  public static function getInstance()
  {
    if (!self::$_instance) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  /**
   * Récupérer une connexion
   * @param string connection name
   * @return object
  */
  public static function getConnection($name = null)
  {
    $instance = self::getInstance();
    if (isset($name)) {
      if (array_key_exists($name, $instance->_connection)) {
        return $instance->_connection[$name];
      } else {
        throw new Exception('Connection introuvable.');
      }
    } else {
      return $instance->_connection['default-mysql_pdo'];
    }
  }

  /**
   * Ajouter une connexion
   * @param array configuration
  */
  public function add($cfg)
  {
    if (isset($cfg['type'])) {
      if ($cfg['type'] == 'mysql_pdo') {
        if (isset($cfg['host']) && isset($cfg['dbname']) && isset($cfg['user']) && isset($cfg['passwd'])) {
          if (isset($cfg['name'])) {
            $name = $cfg['name'];
          } else {
            $name = 'default-mysql_pdo';
          }
          $this->_connection[$name] = new PDO('mysql:host=' . $cfg['host'] . ';dbname=' . $cfg['dbname'], $cfg['user'], $cfg['passwd']);
          $this->_connection[$name]->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); 
          if (isset($cfg['timezone'])) {
            $stmt = $this->_connection[$name]->prepare('set names utf8; set time_zone = :time_zone');
            $stmt->execute(array('time_zone' => $cfg['timezone']));
          } else {
            $this->_connection[$name]->exec('set names utf8');
          }
        } else {
          throw new Exception('Configuration mysql invalide');
        }
      } else if ($cfg['type'] == 'redis') {
        if (isset($cfg['name'])) {
          $name = $cfg['name'];
        } else {
          $name = 'default-redis';
        }
        
        $sally = Sally::getInstance();
        $sally->getLibrary('Predis/autoload.php');
        $this->_connection[$name] = new Predis\Client();

        try {
          $this->_connection[$name]->connect(array(
            'host' => isset($cfg['host']) ? $cfg['host'] : '127.0.0.1', 
            'port' => isset($cfg['port']) ? $cfg['port'] : 6379
          ));
        } catch(Predis\CommunicationException $e) {
          $this->_connection[$name] = false;
        }
      } else {
        throw new Exception('Type de db indisponible.');
      }
    } else {
      throw new Exception('Type de db non précisé.');
    }
  }
}