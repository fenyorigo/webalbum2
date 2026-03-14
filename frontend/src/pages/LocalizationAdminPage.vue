<template>
  <div class="page">
    <header class="hero">
      <h1>{{ $t("admin.localization", "Localization") }}</h1>
      <p>{{ $t("localization.description", "Manage UI string translations and supported languages.") }}</p>
    </header>

    <section class="panel">
      <div class="row filters">
        <label>
          {{ $t("localization.language", "Language") }}
          <select v-model="selectedLanguage" @change="applyFilters">
            <option v-for="lang in languages" :key="lang.code" :value="lang.code">
              {{ languageLabel(lang) }}
            </option>
          </select>
        </label>
        <label>
          {{ $t("localization.context", "Context") }}
          <select v-model="filters.context" @change="applyFilters">
            <option value="">{{ $t("localization.all_contexts", "All contexts") }}</option>
            <option v-for="ctx in contexts" :key="ctx" :value="ctx">{{ ctx }}</option>
          </select>
        </label>
        <label>
          {{ $t("localization.status", "Status") }}
          <select v-model="filters.status" @change="applyFilters">
            <option value="all">{{ $t("status.all", "All") }}</option>
            <option value="missing">{{ $t("status.missing", "Missing") }}</option>
            <option value="draft">{{ $t("status.draft", "Draft") }}</option>
            <option value="final">{{ $t("status.final", "Final") }}</option>
          </select>
        </label>
        <label class="grow">
          {{ $t("localization.search", "Search") }}
          <input
            v-model.trim="filters.query"
            type="text"
            :placeholder="$t('localization.search_placeholder', 'Key, English, translation')"
            @keydown.enter.prevent="applyFilters"
          />
        </label>
      </div>
      <div class="row actions">
        <button type="button" @click="applyFilters" :disabled="loading">{{ $t("ui.apply", "Apply") }}</button>
        <button type="button" class="inline" @click="clearFilters" :disabled="loading">{{ $t("ui.clear", "Clear") }}</button>
        <button type="button" class="inline" @click="openAddLanguage">{{ $t("localization.add_language", "Add language") }}</button>
        <button type="button" class="inline" @click="openAddKey">{{ $t("localization.add_key", "Add key") }}</button>
      </div>
      <p v-if="error" class="error">{{ error }}</p>
    </section>

    <section class="results">
      <div class="meta">
        <span v-if="loading">{{ $t("common.loading", "Loading...") }}</span>
        <span v-else>{{ total }} · {{ $t("audit.page_of", { x: page, y: totalPages }, "Page {x} of {y}") }}</span>
      </div>

      <div class="pager" v-if="totalPages > 1">
        <button :disabled="loading || page <= 1" @click="prevPage">{{ $t("ui.previous", "Previous") }}</button>
        <button :disabled="loading || page >= totalPages" @click="nextPage">{{ $t("ui.next", "Next") }}</button>
      </div>

      <table class="results-table" v-if="items.length">
        <thead>
          <tr>
            <th>{{ $t("localization.key", "Key") }}</th>
            <th>{{ $t("localization.context", "Context") }}</th>
            <th>{{ $t("localization.english", "English") }}</th>
            <th>{{ $t("localization.translation", "Translation") }}</th>
            <th>{{ $t("localization.status", "Status") }}</th>
            <th>{{ $t("localization.actions", "Actions") }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in items" :key="row.string_key">
            <td><code>{{ row.string_key }}</code></td>
            <td>{{ row.context || "—" }}</td>
            <td>{{ row.default_en }}</td>
            <td>{{ row.translated_value || "—" }}</td>
            <td>
              <span class="status-pill" :class="`status-${row.translation_status}`">
                {{ displayStatus(row.translation_status) }}
              </span>
            </td>
            <td>
              <button type="button" class="inline" @click="openEdit(row)">{{ $t("localization.edit_translation", "Edit") }}</button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else-if="!loading" class="muted">{{ $t("localization.table_empty", "No localization rows match the current filters.") }}</p>

      <div class="pager" v-if="totalPages > 1">
        <button :disabled="loading || page <= 1" @click="prevPage">{{ $t("ui.previous", "Previous") }}</button>
        <button :disabled="loading || page >= totalPages" @click="nextPage">{{ $t("ui.next", "Next") }}</button>
      </div>
    </section>

    <div v-if="editOpen" class="modal-backdrop" @click.self="closeEdit">
      <div class="modal localization-modal">
        <h3>{{ $t("localization.edit_translation", "Edit translation") }}</h3>
        <label>
          {{ $t("localization.key", "Key") }}
          <input :value="editForm.string_key" type="text" readonly />
        </label>
        <label>
          {{ $t("localization.context", "Context") }}
          <input :value="editForm.context" type="text" readonly />
        </label>
        <label>
          {{ $t("localization.default_english", "Default English") }}
          <textarea :value="editForm.default_en" rows="3" readonly></textarea>
        </label>
        <label>
          {{ $t("localization.translation", "Translation") }}
          <textarea v-model.trim="editForm.translated_value" rows="4"></textarea>
        </label>
        <label class="checkbox">
          <input v-model="editForm.is_final" type="checkbox" />
          {{ $t("status.final", "Final") }}
        </label>
        <p v-if="hasPlaceholders(editForm.default_en)" class="muted">
          {{ $t("localization.placeholder_note", "Placeholder tokens like {name} must be preserved where needed.") }}
        </p>
        <div class="modal-actions">
          <button type="button" @click="saveTranslation" :disabled="loading">{{ $t("ui.save", "Save") }}</button>
          <button type="button" class="inline" @click="closeEdit" :disabled="loading">{{ $t("ui.cancel", "Cancel") }}</button>
        </div>
      </div>
    </div>

    <div v-if="languageOpen" class="modal-backdrop" @click.self="closeAddLanguage">
      <div class="modal localization-modal">
        <h3>{{ $t("localization.add_language", "Add language") }}</h3>
        <label>
          {{ $t("localization.language_code", "Language code") }}
          <input v-model.trim="languageForm.code" type="text" placeholder="de" />
        </label>
        <label>
          {{ $t("localization.language_name_en", "English name") }}
          <input v-model.trim="languageForm.name_en" type="text" placeholder="German" />
        </label>
        <label>
          {{ $t("localization.language_name_native", "Native name") }}
          <input v-model.trim="languageForm.name_native" type="text" placeholder="Deutsch" />
        </label>
        <label class="checkbox">
          <input v-model="languageForm.is_active" type="checkbox" />
          {{ $t("localization.active", "Active") }}
        </label>
        <div class="modal-actions">
          <button type="button" @click="saveLanguage" :disabled="loading">{{ $t("ui.save", "Save") }}</button>
          <button type="button" class="inline" @click="closeAddLanguage" :disabled="loading">{{ $t("ui.cancel", "Cancel") }}</button>
        </div>
      </div>
    </div>

    <div v-if="keyOpen" class="modal-backdrop" @click.self="closeAddKey">
      <div class="modal localization-modal">
        <h3>{{ $t("localization.add_key", "Add key") }}</h3>
        <label>
          {{ $t("localization.key", "Key") }}
          <input v-model.trim="keyForm.string_key" type="text" placeholder="nav.example" />
        </label>
        <label>
          {{ $t("localization.context", "Context") }}
          <input v-model.trim="keyForm.context" type="text" placeholder="nav" />
        </label>
        <label>
          {{ $t("localization.default_english", "Default English") }}
          <textarea v-model.trim="keyForm.default_en" rows="3"></textarea>
        </label>
        <label>
          {{ $t("localization.initial_translation", "Initial translation") }}
          <textarea v-model.trim="keyForm.translated_value" rows="3"></textarea>
        </label>
        <label class="checkbox">
          <input v-model="keyForm.is_final" type="checkbox" />
          {{ $t("status.final", "Final") }}
        </label>
        <div class="modal-actions">
          <button type="button" @click="saveKey" :disabled="loading">{{ $t("ui.save", "Save") }}</button>
          <button type="button" class="inline" @click="closeAddKey" :disabled="loading">{{ $t("ui.cancel", "Cancel") }}</button>
        </div>
      </div>
    </div>

    <div v-if="toast" class="toast">{{ toast }}</div>
  </div>
</template>

<script>
import { applyI18nBundle } from "../i18n";
import { apiErrorMessage } from "../api-errors";

function emptyLanguageForm() {
  return { code: "", name_en: "", name_native: "", is_active: true };
}

function emptyKeyForm() {
  return { string_key: "", context: "", default_en: "", translated_value: "", is_final: false };
}

export default {
  name: "LocalizationAdminPage",
  data() {
    return {
      loading: false,
      error: "",
      toast: "",
      selectedLanguage: "hu",
      filters: {
        context: "",
        status: "all",
        query: ""
      },
      languages: [],
      contexts: [],
      items: [],
      total: 0,
      totalPages: 1,
      page: 1,
      pageSize: 50,
      editOpen: false,
      editForm: {
        string_key: "",
        context: "",
        default_en: "",
        translated_value: "",
        is_final: false
      },
      languageOpen: false,
      languageForm: emptyLanguageForm(),
      keyOpen: false,
      keyForm: emptyKeyForm()
    };
  },
  mounted() {
    this.loadAll();
  },
  methods: {
    async parseResponse(res) {
      const text = await res.text();
      let data = null;
      try {
        data = text ? JSON.parse(text) : {};
      } catch (_e) {
        data = null;
      }
      return { data, text };
    },
    responseErrorMessage(res, parsed, fallbackKey, fallbackText) {
      if (parsed && typeof parsed === "object") {
        return apiErrorMessage(parsed.error, fallbackKey, fallbackText);
      }
      const text = typeof parsed === "string" ? parsed.trim() : "";
      if (text) {
        return `${fallbackText}: HTTP ${res.status}`;
      }
      return this.$t(fallbackKey, fallbackText);
    },
    async loadAll() {
      this.loading = true;
      this.error = "";
      try {
        await this.fetchLanguages();
        await this.fetchStrings();
      } catch (err) {
        this.error = err && err.message ? err.message : this.$t("common.operation_failed", "Operation failed");
      } finally {
        this.loading = false;
      }
    },
    languageLabel(lang) {
      if (!lang) return "";
      const nativeName = lang.name_native || lang.code;
      const enName = lang.name_en || lang.code;
      return nativeName === enName ? `${nativeName} (${lang.code})` : `${nativeName} / ${enName} (${lang.code})`;
    },
    displayStatus(status) {
      if (status === "missing") return this.$t("status.missing", "Missing");
      if (status === "draft") return this.$t("status.draft", "Draft");
      return this.$t("status.final", "Final");
    },
    hasPlaceholders(text) {
      return /\{[a-zA-Z0-9_]+\}/.test(String(text || ""));
    },
    async fetchLanguages() {
      const res = await fetch("/api/admin/i18n/languages");
      if (res.status === 401 || res.status === 403) {
        window.dispatchEvent(new CustomEvent("wa-auth-changed", { detail: null }));
        this.$router.push("/login");
        return;
      }
      const { data, text } = await this.parseResponse(res);
      if (!res.ok) {
        throw new Error(this.responseErrorMessage(res, data || text, "localization.languages_load_failed", "Failed to load languages"));
      }
      if (!data || typeof data !== "object") {
        throw new Error(this.$t("localization.languages_load_failed", "Failed to load languages"));
      }
      const all = Array.isArray(data.items) ? data.items : [];
      const active = all.filter((row) => Number(row.is_active || 0) === 1);
      const selectable = active.filter((row) => row.code !== "en");
      this.languages = selectable.length ? selectable : active;
      if (!this.languages.some((row) => row.code === this.selectedLanguage)) {
        this.selectedLanguage = this.languages[0] && this.languages[0].code ? this.languages[0].code : "en";
      }
    },
    async fetchStrings() {
      const qs = new URLSearchParams();
      qs.set("language", this.selectedLanguage);
      qs.set("status", this.filters.status);
      qs.set("page", String(this.page));
      qs.set("page_size", String(this.pageSize));
      if (this.filters.context) qs.set("context", this.filters.context);
      if (this.filters.query) qs.set("query", this.filters.query);
      const res = await fetch(`/api/admin/i18n/strings?${qs.toString()}`);
      if (res.status === 401 || res.status === 403) {
        window.dispatchEvent(new CustomEvent("wa-auth-changed", { detail: null }));
        this.$router.push("/login");
        return;
      }
      const { data, text } = await this.parseResponse(res);
      if (!res.ok) {
        throw new Error(this.responseErrorMessage(res, data || text, "localization.strings_load_failed", "Failed to load localization strings"));
      }
      if (!data || typeof data !== "object") {
        throw new Error(this.$t("localization.strings_load_failed", "Failed to load localization strings"));
      }
      this.items = Array.isArray(data.items) ? data.items : [];
      this.contexts = Array.isArray(data.contexts) ? data.contexts : [];
      this.total = Number(data.total || 0);
      this.totalPages = Math.max(1, Number(data.total_pages || 1));
    },
    async refreshBundle() {
      const currentLanguage = (this.$i18n && this.$i18n.language) || "en";
      try {
        const res = await fetch(`/api/i18n?lang=${encodeURIComponent(currentLanguage)}`);
        if (!res.ok) return;
        const data = await res.json();
        applyI18nBundle(data);
      } catch (_e) {
      }
    },
    async applyFilters() {
      this.page = 1;
      this.loading = true;
      this.error = "";
      try {
        await this.fetchStrings();
      } catch (err) {
        this.error = err && err.message ? err.message : this.$t("common.operation_failed", "Operation failed");
      } finally {
        this.loading = false;
      }
    },
    clearFilters() {
      this.filters.context = "";
      this.filters.status = "all";
      this.filters.query = "";
      this.applyFilters();
    },
    prevPage() {
      if (this.page <= 1) return;
      this.page -= 1;
      this.applyPage();
    },
    nextPage() {
      if (this.page >= this.totalPages) return;
      this.page += 1;
      this.applyPage();
    },
    async applyPage() {
      this.loading = true;
      this.error = "";
      try {
        await this.fetchStrings();
      } catch (err) {
        this.error = err && err.message ? err.message : this.$t("common.operation_failed", "Operation failed");
      } finally {
        this.loading = false;
      }
    },
    openEdit(row) {
      this.editForm = {
        string_key: row.string_key || "",
        context: row.context || "",
        default_en: row.default_en || "",
        translated_value: row.translated_value || "",
        is_final: Number(row.is_final || 0) === 1
      };
      this.editOpen = true;
    },
    closeEdit() {
      this.editOpen = false;
    },
    async saveTranslation() {
      this.loading = true;
      this.error = "";
      try {
        const res = await fetch("/api/admin/i18n/translations", {
          method: "PUT",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            string_key: this.editForm.string_key,
            language: this.selectedLanguage,
            translated_value: this.editForm.translated_value,
            is_final: this.editForm.is_final
          })
        });
        const { data, text } = await this.parseResponse(res);
        if (!res.ok) {
          this.error = this.responseErrorMessage(res, data || text, "localization.save_translation_failed", "Failed to save translation");
          return;
        }
        this.closeEdit();
        await this.fetchStrings();
        await this.refreshBundle();
        this.toast = this.$t("localization.translation_saved", "Translation saved");
        setTimeout(() => { this.toast = ""; }, 1800);
      } catch (_e) {
        this.error = this.$t("localization.save_translation_failed", "Failed to save translation");
      } finally {
        this.loading = false;
      }
    },
    openAddLanguage() {
      this.languageForm = emptyLanguageForm();
      this.languageOpen = true;
    },
    closeAddLanguage() {
      this.languageOpen = false;
    },
    async saveLanguage() {
      this.loading = true;
      this.error = "";
      try {
        const res = await fetch("/api/admin/i18n/languages", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(this.languageForm)
        });
        const { data, text } = await this.parseResponse(res);
        if (!res.ok) {
          this.error = this.responseErrorMessage(res, data || text, "localization.save_language_failed", "Failed to add language");
          return;
        }
        this.closeAddLanguage();
        await this.fetchLanguages();
        await this.refreshBundle();
        this.toast = this.$t("localization.language_saved", "Language added");
        setTimeout(() => { this.toast = ""; }, 1800);
      } catch (_e) {
        this.error = this.$t("localization.save_language_failed", "Failed to add language");
      } finally {
        this.loading = false;
      }
    },
    openAddKey() {
      this.keyForm = emptyKeyForm();
      this.keyOpen = true;
    },
    closeAddKey() {
      this.keyOpen = false;
    },
    async saveKey() {
      this.loading = true;
      this.error = "";
      try {
        const res = await fetch("/api/admin/i18n/strings", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            string_key: this.keyForm.string_key,
            context: this.keyForm.context,
            default_en: this.keyForm.default_en,
            language: this.selectedLanguage,
            translated_value: this.keyForm.translated_value,
            is_final: this.keyForm.is_final
          })
        });
        const { data, text } = await this.parseResponse(res);
        if (!res.ok) {
          this.error = this.responseErrorMessage(res, data || text, "localization.save_key_failed", "Failed to add key");
          return;
        }
        this.closeAddKey();
        await this.fetchStrings();
        await this.refreshBundle();
        this.toast = this.$t("localization.key_saved", "Key added");
        setTimeout(() => { this.toast = ""; }, 1800);
      } catch (_e) {
        this.error = this.$t("localization.save_key_failed", "Failed to add key");
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
.filters {
  align-items: end;
  flex-wrap: wrap;
  gap: 0.9rem;
}

.grow {
  flex: 1 1 18rem;
}

.actions {
  margin-top: 0.9rem;
}

.status-pill {
  display: inline-block;
  padding: 0.18rem 0.55rem;
  border-radius: 999px;
  font-size: 0.82rem;
  font-weight: 600;
}

.status-missing {
  background: #fee2e2;
  color: #991b1b;
}

.status-draft {
  background: #fef3c7;
  color: #92400e;
}

.status-final {
  background: #dcfce7;
  color: #166534;
}

.localization-modal {
  width: min(44rem, 96vw);
}

.checkbox {
  display: flex;
  align-items: center;
  gap: 0.55rem;
}
</style>
