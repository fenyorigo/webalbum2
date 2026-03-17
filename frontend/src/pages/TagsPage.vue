<template>
  <div class="page">
    <header class="hero">
      <h1>{{ $t("tag_admin.title", "Tag Admin") }}</h1>
      <p>{{ $t("tag_admin.description", "Control tag visibility for global and personal scope.") }}</p>
    </header>

    <section class="panel">
      <div class="row">
        <label>
          {{ $t("misc.search", "Search") }}
          <input v-model.trim="query" :placeholder="$t('tags.filter_placeholder', 'Filter tags...')" />
        </label>
        <label>
          {{ $t("misc.limit", "Limit") }}
          <input v-model.number="limit" type="number" min="10" max="200" />
        </label>
        <label v-if="isAdmin" class="checkbox">
          <input type="checkbox" v-model="revealHidden" />
          {{ $t("tags.reveal_hidden", "Reveal hidden tags") }}
        </label>
        <button class="inline" :disabled="loading" @click="fetchTags">{{ $t("ui.refresh", "Refresh") }}</button>
        <button
          v-if="isAdmin"
          class="inline"
          :disabled="loading"
          @click="reenableAllTags"
        >
          {{ $t("tags.reenable", "Re-enable tags") }}
        </button>
        <button
          v-if="isAdmin"
          class="inline"
          :disabled="loading"
          @click="exportTagsCsv"
        >
          {{ $t("tags.export", "Export tags") }}
        </button>
        <button class="inline" :disabled="loading || !hasChanges" @click="saveAll">{{ $t("ui.save", "Save") }}</button>
        <button class="inline" :disabled="loading" @click="cancelChanges">{{ $t("ui.cancel", "Cancel") }}</button>
      </div>
      <p v-if="error" class="error">{{ error }}</p>
    </section>

    <section class="results">
      <div class="meta">
        <span v-if="loading">{{ $t("common.loading", "Loading...") }}</span>
        <span v-else-if="total === null">{{ $t("search.results_empty", "Results: —") }}</span>
        <span v-else>{{ $t("results.title", "Results") }}: {{ rows.length }} of {{ total }}</span>
      </div>
      <div class="pager" v-if="total !== null">
        <button :disabled="page === 1 || loading" @click="prevPage">{{ $t("ui.previous", "Previous") }}</button>
        <span>{{ $t("audit.page_of", { x: page, y: totalPages }, "Page {x} of {y}") }}</span>
        <input
          v-model.number="pageInput"
          type="number"
          min="1"
          :max="totalPages"
          :placeholder="$t('ui.go', 'Go')"
        />
        <button :disabled="loading" @click="jumpToPage">{{ $t("ui.go", "Go") }}</button>
        <button :disabled="page >= totalPages || loading" @click="nextPage">{{ $t("ui.next", "Next") }}</button>
      </div>
      <table class="tags-table" v-if="rows.length">
        <thead>
          <tr>
            <th>
              <button type="button" class="sort-link" @click="toggleSort('tag')">
                {{ $t("tags.single", "Tag") }} {{ sortIndicator('tag') }}
              </button>
            </th>
            <th>{{ $t("tags.variants", "Variants") }}</th>
            <th>
              <button type="button" class="sort-link" @click="toggleSort('images')">
                {{ $t("results.images", "images") }} {{ sortIndicator('images') }}
              </button>
            </th>
            <th v-if="isAdmin">{{ $t("tags.enabled_global", "Enabled (Global)") }}</th>
            <th>{{ isAdmin ? $t("tags.enabled_personal", "Enabled (Personal)") : $t("tags.enabled", "Enabled") }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows" :key="row.tag">
            <td class="tag">{{ row.tag }}</td>
            <td class="variants">
              <span class="dots" :title="variantsTitle(row.variants)">
                {{ variantsDots(row.variants) }}
              </span>
            </td>
            <td>{{ row.image_count || 0 }}</td>
            <td v-if="isAdmin">
              <input type="checkbox" v-model="row.enabled_global" @change="markDirty(row, 'global')" />
            </td>
            <td>
              <input type="checkbox" v-model="row.enabled_personal" @change="markDirty(row, 'personal')" />
            </td>
          </tr>
        </tbody>
      </table>
    </section>

    <section v-if="isAdmin" class="panel semantic-tags-panel">
      <div class="row">
        <label>
          {{ $t("semantic_tags.search", "Semantic tag search") }}
          <input v-model.trim="semanticQuery" :placeholder="$t('semantic_tags.search_placeholder', 'Find typed tags...')" />
        </label>
        <label>
          {{ $t("semantic_tags.type", "Tag type") }}
          <select v-model="semanticType" @change="fetchSemanticTags">
            <option value="">{{ $t("status.all", "All") }}</option>
            <option value="person">{{ $t("semantic_tags.type_person", "Person") }}</option>
            <option value="event">{{ $t("semantic_tags.type_event", "Event") }}</option>
            <option value="category">{{ $t("semantic_tags.type_category", "Category") }}</option>
            <option value="generic">{{ $t("semantic_tags.type_generic", "Generic") }}</option>
          </select>
        </label>
        <label>
          {{ $t("semantic_tags.active", "Active") }}
          <select v-model="semanticActive" @change="fetchSemanticTags">
            <option value="all">{{ $t("status.all", "All") }}</option>
            <option value="active">{{ $t("common.active", "Active") }}</option>
            <option value="inactive">{{ $t("common.inactive", "Inactive") }}</option>
          </select>
        </label>
        <button class="inline" :disabled="loading" @click="fetchSemanticTags">{{ $t("ui.refresh", "Refresh") }}</button>
        <button class="inline" :disabled="loading" @click="openSemanticCreate">{{ $t("semantic_tags.add", "Add typed tag") }}</button>
      </div>
      <p v-if="semanticError" class="error">{{ semanticError }}</p>
      <table class="tags-table" v-if="semanticRows.length">
        <thead>
          <tr>
            <th>{{ $t("semantic_tags.name", "Tag name") }}</th>
            <th>{{ $t("semantic_tags.type", "Tag type") }}</th>
            <th>{{ $t("semantic_tags.parent", "Parent tag") }}</th>
            <th>{{ $t("semantic_tags.usage", "Usage") }}</th>
            <th>{{ $t("semantic_tags.usage_state", "State") }}</th>
            <th>{{ $t("common.active", "Active") }}</th>
            <th>{{ $t("object.action", "Action") }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in semanticRows" :key="row.id">
            <td>{{ row.name }}</td>
            <td>{{ semanticTypeLabel(row.tag_type) }}</td>
            <td>{{ row.parent_tag_name || "—" }}</td>
            <td>{{ row.usage_count || 0 }}</td>
            <td>{{ row.usage_state === "orphan" ? $t("semantic_tags.orphan", "Orphan") : $t("semantic_tags.used", "Used") }}</td>
            <td>{{ row.is_active ? $t("common.active", "Active") : $t("common.inactive", "Inactive") }}</td>
            <td>
              <button class="inline" type="button" @click="openSemanticEdit(row)">{{ $t("common.edit", "Edit") }}</button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else class="muted">{{ $t("semantic_tags.empty", "No typed tags yet.") }}</p>
    </section>
    <semantic-tag-create-modal
      :is-open="semanticModalOpen"
      :initial-name="semanticInitialName"
      :edit-item="semanticEditItem"
      @close="closeSemanticModal"
      @created="handleSemanticSaved"
    />
  </div>
</template>

<script>
import { apiErrorMessage } from "../api-errors";
import SemanticTagCreateModal from "../components/SemanticTagCreateModal.vue";

export default {
  name: "TagsPage",
  components: { SemanticTagCreateModal },
  data() {
    return {
      query: "",
      limit: 50,
      page: 1,
      pageInput: null,
      rows: [],
      total: null,
      loading: false,
      error: "",
      dirty: {},
      original: {},
      isAdmin: false,
      revealHidden: false,
      sortField: "tag",
      sortDir: "asc",
      semanticRows: [],
      semanticQuery: "",
      semanticType: "",
      semanticActive: "all",
      semanticError: "",
      semanticModalOpen: false,
      semanticInitialName: "",
      semanticEditItem: null
    };
  },
  mounted() {
    const current = window.__wa_current_user || null;
    this.isAdmin = !!(current && current.is_admin);
    this.fetchTags();
    if (this.isAdmin) {
      this.fetchSemanticTags();
    }
  },
  computed: {
    totalPages() {
      if (this.total === null || this.total === 0) {
        return 1;
      }
      return Math.max(1, Math.ceil(this.total / this.limit));
    },
    hasChanges() {
      return Object.keys(this.dirty).length > 0;
    }
  },
  methods: {
    async fetchTags() {
      this.loading = true;
      this.error = "";
      try {
        const offset = (this.page - 1) * this.limit;
        const qs = new URLSearchParams();
        if (this.query) {
          qs.set("q", this.query);
        }
        if (this.isAdmin && this.revealHidden) {
          qs.set("reveal_hidden", "1");
        }
        qs.set("limit", String(this.limit));
        qs.set("offset", String(offset));
        qs.set("sort_field", this.sortField);
        qs.set("sort_dir", this.sortDir);
        const res = await fetch(`/api/tags/list?${qs.toString()}`);
        if (this.handleAuthError(res)) {
          return;
        }
        const data = await res.json();
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "tags.load_failed", "Failed to load tags");
          this.rows = [];
          this.total = null;
          return;
        }

        this.isAdmin = !!data.is_admin;
        if (this.isAdmin && this.semanticRows.length === 0) {
          this.fetchSemanticTags();
        }
        this.rows = (data.rows || []).map((row) => ({
          ...row,
          enabled_global: !!row.enabled_global,
          enabled_personal: !!row.enabled_personal
        }));
        this.total = typeof data.total === "number" ? data.total : 0;
        this.pageInput = this.page;
        this.dirty = {};
        this.original = {};
        this.rows.forEach((row) => {
          this.original[row.tag] = {
            enabled_global: !!row.enabled_global,
            enabled_personal: !!row.enabled_personal
          };
        });
      } catch (err) {
        this.error = err.message || String(err);
      } finally {
        this.loading = false;
      }
    },
    semanticTypeLabel(type) {
      if (type === "person") return this.$t("semantic_tags.type_person", "Person");
      if (type === "event") return this.$t("semantic_tags.type_event", "Event");
      if (type === "category") return this.$t("semantic_tags.type_category", "Category");
      return this.$t("semantic_tags.type_generic", "Generic");
    },
    async fetchSemanticTags() {
      if (!this.isAdmin) {
        return;
      }
      this.semanticError = "";
      try {
        const qs = new URLSearchParams({
          page: "1",
          page_size: "200",
          active: this.semanticActive
        });
        if (this.semanticQuery) qs.set("q", this.semanticQuery);
        if (this.semanticType) qs.set("tag_type", this.semanticType);
        const res = await fetch(`/api/admin/semantic-tags?${qs.toString()}`);
        if (this.handleAuthError(res)) {
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.semanticError = apiErrorMessage(data.error, "semantic_tags.load_failed", "Failed to load typed tags");
          return;
        }
        this.semanticRows = Array.isArray(data.items) ? data.items : [];
      } catch (_e) {
        this.semanticError = this.$t("semantic_tags.load_failed", "Failed to load typed tags");
      }
    },
    openSemanticCreate() {
      this.semanticInitialName = "";
      this.semanticEditItem = null;
      this.semanticModalOpen = true;
    },
    openSemanticEdit(row) {
      this.semanticInitialName = "";
      this.semanticEditItem = row;
      this.semanticModalOpen = true;
    },
    closeSemanticModal() {
      this.semanticModalOpen = false;
      this.semanticEditItem = null;
      this.semanticInitialName = "";
    },
    handleSemanticSaved() {
      this.closeSemanticModal();
      this.fetchSemanticTags();
    },
    markDirty(row) {
      const original = this.original[row.tag];
      if (!original) {
        return;
      }
      const changed =
        (!!row.enabled_global !== !!original.enabled_global) ||
        (!!row.enabled_personal !== !!original.enabled_personal);
      if (changed) {
        this.dirty[row.tag] = true;
      } else {
        delete this.dirty[row.tag];
      }
    },
    variantsDots(count) {
      if (count >= 3) {
        return "●●●";
      }
      if (count === 2) {
        return "●●○";
      }
      return "●○○";
    },
    variantsTitle(count) {
      if (count >= 3) {
        return "IPTC keyword, XMP subject, Face/person region";
      }
      if (count === 2) {
        return "IPTC keyword, XMP subject";
      }
      return "IPTC keyword";
    },
    sortIndicator(field) {
      if (this.sortField !== field) {
        return "";
      }
      return this.sortDir === "asc" ? "↑" : "↓";
    },
    toggleSort(field) {
      if (this.sortField === field) {
        this.sortDir = this.sortDir === "asc" ? "desc" : "asc";
      } else {
        this.sortField = field;
        this.sortDir = field === "images" ? "desc" : "asc";
      }
      this.page = 1;
      this.fetchTags();
    },
    async saveAll() {
      const tags = Object.keys(this.dirty);
      if (tags.length === 0) {
        this.$router.push("/");
        return;
      }

      this.loading = true;
      this.error = "";
      try {
        const requests = [];
        tags.forEach((tag) => {
          const row = this.rows.find((r) => r.tag === tag);
          const original = this.original[tag];
          if (!row || !original) {
            return;
          }

          if (this.isAdmin && !!row.enabled_global !== !!original.enabled_global) {
            requests.push(
              fetch("/api/tags/prefs", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                  tag,
                  scope: "global",
                  enabled: row.enabled_global ? 1 : 0
                })
              })
            );
          }

          if (!!row.enabled_personal !== !!original.enabled_personal) {
            requests.push(
              fetch("/api/tags/prefs", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                  tag,
                  scope: "personal",
                  enabled: row.enabled_personal ? 1 : 0
                })
              })
            );
          }
        });

        for (const req of requests) {
          const res = await req;
          if (this.handleAuthError(res)) {
            return;
          }
          if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            throw new Error(apiErrorMessage(data.error, "tags.save_failed", "Failed to save tag settings"));
          }
        }
        this.$router.push("/");
      } catch (err) {
        this.error = err.message || String(err);
      } finally {
        this.loading = false;
      }
    },
    async reenableAllTags() {
      if (!this.isAdmin) {
        return;
      }
      if (!window.confirm(this.$t("tags.reenable_confirm", "Re-enable all tags globally and for all users?"))) {
        return;
      }
      this.loading = true;
      this.error = "";
      try {
        const res = await fetch("/api/admin/tags/reenable-all", { method: "POST" });
        if (this.handleAuthError(res)) {
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "tags.reenable_failed", "Failed to re-enable tags");
          return;
        }
        window.alert(this.$t("tags.reenable_done", "All tags are re-enabled."));
        this.page = 1;
        await this.fetchTags();
      } catch (err) {
        this.error = err && err.message ? err.message : this.$t("tags.reenable_failed", "Failed to re-enable tags");
      } finally {
        this.loading = false;
      }
    },
    buildExportTagsFilename() {
      const now = new Date();
      const pad = (n) => String(n).padStart(2, "0");
      return `tags-export-${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}-${pad(now.getHours())}-${pad(now.getMinutes())}.csv`;
    },
    parseFilenameFromContentDisposition(value) {
      if (!value) {
        return "";
      }
      const utf8Match = value.match(/filename\*=UTF-8''([^;]+)/i);
      if (utf8Match && utf8Match[1]) {
        try {
          return decodeURIComponent(utf8Match[1].replace(/["']/g, "").trim());
        } catch (_err) {
          return utf8Match[1].replace(/["']/g, "").trim();
        }
      }
      const plainMatch = value.match(/filename="?([^";]+)"?/i);
      return plainMatch && plainMatch[1] ? plainMatch[1].trim() : "";
    },
    async exportTagsCsv() {
      if (!this.isAdmin) {
        return;
      }
      this.loading = true;
      this.error = "";
      try {
        const suggestedName = this.buildExportTagsFilename();
        const res = await fetch("/api/admin/tags/export");
        if (this.handleAuthError(res)) {
          return;
        }
        if (!res.ok) {
          const data = await res.json().catch(() => ({}));
          this.error = apiErrorMessage(data.error, "tags.export_failed", "Failed to export tags");
          return;
        }

        const blob = await res.blob();
        const headerName = this.parseFilenameFromContentDisposition(res.headers.get("Content-Disposition"));
        const filename = headerName || suggestedName;
        const hasPicker = typeof window.showSaveFilePicker === "function";

        if (hasPicker) {
          try {
            const handle = await window.showSaveFilePicker({
              suggestedName: filename,
              startIn: "downloads",
              types: [{ description: "CSV files", accept: { "text/csv": [".csv"] } }]
            });
            const writable = await handle.createWritable();
            await writable.write(blob);
            await writable.close();
            return;
          } catch (err) {
            if (!err || err.name !== "AbortError") {
              // fallback to normal browser download
            } else {
              return;
            }
          }
        }

        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
      } catch (_err) {
        this.error = this.$t("tags.export_failed", "Failed to export tags");
      } finally {
        this.loading = false;
      }
    },
    cancelChanges() {
      this.$router.push("/");
    },
    nextPage() {
      if (this.page < this.totalPages) {
        this.page += 1;
        this.fetchTags();
      }
    },
    prevPage() {
      if (this.page > 1) {
        this.page -= 1;
        this.fetchTags();
      }
    },
    jumpToPage() {
      const target = Number(this.pageInput);
      if (!Number.isFinite(target)) {
        return;
      }
      const clamped = Math.min(Math.max(1, target), this.totalPages);
      if (clamped !== this.page) {
        this.page = clamped;
        this.fetchTags();
      }
    },
    handleAuthError(res) {
      if (res.status === 401 || res.status === 403) {
        window.dispatchEvent(new CustomEvent("wa-auth-changed", { detail: null }));
        this.$router.push("/login");
        return true;
      }
      return false;
    }
  },
  watch: {
    query() {
      this.page = 1;
      this.fetchTags();
    },
    limit() {
      this.page = 1;
      this.fetchTags();
    },
    revealHidden() {
      if (this.isAdmin) {
        this.page = 1;
        this.fetchTags();
      }
    }
  }
};
</script>


<style scoped>
.sort-link {
  border: 0;
  background: transparent;
  padding: 0;
  margin: 0;
  font: inherit;
  color: inherit;
  cursor: pointer;
}

.sort-link:hover {
  text-decoration: underline;
}
</style>
