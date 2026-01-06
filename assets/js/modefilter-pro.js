/*!
 * ModeFilter Pro — Chips-only runtime (Pro) — 3 layouts only
 * Layouts: grid, overlay, masonry
 * - Loads products via AJAX
 * - Chips-only filters (no checkbox/radio/toggle styles in Pro)
 * - Supports: categories/tags/brands + price chips + rating chips
 * - Supports: terms "Show more/less" toggle
 * - Elementor re-render safe + multi-instance safe (NO global selectors)
 */
(function ($) {
  "use strict";

  const INIT_FLAG = "modepInitDone";
  const PRESETS = ["grid", "overlay", "masonry"];

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
      $group.find(".modep-chip").removeClass("is-selected");
      $group.find(".modep-chip--all").addClass("is-selected");
    }
  }

  function normalizePreset(raw) {
    let p = String(raw || "grid").toLowerCase();

    // Back-compat mapping (old presets)
    // - normal => grid
    // - minimal/creative/asym/bento/etc => grid
    if (p === "normal") p = "grid";
    if (!PRESETS.includes(p)) p = "grid";

    return p;
  }

  function normalizeFilterPos(raw) {
    const p = String(raw || "left").toLowerCase();
    return ["left", "right", "top"].includes(p) ? p : "left";
  }

  function initMODEP($wrap) {
    if (!$wrap || !$wrap.length) return;

    // Prevent double init (Elementor can re-render widgets)
    if ($wrap.data(INIT_FLAG)) return;
    $wrap.data(INIT_FLAG, true);

    const $grid = $wrap.find(".modep-grid").first();
    const $nav = $wrap.find(".modep-pagination").first();
    const $sort = $wrap.find(".modep-sort").first();

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
      preset: "grid",
      filterPos: "left",
      loadMoreText: "Load more",
      isLoading: false,
    };

    function getAttrs() {
      return safeJsonData(
        $wrap.attr("data-shortcode-attrs") || $wrap.data("shortcode-attrs")
      );
    }

    function applyWrapperClasses() {
      // preset classes
      PRESETS.forEach((p) => $wrap.removeClass("modep--preset-" + p));
      $wrap.addClass("modep--preset-" + state.preset);

      // filter position classes
      $wrap.removeClass(
        "modep--filters-left modep--filters-right modep--filters-top"
      );
      $wrap.addClass("modep--filters-" + state.filterPos);

      // Pro explicit UI (chips)
      $wrap.addClass("modep--ui-chips");
    }

    function parseAttrs() {
      const attrs = safeObj(getAttrs());

      state.pagination = String(attrs.pagination || "load_more").toLowerCase();
      state.columns = toInt(attrs.columns || 3, 3);
      state.perPage = toInt(attrs.per_page || 9, 9);
      state.preset = normalizePreset(attrs.preset);
      state.filterPos = normalizeFilterPos(attrs.filter_position);
      state.loadMoreText = String(attrs.load_more_text || "Load more");

      // Grid base
      $grid.css("--modep-cols", state.columns);

      // Masonry grid mode (CSS columns)
      $grid.toggleClass("modep-grid--masonry", state.preset === "masonry");

      applyWrapperClasses();
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

      if ($sort && $sort.length) $sort.prop("disabled", state.isLoading);

      $wrap.find(".modep-chip").prop("disabled", state.isLoading);
      $wrap
        .find(".modep-load-more, .modep-page-btn")
        .prop("disabled", state.isLoading);
    }

    function renderError(msg, append) {
      const safe = msg && typeof msg === "string" ? msg : "No products found.";
      const html = `<div class="modep-no-products">${safe}</div>`;
      if (append) $grid.append(html);
      else $grid.html(html);
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

      return { cat_ids, tag_ids, brand_ids, price_min, price_max, rating_min };
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

      const payload = {
        action: "modep_get_products",
        _nonce: getNonce(),

        shortcode_attrs: attrs,

        sort: sortVal,

        cat_ids: filters.cat_ids,
        tag_ids: filters.tag_ids,
        brand_ids: filters.brand_ids,
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

          if (data.columns) {
            state.columns = toInt(data.columns, state.columns);
            $grid.css("--modep-cols", state.columns);
          }

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
       UI Wiring (Chips-only)
    ------------------------------ */

    $wrap.on("click", ".modep-chips .modep-chip", function () {
      if (state.isLoading) return;

      const $chip = $(this);
      const $group = $chip.closest(".modep-chips");
      if (!$group.length) return;

      const filter = String($group.data("filter") || "");
      const isAll = $chip.hasClass("modep-chip--all");
      const isSingleSelect = filter === "price" || filter === "rating";

      if (isAll) {
        $group.find(".modep-chip").removeClass("is-selected");
        $chip.addClass("is-selected");
      } else if (isSingleSelect) {
        $group.find(".modep-chip").removeClass("is-selected");
        $chip.addClass("is-selected");
        $group.find(".modep-chip--all").removeClass("is-selected");
      } else {
        $chip.toggleClass("is-selected");
        $group.find(".modep-chip--all").removeClass("is-selected");

        if (
          !$group.find(".modep-chip.is-selected").not(".modep-chip--all").length
        ) {
          $group.find(".modep-chip--all").addClass("is-selected");
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
})(jQuery);
