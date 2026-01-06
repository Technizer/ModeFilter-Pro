/*
 * ModeFilter Pro — Admin Shortcode Builder
 *
 * Generates a [modep_filters] shortcode string from the Builder screen.
 *
 * Notes:
 * - No AJAX is required.
 * - This file must not output console logs (plugin review).
 */
(function (window, document) {
  "use strict";

  function byId(id) {
    return document.getElementById(id);
  }

  function getVal(id) {
    var el = byId(id);
    if (!el) return "";
    return (el.value || "").toString();
  }

  function getMultiVals(id) {
    var el = byId(id);
    if (!el) return [];
    var out = [];
    for (var i = 0; i < el.options.length; i++) {
      if (el.options[i].selected) {
        out.push(el.options[i].value);
      }
    }
    return out;
  }

  function setVal(id, value) {
    var el = byId(id);
    if (el) el.value = value;
  }

  function escAttr(v) {
    return (v || "")
      .toString()
      .replace(/&/g, "&amp;")
      .replace(/"/g, "&quot;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");
  }

  function addAttr(parts, key, val) {
    if (val === null || typeof val === "undefined") return;
    if (val === "") return;
    parts.push(" " + key + '="' + escAttr(val) + '"');
  }

  function buildShortcode() {
    // Core
    var onlyCatalog = getVal("modep_sc_only_catalog"); // yes/no
    var columns = getVal("modep_sc_columns");
    var perPage = getVal("modep_sc_per");
    var preset = getVal("modep_sc_preset");
    var pagination = getVal("modep_sc_pagination");
    var loadMoreText = getVal("modep_sc_load_more_text");

    // Filters
    var filtersMode = getVal("modep_sc_filters_mode");
    var filters = getMultiVals("modep_sc_filters");
    var filterPosition = getVal("modep_sc_pos");
    var termsLimit = getVal("modep_sc_terms_limit");
    var termsOrderBy = getVal("modep_sc_terms_orderby");
    var termsOrder = getVal("modep_sc_terms_order");
    var termsShowMore = getVal("modep_sc_terms_show_more"); // yes/no

    // Excludes / sellable base
    var excludeCat = getVal("modep_sc_exclude_cat");
    var excludeTag = getVal("modep_sc_exclude_tag");
    var excludeBrand = getVal("modep_sc_exclude_brand");
    var sellableBase = getVal("modep_sc_sellable");

    // Grid layout extras
    var gridLayout = getVal("modep_sc_grid_layout");
    var masonryGap = getVal("modep_sc_masonry_gap");
    var justifiedRowHeight = getVal("modep_sc_justified_row_height");

    // UX
    var linkWholeCard = getVal("modep_sc_link_whole_card"); // yes/no

    var parts = ["[modep_filters"];

    // Mode
    if (onlyCatalog === "yes") {
      addAttr(parts, "only_catalog", "yes");
    }

    // Query
    addAttr(parts, "columns", columns);
    addAttr(parts, "per_page", perPage);
    addAttr(parts, "preset", preset);
    addAttr(parts, "pagination", pagination);
    if (pagination === "load_more" && loadMoreText) {
      addAttr(parts, "load_more_text", loadMoreText);
    }
    addAttr(parts, "link_whole_card", linkWholeCard === "yes" ? "yes" : "no");

    // Filters
    addAttr(parts, "filter_ui", "chips");
    addAttr(parts, "filter_position", filterPosition);
    addAttr(parts, "filters_mode", filtersMode);
    if (filters.length) {
      addAttr(parts, "filters", filters.join(","));
    }
    addAttr(parts, "terms_limit", termsLimit);
    addAttr(parts, "terms_orderby", termsOrderBy);
    addAttr(parts, "terms_order", termsOrder);
    addAttr(parts, "terms_show_more", termsShowMore === "yes" ? "yes" : "no");
    addAttr(parts, "exclude_cat", excludeCat);
    addAttr(parts, "exclude_tag", excludeTag);
    addAttr(parts, "exclude_brand", excludeBrand);

    // Sellable base category (slug)
    addAttr(parts, "sellable_cat_slug", sellableBase);

    // Grid layout
    addAttr(parts, "grid_layout", gridLayout);
    if (gridLayout === "masonry") {
      addAttr(parts, "masonry_gap", masonryGap);
    }
    if (gridLayout === "justified") {
      addAttr(parts, "justified_row_height", justifiedRowHeight);
    }

    parts.push("]");
    return parts.join("");
  }

  function copyToClipboard(text) {
    if (window.navigator && window.navigator.clipboard && window.navigator.clipboard.writeText) {
      return window.navigator.clipboard.writeText(text);
    }

    // Fallback for older browsers.
    var temp = document.createElement("textarea");
    temp.value = text;
    temp.setAttribute("readonly", "readonly");
    temp.style.position = "absolute";
    temp.style.left = "-9999px";
    document.body.appendChild(temp);
    temp.select();
    try {
      document.execCommand("copy");
    } catch (e) {
      // ignore
    }
    document.body.removeChild(temp);
    return Promise.resolve();
  }

  document.addEventListener("DOMContentLoaded", function () {
    var buildBtn = byId("modep_sc_build");
    var copyBtn = byId("modep_sc_copy");
    var output = byId("modep_sc_output");

    if (!buildBtn || !output) {
      return;
    }

    buildBtn.addEventListener("click", function (e) {
      e.preventDefault();
      output.value = buildShortcode();
      output.focus();
      output.select();
    });

    if (copyBtn) {
      copyBtn.addEventListener("click", function (e) {
        e.preventDefault();
        if (!output.value) {
          output.value = buildShortcode();
        }
        copyToClipboard(output.value);
      });
    }
  });
})(window, document);
