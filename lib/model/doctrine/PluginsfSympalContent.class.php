<?php

/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
abstract class PluginsfSympalContent extends BasesfSympalContent
{
  protected
    $_allGroupsPermissions,
    $_allEditGroupsPermissions,
    $_route,
    $_routeObject,
    $_mainMenuItem,
    $_editableSlotsExistOnPage = false,
    $_slotsByName = null,
    $_contentRouteObject = null,
    $_updateSearchIndex = true;
  
  /**
   * Initializes a new sfSympalContent for the given type
   * 
   * @param   mixed $type Specify either the name of the content type (e.g. sfSympalPage)
   *                      or pass in a sfSympalContentType object
   * 
   * @return  sfSympalContent
   */
  public static function createNew($type)
  {
    if (is_string($type))
    {
      $typeString = $type;
      $type = Doctrine_Core::getTable('sfSympalContentType')->findOneByName($type);

      if (!$type)
      {
        throw new InvalidArgumentException(sprintf('Could not find Sympal Content Type named "%s"', $typeString));
      }
    }

    if (!$type instanceof sfSympalContentType)
    {
      throw new InvalidArgumentException(sprintf('Invalid Content Type', $type));
    }

    $name = $type->name;

    $content = new sfSympalContent();
    $content->Type = $type;
    $content->$name = new $name();

    return $content;
  }

  public function construct()
  {
    $this->populateSlotsByName();
  }

  public function isPublished()
  {
    return $this->date_published && strtotime($this->date_published) <= time() ? true : false;
  }

  public function populateSlotsByName()
  {
    $this->_slotsByName = array();
    foreach ($this->Slots as $slot)
    {
      $this->_slotsByName[$slot->name] = $slot;
    }
  }

  public function getSlotsByName()
  {
    return $this->_slotsByName;
  }

  public function setEditableSlotsExistOnPage($bool)
  {
    $this->_editableSlotsExistOnPage = $bool;
  }

  public function getEditableSlotsExistOnPage()
  {
    return $this->_editableSlotsExistOnPage;
  }

  public function getModuleToRenderWith()
  {
    if ($module = $this->_get('module'))
    {
      return $module;
    } else {
      return $this->getType()->getModuleToRenderWith();
    }
  }

  public function hasCustomAction()
  {
    return ($this->_get('action') || sfSympalToolkit::moduleAndActionExists($this->getModuleToRenderWith(), $this->getCustomActionName()));
  }

  public function getCustomActionName()
  {
    if ($actionName = $this->_get('action'))
    {
      return $actionName;
    } else {
      return $this->getUnderscoredSlug();
    }
  }

  public function getUnderscoredSlug()
  {
    return str_replace('-', '_', $this->getSlug());
  }

  public function getActionToRenderWith()
  {
    if ($this->hasCustomAction())
    {
      return $this->getCustomActionName();
    } else {
      return $this->getType()->getActionToRenderWith();
    }
  }

  public function hasSlot($name)
  {
    return isset($this->_slotsByName[$name]) ? true : false;
  }

  public function hasSlots()
  {
    return count($this->_slotsByName) > 0 ? true : false;
  }

  public function getSlot($name)
  {
    if ($this->hasSlot($name))
    {
      return $this->_slotsByName[$name];
    }
    return null;
  }

  public function removeSlot(sfSympalContentSlot $slot)
  {
    return Doctrine_Core::getTable('sfSympalContentSlotRef')
      ->createQuery()
      ->delete()
      ->where('content_slot_id = ?', $slot->id)
      ->andWhere('content_id = ?', $this->id)
      ->execute();
  }

  public function addSlot(sfSympalContentSlot $slot)
  {
    $this->removeSlot($slot);

    $contentSlotRef = new sfSympalContentSlotRef();
    $contentSlotRef->content_slot_id = $slot->id;
    $contentSlotRef->content_id = $this->id;
    $contentSlotRef->save();

    $this->_slotsByName[$slot->name] = $slot;

    return $contentSlotRef;
  }
  
  /**
   * Retrieves or creates an sfSympalContentSlot object with the given
   * name for this sfSympalContent object
   * 
   * @return sfSympalContentSlot
   */
  public function getOrCreateSlot($name, $options = array())
  {
    $type = isset($options['type']) ? $options['type'] : null;
    
    if (!$hasSlot = $this->hasSlot($name))
    {
      $isColumn = $this->hasField($name) ? true : false;
      $type = $type ? $type : 'Text';
      
      if (!$isColumn && $type == 'Column')
      {
        throw new sfException('Cannot set a non-column slot to type "Column"');
      }

      $slot = new sfSympalContentSlot();
      $slot->setContentRenderedFor($this);
      $slot->is_column = $isColumn;

      $slot->name = $name;
      $slot->type = $type;
      if (isset($options['default_value']))
      {
        $slot->value = $options['default_value'];
      }
      $slot->save();

      $this->addSlot($slot);
    } else {
      $slot = $this->getSlot($name);
    }

    if ($type != null && $slot->type != $type)
    {
      $slot->type = $type;
      $slot->save();
    }

    $slot->setContentRenderedFor($this);

    return $slot;
  }

  public function hasField($name)
  {
    $result = $this->_table->hasField($name);
    if (!$result)
    {
      $className = get_class($this);
      if (sfSympalConfig::isI18nEnabled($className))
      {
        $table = Doctrine_Core::getTable($className.'Translation');
        if ($table->hasField($name))
        {
          $result = true;
        }
      }
    }
    if (!$result)
    {
      $className = $this->getType()->getName();
      $table = Doctrine_Core::getTable($className);
      if ($table->hasField($name))
      {
        $result = true;
      }
      if (sfSympalConfig::isI18nEnabled($className))
      {
        $table = Doctrine_Core::getTable($className.'Translation');
        if ($table->hasField($name))
        {
          $result = true;
        }
      }
    }
    return $result;
  }

  public function getUrl($options = array())
  {
    return sfContext::getInstance()->getController()->genUrl($this->getRoute(), $options);
  }

  public function getPubDate()
  {
    return strtotime($this->date_published);
  }

  public function getContentTypeClassName()
  {
    return $this->getType()->getName();
  }

  public function getAllEditPermissions()
  {
    return $this->getAllPermissions('EditGroups');
  }

  public function getAllPermissions($key = 'Groups')
  {
    $cacheKey = sprintf('_all%sPermissions', $key);
    if (!$this->$cacheKey)
    {
      $this->$cacheKey = array();
      foreach ($this->$key as $group)
      {
        foreach ($group->Permissions as $permission)
        {
          $this->{$cacheKey}[] = $permission->name;
        }
      }
    }
    return $this->$cacheKey;
  }

  public function __toString()
  {
    return $this->getHeaderTitle();
  }

  public function getIndented()
  {
    $menuItem = $this->getMenuItem();
    if ($menuItem)
    {
      return str_repeat('-', $menuItem->getLevel()).' '.(string) $this;
    } else {
      return (string) $this;
    }
  }

  public function getTitle()
  {
    return $this->getHeaderTitle();
  }

  public function getRelatedMenuItem()
  {
    $menuItem = $this->_get('MenuItem');
    if ($menuItem && $menuItem->exists())
    {
      $this->_mainMenuItem = $menuItem;
    }
    return $this->_mainMenuItem;
  }

  public function getRecord()
  {
    if ($this['Type']['name'])
    {
      Doctrine_Core::initializeModels(array($this['Type']['name']));
      return $this[$this['Type']['name']];
    } else {
      return false;
    }
  }

  public function publish()
  {
    if ($this->relatedExists('MenuItem'))
    {
      $menu = $this->getMenuItem();
      $menu->publish();
    }
    $this->date_published = new Doctrine_Expression('NOW()');
    $this->save();
    $this->refresh();
  }

  public function unpublish()
  {
    if ($this->relatedExists('MenuItem'))
    {
      $menu = $this->getMenuItem();
      $menu->unpublish();
    }
    $this->date_published = null;
    $this->save();
  }

  public function getHeaderTitle()
  {
    if ($record = $this->getRecord())
    {
      $guesses = array('name',
                       'title',
                       'username',
                       'subject');

      // we try to guess a column which would give a good description of the object
      foreach ($guesses as $descriptionColumn)
      {
        try
        {
          return (string) $record->get($descriptionColumn);
        } catch (Exception $e) {}
      }
      return (string) $this;
    }

    return sprintf('No description for object of class "%s"', $this->getTable()->getComponentName());
  }

  public function getEditRoute()
  {
    if ($this->exists())
    {
      return '@sympal_content_edit?id='.$this['id'];
    } else {
      throw new sfException('You cannot get the edit route of a object that does not exist.');
    }
  }

  public function getFeedDescriptionPotentialSlots()
  {
    return array(
      'body'
    );
  }

  public function getFeedDescription()
  {
    if (method_exists($this->getContentTypeClassName(), 'getFeedDescription'))
    {
      return $this->getRecord()->getFeedDescription();
    }

    $slot = false;
    foreach ($this->getFeedDescriptionPotentialSlots() as $slotName)
    {
      if ($this->hasSlot($slotName))
      {
        $slot = $this->getSlot($slotName);
        break;
      }
    }

    if ($slot === false && $this->Slots->count() > 0)
    {
      $slot = $this->Slots->getFirst();
    }

    if ($slot instanceof sfSympalContentSlot)
    {
      $slot->setContentRenderedFor($this);

      return $slot->render();
    } else {
      return (string) $this;
    }
  }

  public function getTeaser()
  {
    use_helper('Text');
    return truncate_text(strip_tags($this->getFeedDescription()), 200);
  }

  public function getFormatData($format)
  {
    $method = 'get'.ucfirst($format).'FormatData';
    if (method_exists($this->getContentTypeClassName(), $method))
    {
      return $this->getRecord()->$method();
    } else if (method_exists($this, $method)) {
      $data = $this->$method();
    } else {
      $data = $this->getDefaultFormatData();
    }
    return Doctrine_Parser::dump($this->$method(), $format);
  }

  public function getDefaultFormatData()
  {
    $data = $this->toArray(true);
    unset(
      $data['MenuItem']['__children'],
      $data['MenuItem']['Groups'],
      $data['Groups'],
      $data['Links'],
      $data['Assets'],
      $data['CreatedBy'],
      $data['Site']
    );
    return $data;
  }

  public function getXmlFormatData()
  {
    return $this->getDefaultFormatData();
  }

  public function getYmlFormatData()
  {
    return $this->getDefaultFormatData();
  }

  public function getJsonFormatData()
  {
    return $this->getDefaultFormatData();
  }

  public function getIsPublished()
  {
    return ($this->getDatePublished() && strtotime($this->getDatePublished()) <= time()) ? true : false;
  }

  public function getIsPublishedInTheFuture()
  {
    return ($this->getDatePublished() && strtotime($this->getDatePublished()) > time()) ? true : false;
  }

  public function getMonthPublished($format = 'm')
  {
    return date('m', strtotime($this->getDatePublished()));
  }

  public function getDayPublished()
  {
    return date('d', strtotime($this->getDatePublished()));
  }

  public function getYearPublished()
  {
    return date('Y', strtotime($this->getDatePublished()));
  }

  public function getAuthorName()
  {
    return $this->getCreatedById() ? $this->getCreatedBy()->getName() : null;
  }

  public function getAuthorEmail()
  {
    return $this->getCreatedById() ? $this->getCreatedBy()->getEmailAddress() : null;
  }

  public function getUniqueId()
  {
    return $this->getId().'-'.$this->getSlug();
  }

  public function hasCustomPath()
  {
    return $this->custom_path ? true : false;
  }

  public function getContentRouteObject()
  {
    if (!$this->_contentRouteObject)
    {
      $this->_contentRouteObject = new sfSympalContentRouteObject($this);
    }
    return $this->_contentRouteObject;
  }

  public function getRoute()
  {
    return $this->getContentRouteObject()->getRoute();
  }

  public function getRoutePath()
  {
    return $this->getContentRouteObject()->getRoutePath();
  }

  public function getRouteName()
  {
    return $this->getContentRouteObject()->getRouteName();
  }

  public function getRouteObject()
  {
    return $this->getContentRouteObject()->getRouteObject();
  }

  public function getEvaluatedRoutePath()
  {
    return $this->getContentRouteObject()->getEvaluatedRoutePath();
  }

  public function trySettingTitleProperty($value)
  {
    foreach (array('title', 'name', 'subject', 'header') as $name)
    {
      try {
        $this->$name = $value;
      } catch (Exception $e) {}
    }
  }

  public function addLink(sfSympalContent $content)
  {
    $link = new sfSympalContentLink();
    $link->content_id = $this->id;
    $link->linked_content_id = $content->id;
    $link->save();

    return $link;
  }

  public function addAsset(sfSympalAsset $asset)
  {
    $contentAsset = new sfSympalContentAsset();
    $contentAsset->content_id = $this->id;
    $contentAsset->asset_id = $asset->id;
    $contentAsset->save();

    return $contentAsset;
  }

  public function deleteLinkAndAssetReferences()
  {
    $this->deleteAssetReferences();
    $this->deleteLinkReferences();
  }

  public function deleteAssetReferences()
  {
    $count = Doctrine_Query::create()
      ->delete('sfSympalContentLink')
      ->where('content_id = ?', $this->getId())
      ->execute();
    return $count;
  }

  public function deleteLinkReferences()
  {
    $count = Doctrine_Query::create()
      ->delete('sfSympalContentAsset')
      ->where('content_id = ?', $this->getId())
      ->execute();
    return $count;
  }

  public function postInsert($event)
  {
    $event->getInvoker()->deleteLinkAndAssetReferences();
  }

  public function postUpdate($event)
  {
    $event->getInvoker()->deleteLinkAndAssetReferences();
  }

  public static function slugBuilder($text, $content)
  {
    if ($record = $content->getRecord())
    {
      try {
        return $record->slugBuilder($text);
      } catch (Doctrine_Record_UnknownPropertyException $e) {
        return Doctrine_Inflector::urlize($text);
      }
    } else {
      return Doctrine_Inflector::urlize($text);
    }
  }

  public function getTemplateToRenderWith()
  {
    if (!$template = $this->getTemplate())
    {
      $template = $this->getType()->getTemplate();
    }
    $templates = sfSympalConfiguration::getActive()->getContentTemplates($this->getType()->getSlug());
    if (isset($templates[$template]))
    {
      $template = $templates[$template]['template'];
    }
    $template = $template ? $template : sfSympalConfig::get($this->getType()->getSlug(), 'default_content_template', sfSympalConfig::get('default_content_template'));
    return $template;
  }

  public function getThemeToRenderWith()
  {
    if ($theme = $this->getTheme()) {
      return $theme;
    } else if ($theme = $this->getType()->getTheme()) {
      return $theme;
    } else if ($theme = $this->getSite()->getTheme()) {
      return $theme;
    } else {
      return sfSympalConfig::get($this->getType()->getSlug(), 'default_theme', sfSympalConfig::get('default_theme', null, $this->getSite()->getSlug()));
    }
  }

  public function getSiteId()
  {
    return $this->_get('site_id');
  }

  public function getContentTypeId()
  {
    return $this->_get('content_type_id');
  }

  public function getLastUpdatedBy()
  {
    return $this->_get('LastUpdatedBy');
  }

  public function getLastUpdatedById()
  {
    return $this->_get('last_updated_by_id');
  }

  public function getCreatedBy()
  {
    return $this->_get('CreatedBy');
  }

  public function getCreatedById()
  {
    return $this->_get('created_by_id');
  }

  public function getDatePublished()
  {
    return $this->_get('date_published');
  }

  public function getCustomPath()
  {
    return $this->_get('custom_path');
  }

  public function getPageTitle()
  {
    return $this->_get('page_title');
  }

  public function getMetaKeywords()
  {
    return $this->_get('meta_keywords');
  }

  public function getMetaDescription()
  {
    return $this->_get('meta_description');
  }

  public function getI18nSlug()
  {
    return $this->_get('i18n_slug');
  }

  public function getSite()
  {
    return $this->_get('Site');
  }

  public function getType()
  {
    return $this->_get('Type');
  }

  public function getGroups()
  {
    return $this->_get('Groups');
  }

  public function getPermissions()
  {
    return $this->_get('Permissions');
  }

  public function getMenuItem()
  {
    return $this->_get('MenuItem');
  }

  public function getSlots()
  {
    return $this->_get('Slots');
  }

  public function getContentGroups()
  {
    return $this->_get('ContentGroups');
  }

  public function disableSearchIndexUpdateForSave()
  {
    $this->_updateSearchIndex = false;
  }

  public function save(Doctrine_Connection $conn = null)
  {
    $result = parent::save($conn);

    if ($this->_updateSearchIndex)
    {
      sfSympalSearch::getInstance()->updateSearchIndex($this);
    }

    $this->_updateSearchIndex = true;

    return $result;
  }

  public function delete(Doctrine_Connection $conn = null)
  {
    if ($this->_updateSearchIndex)
    {
      $index = sfSympalSearch::getInstance()->getIndex();
      foreach ($index->find('pk:'.$this->getId()) as $hit)
      {
        $index->delete($hit->id);
      }
    }
    return parent::delete($conn);
  }

  public function getSearchData()
  {
    $searchData = array();
    $clone = clone $this;
    $data = $clone->toArray(false);
    if ($data)
    {
      foreach ($data as $key => $value)
      {
        if (is_scalar($value))
        {
          $searchData[$key] = $value;
        }
      }
    }
    $data = $clone->getRecord()->toArray(false);
    if ($data)
    {
      foreach ($data as $key => $value)
      {
        if (is_scalar($value))
        {
          $searchData[$key] = $value;
        }
      }
    }
    foreach ($this->getSlots() as $slot)
    {
      $slot->setContentRenderedFor($this);
      $searchData[$slot->getName()] = $slot->getValue();
    }
    return $searchData;
  }
  
  /**
   * Used by sfSympalContentSlot to render the created_at_id slot value
   * 
   * @see sfSympalContentSlot::getValueForRendering()
   * @return string
   */
  public function getCreatedByIdSlotValue(sfSympalContentSlot $slot)
  {
    return $this->created_by_id ? $this->CreatedBy->username : 'nobody';
  }
  
  /**
   * Used by sfSympalContentSlot to render the date_published slot value
   * 
   * @see sfSympalContentSlot::getValueForRendering()
   * @return string
   */
  public function getDatePublishedSlotValue(sfSympalContentSlot $slot)
  {
    if ($this->date_published)
    {
      sfSympalToolkit::loadHelpers('Date');
      return format_datetime($this->date_published, sfSympalConfig::get('date_published_format'));
    } else {
      return 'unpublished';
    }
  }
}