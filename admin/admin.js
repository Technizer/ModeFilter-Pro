(function ($) {
  "use strict";

  // Copy buttons (dashboard quick shortcodes)
  // Selector updated: .stf-copy -> .modep-copy
  $(document).on("click", ".modep-copy", function (e) {
    e.preventDefault();
    const $btn = $(this);
    const text = $btn.data("copy") || "";

    // Create temporary element
    const el = document.createElement("textarea");
    el.value = text;
    el.setAttribute("readonly", "");
    el.style.position = "absolute";
    el.style.left = "-9999px";
    document.body.appendChild(el);

    // Execute copy
    el.select();
    document.execCommand("copy");
    document.body.removeChild(el);

    // Feedback Loop
    const originalText = $btn.text();
    $btn.text("Copied ✓").addClass("is-copied");

    setTimeout(() => {
      $btn.text(originalText).removeClass("is-copied");
    }, 1200);
  });

  $(document).on("click", ".modep-loader-media-select", function (e) {
    e.preventDefault();
    if (!window.wp || !wp.media) return;
    const target = String($(this).data("target") || "modep_global_loader_image");
    const i18n = (window.MODEP_ADMIN && MODEP_ADMIN.i18n) || {};
    const frame = wp.media({
      title: i18n.choose_loader || "Choose AJAX loader",
      button: { text: i18n.use_loader || "Use this loader" },
      multiple: false,
    });
    frame.on("select", function () {
      const attachment = frame.state().get("selection").first().toJSON();
      $("#" + target).val(attachment.url || "").trigger("change");
    });
    frame.open();
  });

  $(document).on("click", ".modep-loader-media-clear", function (e) {
    e.preventDefault();
    const target = String($(this).data("target") || "modep_global_loader_image");
    $("#" + target).val("").trigger("change");
  });
})(jQuery);
