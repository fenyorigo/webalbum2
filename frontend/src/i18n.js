import { reactive } from "vue";

const state = reactive({
  language: "en",
  fallback_language: "en",
  strings: {},
  supported_languages: [{ code: "en", translated_count: 0 }]
});

function normalizeBundle(bundle) {
  if (!bundle || typeof bundle !== "object") {
    return {
      language: "en",
      fallback_language: "en",
      strings: {},
      supported_languages: [{ code: "en", translated_count: 0 }]
    };
  }
  return {
    language: typeof bundle.language === "string" && bundle.language ? bundle.language : "en",
    fallback_language:
      typeof bundle.fallback_language === "string" && bundle.fallback_language ? bundle.fallback_language : "en",
    strings: bundle.strings && typeof bundle.strings === "object" ? { ...bundle.strings } : {},
    supported_languages: Array.isArray(bundle.supported_languages) && bundle.supported_languages.length
      ? bundle.supported_languages
      : [{ code: "en", translated_count: 0 }]
  };
}

export function applyI18nBundle(bundle) {
  const next = normalizeBundle(bundle);
  state.language = next.language;
  state.fallback_language = next.fallback_language;
  state.strings = next.strings;
  state.supported_languages = next.supported_languages;
  window.__wa_i18n = next;
  window.dispatchEvent(new CustomEvent("wa-i18n-changed", { detail: next }));
}

function applyParams(template, params) {
  if (!params || typeof params !== "object") {
    return template;
  }
  return String(template).replace(/\{([a-zA-Z0-9_]+)\}/g, (_match, name) => {
    if (Object.prototype.hasOwnProperty.call(params, name)) {
      const value = params[name];
      return value === null || value === undefined ? "" : String(value);
    }
    return `{${name}}`;
  });
}

export function t(key, paramsOrFallback = "", maybeFallback = "") {
  const hasParams = paramsOrFallback && typeof paramsOrFallback === "object" && !Array.isArray(paramsOrFallback);
  const params = hasParams ? paramsOrFallback : null;
  const fallback = hasParams ? maybeFallback : paramsOrFallback;
  let value = "";
  if (key && Object.prototype.hasOwnProperty.call(state.strings, key)) {
    value = state.strings[key];
  } else if (fallback) {
    value = fallback;
  } else {
    value = key;
  }
  return applyParams(value, params);
}

export default {
  install(app) {
    app.config.globalProperties.$t = (key, paramsOrFallback = "", maybeFallback = "") =>
      t(key, paramsOrFallback, maybeFallback);
    app.config.globalProperties.$i18n = state;
  }
};
