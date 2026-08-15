/**
 * ModeFilter Pro — Elementor Widget Enhancements (v1.0.7)
 * File: admin/elementor-widget-enhancements.js
 * 
 * Adds collapsible sections and better organization to Elementor controls
 */

(function() {
  /**
   * Template kits configuration for Elementor widget
   */
  const templateKits = {
    classic: {
      columns: 3,
      grid_layout: 'grid',
      filter_style: 'chips',
      filter_position: 'left',
      pagination: 'load_more',
    },
    minimal: {
      columns: 2,
      grid_layout: 'grid',
      filter_style: 'checkboxes',
      filter_position: 'left',
      pagination: 'load_more',
    },
    masonry: {
      columns: 4,
      grid_layout: 'masonry',
      masonry_gap: '24',
      filter_style: 'toggles',
      filter_position: 'top',
      pagination: 'load_more',
    },
    hierarchy: {
      columns: 4,
      grid_layout: 'grid',
      filter_style: 'hierarchical',
      filter_position: 'left',
      pagination: 'load_more',
      category_hierarchy: 'yes',
    },
    justified: {
      columns: 5,
      grid_layout: 'justified',
      justified_row_height: '250',
      filter_style: 'radios',
      filter_position: 'left',
      pagination: 'load_more',
    },
    catalog: {
      columns: 3,
      grid_layout: 'grid',
      filter_style: 'chips',
      filter_position: 'left',
      pagination: 'load_more',
      only_catalog: 'yes',
    },
  };

  // Wait for Elementor to be ready
  if (typeof elementor === 'undefined') {
    return;
  }

  elementor.channels.editor.on('document:loaded', function() {
    initElementorEnhancements();
    initTemplateKitSelector();
  });

  /**
   * Initialize template kit selector for Elementor widget
   */
  function initTemplateKitSelector() {
    // Watch for template kit changes in Elementor widgets
    elementor.on('document:loaded', function() {
      watchTemplateKitChange();
    });

    // Also watch any control changes
    if (elementor.hasOwnProperty('channels') && elementor.channels.hasOwnProperty('editor')) {
      elementor.channels.editor.on('elementor:init', function() {
        watchTemplateKitChange();
      });
    }
  }

  /**
   * Watch for template kit control changes
   */
  function watchTemplateKitChange() {
    // Listen to control change events in the editor panel
    if (elementor.hasOwnProperty('settings') && elementor.settings.hasOwnProperty('controls')) {
      document.addEventListener('change', function(e) {
        const control = e.target.closest('[data-setting="modep_template_kit"]');
        if (!control) return;

        const kitId = control.value;
        if ('none' === kitId) return;

        const kit = templateKits[kitId];
        if (!kit) return;

        // Apply template kit settings to widget controls
        applyTemplateKitToWidget(kit);

        // Show feedback
        showTemplateKitFeedback(kitId);
      });
    }
  }

  /**
   * Apply template kit settings to Elementor widget controls
   */
  function applyTemplateKitToWidget(kit) {
    // Get the current selected element in Elementor editor
    const selectedElement = elementor.getPreviewContainer().model;
    if (!selectedElement) return;

    // Apply each setting from the template kit
    for (const key in kit) {
      if (kit.hasOwnProperty(key)) {
        try {
          selectedElement.setSetting(key, kit[key]);
        } catch (e) {
          // Some settings may not exist in this widget, that's okay
          console.debug('Could not set setting:', key);
        }
      }
    }
  }

  /**
   * Show feedback when template kit is applied
   */
  function showTemplateKitFeedback(kitId) {
    const kitNames = {
      classic: 'Classic Grid',
      minimal: 'Minimal',
      masonry: 'Masonry',
      hierarchy: 'Hierarchy Browser',
      justified: 'Justified Gallery',
      catalog: 'Catalog Mode',
    };

    const message = '✓ ' + (kitNames[kitId] || kitId) + ' template applied!';
    const feedback = document.createElement('div');
    feedback.className = 'modep-elementor-feedback';
    feedback.innerHTML = message;

    document.body.appendChild(feedback);

    setTimeout(function() {
      feedback.classList.add('is-visible');
    }, 10);

    setTimeout(function() {
      feedback.classList.remove('is-visible');
      setTimeout(function() {
        feedback.remove();
      }, 300);
    }, 2500);
  }

  /**
   * Enhance filter-related controls with collapsible sections
   */
  function enhanceFilterControls() {
    const filterSections = [
      'modep_section_filters',
      'modep_section_presentation',
      'modep_section_grid',
      'modep_section_catalog',
    ];

    filterSections.forEach(function(sectionId) {
      const $section = document.querySelector(
        '[data-section="' + sectionId + '"]'
      );

      if ($section) {
        addCollapsibleToggle($section);
      }
    });
  }

  /**
   * Add collapsible toggle to a section
   */
  function addCollapsibleToggle($section) {
    const title = $section.querySelector('.elementor-control-section-title');

    if (!title || title.querySelector('.modep-toggle-indicator')) {
      return; // Already enhanced
    }

    const indicator = document.createElement('span');
    indicator.className = 'modep-toggle-indicator';
    indicator.innerHTML = '▼';

    title.insertBefore(indicator, title.firstChild);
    title.style.cursor = 'pointer';

    const controls = $section.querySelector('.elementor-controls-section');

    // Toggle on click
    title.addEventListener('click', function(e) {
      e.preventDefault();

      const isCollapsed = controls.style.display === 'none';

      controls.style.display = isCollapsed ? 'block' : 'none';
      indicator.innerHTML = isCollapsed ? '▼' : '▶';
      indicator.classList.toggle('is-collapsed', !isCollapsed);

      // Save state in sessionStorage
      const sectionKey = 'elementor_section_' + $section.getAttribute('data-section');
      sessionStorage.setItem(sectionKey, isCollapsed ? 'open' : 'closed');
    });

    // Restore state from sessionStorage
    const sectionKey = 'elementor_section_' + $section.getAttribute('data-section');
    const savedState = sessionStorage.getItem(sectionKey);

    if (savedState === 'closed') {
      controls.style.display = 'none';
      indicator.innerHTML = '▶';
      indicator.classList.add('is-collapsed');
    }
  }

  /**
   * Add visual grouping to related controls
   */
  function enhanceControlGrouping() {
    // Group related controls together visually
    const groups = {
      filters: [
        'filters_mode',
        'enabled_filters',
        'terms_limit',
        'terms_orderby',
        'terms_order',
      ],
      presentation: [
        'filter_style',
        'category_hierarchy',
        'filter_position',
        'loader_style',
      ],
      grid: ['grid_layout', 'columns', 'masonry_gap', 'justified_row_height'],
    };

    for (const groupName in groups) {
      const controlIds = groups[groupName];
      let lastControl = null;

      controlIds.forEach(function(controlId) {
        const control = document.querySelector(
          '[data-setting="' + controlId + '"]'
        );

        if (control) {
          // Add group class
          control.classList.add('modep-group-' + groupName);

          // Add spacing
          if (lastControl) {
            control.style.marginTop = '12px';
          }

          lastControl = control;
        }
      });
    }
  }

  /**
   * Enhance template kit selector in Elementor
   */
  function enhanceTemplateKitSelector() {
    const templateControl = document.querySelector(
      '[data-setting="modep_template_kit"]'
    );

    if (!templateControl) {
      return;
    }

    templateControl.classList.add('modep-template-kit-control');

    // Add visual feedback when template is selected
    templateControl
      .querySelectorAll('input[type="radio"]')
      .forEach(function($radio) {
        $radio.addEventListener('change', function() {
          showTemplateKitFeedback(this.value);
        });
      });
  }

  /**
   * Show feedback when template kit is selected
   */
  function showTemplateKitFeedback(kitName) {
    const feedback = document.createElement('div');
    feedback.className = 'modep-template-feedback';
    feedback.innerHTML = '✓ ' + kitName + ' template applied';

    document.body.appendChild(feedback);

    setTimeout(function() {
      feedback.classList.add('is-visible');
    }, 10);

    setTimeout(function() {
      feedback.classList.remove('is-visible');

      setTimeout(function() {
        feedback.remove();
      }, 300);
    }, 2000);
  }

  /**
   * Add styles for enhancements
   */
  function injectStyles() {
    const css = `
      /* Collapsible Indicators */
      .modep-toggle-indicator {
        display: inline-block;
        margin-right: 8px;
        font-size: 12px;
        transition: transform 0.2s ease;
      }

      .modep-toggle-indicator.is-collapsed {
        transform: rotate(-90deg);
      }

      /* Control Grouping */
      .elementor-control-section-modep_section_filters .modep-group-filters,
      .elementor-control-section-modep_section_presentation .modep-group-presentation {
        padding: 12px;
        background: rgba(11, 102, 255, 0.05);
        border-radius: 6px;
        margin-bottom: 8px;
      }

      /* Template Feedback */
      .modep-template-feedback {
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 12px 16px;
        background: #ecfdf3;
        color: #166534;
        border: 1px solid #86efac;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
        z-index: 999999;
      }

      .modep-template-feedback.is-visible {
        opacity: 1;
        transform: translateY(0);
      }

      /* Elementor Feedback */
      .modep-elementor-feedback {
        position: fixed;
        bottom: 60px;
        right: 20px;
        padding: 12px 16px;
        background: #ecfdf3;
        color: #166534;
        border: 1px solid #86efac;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
        z-index: 999999;
      }

      .modep-elementor-feedback.is-visible {
        opacity: 1;
        transform: translateY(0);
      }

      /* Better spacing for sections */
      .elementor-control-section-modep_section_filters,
      .elementor-control-section-modep_section_presentation,
      .elementor-control-section-modep_section_grid,
      .elementor-control-section-modep_section_catalog {
        margin-bottom: 16px;
      }

      .elementor-control-section-modep_section_filters .elementor-control-section-title,
      .elementor-control-section-modep_section_presentation .elementor-control-section-title {
        color: #0b66ff;
        font-weight: 600;
        padding: 10px 12px;
        background: rgba(11, 102, 255, 0.05);
        border-radius: 6px;
        margin-bottom: 8px;
      }
    `;

    const style = document.createElement('style');
    style.textContent = css;
    document.head.appendChild(style);
  }

  // Initialize on load
  injectStyles();
  enhanceControlGrouping();
  enhanceTemplateKitSelector();
})();
