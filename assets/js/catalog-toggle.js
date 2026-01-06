jQuery(function ($) {
  "use strict";
  if (typeof MODEPCatalogToggle === "undefined") return;

  function ensureToastContainer() {
    var $c = $("#modep-toast-container");
    if ($c.length) return $c;

    $("body").append(
      '<div id="modep-toast-container" aria-live="polite" aria-atomic="true"></div>'
    );
    return $("#modep-toast-container");
  }

  function initHelpTips(context) {
    var $ctx = context ? $(context) : $(document);
    var $tips = $ctx.find(".woocommerce-help-tip");
    if (!$tips.length || !$.fn.tipTip) return;

    $tips.tipTip({
      attribute: "data-tip",
      fadeIn: 50,
      fadeOut: 50,
      delay: 0,
    });
  }

  initHelpTips(document);

  function showToast(message, mode) {
    var $container = ensureToastContainer();

    var $toast = $(
      '<div class="modep-toast modep-toast-' +
        mode +
        '" role="status">' +
        '<div class="modep-toast__title">ModeFilter Pro</div>' +
        '<div class="modep-toast__msg"></div>' +
        "</div>"
    );

    $toast.find(".modep-toast__msg").text(message);
    $container.append($toast);

    requestAnimationFrame(function () {
      $toast.addClass("is-active");
    });

    setTimeout(function () {
      $toast.removeClass("is-active");
      setTimeout(function () {
        $toast.remove();
      }, 250);
    }, 2600);
  }

  function nextMode(current) {
    // two-state
    return current === "catalog" ? "sell" : "catalog";
  }

  $(document).on("click", ".modep-mode-badge[data-modep-toggle]", function (e) {
    e.preventDefault();

    var $btn = $(this);
    var current = $btn.attr("data-mode") || "sell";
    var postId = parseInt($btn.attr("data-post-id"), 10);
    var mode = nextMode(current);

    $btn.addClass("is-updating");

    $.post(MODEPCatalogToggle.ajaxUrl, {
      action: "modep_toggle_mode",
      nonce: MODEPCatalogToggle.nonce,
      post_id: postId,
      mode: mode,
    })
      .done(function (resp) {
        if (!resp || !resp.success) {
          showToast("Could not update product mode.", "sell");
          return;
        }

        var data = resp.data;

        $btn
          .attr("data-mode", data.mode)
          .removeClass("modep-mode-sell modep-mode-catalog")
          .addClass("modep-mode-" + data.class)
          .text(data.label);

        showToast("Product set to " + data.label, data.class);
      })
      .fail(function () {
        showToast("Request failed. Please try again.", "sell");
      })
      .always(function () {
        $btn.removeClass("is-updating");
      });
  });
});
