<?php
class sfSympalMenuSite extends sfSympalMenu
{
  protected 
    $_menuItem = null;

  public function findMenuItem($menuItem)
  {
    if ($this->_menuItem->id == $menuItem->id)
    {
      return $this;
    }
    foreach ($this->_children as $child)
    {
      if ($i = $child->findMenuItem($menuItem))
      {
        return $i;
      }
    }
    return false;
  }

  public function getPathAsString()
  {
    $path = array();
    $obj = $this;

    do {
    	$path[] = __($obj->getMenuItem()->getLabel());
    } while ($obj = $obj->getParent());

    return implode(' > ', array_reverse($path));
  }

  public function getBreadcrumbsArray($subItem = null)
  {
    $breadcrumbs = array();
    $obj = $this;

    if ($subItem)
    {
      if (!is_array($subItem))
      {
        $subItem = array((string) $subItem => null);
      }
      $subItem = array_reverse($subItem);
      foreach ($subItem as $key => $value)
      {
        $breadcrumbs[(string) $key] = $value;
      }
    }

    do {
      $menuItem = $obj->getMenuItem();
      $label = __($menuItem->getLabel());
    	$breadcrumbs[$label] = $menuItem->getItemRoute();
    } while ($obj = $obj->getParent());

    return count($breadcrumbs) > 1 ? array_reverse($breadcrumbs):array();
  }

  public function getBreadcrumbs($subItem = null)
  {
    return sfSympalMenuBreadcrumbs::generate($this->getBreadcrumbsArray($subItem));
  }

  public function getMenuItem()
  {
    return $this->_menuItem;
  }

  public function setMenuItem(MenuItem $menuItem)
  {
    $this->_menuItem = $menuItem;

    $this->requiresAuth($menuItem->requires_auth);
    $this->requiresNoAuth($menuItem->requires_no_auth);
    $this->setCredentials($menuItem->getAllPermissions());

    $currentMenuItem = sfSympalToolkit::getCurrentMenuItem();

    if ($currentMenuItem && $currentMenuItem->exists())
    {
      $this->isCurrent($menuItem->id == $currentMenuItem->id);
    }

    $this->setLevel($menuItem->level);
  }

  public function getMenuItemSubMenu(MenuItem $menuItem)
  {
    foreach ($this->_children as $child)
    {
      if ($child->getMenuItem()->id == $menuItem->id && $child->getChildren())
      {
        $result = $child;
      } else if ($n = $child->getMenuItemSubMenu($menuItem)) {
        $result = $n;
      }

      if (isset($result))
      {
        $class = $result->getParent() ? get_class($result->getParent()):get_class($result);
        $instance = new $class($child->getName());
        $instance->setMenuItem($menuItem);
        $instance->setChildren($result->getChildren());

        return $instance;
      }
    }
  }

  public function isCurrentAncestor()
  {
    $menuItem = sfSympalToolkit::getCurrentMenuItem();
    if ($menuItem) {

      $current = $this;
      $children = $current->getChildren();
      do {
        foreach ($children as $child)
        {
          if ($child->getMenuItem() == $menuItem)
          {
            return true;
          } else {
            $current = $child;
          }
        }
      } while ($children = $current->getChildren());
    }

    return false;
  }
}