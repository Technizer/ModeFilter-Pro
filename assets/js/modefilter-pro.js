/*!
 * ModeFilter Pro — multi-style filter runtime
 * Grid Layouts: grid, masonry, justified (via attrs.grid_layout)
 * Presets (skins): grid, overlay, masonry (kept for backward compatibility only)
 * - Loads products via AJAX
 * - Chips, checkboxes, radios, toggles, and hierarchical categories
 * - Supports: categories/tags/brands + price chips + rating chips
 * - Supports: terms "Show more/less" toggle
 * - Elementor re-render safe + multi-instance safe (NO global selectors)
 */
(function ($) {
  "use strict";

  const INIT_FLAG = "modepInitDone";

  // Presets are purely visual skins. Keep old list for back-compat.
  const PRESETS = ["normal", "overlay", "minimal", "custom"];

  // Grid layout modes are functional layout engines.
  const GRID_LAYOUTS = ["grid", "masonry", "justified"];
  const FILTER_STYLES = ["chips", "checkboxes", "radios", "toggles", "hierarchical"];
  const TEMPLATE_KITS = ["classic", "minimal", "masonry", "hierarchy", "justified", "catalog"];

  function getAjaxUrl() {
    return (
      (window.MODEP_VARS && window.MODEP_VARS.ajax_url) || window.ajaxurl || ""
    );
  }

  function getNonce() {
    return (window.MODEP_VARS && window.MODEP_VARS.nonce) || "";
  }

  function toInt(value, fallback) {
    const n = parseInt(value, 10);
    return Number.isFinite(n) ? n : fallback;
  }

  function safeObj(v) {
    return v && typeof v === "object" ? v : {};
  }

  function safeArr(v) {
    return Array.isArray(v) ? v : [];
  }

  function safeJsonData(v) {
    if (!v) return {};
    if (typeof v === "object") return v;
    if (typeof v === "string") {
      try {
        const parsed = JSON.parse(v);
        return parsed && typeof parsed === "object" ? parsed : {};
      } catch (e) {
        return {};
      }
    }
    return {};
  }

  function parseMinMax(value) {
    const s = String(value || "");
    if (!s.includes("|")) return { min: "", max: "" };
    const parts = s.split("|");
    const min = parts[0] !== "" ? parseFloat(parts[0]) : "";
    const max = parts[1] !== "" ? parseFloat(parts[1]) : "";
    return {
      min: Number.isNaN(min) ? "" : min,
      max: Number.isNaN(max) ? "" : max,
    };
  }

  function normalizePreset(raw) {
    let p = String(raw || "normal").toLowerCase();

    // Back-compat mapping (old presets)
    // Backward compatibility with the original runtime-only names.
    if (p === "grid") p = "normal";
    if (p === "masonry") p = "minimal";
    if (!PRESETS.includes(p)) p = "normal";

    return p;
  }

  function normalizeGridLayout(raw) {
    const g = String(raw || "grid").toLowerCase();
    return GRID_LAYOUTS.includes(g) ? g : "grid";
  }

  function normalizeFilterPos(raw) {
    const p = String(raw || "left").toLowerCase();
    return ["left", "right", "top"].includes(p) ? p : "left";
  }

  function normalizeFilterStyle(raw) {
    const style = String(raw || "chips").toLowerCase();
    return FILTER_STYLES.includes(style) ? style : "chips";
  }

  function normalizeTemplateKit(raw) {
    const kit = String(raw || "none").toLowerCase();
    return TEMPLATE_KITS.includes(kit) ? kit : "none";
  }

  function setSelected($items, selected) {
    $items.toggleClass("is-selected", !!selected);
    $items.each(function () {
      const $item = $(this);
      if ($item.is("[role='checkbox'],[role='radio']")) {
        $item.attr("aria-checked", selected ? "true" : "false");
      } else {
        $item.attr("aria-pressed", selected ? "true" : "false");
      }
    });
  }

  function syncControlSemantics($wrap, style) {
    const radio = style === "radios";
    const checked = radio || ["checkboxes", "toggles", "hierarchical"].includes(style);
    $wrap.find(".modep-options").attr("role", radio ? "radiogroup" : "group");
    $wrap.find(".modep-option").each(function () {
      const $item = $(this);
      const selected = $item.hasClass("is-selected");
      $item.removeAttr("role aria-checked aria-pressed");
      if (checked) {
        $item.attr("role", radio ? "radio" : "checkbox").attr("aria-checked", selected ? "true" : "false");
      } else {
        $item.attr("aria-pressed", selected ? "true" : "false");
      }
    });
  }

  function getSelectedTerms($group) {
    if (!$group || !$group.length) return [];
    return $group
      .find(".modep-chip.is-selected")
      .not(".modep-chip--all")
      .map(function () {
        const v = $(this).data("term");
        return v !== undefined && v !== null ? String(v) : "";
      })
      .get()
      .filter((x) => x !== "");
  }

  function ensureAllIfEmpty($group) {
    if (!$group || !$group.length) return;
    const hasSpecific = $group
      .find(".modep-chip.is-selected")
      .not(".modep-chip--all").length;
    if (!hasSpecific) {
      setSelected($group.find(".modep-chip"), false);
      setSelected($group.find(".modep-chip--all"), true);
    }
  }

  function initMODEP($wrap) {
    if (!$wrap || !$wrap.length) return;

    // Prevent double init (Elementor can re-render widgets)
    if ($wrap.data(INIT_FLAG)) return;
    $wrap.data(INIT_FLAG, true);

    const $grid = $wrap.find(".modep-grid").first();
    const $nav = $wrap.find(".modep-pagination").first();
    const $sort = $wrap.find(".modep-sort").first();
    const $loader = $wrap.find(".modep-loader").first();

    if (!$grid.length) return;

    let io = null;
    let xhr = null;

    const state = {
      page: 1,
      maxPages: 1,
      total: 0,
      pagination: "load_more",
      columns: 3,
      perPage: 9,
      preset: "normal", // purely skin
      gridLayout: "grid", // real layout engine
      filterPos: "left",
      filterStyle: "chips",
      templateKit: "none",
      loadMoreText: "Load more",
      isLoading: false,

      // Justified parameters (if you implement in CSS/JS later)
      justifiedRowHeight: 220,
      masonryGap: 20,
    };

    function getAttrs() {
      return safeJsonData(
        $wrap.attr("data-shortcode-attrs") || $wrap.data("shortcode-attrs")
      );
    }

    function applyWrapperClasses() {
      TEMPLATE_KITS.forEach((kit) => $wrap.removeClass("modep--kit-" + kit));
      $wrap.removeClass("modep--kit-none");

      // preset classes (skin)
      PRESETS.forEach((p) => $wrap.removeClass("modep--preset-" + p));
      $wrap.addClass("modep--preset-" + state.preset);

      // filter position classes
      $wrap.removeClass(
        "modep--filters-left modep--filters-right modep--filters-top"
      );
      $wrap.addClass("modep--filters-" + state.filterPos);

      FILTER_STYLES.forEach((style) => $wrap.removeClass("modep--ui-" + style));
      $wrap.addClass("modep--ui-" + state.filterStyle);
      $wrap.attr("data-modep-filter-style", state.filterStyle);
      $wrap.addClass("modep--kit-" + state.templateKit);
      $wrap.attr("data-template-kit", state.templateKit);
      syncControlSemantics($wrap, state.filterStyle);
    }

    function applyGridLayoutClasses() {
      // Reset
      $grid.removeClass("modep-grid--masonry modep-grid--justified");

      // Base CSS vars
      $grid.css("--modep-cols", state.columns);
      $grid.css("--modep-masonry-gap", state.masonryGap);
      $grid.css("--modep-justified-row-height", state.justifiedRowHeight);

      // Apply layout engine classes
      if (state.gridLayout === "masonry") {
        $grid.addClass("modep-grid--masonry");
      } else if (state.gridLayout === "justified") {
        $grid.addClass("modep-grid--justified");
      }
    }

    function parseAttrs() {
      const attrs = safeObj(getAttrs());

      state.pagination = String(attrs.pagination || "load_more").toLowerCase();
      state.columns = toInt(attrs.columns || 3, 3);
      state.perPage = toInt(attrs.per_page || 9, 9);

      // preset is still used as skin
      state.preset = normalizePreset(attrs.preset);

      // ✅ NEW: functional layout engine
      state.gridLayout = normalizeGridLayout(attrs.grid_layout);

      state.filterPos = normalizeFilterPos(attrs.filter_position);
      state.filterStyle = normalizeFilterStyle(attrs.filter_style || attrs.filter_ui);
      state.templateKit = normalizeTemplateKit(attrs.template_kit);
      state.loadMoreText = String(attrs.load_more_text || "Load more");

      state.masonryGap = toInt(attrs.masonry_gap || 20, 20);
      state.justifiedRowHeight = toInt(attrs.justified_row_height || 220, 220);

      applyWrapperClasses();
      applyGridLayoutClasses();
    }

    function hasSidebarFilters() {
      const $sidebar = $wrap.find(".modep-sidebar").first();
      if (!$sidebar.length) return false;
      if (state.filterPos === "top") return false;
      return $sidebar.find(".modep-chips").length > 0;
    }

    function syncToggleButtonVisibility() {
      const $btn = $wrap.find(".modep-toggle-btn").first();
      if (!$btn.length) return;

      const show = hasSidebarFilters() && $(window).width() <= 768;
      $btn.toggle(!!show);

      if (!show) {
        $wrap.find(".modep-sidebar").removeClass("open");
      }
    }

    function destroyInfiniteObserver() {
      if (io && typeof io.disconnect === "function") {
        io.disconnect();
      }
      io = null;
    }

    function setUILoading(isLoading) {
      state.isLoading = !!isLoading;

      $grid
        .toggleClass("loading", state.isLoading)
        .attr("aria-busy", state.isLoading ? "true" : "false");

      if ($loader && $loader.length) {
        $loader.prop("hidden", !state.isLoading);
      }

      if ($sort && $sort.length) $sort.prop("disabled", state.isLoading);

      // In some installs chips are <button>, sometimes <a>/<span>.
      // prop("disabled") is safe for <button>, harmless otherwise.
      $wrap.find(".modep-chip").prop("disabled", state.isLoading);
      $wrap
        .find(".modep-load-more, .modep-page-btn")
        .prop("disabled", state.isLoading);
    }

    function renderError(msg, append) {
      const safe = msg && typeof msg === "string" ? msg : "No products found.";
      const $message = $("<div>", { class: "modep-no-products" }).text(safe);
      if (append) $grid.append($message);
      else $grid.empty().append($message);
    }

    function buildPagination() {
      if (!$nav || !$nav.length) return;

      destroyInfiniteObserver();
      $nav.empty();

      if (state.pagination === "none" || state.maxPages <= 1) return;

      if (state.pagination === "load_more") {
        if (state.page < state.maxPages) {
          const label = `${state.loadMoreText} (Page ${state.page} of ${state.maxPages})`;
          $nav.append(
            `<button class="modep-load-more" type="button" data-next="${
              state.page + 1
            }">${label}</button>`
          );
        }
        return;
      }

      if (state.pagination === "numbers") {
        const prevDisabled = state.page === 1 ? "disabled" : "";
        const nextDisabled = state.page === state.maxPages ? "disabled" : "";

        $nav.append(
          `<button class="modep-page-btn" type="button" data-page="${Math.max(
            1,
            state.page - 1
          )}" ${prevDisabled}>&laquo;</button>`
        );

        const start = Math.max(1, state.page - 2);
        const end = Math.min(state.maxPages, start + 4);

        for (let p = start; p <= end; p++) {
          $nav.append(
            `<button class="modep-page-btn ${
              p === state.page ? "is-active" : ""
            }" type="button" data-page="${p}">${p}</button>`
          );
        }

        $nav.append(
          `<button class="modep-page-btn" type="button" data-page="${Math.min(
            state.maxPages,
            state.page + 1
          )}" ${nextDisabled}>&raquo;</button>`
        );

        return;
      }

      if (state.pagination === "infinite") {
        if (!("IntersectionObserver" in window)) {
          state.pagination = "load_more";
          buildPagination();
          return;
        }

        const $sentinel = $(
          '<div class="modep-infinite-sentinel" aria-hidden="true"></div>'
        );
        $nav.append($sentinel);

        io = new IntersectionObserver(
          (entries) => {
            entries.forEach((e) => {
              if (e.isIntersecting && !state.isLoading) {
                if (state.page < state.maxPages) {
                  loadProducts({ page: state.page + 1, append: true });
                }
              }
            });
          },
          { rootMargin: "250px" }
        );

        io.observe($sentinel[0]);
      }
    }

    function getEnabledFilters(attrs) {
      // attrs.filters is an array in our new architecture
      return safeArr(attrs.filters)
        .map((x) => String(x || "").toLowerCase())
        .filter(Boolean);
    }

    function hasFilterEnabled(enabled, id) {
      if (!enabled.length) return null; // unknown -> fall back to DOM presence
      return enabled.includes(id);
    }

    function collectFiltersForRequest(attrs) {
      const enabled = getEnabledFilters(attrs);

      const wantCats = hasFilterEnabled(enabled, "categories");
      const wantTags = hasFilterEnabled(enabled, "tags");
      const wantBrands = hasFilterEnabled(enabled, "brands");
      const wantPrice = hasFilterEnabled(enabled, "price");
      const wantRating = hasFilterEnabled(enabled, "rating");

      const $cats = $wrap.find('.modep-chips[data-filter="category"]').first();
      const $tags = $wrap.find('.modep-chips[data-filter="tag"]').first();
      const $brands = $wrap.find('.modep-chips[data-filter="brand"]').first();
      const $price = $wrap.find('.modep-chips[data-filter="price"]').first();
      const $rating = $wrap.find('.modep-chips[data-filter="rating"]').first();

      const readIf = (flag, $el) => {
        if (flag === false) return [];
        if (flag === null && (!$el || !$el.length)) return [];
        return getSelectedTerms($el);
      };

      const cat_ids = readIf(wantCats, $cats)
        .map((v) => toInt(v, 0))
        .filter(Boolean);

      const tag_ids = readIf(wantTags, $tags)
        .map((v) => toInt(v, 0))
        .filter(Boolean);

      const brand_ids = readIf(wantBrands, $brands)
        .map((v) => toInt(v, 0))
        .filter(Boolean);

		const attribute_ids = {};
		enabled
			.filter((id) => id.indexOf("pa_") === 0)
			.forEach((taxonomy) => {
				const $group = $wrap.find(`.modep-chips[data-filter="${taxonomy}"]`).first();
				const values = getSelectedTerms($group).map((v) => toInt(v, 0)).filter(Boolean);
				if (values.length) attribute_ids[taxonomy] = values;
			});

      let price_min = attrs.price_min !== undefined ? attrs.price_min : "";
      let price_max = attrs.price_max !== undefined ? attrs.price_max : "";

      if (wantPrice !== false && $price.length) {
        const selected = getSelectedTerms($price);
        if (selected.length) {
          const mm = parseMinMax(selected[selected.length - 1]);
          price_min = mm.min;
          price_max = mm.max;
        } else {
          ensureAllIfEmpty($price);
        }
      }

      let rating_min = "";
      if (wantRating !== false && $rating.length) {
        const selected = getSelectedTerms($rating);
        if (selected.length) {
          rating_min = toInt(selected[selected.length - 1], 0) || "";
        } else {
          ensureAllIfEmpty($rating);
        }
      }

      return { cat_ids, tag_ids, brand_ids, attribute_ids, price_min, price_max, rating_min };
    }

    function abortXHR() {
      if (xhr && xhr.readyState !== 4) {
        try {
          xhr.abort();
        } catch (e) {}
      }
      xhr = null;
    }

    function loadProducts(opts = {}) {
      const ajaxUrl = getAjaxUrl();
      if (!ajaxUrl) {
        renderError("AJAX endpoint not available.", false);
        return;
      }

      const attrs = safeObj(getAttrs());
      const append = !!opts.append;

      const filters = collectFiltersForRequest(attrs);

      const sortVal =
        ($sort && $sort.length ? $sort.val() : "") ||
        (attrs.sort !== undefined ? attrs.sort : "") ||
        "";

      // ✅ IMPORTANT:
      // We DO NOT mutate attrs.only_catalog / attrs.context here.
      // Those are decided server-side & provided in data-shortcode-attrs.
      const payload = {
        action: "modep_get_products",
        _nonce: getNonce(),

        shortcode_attrs: attrs,

        sort: sortVal,

        cat_ids: filters.cat_ids,
        tag_ids: filters.tag_ids,
        brand_ids: filters.brand_ids,
        attribute_ids: filters.attribute_ids,
        price_min: filters.price_min,
        price_max: filters.price_max,
        rating_min: filters.rating_min,

        page: opts.page || 1,
      };

      if (!append) {
        abortXHR();
      }

      setUILoading(true);

      if (!append) {
        if ($nav && $nav.length) $nav.empty();
        destroyInfiniteObserver();
      }

      xhr = $.ajax({
        url: ajaxUrl,
        type: "POST",
        dataType: "json",
        data: payload,
      })
        .done(function (res) {
          setUILoading(false);

          if (!res || typeof res !== "object") {
            renderError("Unexpected response from server.", append);
            return;
          }

          if (!res.success) {
            const msg = (res.data && res.data.message) || "No products found.";
            renderError(msg, append);
            return;
          }

          const data = res.data || {};
          state.page = toInt(data.page || 1, 1);
          state.maxPages = toInt(data.max_pages || 1, 1);
          state.total = toInt(data.total || 0, 0);

          // Optional if server ever returns these
          if (data.justified_row_height) {
            state.justifiedRowHeight = toInt(
              data.justified_row_height,
              state.justifiedRowHeight
            );
          }
          if (data.masonry_gap) {
            state.masonryGap = toInt(data.masonry_gap, state.masonryGap);
          }

          applyGridLayoutClasses();

          const html = (data.html || "").toString();
          if (!html.trim()) {
            renderError("No products found.", append);
            return;
          }

          if (append) $grid.append(html);
          else $grid.html(html);

          buildPagination();
          syncToggleButtonVisibility();

          $(document).trigger("modep:products:loaded", [$wrap, data]);
        })
        .fail(function (xhrObj) {
          if (xhrObj && xhrObj.statusText === "abort") return;

          setUILoading(false);

          let msg = "An error occurred while loading products.";
          if (
            xhrObj &&
            xhrObj.responseJSON &&
            xhrObj.responseJSON.data &&
            xhrObj.responseJSON.data.message
          ) {
            msg = xhrObj.responseJSON.data.message;
          } else if (xhrObj && typeof xhrObj.responseText === "string") {
            const t = xhrObj.responseText.trim();
            if (t === "-1")
              msg = "Security check failed (nonce). Please refresh the page.";
            else if (t === "0")
              msg =
                "AJAX action not available. Please check plugin initialization.";
          }

          renderError(msg, false);
        });
    }

    /* -----------------------------
       UI Wiring
    ------------------------------ */

    $wrap.on("click", ".modep-chips .modep-chip", function () {
      if (state.isLoading) return;

      const $chip = $(this);
      const $group = $chip.closest(".modep-chips");
      if (!$group.length) return;

      const filter = String($group.data("filter") || "");
      const isAll = $chip.hasClass("modep-chip--all");
      const activeStyle = normalizeFilterStyle($wrap.attr("data-modep-filter-style") || state.filterStyle);
      const isSingleSelect = activeStyle === "radios" || filter === "price" || filter === "rating";

      if (isAll) {
        setSelected($group.find(".modep-chip"), false);
        setSelected($chip, true);
      } else if (isSingleSelect) {
        setSelected($group.find(".modep-chip"), false);
        setSelected($chip, true);
        setSelected($group.find(".modep-chip--all"), false);
      } else {
        setSelected($chip, !$chip.hasClass("is-selected"));
        setSelected($group.find(".modep-chip--all"), false);

        if (
          !$group.find(".modep-chip.is-selected").not(".modep-chip--all").length
        ) {
          setSelected($group.find(".modep-chip--all"), true);
        }
      }

      state.page = 1;
      loadProducts({ page: 1 });

      if ($(window).width() <= 768) {
        $wrap.find(".modep-sidebar").removeClass("open");
      }

      $(document).trigger("modep:filters:changed", [$wrap, filter]);
    });

    $wrap.on("click", ".modep-terms-more", function () {
      const $btn = $(this);
      const $box = $btn.closest(".modep-filter-box");
      const $chips = $box.find(".modep-chips").first();
      if (!$chips.length) return;

      const isHiddenNow = $chips.find('[data-hidden="1"][hidden]').length > 0;

      if (isHiddenNow) {
        $chips.find('[data-hidden="1"]').prop("hidden", false);
        $btn.find(".modep-terms-more__more").prop("hidden", true);
        $btn.find(".modep-terms-more__less").prop("hidden", false);
      } else {
        $chips.find('[data-hidden="1"]').prop("hidden", true);
        $btn.find(".modep-terms-more__more").prop("hidden", false);
        $btn.find(".modep-terms-more__less").prop("hidden", true);
      }
    });

    $wrap.on("change", ".modep-sort", function () {
      if (state.isLoading) return;
      state.page = 1;
      loadProducts({ page: 1 });
    });

    $wrap.on("modep:presentation:changed", function (event, presentation) {
      const next = safeObj(presentation);
      state.filterStyle = normalizeFilterStyle(next.filterStyle || next.filter_style || state.filterStyle);
      applyWrapperClasses();
    });

    $wrap.on("click", ".modep-toggle-btn", function () {
      if (!hasSidebarFilters()) return;
      $wrap.find(".modep-sidebar").toggleClass("open");
    });

    $(document).on("click.modep_outside", function (e) {
      if ($(window).width() > 768) return;
      if (!$wrap.is(":visible")) return;

      const $sidebar = $wrap.find(".modep-sidebar").first();
      if (!$sidebar.length || !$sidebar.hasClass("open")) return;

      const $target = $(e.target);
      const clickedInside = $target.closest($wrap).length > 0;

      if (!clickedInside) {
        $sidebar.removeClass("open");
      }
    });

    $wrap.on("click", ".modep-load-more", function () {
      if (state.isLoading) return;
      const next = toInt($(this).data("next"), 0);
      if (next) loadProducts({ page: next, append: true });
    });

    $wrap.on("click", ".modep-page-btn", function () {
      if (state.isLoading) return;
      const page = toInt($(this).data("page"), 0);
      if (!page) return;
      state.page = page;
      loadProducts({ page: page });
    });

    $wrap.on("click", ".modep-product-inner", function (e) {
      const attrs = safeObj(getAttrs());
      const linkWholeCard = !!attrs.link_whole_card;
      if (!linkWholeCard) return;

      if ($(e.target).closest("a,button,input,select,label,textarea").length)
        return;

      const $a = $(this).closest("li").find("a:first");
      if ($a.length) window.location = $a.attr("href");
    });

    $(window).on("resize.modep", function () {
      syncToggleButtonVisibility();
    });

    // Init
    parseAttrs();
    syncToggleButtonVisibility();

    $wrap.find(".modep-chips").each(function () {
      ensureAllIfEmpty($(this));
    });

    loadProducts({ page: 1 });
  }

  /* -----------------------------
     Elementor + Frontend bootstraps
  ------------------------------ */

  $(window).on("elementor/frontend/init", function () {
    if (!window.elementorFrontend || !elementorFrontend.hooks) return;

    elementorFrontend.hooks.addAction(
      "frontend/element_ready/modep_filters.default",
      function ($scope) {
        $scope.find(".modep").each(function () {
          initMODEP($(this));
        });
      }
    );

    elementorFrontend.hooks.addAction(
      "frontend/element_ready/modep_catalog.default",
      function ($scope) {
        $scope.find(".modep").each(function () {
          initMODEP($(this));
        });
      }
    );
  });

  $(function () {
    $(".modep").each(function () {
      initMODEP($(this));
    });
  });

  $(document).on("click", ".modep-notify-btn", function () {
    const $form = $(this).next(".modep-stock-form");
    if (!$form.length) return;
    $form.prop("hidden", !$form.prop("hidden"));
    if (!$form.prop("hidden")) $form.find('input[type="email"]').trigger("focus");
  });

  $(document).on("submit", ".modep-stock-form", function (event) {
    event.preventDefault();
    const $form = $(this);
    const $status = $form.find(".modep-stock-form__status");
    const $button = $form.find('button[type="submit"]');
    $button.prop("disabled", true);
    $status.text((window.MODEP_VARS && MODEP_VARS.i18n.loading) || "Submitting…");

    $.post(getAjaxUrl(), {
      action: "modep_subscribe_stock",
      _nonce: getNonce(),
      product_id: $form.data("product-id"),
      email: $form.find('[name="email"]').val(),
      website: $form.find('[name="website"]').val(),
    })
      .done(function (response) {
        const message = response && response.data && response.data.message
          ? response.data.message
          : ((window.MODEP_VARS && MODEP_VARS.i18n.error) || "Please try again.");
        $status.text(message);
        if (response && response.success) $form.find("input").prop("disabled", true);
      })
      .fail(function () {
        $status.text((window.MODEP_VARS && MODEP_VARS.i18n.error) || "Please try again.");
      })
      .always(function () {
        $button.prop("disabled", false);
      });
  });
})(jQuery);
