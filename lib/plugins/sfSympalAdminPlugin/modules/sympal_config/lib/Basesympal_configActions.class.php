<?php

/**
 * Admin class for modifying system configuration (app.yml)
 * 
 * @package     sfSympalPlugin
 * @subpackage  actions
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @version     SVN: $Id: BaseActions.class.php 12534 2008-11-01 13:38:27Z Kris.Wallsmith $
 */
abstract class Basesympal_configActions extends sfActions
{

  /**
   * Returns the config form and checks the file permissions
   * 
   * @return sfSympalConfigForm
   */
  protected function _getForm()
  {
    $this->checkFilePermissions();

    $class = sfSympalConfig::get('config_form_class', null, 'sfSympalConfigForm');
    $this->form = new $class();

    return $this->form;
  }

  /**
   * Actually display the config form
   */
  public function executeIndex(sfWebRequest $request)
  {
    $this->form = $this->_getForm();
  }

  /**
   * Processes and saves the config form
   */
  public function executeSave(sfWebRequest $request)
  {
    $this->form = $this->_getForm();
    $this->form->bind($request->getParameter($this->form->getName()));

    if ($this->form->isValid())
    {
      $this->dispatcher->notify(new sfEvent($this, 'sympal.pre_save_config_form', array('form' => $this->form)));

      $this->form->save();

      $this->dispatcher->notify(new sfEvent($this, 'sympal.post_save_config_form', array('form' => $this->form)));

      // reset the cache
      $this->clearCache();
      
      $this->getUser()->setFlash('notice', 'Settings updated successfully!');
      $this->redirect('@sympal_config');
    }
    $this->setTemplate('index');
  }
}