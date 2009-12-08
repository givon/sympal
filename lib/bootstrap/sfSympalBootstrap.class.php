<?php

class sfSympalBootstrap
{
  protected
    $_sympalConfiguration,
    $_projectConfiguration,
    $_cache;

  public function __construct(sfSympalConfiguration $configuration)
  {
    $this->_sympalConfiguration = $configuration;
    $this->_projectConfiguration = $configuration->getProjectConfiguration();
    $this->_dispatcher = $this->_projectConfiguration->getEventDispatcher();

    sfConfig::set('sf_admin_module_web_dir', sfSympalConfig::get('admin_module_web_dir', null, '/sfSympalPlugin'));

    if (sfConfig::get('sf_login_module') == 'default')
    {
      sfConfig::set('sf_login_module', 'sympal_auth');
      sfConfig::set('sf_login_action', 'signin');
    }

    if (sfConfig::get('sf_secure_module') == 'default')
    {
      sfConfig::set('sf_secure_module', 'sympal_default');
      sfConfig::set('sf_secure_action', 'secure');
    }

    if (sfConfig::get('sf_error_404_module') == 'default')
    {
      sfConfig::set('sf_error_404_module', 'sympal_default');
      sfConfig::set('sf_error_404_action', 'error404');
    }

    if (sfConfig::get('sf_module_disabled_module') == 'default')
    {
      sfConfig::set('sf_module_disabled_module', 'sympal_default');
      sfConfig::set('sf_module_disabled_action', 'disabled');
    }

    $options = array('baseClassName' => 'sfSympalDoctrineRecord');
    $options = array_merge(sfConfig::get('doctrine_model_builder_options', array()), $options);
    sfConfig::set('doctrine_model_builder_options', $options);

    $manager = Doctrine_Manager::getInstance();
    $manager->setAttribute(Doctrine_Core::ATTR_VALIDATE, Doctrine_Core::VALIDATE_ALL);

    $this->_dispatcher->connect('context.load_factories', array($this, 'bootstrap'));
    $this->_dispatcher->connect('component.method_not_found', array(new sfSympalActions(), 'extend'));

    $eventHandler = new sfSympalEventHandler($this->_dispatcher);
  }

  public function getCache()
  {
    return $this->_cache;
  }

  public function bootstrap()
  {
    $this->_cache = new sfSympalCache($this->_sympalConfiguration);

    if (sfSympalConfig::get('load_all_modules', null, true))
    {
      $this->_loadAllModules();
    }

    sfOutputEscaper::markClassesAsSafe(array(
      'Content',
      'ContentTranslation',
      'ContentSlot',
      'ContentSlotTranslation',
      'MenuItem',
      'MenuItemTranslation',
      'Version',
      'VersionChange',
      'sfSympalContentRenderer',
      'sfSympalMenu',
      'sfParameterHolder'
    ));

    sfSympalContext::createInstance(
      sfConfig::get('app_sympal_config_site_slug', sfConfig::get('sf_app')),
      sfContext::getInstance()
    );

    $this->_handleInstall();

    $this->_projectConfiguration->loadHelpers(array('Sympal', 'I18N'));

    $request = sfContext::getInstance()->getRequest();

    if (!$request->isXmlHttpRequest() && $request->getParameter('module') != 'sympal_content_renderer')
    {
      $layout = sfSympalConfig::get($request->getParameter('module'), 'layout');
      if (!$layout)
      {
        $layout = sfSympalConfig::get('default_layout');
      }
      sfSympalTheme::change($layout);
    }

    if (sfConfig::get('sf_debug'))
    {
      $this->_checkDependencies();
    }

    if ($request->getParameter('module') == 'sympal_content_renderer')
    {
      $contentTypes = $this->_sympalConfiguration->getContentTypes();
      Doctrine_Core::initializeModels($contentTypes);
    }
  }

  protected function _loadAllModules()
  {
    $modules = sfConfig::get('sf_enabled_modules', array());
    if (sfSympalConfig::get('enable_all_modules'))
    {
      $modules = array_merge($modules, $this->_sympalConfiguration->getModules());
    }
    $modules = array_merge($modules, sfSympalConfig::get('enabled_modules', null, array()));

    sfConfig::set('sf_enabled_modules', $modules);
  }

  protected function _handleInstall()
  {
    $sfContext = sfContext::getInstance();
    $request = $sfContext->getRequest();
    $environment = sfConfig::get('sf_environment');
    $module = $request->getParameter('module');

    // Redirect to install module if...
    //  not in test environment
    //  sympal has not been installed
    //  module is not already sympal_install
    if ($environment != 'test' && !sfSympalConfig::get('installed') && $module != 'sympal_install')
    {
      $sfContext->getController()->redirect('@sympal_install');
    }
  }

  protected function _checkDependencies()
  {
    foreach ($this->_projectConfiguration->getPlugins() as $pluginName)
    {
      if (strpos($pluginName, 'sfSympal') !== false)
      {
        $dependencies = sfSympalPluginToolkit::getPluginDependencies($pluginName);
        sfSympalPluginToolkit::checkPluginDependencies($pluginName, $dependencies);
      }
    }
  }

}