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
 * Sally Engine
*/
class Engine
{
  /**
   * @var string
  */
  private $_out = null;

  /**
   * @var mixed
  */
  private $_dataBack = null;

  /**
   * @var boolean
  */
  private $_forward = false;
  private $_redirect = false;

  /**
   * @var object
  */
  public $trafficker;
  public $view;
  public $layout;
  public $helper;
  public $request;

  /**
   * @param string, array
  */
  public function __construct($request_string, $traffickers = array(), $helpers = array())
  {
    $this->trafficker = new Trafficker($this);
    $this->view = new View($this);
    $this->layout = new Layout($this);
    $this->helper = new Helper($this);
    $this->request = new Request($this);

    foreach ($traffickers as $name) {
      $this->trafficker->add($name);
    }

    foreach ($helpers as $name) {
      $this->helper->add($name);
    }

    $this->request->path($request_string);
  }

  /**
   * chargement des trafiquants , controleur, action, layout;
   * redirection interne;
   * redirection client;
   * écrire cookie;
   * @return string
  */
  public function call()
  {
    // preDeal n'est pas de nouveau executé en cas de redirection interne 
    if (!$this->trafficker->preDealIsExec()) {
      $this->trafficker->preDeal();
    }

    $controller_class_name = ucfirst($this->request->getController()) . 'Controller';

    // chemin du controleur
    if ($module = $this->request->getModule()) {
      $controller_path = \Sally::get('application') . '/modules/' . $module . '/controllers/' . $controller_class_name . '.php';
    } else {
      $controller_path = \Sally::get('application') . '/controllers/' . $controller_class_name . '.php';
    }

    if (!file_exists($controller_path)) {
      throw new sally\Exception('Le controleur "' . $this->request->getController() . '" n\'est pas accessible.');
    }

    require_once $controller_path;
    
    // demarrage du tampon de sortie
    ob_start();

    // instanciation du controleur
    $controller = new $controller_class_name($this);

    // check si l'action existe
    if (!method_exists($controller, $this->request->getAction())) {
      throw new sally\Exception('L\'action "' . $this->request->getAction() . '" n\'existe pas dans le controller "' . $this->request->getController() . '".');
    }

    // forward demandé dans le __construct du controleur
    if ($this->_forward) {
      return $this->launchForward();
    }

    // appel de l'action du controleur
    $this->_dataBack = $controller->{$this->request->getAction()}();

    // en cas de redirection client
    if ($this->_redirect) {
      ob_end_clean();
    } else {
      // forward demandé dans l'action du controleur
      if ($this->_forward) {
        return $this->launchForward();
      }

      // Vue par defaut
      if ($this->view->controllerViewIsEnabled()) {
        echo $this->view->load($this->request->getController() . '/' . $this->request->getAction(), null, true);
      }

      // Fin et récupération du tampon de sortie
      $this->_out = ob_get_contents();
      ob_end_clean();

      // Place le tampon de sortie dans un layout si possible
      if ($this->layout->isDefined() && $this->layout->isEnabled()) {
        $this->_out = $this->layout->integrate($this->_out);
      }

      // Dernière action du traffiquant
      $this->trafficker->preDelivery();
    }

    // Écrire du cookie
    if (class_exists('Session')) {
      Session::getInstance()->sendHeaderCookie();
    }

    // Redirection client
    if ($this->_redirect) {
      header('Location: ' . $this->_redirect);
      exit;
    }

    return $this->_out;
  }

  /**
   * Lance le forward (redirection interne);
   * Execute de nouveau $this->call();
   * @return string
  */
  private function launchForward()
  {
    if ($this->_forward['module'] == null) {
      $this->request->setModule($this->request->getModule());
    } else {
      $this->request->setModule($this->_forward['module']);
    }

    if ($this->_forward['controller'] == null) {
      $this->request->setController($this->request->getController());
    } else {
      $this->request->setController($this->_forward['controller']);
    }

    $this->request->setAction($this->_forward['action']);
    ob_end_clean();
    $this->disableForward();
    return $this->call();
  }

  /**
   * Définit le forward
   * @param array
  */
  public function setForward($data = array())
  {
    $this->_forward = $data;
  }

  /**
   * Désactive le forward
  */
  public function disableForward()
  {
    $this->_forward = false;
  }

  /**
   * Récupérer les données retournée par une action
   * @return mixed
  */
  public function getDataBack()
  {
    return $this->_dataBack;
  }

  /**
   * Récupérer le contenu qui sera envoyé au client
   * @return string
  */
  public function getOut()
  {
    return $this->_out;
  }

  /**
   * Redéfinir le contenu qui sera envoyé au client
   * @param string
  */
  public function setOut($out)
  {
    $this->_out = $out;
  }

  /**
   * Définit une redirection client
  */
  public function setRedirect($url)
  {
    $this->_redirect = $url;
  }

  /**
   * Détermine le chemin des fichiers helper, layout et view
   * @param string name, string file type
   * @return array path, file name
  */
  public function getFilePath($name, $type)
  {
    $module_path = '';

    if (preg_match('/\//', $name)) {
      $pre_file = strtolower(substr($name, strrpos($name, '/') + 1));
      $path = substr($name, 0, strrpos($name, '/') + 1);
      if ($path == '/') {
        $path = '';
      }
    } else {
      $pre_file = $name;
      $path = '';
    }

    if ($this->request->getModule() && $name[0] != '/') {
      $module_path = 'modules/' . $this->request->getModule() . '/';
    }

    // helper
    if ($type == 'helper') {
      $directory = 'helpers';
      $file = $pre_file . 'Helper';
    } 

    // layout
    elseif ($type == 'layout') {
      $directory = 'layouts';
      $file = $pre_file . 'Layout';
    }

    // view
    elseif ($type == 'view') {
      $directory = 'views';
      $file = $pre_file . 'View';
    }

    // pas pris en charge
    else {
      throw new sally\Exception('Le fichier "' . $name . '" ayant pour type "' . $type . '" n\'est pas pris en charge');
    }

    $path = \Sally::get('application') . '/' . $module_path . $directory . '/' . $path . $file . '.php';

    if (!file_exists($path)) {
      throw new sally\Exception('Le fichier "' . $path . '" n\'existe pas.');
    }

    return array($path, $file);
  }
}