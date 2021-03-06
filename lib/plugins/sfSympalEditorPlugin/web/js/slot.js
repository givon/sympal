(function($) {

$.widget('ui.sympalSlot', {
  
  options: {
    edit_mode: 'popup'
  },
  
  _create: function() {
    this.initialize();
  },
  
  openEditor: function() {
    var self = this;
    
    // disable the non-edit-mode controls
    this._disableControls();
    
    /*
     * 1) Ajax load the contents into the editor element
     * 2) Calls initializeEditor, which creates a slotEditor object
     */
    if (this.option('edit_mode') == 'inline')
    {
      var editorElement = $('.sympal_slot_content', self.element);
      editorElement.load(self.option('edit_url'), function() {
        self._initializeEditor(editorElement);
      });
    }
    else
    {
      $.fancybox(self.option('edit_url'), {
        'zoomSpeedIn': 300,
        'zoomSpeedOut': 300,
        'overlayShow': true,
        'autoDimensions': false,
        'hideOnContentClick': false,
        'type': 'ajax',
        'onComplete': function() {
          self._initializeEditor($('#fancybox-inner'));
          
          // a VERY dirty hack - resize makes TincyMCE sob quietly...
          if (self.option('slotType') != 'TinyMCE')
          {
            // makes the window fit around the internal. This is needed
            // here because it means we've given the ajax'ed content a chance
            // to do its own javascript and resize its internals
            $.fancybox.resize();
          }
        },
        'onCleanup': function() {
          self.closeEditor();
        },
      });
    }
  },
  
  closeEditor: function() {
    var self = this;
    
    if (!this.getEditor())
    {
      return;
    }
    
    // kill the editor
    var editor = this.getEditor();
    this._setOption('slotEditor', null);
    
    $.blockUI();
    
    // ajax in the content, and then set things back up
    $('.sympal_slot_content', self.element).load(
      self.option('view_url'),
      function() {
        // reinitialize the non-edit-mode controls
        self._enableControls();
        
        // make sure fancybox is closed
        $.fancybox.close();
        
        // destroy the editor
        editor.sympalSlotEditor('destroy');
        
        $.unblockUI();
        
        // throw a close event to listen onto
        self.element.trigger('closeEditor');
      }
    );
  },
  
  initialize: function() {
    var self = this;
    
    // register non-edit-handlers: effects for when the slot is not being edited    
    this._initializeControls();
    // attach the nonEditHandler events
    this._enableControls();
    
    // set the edit_url option
    this._setOption('edit_url', $('.sympal_slot_button', this.element).attr('href'));
    
    // enable editing if the slot button is clicked
    $('a.sympal_slot_button', this.element).click(function() {
      self.openEditor()
      return false;
    });
    
    // highlight editable area on edit button hover
    $('a.sympal_slot_button', this.element).hover(function() {
      $('.sympal_slot_content', self.element)
        .css('opacity', .2)
        .children().css('opacity', .2);
    }, function() {
      $('.sympal_slot_content', self.element)
        .css('opacity', 1)
        .children().css('opacity', 1);
    });
  },
  
  _initializeEditor: function(editorSelector) {
    // initializes the editor object on the given selector
    var self = this;
    
    editorSelector.sympalSlotEditor({
      slotType: self.option('slotType')
    });
    
    editorSelector.bind('close', function() {
      self.closeEditor();
    });

    // register the type-change toggle link
    $('.sympal_change_slot_type', editorSelector).click(function() {
      $('.sympal_change_slot_type_dropdown', editorSelector).slideToggle();
    });

    // register the type drop-down change event
    $('.sympal_change_slot_type_dropdown', editorSelector).change(function() {
      // make sure fancybox is closed (will do nothing for inline editing)
      $.fancybox.close();
      
      // the url is matadata on the dropdown wrapper - replace "/select" with selected type
      url = $(this).metadata().url.replace('/replace', '/' + $('select', this).val());

      // post to the url that changes the type, then completely replace the
      // .sympal_slot_wrapper with the content. This effectively kills
      // this widget, but it's necessary because the new type has different
      // metadata. The response itself will reinit the slots and re-open this slot.
      $.blockUI();
      $.post(url, {}, function(data) {
        
        // replace this entire wrapper with the data
        self.element.replaceWith(data);
        
        // unblock the ui
        $.unblockUI();
      });
    });

    // store the slotEditor
    this._setOption('slotEditor', editorSelector);
    
    // throw a close event to listen onto
    self.element.trigger('openEditor');
  },
  
  _initializeControls: function() {
    var self = this;
    
    controlEvents = {};
    controlEvents['dblclick'] = function() {
      self.openEditor()
    }
    controlEvents['mouseover'] = function() {
      $('.sympal_slot_button', self.element).show();
    }
    controlEvents['mouseout'] = function() {
      $('.sympal_slot_button', self.element).hide();
    }
    this._setOption('controlEvents', controlEvents);
  },
  
  _enableControls: function() {
    var self = this;
    
    // bind all of the non-edit-mode handlers
    $.each(this.option('controlEvents'), function(key, value) {
      self.element.bind(key, value);
    });
  },
  
  _disableControls: function() {
    var self = this;
    
    // disable all of the non-edit-mode handlers
    $.each(this.option('controlEvents'), function(key, value) {
      self.element.unbind(key, value);
    });
    
    // make sure the edit button is hidden
    $('.sympal_slot_button', this.element).hide();
  },
  
  getEditor: function(){
    return this.option('slotEditor');
  }
  
});

$.sympal.editor = {

  openEditors: 0

};

$.extend({

  sympalShowInlineEditButtons: function()
  {
    $('.sympal_inline_edit_bar_edit_buttons').show();
  },

  sympalHideInlineEditButtons: function()
  {
    $('.sympal_inline_edit_bar_edit_buttons').hide();
  }

});


})(jQuery);