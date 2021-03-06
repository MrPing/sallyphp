<?php
/**
 * SallyPHP
 *
 * @link      https://github.com/MrPing/sallyphp
 * @license   https://github.com/MrPing/sallyphp#license
*/

namespace sally;

/**
 * Sally
*/
abstract class TraffickerAbstract
{
  /**
   * @var object
  */
  private $engine;
  public $request;
  public $layout;
  public $view;
  public $helper;
  public $query;

  /**
   * TraffickerAbstrart constructor
  */
  function __construct($engine)
  {
    $this->engine = $engine;
    $this->request = $engine->request;
    $this->layout = $engine->layout;
    $this->view = $engine->view;
    $this->helper = $engine->helper;
    $this->query = $engine->query;
  }

  /**
   * Appelée au début de la requête
  */
  function preEngine() {}

  /**
   * Appelée avant la livraison de la vue
   * @param string contenu de la vue
   * @return mixed Si vous ne retournez pas de valeur la vue ne sera pas écrasée.
  */
  function viewDelivery($content, $data) {}

  /**
   * Appelée avant d'intégrer le contenu au layout
  */
  function preLayout() {}

  /**
   * Appelée avant de retourner le contenu de la réponse au client
   * @param string contenu de la réponse
   * @return mixed Si vous ne retournez pas de valeur la réponse ne sera pas écrasée.
  */
  function engineDelivery($content, $databack) {}
}