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
})(jQuery);
