jQuery(function ($) {
  "use strict";

  if (typeof MODEP_Enquiry === "undefined") return;

  function openBuiltinModal() {
    // #mfil-enquiry-modal -> #modep-enquiry-modal
    var $modal = $("#modep-enquiry-modal");
    if (!$modal.length) return;

    $modal.show().attr("aria-hidden", "false");
    // mfil-enquiry-open -> modep-enquiry-open
    $("body").addClass("modep-enquiry-open");
  }

  function closeBuiltinModal() {
    // #mfil-enquiry-modal -> #modep-enquiry-modal
    var $modal = $("#modep-enquiry-modal");
    if (!$modal.length) return;

    $modal.hide().attr("aria-hidden", "true");
    // mfil-enquiry-open -> modep-enquiry-open
    $("body").removeClass("modep-enquiry-open");
  }

  // Click handler for Enquire buttons
  // .mfil-enquire -> .modep-enquire
  $(document).on("click", ".modep-enquire", function (e) {
    var action = MODEP_Enquiry.action || "popup_builtin";

    if (action === "popup_builtin") {
      e.preventDefault();
      if (MODEP_Enquiry.hasShortcodeForm) {
        openBuiltinModal();
      }
      return;
    }

    if (action === "popup_elementor") {
      e.preventDefault();
      var popupId = parseInt(MODEP_Enquiry.elementorPopupId || 0, 10);
      if (
        popupId &&
        window.elementorProFrontend &&
        elementorProFrontend.modules &&
        elementorProFrontend.modules.popup
      ) {
        try {
          elementorProFrontend.modules.popup.showPopup({ id: popupId });
        } catch (err) {
          console && console.error(err);
        }
      }
      return;
    }

    if (action === "redirect_page" || action === "redirect_url") {
      // Let browser follow href; if settings changed, they’re already reflected in href
      var redirectUrl = MODEP_Enquiry.redirectUrl || $(this).attr("href");
      if (redirectUrl) {
        // allow default navigation
        $(this).attr("href", redirectUrl);
      }
      return;
    }
  });

  // Modal close handlers
  $(document).on(
    "click",
    // .mfil-enquiry-close -> .modep-enquiry-close, .mfil-enquiry-backdrop -> .modep-enquiry-backdrop
    ".modep-enquiry-close, .modep-enquiry-backdrop",
    function (e) {
      e.preventDefault();
      closeBuiltinModal();
    }
  );

  $(document).on("keyup", function (e) {
    if (e.key === "Escape") {
      closeBuiltinModal();
    }
  });
});