<template>
  <div class="page">
    <header class="hero">
      <h1>{{ $t("admin.assets", "Assets") }}</h1>
      <p>{{ $t("assets.description", "Documents and audio indexed in the archive.") }}</p>
    </header>

    <section class="panel">
      <div class="row">
        <button type="button" @click="scanNow" :disabled="loading">{{ $t("admin.scan_documents_audio", "Scan documents and audio") }}</button>
        <button type="button" class="inline" @click="refreshJobs" :disabled="loading">{{ $t("ui.refresh", "Refresh") }} {{ $t("admin.job_status", "Job status") }}</button>
      </div>
      <p v-if="jobs" class="muted">
        {{ $t("assets.jobs_summary", { queued: jobs.counts.queued || 0, running: jobs.counts.running || 0, done: jobs.counts.done || 0, error: jobs.counts.error || 0 }, "Jobs: queued {queued}, running {running}, done {done}, error {error}") }}
      </p>
      <p v-if="jobs && jobs.split" class="muted">
        Split: queued thumb {{ jobs.split.queued.doc_thumb || 0 }}, queued preview {{ jobs.split.queued.doc_pdf_preview || 0 }}
        <span v-if="jobs.split.queued.other">, queued other {{ jobs.split.queued.other }}</span>
        · running thumb {{ jobs.split.running.doc_thumb || 0 }}, running preview {{ jobs.split.running.doc_pdf_preview || 0 }}
        <span v-if="jobs.split.running.other">, running other {{ jobs.split.running.other }}</span>
      </p>
      <div class="row">
        <label>
          {{ $t("assets.search_path", "Search path") }}
          <input v-model.trim="filters.q" type="text" placeholder="2020/Budapest" />
        </label>
        <label>
          Type
          <select v-model="filters.type">
            <option value="">{{ $t("common.any", "Any") }}</option>
            <option value="doc">{{ $t("search.type.documents", "Documents") }}</option>
            <option value="audio">{{ $t("search.type.audio", "Audio") }}</option>
          </select>
        </label>
        <label>
          {{ $t("assets.ext", "Ext") }}
          <input v-model.trim="filters.ext" type="text" placeholder="pdf" />
        </label>
        <label>
          {{ $t("assets.derivative_status", "Derivative status") }}
          <select v-model="filters.status">
            <option value="">{{ $t("common.any", "Any") }}</option>
            <option value="pending">{{ $t("status.pending", "Pending") }}</option>
            <option value="ready">{{ $t("status.ready", "Ready") }}</option>
            <option value="error">{{ $t("common.error", "Error") }}</option>
          </select>
        </label>
        <label>
          {{ $t("misc.limit", "Limit") }}
          <select v-model.number="pageSize">
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </label>
      </div>
      <div class="row actions">
        <button type="button" @click="applyFilters" :disabled="loading">{{ $t("ui.apply", "Apply") }}</button>
        <button type="button" class="inline" @click="clearFilters" :disabled="loading">{{ $t("ui.clear", "Clear") }}</button>
      </div>
      <p v-if="error" class="error">{{ error }}</p>
    </section>

    <section class="results">
      <div class="meta">
        <span v-if="loading">{{ $t("common.loading", "Loading...") }}</span>
        <span v-else>Total: {{ total }} · {{ $t("audit.page_of", { x: page, y: totalPages }, "Page {x} of {y}") }}</span>
      </div>
      <div class="status-counters">
        <span>{{ $t("status.pending", "Pending") }}: {{ counters.pending }}</span>
        <span>{{ $t("status.running", "Running") }}: {{ counters.running }}</span>
        <span>{{ $t("status.ready", "Ready") }}: {{ counters.ready }}</span>
        <span>{{ $t("status.no_processing", "No processing needed") }}: {{ counters.no_processing }}</span>
        <span>{{ $t("status.failed", "Failed") }}: {{ counters.failed }}</span>
      </div>
      <div class="status-tabs">
        <button type="button" class="inline" :class="{ active: statusTab === 'pending' }" @click="statusTab = 'pending'">{{ $t("status.pending", "Pending") }}</button>
        <button type="button" class="inline" :class="{ active: statusTab === 'running' }" @click="statusTab = 'running'">{{ $t("status.running", "Running") }}</button>
        <button type="button" class="inline" :class="{ active: statusTab === 'ready' }" @click="statusTab = 'ready'">{{ $t("status.ready", "Ready") }}</button>
        <button type="button" class="inline" :class="{ active: statusTab === 'no_processing' }" @click="statusTab = 'no_processing'">{{ $t("status.no_processing", "No processing needed") }}</button>
        <button type="button" class="inline" :class="{ active: statusTab === 'failed' }" @click="statusTab = 'failed'">{{ $t("status.failed", "Failed") }}</button>
        <button
          type="button"
          class="inline"
          @click="clearList"
          :disabled="clearableCount === 0"
          :title="$t('assets.clear_done_only', 'Only completed items can be cleared.')"
        >
          {{ $t("admin.scan_clear_list", "Clear list") }}
        </button>
      </div>

      <div class="pager" v-if="totalPages > 1">
        <button :disabled="loading || page <= 1" @click="prevPage">{{ $t("ui.previous", "Previous") }}</button>
        <button :disabled="loading || page >= totalPages" @click="nextPage">{{ $t("ui.next", "Next") }}</button>
      </div>

      <table class="results-table" v-if="rowsForTab.length">
        <thead>
          <tr>
            <th>{{ $t("common.id", "ID") }}</th>
            <th>{{ $t("common.path", "Path") }}</th>
            <th>{{ $t("common.type", "Type") }}</th>
            <th>{{ $t("assets.ext", "Ext") }}</th>
            <th>{{ $t("common.size", "Size") }}</th>
            <th>{{ $t("assets.mtime", "MTime") }}</th>
            <th>
              <button class="sort-btn" type="button" @click="toggleSort('thumb_status')">
                Thumb {{ sortLabel('thumb_status') }}
              </button>
            </th>
            <th>
              <button class="sort-btn" type="button" @click="toggleSort('preview_status')">
                Preview {{ sortLabel('preview_status') }}
              </button>
            </th>
            <th>{{ $t("object.status", "Status") }}</th>
            <th>{{ $t("object.action", "Action") }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rowsForTab" :key="row.id">
            <td>{{ row.id }}</td>
            <td :title="row.rel_path">{{ row.rel_path }}</td>
            <td>{{ row.type }}</td>
            <td>{{ row.ext }}</td>
            <td>{{ formatSize(row.size) }}</td>
            <td>{{ formatTs(row.mtime) }}</td>
            <td>{{ displayStatus(row, "thumb") }}</td>
            <td>{{ displayStatus(row, "preview") }}</td>
            <td>{{ displayOverallStatus(row) }}</td>
            <td class="actions-col">
              <button
                v-if="canMoveAsset(row)"
                class="inline"
                type="button"
                @click="openMoveModal(row)"
                :disabled="loading"
              >
                {{ $t("asset_move.action") }}
              </button>
              <button
                v-if="canUndoAsset(row)"
                class="inline"
                type="button"
                @click="undoAssetMove(row)"
                :disabled="loading || undoSavingIds.includes(Number(row.id))"
              >
                {{ $t("asset_move.undo_action") }}
              </button>
              <button class="inline" type="button" @click="requeue(row, 'thumb')" :disabled="loading">{{ $t("assets.requeue_thumb", "Requeue thumb") }}</button>
              <button
                v-if="canRequeuePreview(row)"
                class="inline"
                type="button"
                @click="requeue(row, 'pdf_preview')"
                :disabled="loading"
              >
                Requeue preview
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else-if="!loading" class="muted">{{ $t("admin.no_items_section", "No items in this section.") }}</p>

      <div class="pager" v-if="totalPages > 1">
        <button :disabled="loading || page <= 1" @click="prevPage">{{ $t("ui.previous", "Previous") }}</button>
        <button :disabled="loading || page >= totalPages" @click="nextPage">{{ $t("ui.next", "Next") }}</button>
      </div>
    </section>

    <div v-if="toast" class="toast">{{ toast }}</div>
    <move-media-modal
      :is-open="moveOpen"
      :current-rel-path="moveItem ? moveItem.rel_path : ''"
      :current-name="moveItem ? fileName(moveItem.rel_path) : ''"
      :saving="moveSaving"
      i18n-prefix="asset_move"
      @close="closeMoveModal"
      @confirm="confirmMove"
    />
  </div>
</template>

<script>
import { apiErrorMessage } from "../api-errors";
import MoveMediaModal from "../components/MoveMediaModal.vue";

export default {
  name: "AssetsPage",
  components: { MoveMediaModal },
  data() {
    return {
      loading: false,
      error: "",
      items: [],
      total: 0,
      totalPages: 1,
      page: 1,
      pageSize: 50,
      filters: {
        q: "",
        type: "",
        ext: "",
        status: ""
      },
      jobs: {
        counts: { queued: 0, running: 0, done: 0, error: 0 },
        recent_errors: [],
        running: []
      },
      toast: "",
      sortField: "updated_at",
      sortDir: "desc",
      autoRefreshMs: 15000,
      refreshTimer: null,
      statusTab: "pending",
      clearedIds: [],
      moveOpen: false,
      moveSaving: false,
      moveItem: null,
      undoEligibilityById: {},
      undoSavingIds: []
    };
  },
  mounted() {
    this.load();
  },
  beforeUnmount() {
    this.stopAutoRefresh();
  },
  computed: {
    visibleItems() {
      if (!this.items.length) {
        return [];
      }
      const cleared = new Set(this.clearedIds);
      return this.items.filter((row) => !cleared.has(Number(row.id)));
    },
    counters() {
      const out = { pending: 0, running: 0, ready: 0, no_processing: 0, failed: 0 };
      this.visibleItems.forEach((row) => {
        const st = this.overallStatus(row);
        out[st] += 1;
      });
      return out;
    },
    rowsForTab() {
      return this.visibleItems.filter((row) => this.overallStatus(row) === this.statusTab);
    },
    clearableCount() {
      return this.visibleItems.filter((row) => {
        const st = this.overallStatus(row);
        return st === "ready" || st === "no_processing";
      }).length;
    }
  },
  methods: {
    async load() {
      this.loading = true;
      this.error = "";
      try {
        await Promise.all([this.fetchAssets(), this.refreshJobs()]);
      } finally {
        this.loading = false;
      }
    },
    async fetchAssets() {
      const qs = new URLSearchParams();
      qs.set("page", String(this.page));
      qs.set("page_size", String(this.pageSize));
      if (this.filters.q) qs.set("q", this.filters.q);
      if (this.filters.type) qs.set("type", this.filters.type);
      if (this.filters.ext) qs.set("ext", this.filters.ext.toLowerCase());
      if (this.filters.status) qs.set("status", this.filters.status);
      qs.set("sort_field", this.sortField);
      qs.set("sort_dir", this.sortDir);

      const res = await fetch(`/api/admin/assets?${qs.toString()}`);
      if (res.status === 401 || res.status === 403) {
        window.dispatchEvent(new CustomEvent("wa-auth-changed", { detail: null }));
        this.$router.push("/login");
        return;
      }
      const data = await res.json();
      if (!res.ok) {
        this.error = apiErrorMessage(data.error, "assets.load_failed", "Failed to load assets");
        this.items = [];
        this.total = 0;
        this.totalPages = 1;
        return;
      }
      this.items = data.items || [];
      this.total = Number(data.total || 0);
      this.totalPages = Math.max(1, Number(data.total_pages || 1));
      this.page = Number(data.page || this.page);
      this.pageSize = Number(data.page_size || this.pageSize);
      this.sortField = String(data.sort_field || this.sortField);
      this.sortDir = String(data.sort_dir || this.sortDir);
      await this.refreshUndoEligibility();
      this.updateRefreshPolicy();
    },
    async refreshUndoEligibility() {
      const next = {};
      const rows = (this.items || []).filter((row) => this.canMoveAsset(row));
      await Promise.all(rows.map(async (row) => {
        const id = Number(row && row.id ? row.id : 0);
        if (!id) {
          return;
        }
        try {
          const res = await fetch(`/api/admin/assets/${id}/undo-eligibility`);
          if (res.status === 401 || res.status === 403) {
            return;
          }
          const data = await res.json().catch(() => ({}));
          if (res.ok && data && data.available) {
            next[id] = data;
          }
        } catch (_e) {
          // non-blocking
        }
      }));
      this.undoEligibilityById = next;
    },
    async refreshJobs() {
      const res = await fetch("/api/admin/jobs/status");
      if (res.status === 401 || res.status === 403) {
        return;
      }
      const data = await res.json();
      if (res.ok) {
        this.jobs = data;
      }
    },
    startAutoRefresh() {
      this.stopAutoRefresh();
      this.refreshTimer = window.setInterval(async () => {
        if (this.counters.pending === 0 && this.counters.running === 0) {
          this.stopAutoRefresh();
          return;
        }
        if (this.loading) return;
        try {
          await Promise.all([this.fetchAssets(), this.refreshJobs()]);
        } catch (_e) {
          // keep silent; explicit actions still show errors
        }
      }, this.autoRefreshMs);
    },
    stopAutoRefresh() {
      if (this.refreshTimer) {
        clearInterval(this.refreshTimer);
        this.refreshTimer = null;
      }
    },
    updateRefreshPolicy() {
      if ((this.counters.pending > 0 || this.counters.running > 0) && !this.refreshTimer) {
        this.startAutoRefresh();
      }
      if (this.counters.pending === 0 && this.counters.running === 0 && this.refreshTimer) {
        this.stopAutoRefresh();
      }
    },
    toggleSort(field) {
      if (this.sortField === field) {
        this.sortDir = this.sortDir === "asc" ? "desc" : "asc";
      } else {
        this.sortField = field;
        this.sortDir = "asc";
      }
      this.page = 1;
      this.fetchAssets();
    },
    sortLabel(field) {
      if (this.sortField !== field) return "";
      return this.sortDir === "asc" ? "↑" : "↓";
    },
    displayStatus(row, kind) {
      const value = kind === "thumb" ? row.thumb_status : row.preview_status;
      if (!value || value === "na") {
        return "N/A";
      }
      if (value === "error") {
        return this.$t("status.failed", "Failed");
      }
      if (value === "pending") {
        return this.$t("status.pending", "Pending");
      }
      if (value === "running") {
        return this.$t("status.running", "Running");
      }
      if (value === "ready") {
        return this.$t("status.ready", "Ready");
      }
      return String(value);
    },
    overallStatus(row) {
      const thumbApplicable = Number(row.thumb_applicable || 0) === 1;
      const previewApplicable = Number(row.preview_applicable || 0) === 1;
      const thumb = String(row.thumb_status || "").toLowerCase();
      const preview = String(row.preview_status || "").toLowerCase();
      if (!thumbApplicable && !previewApplicable) {
        return "no_processing";
      }
      if (thumb === "error" || preview === "error") {
        return "failed";
      }
      if (thumb === "running" || preview === "running") {
        return "running";
      }
      if (thumb === "pending" || preview === "pending") {
        return "pending";
      }
      return "ready";
    },
    displayOverallStatus(row) {
      const status = this.overallStatus(row);
      if (status === "pending") return this.$t("status.pending", "Pending");
      if (status === "running") return this.$t("status.running", "Running");
      if (status === "ready") return this.$t("status.ready", "Ready");
      if (status === "no_processing") return this.$t("status.no_processing", "No processing needed");
      if (status === "failed") return this.$t("status.failed", "Failed");
      return "N/A";
    },
    clearList() {
      if (this.clearableCount === 0) {
        return;
      }
      const ids = this.visibleItems
        .filter((row) => {
          const st = this.overallStatus(row);
          return st === "ready" || st === "no_processing";
        })
        .map((row) => Number(row.id));
      this.clearedIds = Array.from(new Set([...this.clearedIds, ...ids]));
      this.updateRefreshPolicy();
    },
    async scanNow() {
      if (!window.confirm(this.$t("assets.scan_confirm", "Scan your photo library for documents and audio files, then queue any required processing (thumbnails/previews) for supported document types.\n\nNote: Audio items don’t generate thumbnails or previews."))) {
        return;
      }
      this.loading = true;
      try {
        const res = await fetch("/api/admin/assets/scan", { method: "POST" });
        const data = await res.json();
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "assets.scan_failed", "Scan failed");
          return;
        }
        this.toast = this.$t("assets.scan_done", { scanned: data.scanned, docs: data.scanned_docs || 0, audio: data.scanned_audio || 0, jobs: data.jobs_enqueued }, "Scan done. Scanned {scanned} (Documents: {docs}, Audio: {audio}), enqueued {jobs} doc jobs.");
        setTimeout(() => (this.toast = ""), 2000);
        this.clearedIds = [];
        this.statusTab = "pending";
        await this.load();
      } finally {
        this.loading = false;
      }
    },
    async requeue(row, kind) {
      this.loading = true;
      try {
        const res = await fetch("/api/admin/assets/requeue", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ asset_id: row.id, kind })
        });
        const data = await res.json();
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "assets.requeue_failed", "Requeue failed");
          return;
        }
        this.toast = this.$t("assets.requeue_done", { items: data.queued.join(", ") }, "Queued: {items}");
        setTimeout(() => (this.toast = ""), 1800);
        await this.fetchAssets();
        await this.refreshJobs();
      } finally {
        this.loading = false;
      }
    },
    canRequeuePreview(row) {
      return row.type === "doc" && ["txt", "doc", "docx", "xls", "xlsx", "ppt", "pptx"].includes((row.ext || "").toLowerCase());
    },
    canMoveAsset(row) {
      if (!row) {
        return false;
      }
      const type = String(row.type || "").toLowerCase();
      const ext = String(row.ext || "").toLowerCase();
      if (type === "audio") {
        return ["mp3", "m4a", "flac"].includes(ext);
      }
      if (type === "doc") {
        return ["pdf", "txt", "doc", "docx", "xls", "xlsx", "ppt", "pptx"].includes(ext);
      }
      return false;
    },
    canUndoAsset(row) {
      const id = Number(row && row.id ? row.id : 0);
      return !!(id && this.undoEligibilityById[id] && this.undoEligibilityById[id].available);
    },
    openMoveModal(row) {
      if (!this.canMoveAsset(row)) {
        return;
      }
      this.moveItem = row;
      this.moveOpen = true;
    },
    closeMoveModal(force = false) {
      if (this.moveSaving && !force) {
        return;
      }
      this.moveOpen = false;
      this.moveItem = null;
    },
    async confirmMove({ targetRelPath }) {
      if (!this.moveItem || !targetRelPath) {
        return;
      }
      this.moveSaving = true;
      this.error = "";
      try {
        const res = await fetch(`/api/admin/assets/${this.moveItem.id}/move`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ target_rel_path: targetRelPath })
        });
        if (res.status === 401 || res.status === 403) {
          window.dispatchEvent(new CustomEvent("wa-auth-changed", { detail: null }));
          this.$router.push("/login");
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "asset_move.failed", "Failed to move asset");
          return;
        }
        const renamedDueToCollision = !!data.renamed_due_to_collision
          || (data.desired_new_rel_path && data.new_rel_path && data.desired_new_rel_path !== data.new_rel_path);
        this.toast = renamedDueToCollision && Array.isArray(data.warnings) && data.warnings.includes("derivative_cleanup_failed")
          ? this.$t("asset_move.completed_renamed_with_warnings")
          : renamedDueToCollision
            ? this.$t("asset_move.completed_renamed")
            : Array.isArray(data.warnings) && data.warnings.includes("derivative_cleanup_failed")
              ? this.$t("asset_move.success_with_warnings")
              : this.$t("asset_move.success");
        setTimeout(() => (this.toast = ""), 2200);
        this.closeMoveModal(true);
        await this.fetchAssets();
      } catch (_e) {
        this.error = this.$t("asset_move.failed");
      } finally {
        this.moveSaving = false;
      }
    },
    async undoAssetMove(row) {
      const id = Number(row && row.id ? row.id : 0);
      const eligibility = id ? this.undoEligibilityById[id] : null;
      if (!id || !eligibility || !eligibility.available) {
        return;
      }
      const ok = window.confirm(
        this.$t("asset_move.undo_confirm_message", {
          current: eligibility.current_rel_path || row.rel_path || "",
          destination: eligibility.original_rel_path || ""
        })
      );
      if (!ok) {
        return;
      }
      this.undoSavingIds = [...this.undoSavingIds, id];
      this.error = "";
      try {
        const res = await fetch(`/api/admin/assets/${id}/undo`, {
          method: "POST",
          headers: { "Content-Type": "application/json" }
        });
        if (res.status === 401 || res.status === 403) {
          window.dispatchEvent(new CustomEvent("wa-auth-changed", { detail: null }));
          this.$router.push("/login");
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "api.undo_failed", this.$t("undo.failed"));
          await this.refreshUndoEligibility();
          return;
        }
        const renamedDueToCollision = !!data.renamed_due_to_collision
          || (data.desired_new_rel_path && data.new_rel_path && data.desired_new_rel_path !== data.new_rel_path);
        this.toast = renamedDueToCollision
          ? this.$t("asset_move.undo_completed_renamed")
          : this.$t("asset_move.undo_completed");
        setTimeout(() => (this.toast = ""), 2200);
        await this.fetchAssets();
      } catch (_e) {
        this.error = this.$t("undo.failed");
      } finally {
        this.undoSavingIds = this.undoSavingIds.filter((value) => value !== id);
      }
    },
    applyFilters() {
      this.page = 1;
      this.fetchAssets();
    },
    clearFilters() {
      this.filters = { q: "", type: "", ext: "", status: "" };
      this.page = 1;
      this.clearedIds = [];
      this.fetchAssets();
    },
    prevPage() {
      if (this.page > 1) {
        this.page -= 1;
        this.fetchAssets();
      }
    },
    nextPage() {
      if (this.page < this.totalPages) {
        this.page += 1;
        this.fetchAssets();
      }
    },
    formatSize(size) {
      const n = Number(size || 0);
      if (n < 1024) return `${n} B`;
      if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`;
      return `${(n / (1024 * 1024)).toFixed(1)} MB`;
    },
    formatTs(ts) {
      const n = Number(ts || 0);
      if (!n) return "—";
      try {
        return new Date(n * 1000).toISOString().slice(0, 19).replace("T", " ");
      } catch (_e) {
        return "—";
      }
    },
    fileName(path) {
      const value = String(path || "");
      if (!value) {
        return "";
      }
      const parts = value.split("/");
      return parts[parts.length - 1] || value;
    }
  }
};
</script>

<style scoped>
.actions-col {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.sort-btn {
  background: none;
  border: none;
  padding: 0;
  font: inherit;
  color: inherit;
  cursor: pointer;
}

.sort-btn:hover {
  text-decoration: underline;
}

.status-counters {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin: 8px 0;
}

.status-tabs {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 10px;
}

.status-tabs .active {
  background: var(--accent);
  color: #fff;
}

</style>
