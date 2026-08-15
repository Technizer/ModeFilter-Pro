/**
 * ModeFilter Pro — Admin Template Kits & Live Preview
 * File: admin/admin-template-kits.js
 */

(function($) {
  'use strict';

  const templateKits = {
    classic: {
      name: 'Classic Grid',
      columns: 3,
      grid_layout: 'grid',
      preset: 'normal',
      filter_style: 'chips',
      filter_position: 'left',
      filters_mode: 'auto',
      pagination: 'load_more',
      loader_style: 'skeleton',
      only_catalog: 'no',
      link_whole_card: 'no',
      terms_limit: 10,
      terms_orderby: 'count',
      terms_order: 'DESC',
      category_hierarchy: 'no',
    },
    minimal: {
      name: 'Minimal (2 Column)',
      columns: 2,
      grid_layout: 'grid',
      preset: 'minimal',
      filter_style: 'checkboxes',
      filter_position: 'left',
      filters_mode: 'auto',
      pagination: 'numbers',
      loader_style: 'dots',
      only_catalog: 'no',
      terms_limit: 8,
      terms_orderby: 'name',
      terms_order: 'ASC',
      category_hierarchy: 'no',
      link_whole_card: 'yes',
    },
    masonry: {
      name: 'Masonry',
      columns: 4,
      grid_layout: 'masonry',
      masonry_gap: '28',
      preset: 'normal',
      filter_style: 'toggles',
      filter_position: 'top',
      filters_mode: 'auto',
      pagination: 'load_more',
      loader_style: 'pulse',
      only_catalog: 'no',
      link_whole_card: 'no',
      terms_limit: 12,
      terms_orderby: 'count',
      terms_order: 'DESC',
      category_hierarchy: 'no',
    },
    hierarchy: {
      name: 'Hierarchy Browser',
      columns: 3,
      grid_layout: 'grid',
      preset: 'normal',
      filter_style: 'hierarchical',
      filter_position: 'left',
      filters_mode: 'auto',
      pagination: 'numbers',
      loader_style: 'skeleton',
      only_catalog: 'no',
      link_whole_card: 'no',
      terms_limit: 18,
      terms_orderby: 'name',
      terms_order: 'ASC',
      category_hierarchy: 'yes',
    },
    justified: {
      name: 'Justified Gallery',
      columns: 4,
      grid_layout: 'justified',
      justified_row_height: '238',
      preset: 'overlay',
      filter_style: 'radios',
      filter_position: 'top',
      filters_mode: 'auto',
      pagination: 'load_more',
      loader_style: 'dots',
      only_catalog: 'no',
      terms_limit: 10,
      terms_orderby: 'name',
      terms_order: 'ASC',
      category_hierarchy: 'no',
      link_whole_card: 'yes',
    },
    catalog: {
      name: 'Catalog Mode',
      columns: 2,
      grid_layout: 'grid',
      preset: 'normal',
      filter_style: 'chips',
      filter_position: 'right',
      filters_mode: 'auto',
      pagination: 'numbers',
      loader_style: 'skeleton',
      terms_limit: 9,
      terms_orderby: 'name',
      terms_order: 'ASC',
      category_hierarchy: 'no',
      only_catalog: 'yes',
      link_whole_card: 'no',
    },
  };

  const builderFieldMap = {
    columns: 'modep_sc_columns',
    preset: 'modep_sc_preset',
    pagination: 'modep_sc_pagination',
    filter_style: 'modep_sc_filter_style',
    filter_position: 'modep_sc_pos',
    filters_mode: 'modep_sc_filters_mode',
    category_hierarchy: 'modep_sc_category_hierarchy',
    loader_style: 'modep_sc_loader_style',
    terms_limit: 'modep_sc_terms_limit',
    terms_orderby: 'modep_sc_terms_orderby',
    terms_order: 'modep_sc_terms_order',
    grid_layout: 'modep_sc_grid_layout',
    masonry_gap: 'modep_sc_masonry_gap',
    justified_row_height: 'modep_sc_justified_row_height',
    only_catalog: 'modep_sc_only_catalog',
    link_whole_card: 'modep_sc_link_whole_card',
  };

  function applyKitToBuilder(kitId) {
    const kit = templateKits[kitId];
    const $builder = $('#modep-shortcode-builder');
    if (!kit || !$builder.length) return false;

    Object.keys(builderFieldMap).forEach(function(key) {
      if (!Object.prototype.hasOwnProperty.call(kit, key)) return;
      const field = document.getElementById(builderFieldMap[key]);
      if (!field) return;
      field.value = String(kit[key]);
      field.dispatchEvent(new Event('change', { bubbles: true }));
    });

    $builder.attr('data-selected-kit', kitId);
    const buildButton = document.getElementById('modep_sc_build');
    if (buildButton) {
      // The builder registers its native DOMContentLoaded listener after this
      // script. Queue the click so the initial kit also generates its shortcode.
      window.setTimeout(function() {
        buildButton.click();
      }, 0);
    }
    return true;
  }

  /**
   * Initialize template kit selector
   */
  function initTemplateKits() {
    $(document).on('change', '.modep-kit-radio', function() {
      const kitId = $(this).val();
      const kit = templateKits[kitId];

      if (!kit) return;

      // Animate the card selection
      $('.modep-kit-option').removeClass('is-selected');
      $(this).closest('.modep-kit-option').addClass('is-selected');

      if (!applyKitToBuilder(kitId)) {
        const builderUrl = $(this).closest('.modep-kit-selector').data('builder-url');
        if (builderUrl) {
          window.location.href = builderUrl + '&kit=' + encodeURIComponent(kitId);
          return;
        }
      }

      // Show feedback
      showFeedback(kit.name + ' template loaded!', 'success');

      // Optional: scroll to first form field
      setTimeout(function() {
        $('html, body').animate(
          { scrollTop: $('#modep_sc_columns').offset().top - 100 },
          500
        );
      }, 300);
    });
  }

  /**
   * Show temporary feedback message
   */
  function showFeedback(message, type = 'info') {
    const classes = 'modep-feedback modep-feedback--' + type;
    const $feedback = $('<div class="' + classes + '">' + message + '</div>');

    $('body').prepend($feedback);

    setTimeout(function() {
      $feedback.addClass('is-shown');
    }, 10);

    setTimeout(function() {
      $feedback.removeClass('is-shown');
      setTimeout(function() {
        $feedback.remove();
      }, 300);
    }, 3000);
  }

  /**
   * Enhance shortcode builder with template preview
   */
  function initShortcodeBuilder() {
    const $builder = $('#modep-shortcode-builder');
    if (!$builder.length) return;

    // Show/hide grid layout options based on preset
    $(document).on('change', '#modep_sc_grid_layout', function() {
      const layout = $(this).val();

      // Show/hide masonry gap
      $('#modep_masonry_gap_field').toggle(layout === 'masonry');

      // Show/hide justified row height
      $('#modep_justified_row_height_field').toggle(layout === 'justified');

      showFeedback('Grid layout changed to: ' + layout, 'info');
    });

    // Live shortcode preview
    const previewShortcode = function() {
      const attrs = getBuilderValues();
      const sc = buildShortcode(attrs);
      updateShortcodePreview(sc);
    };

    // Bind to all form changes
    $builder.find('input, select, textarea').on('change', previewShortcode);

    // Initial preview
    previewShortcode();
  }

  /**
   * Get all builder form values
   */
  function getBuilderValues() {
    const attrs = {};
    const $form = $('#modep-shortcode-builder');

    $form.find('input, select, textarea').each(function() {
      const $el = $(this);
      const name = $el.attr('id');

      if (!name || name.indexOf('modep_sc_') !== 0) return;

      const key = name.replace('modep_sc_', '');
      const value = $el.val();

      if (value && value !== '') {
        attrs[key] = value;
      }
    });

    return attrs;
  }

  /**
   * Build shortcode from attributes
   */
  function buildShortcode(attrs) {
    let sc = '[modep_filters';

    for (const key in attrs) {
      if (attrs.hasOwnProperty(key)) {
        sc += ' ' + key + '="' + String(attrs[key]).replace(/"/g, '&quot;') + '"';
      }
    }

    sc += ']';
    return sc;
  }

  /**
   * Update shortcode preview area
   */
  function updateShortcodePreview(sc) {
    const $preview = $('#modep-shortcode-preview');

    if (!$preview.length) return;

    $preview.find('code').text(sc);

    // Add copy button
    if ($preview.find('.modep-copy-btn').length === 0) {
      $preview.append(
        '<button type="button" class="button modep-copy-btn" data-copy="' +
          sc.replace(/"/g, '&quot;') +
          '">Copy Shortcode</button>'
      );
    } else {
      $preview.find('.modep-copy-btn').attr('data-copy', sc);
    }
  }

  /**
   * Copy to clipboard functionality
   */
  function initCopyButtons() {
    $(document).on('click', '.modep-copy, .modep-copy-btn', function(e) {
      e.preventDefault();

      const text = $(this).attr('data-copy') || $(this).text();
      const $btn = $(this);

      // Fallback for older browsers
      if (!navigator.clipboard) {
        copyToClipboardFallback(text);
        showFeedback('Copied to clipboard!', 'success');
        return;
      }

      navigator.clipboard.writeText(text).then(function() {
        const originalText = $btn.text();
        $btn.text('✓ Copied!').prop('disabled', true);

        setTimeout(function() {
          $btn.text(originalText).prop('disabled', false);
        }, 2000);

        showFeedback('Copied to clipboard!', 'success');
      });
    });
  }

  /**
   * Fallback copy for older browsers
   */
  function copyToClipboardFallback(text) {
    const $textarea = $('<textarea>').val(text).css({
      position: 'fixed',
      opacity: 0,
    });
    $('body').append($textarea);
    $textarea[0].select();
    document.execCommand('copy');
    $textarea.remove();
  }

  /**
   * Initialize filter customization color picker
   */
  function initColorPickers() {
    // If WordPress color picker is available
    if ($.wp && $.wp.wpColorPicker) {
      $('.modep-color-picker').wpColorPicker({
        palettes: [
          '#0b66ff',
          '#059669',
          '#f59e0b',
          '#dc2626',
          '#8b5cf6',
          '#06b6d4',
          '#ec4899',
        ],
      });
    }
  }

  /**
   * Show/hide advanced options
   */
  function initAdvancedToggle() {
    $(document).on('click', '.modep-toggle-advanced', function(e) {
      e.preventDefault();

      const $section = $(this).closest('.modep-form-section');
      const $advanced = $section.find('.modep-advanced-options');

      $advanced.slideToggle(300, function() {
        const isOpen = $(this).is(':visible');
        $(this).closest('.modep-form-section').toggleClass('is-expanded', isOpen);
      });

      $(this).toggleClass('is-open');
    });
  }

  /**
   * Admin onboarding tour for v1.0.7
   */
  function initOnboardingTour() {
    // Check if tour should be shown
    const showTour = localStorage.getItem('modep_v107_tour_shown') !== 'true';

    if (!showTour) return;

    // Only show on dashboard
    if (window.location.search.indexOf('page=modefilter-pro') === -1) return;

    setTimeout(function() {
      showOnboardingModal();
    }, 500);
  }

  /**
   * Show onboarding modal
   */
  function showOnboardingModal() {
    const html = `
      <div class="modep-modal-overlay modep-onboarding-modal">
        <div class="modep-modal-content">
          <button type="button" class="modep-modal-close" aria-label="Close">&times;</button>
          
          <h2>🎉 Welcome to ModeFilter Pro v1.0.7!</h2>
          
          <div class="modep-onboarding-steps">
            <div class="modep-step">
              <div class="modep-step-icon">🎨</div>
              <h3>6 New Template Kits</h3>
              <p>Pre-configured grid + filter layouts. Click one to auto-configure your shortcode.</p>
            </div>
            
            <div class="modep-step">
              <div class="modep-step-icon">✨</div>
              <h3>All Filter Styles Working</h3>
              <p>Chips, Checkboxes, Radio Buttons, Toggle Switches, and Hierarchical Trees now render perfectly.</p>
            </div>
            
            <div class="modep-step">
              <div class="modep-step-icon">🎯</div>
              <h3>Modern Admin Interface</h3>
              <p>New design system, better forms, and improved layout throughout.</p>
            </div>
          </div>
          
          <div class="modep-onboarding-footer">
            <label class="modep-checkbox">
              <input type="checkbox" id="modep_dont_show_again" />
              <span>Don't show this again</span>
            </label>
            <button type="button" class="button button-primary modep-onboarding-close">
              Get Started
            </button>
          </div>
        </div>
      </div>
    `;

    $('body').append(html);

    // Close handlers
    $(document).on('click', '.modep-modal-close, .modep-onboarding-close', function() {
      closeOnboardingModal();
    });

    $(document).on('change', '#modep_dont_show_again', function() {
      if ($(this).is(':checked')) {
        localStorage.setItem('modep_v107_tour_shown', 'true');
      }
    });

    // Animate in
    setTimeout(function() {
      $('.modep-onboarding-modal').addClass('is-visible');
    }, 10);
  }

  /**
   * Close onboarding modal
   */
  function closeOnboardingModal() {
    const $modal = $('.modep-onboarding-modal');

    $modal.removeClass('is-visible');

    setTimeout(function() {
      $modal.remove();

      if ($('#modep_dont_show_again').is(':checked')) {
        localStorage.setItem('modep_v107_tour_shown', 'true');
      }
    }, 300);
  }

  /**
   * Initialize all on document ready
   */
  $(document).ready(function() {
    initTemplateKits();
    initShortcodeBuilder();
    initCopyButtons();
    initColorPickers();
    initAdvancedToggle();
    initOnboardingTour();

    // Add feedback animation styles
    addFeedbackStyles();

    const $builder = $('#modep-shortcode-builder');
    if ($builder.length) {
      const initialKit = String($builder.data('selected-kit') || 'classic');
      applyKitToBuilder(initialKit);
    }
  });

  /**
   * Add dynamic feedback and modal styles
   */
  function addFeedbackStyles() {
    const css = `
      /* Feedback Messages */
      .modep-feedback {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 14px 18px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 13px;
        z-index: 999999;
        opacity: 0;
        transform: translateY(-20px);
        transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
      }

      .modep-feedback.is-shown {
        opacity: 1;
        transform: translateY(0);
      }

      .modep-feedback--success {
        background: #ecfdf3;
        color: #166534;
        border: 1px solid #86efac;
      }

      .modep-feedback--info {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #93c5fd;
      }

      .modep-feedback--error {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
      }

      /* Onboarding Modal */
      .modep-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999999;
        opacity: 0;
        transition: opacity 0.3s ease;
      }

      .modep-modal-overlay.is-visible {
        opacity: 1;
      }

      .modep-modal-content {
        background: white;
        border-radius: 12px;
        padding: 40px;
        max-width: 600px;
        width: 90%;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        position: relative;
        animation: slideUp 0.3s cubic-bezier(0.23, 1, 0.32, 1);
      }

      @keyframes slideUp {
        from {
          opacity: 0;
          transform: translateY(30px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      .modep-modal-close {
        position: absolute;
        top: 16px;
        right: 16px;
        width: 32px;
        height: 32px;
        padding: 0;
        border: none;
        background: transparent;
        font-size: 24px;
        color: #9ca3af;
        cursor: pointer;
        transition: color 0.2s ease;
      }

      .modep-modal-close:hover {
        color: #111827;
      }

      .modep-modal-content h2 {
        margin: 0 0 24px;
        font-size: 24px;
        font-weight: 700;
        color: #111827;
      }

      .modep-onboarding-steps {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
      }

      .modep-step {
        text-align: center;
      }

      .modep-step-icon {
        font-size: 32px;
        margin-bottom: 8px;
      }

      .modep-step h3 {
        margin: 8px 0 6px;
        font-size: 14px;
        font-weight: 600;
        color: #111827;
      }

      .modep-step p {
        margin: 0;
        font-size: 12px;
        color: #6b7280;
        line-height: 1.5;
      }

      .modep-onboarding-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
      }

      .modep-onboarding-footer .modep-checkbox {
        margin: 0;
      }

      /* Advanced toggle */
      .modep-toggle-advanced {
        color: #0b66ff;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: color 0.2s ease;
      }

      .modep-toggle-advanced:hover {
        color: #0951d8;
      }

      .modep-toggle-advanced.is-open::before {
        content: '▼ ';
      }

      .modep-toggle-advanced:not(.is-open)::before {
        content: '▶ ';
      }

      .modep-advanced-options {
        display: none;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #e5e7eb;
      }

      /* Shortcode preview */
      #modep-shortcode-preview {
        background: #f3f4f6;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 12px;
        margin-top: 12px;
        font-family: 'Monaco', 'Courier New', monospace;
        font-size: 12px;
        overflow-x: auto;
        word-break: break-all;
      }

      #modep-shortcode-preview code {
        color: #1f2937;
      }

      .modep-copy-btn {
        margin-top: 8px !important;
      }

      @media (max-width: 768px) {
        .modep-modal-content {
          padding: 24px;
        }

        .modep-onboarding-steps {
          grid-template-columns: 1fr;
        }

        .modep-feedback {
          left: 20px;
          right: 20px;
        }
      }
    `;

    $('<style>').text(css).appendTo('head');
  }
})(jQuery);
