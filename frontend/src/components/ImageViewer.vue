<template>
  <div
    v-if="isOpen"
    class="viewer-backdrop"
    @click.self="close"
    role="dialog"
    aria-modal="true"
  >
    <div class="viewer-panel" ref="panel">
      <div class="viewer-bar">
        <button class="viewer-btn" @click="close" :aria-label="$t('ui.close', 'Close')">✕</button>
        <div class="viewer-title" :title="current?.path || ''">
          {{ fileName(current?.path || "") }}
        </div>
        <div class="viewer-count">{{ index + 1 }} / {{ results.length }}</div>
        <template v-if="pageCount > 1">
          <button class="viewer-btn" @click="prevPage" :disabled="currentPage <= 0" :aria-label="$t('viewer.previous_page', 'Previous page')">Page ‹</button>
          <div class="viewer-count">{{ currentPage + 1 }} / {{ pageCount }}</div>
          <button class="viewer-btn" @click="nextPage" :disabled="currentPage >= pageCount - 1" :aria-label="$t('viewer.next_page', 'Next page')">Page ›</button>
        </template>
        <button class="viewer-btn" @click="copyLink" :aria-label="$t('results.copy_link', 'Copy link')">{{ $t("results.copy_link", "Copy link") }}</button>
        <button class="viewer-btn" @click="downloadCurrent" :aria-label="$t('common.download', 'Download')">{{ $t("common.download", "Download") }}</button>
        <button class="viewer-btn" @click="openObject" :aria-label="$t('object.open', 'Open object')">{{ $t("common.object", "Object") }}</button>
        <label class="viewer-slideshow-control">
          <span>{{ $t("search.asset_seconds", "Sec") }}</span>
          <input
            :value="slideshowSeconds"
            type="number"
            min="1"
            max="3600"
            @input="onSlideshowSecondsInput"
          />
        </label>
        <button
          class="viewer-btn"
          @click="toggleSlideshow"
          :aria-label="slideshowActive ? $t('viewer.end_slideshow', 'End slideshow') : $t('viewer.start_slideshow', 'Start slideshow')"
        >
          {{ slideshowActive ? $t("viewer.end_slideshow", "End slideshow") : $t("viewer.start_slideshow", "Start slideshow") }}
        </button>
        <button v-if="canProposeRotate" class="viewer-btn" @click="rotateLeft" :aria-label="$t('viewer.rotate_ccw', 'Rotate counterclockwise')">↺</button>
        <button v-if="canProposeRotate" class="viewer-btn" @click="rotateRight" :aria-label="$t('viewer.rotate_cw', 'Rotate clockwise')">↻</button>
        <button
          v-if="canProposeRotate && pendingQuarterTurns !== 0"
          class="viewer-btn"
          :disabled="rotateSaving"
          @click="createRotateProposal"
          :aria-label="$t('viewer.create_proposal', 'Create proposal')"
        >
          {{ $t("viewer.create_proposal", "Create proposal") }}
        </button>
        <button
          v-if="canProposeRotate && pendingQuarterTurns !== 0"
          class="viewer-btn"
          :disabled="rotateSaving"
          @click="cancelPendingRotation"
          :aria-label="$t('viewer.cancel_rotation', 'Cancel preview rotation')"
        >
          {{ $t("ui.cancel", "Cancel") }}
        </button>
        <button
          v-if="canEditTags"
          class="viewer-btn"
          @click="openEditor"
          :aria-label="$t('viewer.edit_tags', 'Edit Tags')"
        >
          {{ $t("viewer.edit_tags", "Edit Tags") }}
        </button>
        <button
          v-if="canTrash"
          class="viewer-btn danger"
          @click="moveToTrash"
          :aria-label="$t('viewer.move_to_trash', 'Move to Trash')"
        >
          {{ $t("viewer.move_to_trash", "Move to Trash") }}
        </button>
      </div>

      <div class="viewer-body">
        <button
          class="nav-btn"
          :disabled="index <= 0"
          @click="prev"
          :aria-label="$t('ui.previous', 'Previous')"
        >
          ‹
        </button>
        <div class="viewer-media">
          <img
            v-if="current && current.type === 'image'"
            :src="currentImageSrc()"
            :alt="fileName(current.path)"
            class="viewer-img"
            :style="mediaTransformStyle"
            @error="onMediaError"
          />
          <div v-else class="viewer-placeholder">{{ $t("viewer.image_not_available", "Preview not supported for this file type") }}</div>
        </div>
        <button
          class="nav-btn"
          :disabled="index >= results.length - 1"
          @click="next"
          :aria-label="$t('ui.next', 'Next')"
        >
          ›
        </button>
      </div>
      <div v-if="currentTags.length" class="viewer-tags" :title="currentTags.join(', ')">
        {{ currentTags.join(", ") }}
      </div>
      <div v-if="currentSemanticTags.length" class="viewer-tags semantic-viewer-tags">
        <span v-for="item in currentSemanticTags" :key="`semantic:${item.id}`" class="tag-pill">
          {{ item.name }}
        </span>
      </div>
      <div v-if="mediaError" class="viewer-badge">{{ mediaError }}</div>
      <div v-if="rotateSaving" class="viewer-badge">{{ $t("viewer.create_proposal_busy", "Creating proposal...") }}</div>
      <div v-if="toast" class="viewer-inline-toast">{{ toast }}</div>
    </div>

    <div v-if="editOpen" class="modal-backdrop" @click.self="closeEditor">
      <div class="modal tag-editor-modal">
        <h3>{{ $t("viewer.edit_tags", "Edit Tags") }}</h3>
        <div class="tag-editor-chips">
          <span v-for="tag in editTags" :key="tag" class="tag-chip">
            {{ tag }}
            <button type="button" @click="removeEditTag(tag)" :aria-label="$t('viewer.remove_tag', 'Remove tag')">✕</button>
          </span>
        </div>
        <label>
          {{ $t("viewer.add_tag_label", "Add tag") }}
          <input
            v-model="editInput"
            type="text"
            :placeholder="$t('viewer.add_tag_placeholder', 'Type a tag and press Enter')"
            @input="onEditInput"
            @keydown.enter.prevent="addEditTagFromInput"
          />
        </label>
        <div v-if="editSuggestions.length" class="suggestions">
          <button
            v-for="item in editSuggestions"
            :key="item.tag"
            type="button"
            class="suggestion"
            @click="addEditTag(item.tag)"
          >
            <span class="name">{{ item.tag }}</span>
            <span class="count">{{ item.cnt }}</span>
          </button>
        </div>
        <div v-if="canEditTags && normalizedEditInput && !editTags.includes(normalizedEditInput)" class="modal-actions">
          <button class="inline" type="button" @click="openSemanticCreate">
            {{ $t("semantic_tags.create_and_assign", "Create typed tag and assign") }}
          </button>
        </div>
        <div class="semantic-editor-block">
          <div class="batch-tag-label">{{ $t("semantic_tags.current", "Typed tags") }}</div>
          <div v-if="editSemanticTags.length" class="tag-editor-chips">
            <span v-for="item in editSemanticTags" :key="`typed:${item.id}`" class="tag-chip">
              {{ item.name }}
              <small>({{ semanticSourcesLabel(item.relation_sources) }})</small>
              <button
                v-if="canRemoveManualSemantic(item)"
                type="button"
                @click="removeSemanticTag(item)"
                :aria-label="$t('semantic_tags.remove_manual', 'Remove manual relation')"
              >✕</button>
            </span>
          </div>
          <div v-else class="muted">{{ $t("semantic_tags.none_current", "No typed tags") }}</div>
          <label>
            {{ $t("semantic_tags.assign_tag", "Typed tag") }}
            <input
              v-model="semanticInput"
              type="text"
              :placeholder="$t('semantic_tags.assign_placeholder', 'Search typed tag...')"
              @input="onSemanticInput"
            />
          </label>
          <div v-if="semanticSuggestions.length" class="suggestions">
            <button
              v-for="item in semanticSuggestions"
              :key="`assign:${item.id}`"
              type="button"
              class="suggestion"
              @click="selectSemanticSuggestion(item)"
            >
              <span class="name">{{ item.name }}</span>
              <span class="count">{{ semanticTypeLabel(item.tag_type) }}</span>
            </button>
          </div>
          <div class="modal-actions">
            <button class="inline" type="button" :disabled="!semanticSelected || editLoading" @click="assignSemanticTag">
              {{ $t("semantic_tags.assign_here", "Assign here") }}
            </button>
          </div>
        </div>
        <div class="modal-actions">
          <button class="inline" @click="saveEditTags" :disabled="editLoading">{{ $t("ui.save", "Save") }}</button>
          <button class="inline" @click="restoreOriginalTags" :disabled="editLoading">{{ $t("viewer.restore_original", "Restore original") }}</button>
          <button class="inline" @click="closeEditor" :disabled="editLoading">{{ $t("ui.cancel", "Cancel") }}</button>
        </div>
        <p v-if="editError" class="error">{{ editError }}</p>
      </div>
    </div>
    <semantic-tag-create-modal
      :is-open="semanticCreateOpen"
      :initial-name="normalizedEditInput"
      @close="semanticCreateOpen = false"
      @created="handleSemanticCreated"
    />
  </div>
</template>

<script>
import { apiErrorMessage } from "../api-errors";
import SemanticTagCreateModal from "./SemanticTagCreateModal.vue";

export default {
  name: "ImageViewer",
  components: { SemanticTagCreateModal },
  props: {
    results: { type: Array, required: true },
    startId: { type: Number, required: true },
    isOpen: { type: Boolean, required: true },
    fileUrl: { type: Function, required: true },
    currentUser: { type: Object, default: null },
    slideshowActive: { type: Boolean, default: false },
    slideshowSeconds: { type: Number, default: 5 }
  },
  emits: [
    "close",
    "trashed",
    "open-asset",
    "open-video",
    "open-object",
    "rotated",
    "slideshow-start",
    "slideshow-stop",
    "slideshow-seconds-change",
    "slideshow-finished"
  ],
  data() {
    return {
      index: 0,
      lastFocused: null,
      tagsById: {},
      semanticTagsById: {},
      editOpen: false,
      editInput: "",
      editTags: [],
      editSuggestions: [],
      editSemanticTags: [],
      semanticInput: "",
      semanticSelected: null,
      semanticSuggestions: [],
      editError: "",
      editLoading: false,
      suggestTimer: null,
      semanticSuggestTimer: null,
      semanticCreateOpen: false,
      toast: "",
      mediaError: "",
      pendingQuarterTurns: 0,
      rotateVersion: 0,
      rotateSaving: false,
      currentPage: 0,
      pageCount: 1,
      slideshowTimer: null
    };
  },
  computed: {
    current() {
      return this.results[this.index] || null;
    },
    currentTags() {
      if (!this.current) {
        return [];
      }
      return this.tagsById[this.current.id] || [];
    },
    currentSemanticTags() {
      if (!this.current) {
        return [];
      }
      return this.semanticTagsById[this.current.id] || [];
    },
    canEditTags() {
      const user = this.currentUser || window.__wa_current_user || null;
      return !!(user && user.is_admin);
    },
    canProposeRotate() {
      const user = this.currentUser || window.__wa_current_user || null;
      return !!user;
    },
    canTrash() {
      return this.canEditTags;
    },
    normalizedEditInput() {
      return this.normalizeTag(this.editInput);
    },
    mediaTransformStyle() {
      const deg = this.pendingQuarterTurns * 90;
      if (deg === 0) {
        return null;
      }
      return { transform: `rotate(${deg}deg)` };
    }
  },
  watch: {
    isOpen(value) {
      if (value) {
        this.lastFocused = document.activeElement;
        document.body.style.overflow = "hidden";
        this.$nextTick(() => {
          this.mediaError = "";
          this.setIndexFromId();
          this.currentPage = 0;
          this.pageCount = 1;
          this.fetchCurrentPreviewMeta();
          this.preloadNeighbors();
          this.fetchCurrentTags();
          this.fetchCurrentSemanticTags();
          this.syncSlideshowTimer();
          this.focusFirst();
        });
        window.addEventListener("keydown", this.onKeydown);
      } else {
        this.clearSlideshowTimer();
        this.tagsById = {};
        this.semanticTagsById = {};
        this.closeEditor();
        document.body.style.overflow = "";
        window.removeEventListener("keydown", this.onKeydown);
        if (this.lastFocused && this.lastFocused.focus) {
          this.lastFocused.focus();
        }
      }
    },
    startId() {
      if (this.isOpen) {
        this.mediaError = "";
        this.setIndexFromId();
        this.currentPage = 0;
        this.pageCount = 1;
        this.fetchCurrentPreviewMeta();
        this.preloadNeighbors();
        this.fetchCurrentTags();
        this.fetchCurrentSemanticTags();
        this.syncSlideshowTimer();
      }
    },
    results() {
      if (this.isOpen) {
        this.mediaError = "";
        this.setIndexFromId();
        this.currentPage = 0;
        this.pageCount = 1;
        this.fetchCurrentPreviewMeta();
        this.preloadNeighbors();
        this.fetchCurrentTags();
        this.fetchCurrentSemanticTags();
        this.syncSlideshowTimer();
      }
    },
    index() {
      this.pendingQuarterTurns = 0;
      this.rotateVersion = 0;
      this.currentPage = 0;
      this.pageCount = 1;
      this.fetchCurrentPreviewMeta();
      this.preloadNeighbors();
      this.fetchCurrentTags();
      this.fetchCurrentSemanticTags();
      this.syncSlideshowTimer();
    },
    slideshowActive() {
      this.syncSlideshowTimer();
    },
    slideshowSeconds() {
      this.syncSlideshowTimer();
    }
  },
  methods: {
    close() {
      if (this.slideshowActive) {
        this.$emit("slideshow-stop");
      }
      this.clearSlideshowTimer();
      this.pendingQuarterTurns = 0;
      this.rotateVersion = 0;
      this.rotateSaving = false;
      this.$emit("close");
    },
    setIndexFromId() {
      const idx = this.results.findIndex((r) => r.id === this.startId);
      this.index = idx >= 0 ? idx : 0;
    },
    prev() {
      if (this.index <= 0) {
        return;
      }
      this.navigateToIndex(this.index - 1);
    },
    next() {
      if (this.index >= this.results.length - 1) {
        return;
      }
      this.navigateToIndex(this.index + 1);
    },
    navigateToIndex(targetIndex) {
      const row = this.results[targetIndex] || null;
      if (!row) {
        return;
      }
      if (row.type === "image") {
        this.index = targetIndex;
        return;
      }
      if (row.entity === "asset") {
        this.$emit("open-asset", row);
        return;
      }
      if (row.type === "video") {
        this.$emit("open-video", row.id);
        return;
      }
      this.showToast(this.$t("search.preview_unsupported", "Preview not supported for this file type"));
    },
    toggleSlideshow() {
      if (this.slideshowActive) {
        this.$emit("slideshow-stop");
        return;
      }
      this.$emit("slideshow-start", this.slideshowSeconds);
    },
    onSlideshowSecondsInput(event) {
      const value = Number(event && event.target ? event.target.value : 0);
      this.$emit("slideshow-seconds-change", value);
    },
    clearSlideshowTimer() {
      if (this.slideshowTimer) {
        window.clearTimeout(this.slideshowTimer);
        this.slideshowTimer = null;
      }
    },
    syncSlideshowTimer() {
      this.clearSlideshowTimer();
      if (!this.isOpen || !this.slideshowActive || !this.current || this.current.type !== "image") {
        return;
      }
      const seconds = Math.max(1, Number(this.slideshowSeconds || 5));
      this.slideshowTimer = window.setTimeout(() => {
        this.slideshowTimer = null;
        if (this.index >= this.results.length - 1) {
          this.$emit("slideshow-finished");
          return;
        }
        this.next();
      }, seconds * 1000);
    },
    fileName(path) {
      const parts = path.split("/");
      return parts[parts.length - 1] || path;
    },
    currentImageSrc() {
      if (!this.current) return "";
      const baseRaw = this.fileUrl(this.current.id);
      const base = this.needsRasterPreview(this.current)
        ? `${baseRaw}${baseRaw.includes("?") ? "&" : "?"}preview=1&page=${this.currentPage}`
        : baseRaw;
      if (!this.rotateVersion) {
        return base;
      }
      return `${base}${base.includes("?") ? "&" : "?"}v=${this.rotateVersion}`;
    },
    needsRasterPreview(row) {
      if (!row || row.type !== "image") {
        return false;
      }
      const ext = this.fileExt(row.path || "");
      return ext === "tif" || ext === "tiff" || ext === "heic" || ext === "heif";
    },
    fileExt(path) {
      const value = String(path || "");
      const dot = value.lastIndexOf(".");
      if (dot < 0 || dot === value.length - 1) {
        return "";
      }
      return value.slice(dot + 1).toLowerCase();
    },
    prevPage() {
      if (this.currentPage <= 0) {
        return;
      }
      this.currentPage -= 1;
      this.mediaError = "";
    },
    nextPage() {
      if (this.currentPage >= this.pageCount - 1) {
        return;
      }
      this.currentPage += 1;
      this.mediaError = "";
    },
    async fetchCurrentPreviewMeta() {
      if (!this.current || this.current.type !== "image" || !this.needsRasterPreview(this.current)) {
        this.pageCount = 1;
        this.currentPage = 0;
        return;
      }
      try {
        const res = await fetch(`/api/file/meta?id=${this.current.id}`);
        if (!res.ok) {
          this.pageCount = 1;
          this.currentPage = 0;
          return;
        }
        const data = await res.json();
        const count = Math.max(1, Number(data && data.pages ? data.pages : 1));
        this.pageCount = count;
        if (this.currentPage > count - 1) {
          this.currentPage = 0;
        }
      } catch (_e) {
        this.pageCount = 1;
        this.currentPage = 0;
      }
    },
    rotateLeft() {
      this.pendingQuarterTurns -= 1;
      if (this.pendingQuarterTurns < -3) {
        this.pendingQuarterTurns += 4;
      }
    },
    rotateRight() {
      this.pendingQuarterTurns += 1;
      if (this.pendingQuarterTurns > 3) {
        this.pendingQuarterTurns -= 4;
      }
    },
    async createRotateProposal() {
      if (!this.current || this.pendingQuarterTurns === 0 || this.rotateSaving) {
        return;
      }
      this.rotateSaving = true;
      try {
        const resolveRes = await fetch(`/api/objects/resolve?file_id=${this.current.id}`);
        const resolved = await resolveRes.json().catch(() => ({}));
        if (!resolveRes.ok) {
          this.toast = resolved.error || this.$t("viewer.resolve_failed", "Failed to resolve object");
          return;
        }
        const sha256 = String(resolved.sha256 || "");
        if (!sha256) {
          this.toast = this.$t("viewer.sha_unavailable", "Object SHA-256 not available");
          return;
        }
        const proposalType = this.pendingQuarterTurns < 0 ? "rotate_left" : "rotate_right";
        const res = await fetch("/api/objects/proposals", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            sha256,
            proposal_type: proposalType,
            payload: null
          })
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.toast = apiErrorMessage(data.error, "viewer.proposal_create_failed", "Failed to create proposal");
          return;
        }
        this.pendingQuarterTurns = 0;
        this.toast = this.$t("viewer.proposal_rotate_created", "Rotate proposal created");
        setTimeout(() => {
          this.toast = "";
        }, 2000);
      } catch (_e) {
        this.toast = this.$t("viewer.proposal_create_failed", "Failed to create proposal");
      } finally {
        this.rotateSaving = false;
      }
    },
    cancelPendingRotation() {
      this.pendingQuarterTurns = 0;
    },
    openObject() {
      if (!this.current) {
        return;
      }
      this.$emit("open-object", this.current);
    },
    copyLink() {
      if (!this.current) return;
      const url = `${window.location.origin}/api/file?id=${this.current.id}`;
      navigator.clipboard?.writeText(url).catch(() => {});
    },
    async downloadCurrent() {
      if (!this.current) return;
      const res = await fetch("/api/download", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ ids: [this.current.id] })
      });
      if (!res.ok) {
        return;
      }
      const blob = await res.blob();
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      const disposition = res.headers.get("Content-Disposition") || "";
      const match = disposition.match(/filename=\"?([^\";]+)\"?/);
      link.download = match ? match[1] : "webalbum-selected.zip";
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    },
    onMediaError() {
      this.mediaError = "Trashed";
    },
    async moveToTrash() {
      if (!this.current || !this.canTrash) {
        return;
      }
      const relPath = this.current.path || this.fileName(this.current.path);
      const ok = window.confirm(`${this.$t("viewer.move_to_trash", "Move to Trash")}?
${relPath}
${this.$t("viewer.trash_reversible", "This is reversible from Admin -> Trash.")}`);
      if (!ok) {
        return;
      }
      try {
        const res = await fetch("/api/admin/trash", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id: this.current.id, type: this.current.type || "image" })
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.toast = apiErrorMessage(data.error, "viewer.trash_failed", "Failed to move to trash");
          return;
        }
        this.$emit("trashed", { id: this.current.id });
        this.close();
      } catch (_e) {
        this.toast = this.$t("viewer.trash_failed", "Failed to move to trash");
      }
    },
    preloadNeighbors() {
      const ids = [];
      if (this.results[this.index - 1]) ids.push(this.results[this.index - 1].id);
      if (this.results[this.index + 1]) ids.push(this.results[this.index + 1].id);
      ids.forEach((id) => {
        const row = this.results.find((item) => item && item.id === id) || null;
        const img = new Image();
        const baseRaw = this.fileUrl(id);
        const base = this.needsRasterPreview(row)
          ? `${baseRaw}${baseRaw.includes("?") ? "&" : "?"}preview=1&page=0`
          : baseRaw;
        img.src = base;
      });
    },
    async fetchCurrentTags() {
      if (!this.current || this.tagsById[this.current.id]) {
        return;
      }
      try {
        const res = await fetch(`/api/media/${this.current.id}/tags`);
        if (!res.ok) {
          this.tagsById = { ...this.tagsById, [this.current.id]: [] };
          return;
        }
        const data = await res.json();
        const tags = Array.isArray(data.tags) ? data.tags.filter((t) => typeof t === "string" && t) : [];
        this.tagsById = { ...this.tagsById, [this.current.id]: tags };
      } catch (_e) {
        this.tagsById = { ...this.tagsById, [this.current.id]: [] };
      }
    },
    async fetchCurrentSemanticTags() {
      if (!this.current || !this.currentUser || this.semanticTagsById[this.current.id]) {
        return;
      }
      try {
        const params = new URLSearchParams({ entity_type: "media", id: String(this.current.id) });
        const res = await fetch(`/api/semantic-tags/target?${params.toString()}`);
        if (!res.ok) {
          this.semanticTagsById = { ...this.semanticTagsById, [this.current.id]: [] };
          return;
        }
        const data = await res.json().catch(() => ({}));
        this.semanticTagsById = {
          ...this.semanticTagsById,
          [this.current.id]: Array.isArray(data.items) ? data.items : []
        };
      } catch (_e) {
        this.semanticTagsById = { ...this.semanticTagsById, [this.current.id]: [] };
      }
    },
    openEditor() {
      this.editError = "";
      this.editInput = "";
      this.semanticInput = "";
      this.semanticSelected = null;
      this.semanticSuggestions = [];
      this.editSuggestions = [];
      this.editTags = [...this.currentTags];
      this.editSemanticTags = [...this.currentSemanticTags];
      this.semanticCreateOpen = false;
      this.editOpen = true;
    },
    closeEditor() {
      this.editOpen = false;
      this.editLoading = false;
      this.editError = "";
      this.editInput = "";
      this.semanticInput = "";
      this.semanticSelected = null;
      this.semanticSuggestions = [];
      this.editSuggestions = [];
      this.semanticCreateOpen = false;
      if (this.suggestTimer) {
        clearTimeout(this.suggestTimer);
        this.suggestTimer = null;
      }
      if (this.semanticSuggestTimer) {
        clearTimeout(this.semanticSuggestTimer);
        this.semanticSuggestTimer = null;
      }
    },
    openSemanticCreate() {
      if (!this.normalizedEditInput) {
        return;
      }
      this.semanticCreateOpen = true;
    },
    handleSemanticCreated(item) {
      this.semanticCreateOpen = false;
      const name = item && item.name ? String(item.name) : this.normalizedEditInput;
      if (!name) {
        return;
      }
      this.addEditTag(name);
      this.toast = this.$t("semantic_tags.created_and_assigned", "Typed tag created and added");
      setTimeout(() => {
        this.toast = "";
      }, 2000);
      this.semanticSelected = item || null;
      this.semanticInput = name;
      this.assignSemanticTag();
    },
    normalizeTag(raw) {
      if (typeof raw !== "string") {
        return "";
      }
      return raw.trim().replace(/\s+/g, " ");
    },
    semanticTypeLabel(type) {
      if (type === "person") return this.$t("semantic_tags.type_person", "Person");
      if (type === "event") return this.$t("semantic_tags.type_event", "Event");
      if (type === "category") return this.$t("semantic_tags.type_category", "Category");
      return this.$t("semantic_tags.type_generic", "Generic");
    },
    semanticSourcesLabel(sources) {
      const values = Array.isArray(sources) ? sources : [];
      return values.map((source) => this.$t(`semantic_tags.source_${source}`, source)).join(", ");
    },
    canRemoveManualSemantic(item) {
      return !!(item && Array.isArray(item.relation_sources) && item.relation_sources.includes("manual"));
    },
    validateTag(tag) {
      if (!tag) {
        return this.$t("viewer.tag_empty", "Tag cannot be empty");
      }
      if (tag.length > 128) {
        return this.$t("viewer.tag_too_long", "Tag too long (max 128)");
      }
      if (tag.includes("|")) {
        return this.$t("viewer.tag_pipe_forbidden", "Tag must not contain pipe character");
      }
      return "";
    },
    onSemanticInput() {
      this.semanticSelected = null;
      if (this.semanticSuggestTimer) {
        clearTimeout(this.semanticSuggestTimer);
      }
      const q = this.normalizeTag(this.semanticInput);
      if (q.length < 2) {
        this.semanticSuggestions = [];
        return;
      }
      this.semanticSuggestTimer = setTimeout(() => {
        this.fetchSemanticSuggestions(q);
      }, 150);
    },
    async fetchSemanticSuggestions(q) {
      try {
        const params = new URLSearchParams({ q, limit: "12" });
        const res = await fetch(`/api/admin/semantic-tags/lookup?${params.toString()}`);
        if (!res.ok) {
          this.semanticSuggestions = [];
          return;
        }
        const data = await res.json().catch(() => ({}));
        this.semanticSuggestions = Array.isArray(data.items) ? data.items : [];
      } catch (_e) {
        this.semanticSuggestions = [];
      }
    },
    selectSemanticSuggestion(item) {
      this.semanticSelected = item;
      this.semanticInput = item.name || "";
      this.semanticSuggestions = [];
    },
    async assignSemanticTag() {
      if (!this.current || !this.semanticSelected) {
        return;
      }
      this.editLoading = true;
      this.editError = "";
      try {
        const res = await fetch("/api/admin/semantic-tags/assign", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            apply_to: "selected",
            ids: [this.current.id],
            semantic_tag_id: this.semanticSelected.id
          })
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.editError = apiErrorMessage(data.error, "semantic_tags.assign_submit_failed", "Failed to assign typed tag");
          return;
        }
        await this.forceRefreshSemanticTags();
        this.semanticInput = "";
        this.semanticSelected = null;
        this.semanticSuggestions = [];
      } catch (_e) {
        this.editError = this.$t("semantic_tags.assign_submit_failed", "Failed to assign typed tag");
      } finally {
        this.editLoading = false;
      }
    },
    async removeSemanticTag(item) {
      if (!this.current || !item || !item.id) {
        return;
      }
      this.editLoading = true;
      this.editError = "";
      try {
        const res = await fetch("/api/admin/semantic-tags/unassign", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            ids: [this.current.id],
            semantic_tag_id: item.id
          })
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.editError = apiErrorMessage(data.error, "semantic_tags.unassign_failed", "Failed to remove typed tag relation");
          return;
        }
        await this.forceRefreshSemanticTags();
        this.toast = this.$t("semantic_tags.unassign_done", "Typed tag relation removed.");
        setTimeout(() => {
          this.toast = "";
        }, 2000);
      } catch (_e) {
        this.editError = this.$t("semantic_tags.unassign_failed", "Failed to remove typed tag relation");
      } finally {
        this.editLoading = false;
      }
    },
    async forceRefreshSemanticTags() {
      if (!this.current) {
        return;
      }
      this.semanticTagsById = { ...this.semanticTagsById, [this.current.id]: null };
      await this.fetchCurrentSemanticTags();
      this.editSemanticTags = [...this.currentSemanticTags];
    },
    showToast(message) {
      this.toast = message || "";
      if (!this.toast) {
        return;
      }
      setTimeout(() => {
        this.toast = "";
      }, 2000);
    },
    addEditTagFromInput() {
      this.addEditTag(this.editInput);
    },
    addEditTag(raw) {
      const tag = this.normalizeTag(raw);
      const err = this.validateTag(tag);
      if (err) {
        this.editError = err;
        return;
      }
      if (!this.editTags.includes(tag)) {
        this.editTags.push(tag);
      }
      this.editError = "";
      this.editInput = "";
      this.editSuggestions = [];
    },
    removeEditTag(tag) {
      this.editTags = this.editTags.filter((t) => t !== tag);
    },
    onEditInput() {
      this.editError = "";
      if (this.suggestTimer) {
        clearTimeout(this.suggestTimer);
      }
      const q = this.normalizeTag(this.editInput);
      if (q.length < 2) {
        this.editSuggestions = [];
        return;
      }
      this.suggestTimer = setTimeout(() => {
        this.fetchSuggestions(q);
      }, 150);
    },
    async fetchSuggestions(q) {
      try {
        const params = new URLSearchParams({ q, limit: "12" });
        const res = await fetch(`/api/tags?${params.toString()}`);
        if (!res.ok) {
          this.editSuggestions = [];
          return;
        }
        const data = await res.json();
        if (!Array.isArray(data)) {
          this.editSuggestions = [];
          return;
        }
        this.editSuggestions = data
          .filter((item) => item && typeof item.tag === "string")
          .map((item) => ({ tag: item.tag, cnt: Number(item.cnt || 0) }))
          .filter((item) => !this.editTags.includes(item.tag));
      } catch (_e) {
        this.editSuggestions = [];
      }
    },
    async saveEditTags() {
      if (!this.current) {
        return;
      }
      this.editLoading = true;
      this.editError = "";
      try {
        const normalized = this.editTags.map((t) => this.normalizeTag(t));
        for (const t of normalized) {
          const err = this.validateTag(t);
          if (err) {
            this.editError = err;
            this.editLoading = false;
            return;
          }
        }
        const res = await fetch(`/api/media/${this.current.id}/tags`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ tags: normalized })
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.editError = apiErrorMessage(data.error, "viewer.tags_save_failed", "Failed to save tags");
          this.editLoading = false;
          return;
        }
        const tags = Array.isArray(data.tags) ? data.tags : normalized;
        this.tagsById = { ...this.tagsById, [this.current.id]: tags };
        this.closeEditor();
        this.toast = this.$t("viewer.tag_edit_queued", "Tag edit queued.");
        setTimeout(() => {
          this.toast = "";
        }, 2500);
      } catch (_e) {
        this.editError = this.$t("viewer.tags_save_failed", "Failed to save tags");
      } finally {
        this.editLoading = false;
      }
    },
    async restoreOriginalTags() {
      if (!this.current) {
        return;
      }
      this.editLoading = true;
      this.editError = "";
      try {
        const res = await fetch(`/api/admin/media/${this.current.id}/tags/restore`, {
          method: "POST"
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.editError = apiErrorMessage(data.error, "viewer.restore_failed", "Failed to restore original tags");
          this.editLoading = false;
          return;
        }
        this.closeEditor();
        this.toast = this.$t("viewer.restore_queued", "Original tag restore queued.");
        setTimeout(() => {
          this.toast = "";
        }, 2500);
      } catch (_e) {
        this.editError = this.$t("viewer.restore_failed", "Failed to restore original tags");
      } finally {
        this.editLoading = false;
      }
    },
    onKeydown(event) {
      if (this.editOpen) {
        if (event.key === "Escape") {
          event.preventDefault();
          this.closeEditor();
        }
        return;
      }
      if (event.key === "Escape") {
        this.close();
        return;
      }
      if (event.key === "ArrowLeft") {
        this.prev();
        return;
      }
      if (event.key === "ArrowRight") {
        this.next();
        return;
      }
      if (event.key === "Tab") {
        this.trapFocus(event);
      }
    },
    focusableElements() {
      const root = this.$refs.panel;
      if (!root) return [];
      return Array.from(
        root.querySelectorAll(
          "button, [href], input, select, textarea, [tabindex]:not([tabindex='-1'])"
        )
      );
    },
    focusFirst() {
      const focusables = this.focusableElements();
      if (focusables.length) {
        focusables[0].focus();
      }
    },
    trapFocus(event) {
      const focusables = this.focusableElements();
      if (focusables.length === 0) return;
      const first = focusables[0];
      const last = focusables[focusables.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        last.focus();
        event.preventDefault();
      } else if (!event.shiftKey && document.activeElement === last) {
        first.focus();
        event.preventDefault();
      }
    }
  },
  beforeUnmount() {
    this.clearSlideshowTimer();
  }
};
</script>

<style scoped>
.tag-editor-modal {
  width: min(640px, 92vw);
}

.viewer-slideshow-control {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #f4ead7;
}

.viewer-slideshow-control input {
  width: 72px;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.25);
  background: rgba(255, 255, 255, 0.08);
  color: inherit;
  padding: 6px 8px;
}

.tag-editor-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 10px;
}

.tag-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #efe2c9;
  color: #2b2b2b;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 13px;
}

.tag-chip button {
  border: none;
  background: transparent;
  cursor: pointer;
  color: #2b2b2b;
}

.viewer-badge {
  position: absolute;
  left: 16px;
  bottom: 16px;
  background: #a84a3a;
  color: #fff;
  border-radius: 8px;
  padding: 8px 10px;
  font-size: 12px;
}

.viewer-inline-toast {
  position: absolute;
  right: 16px;
  bottom: 16px;
  background: #1e1e1e;
  color: #fff;
  border-radius: 8px;
  padding: 8px 10px;
  font-size: 12px;
}

.viewer-btn.danger {
  border-color: #a84a3a;
  color: #a84a3a;
}
</style>
