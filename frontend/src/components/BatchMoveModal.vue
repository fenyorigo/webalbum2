<template>
  <div v-if="isOpen" class="modal-backdrop" @click.self="close">
    <div class="modal batch-move-modal">
      <div class="modal-header">
        <h3>{{ $t("batch_move.title", "Batch move") }}</h3>
        <button class="inline" type="button" @click="close" :disabled="saving">{{ $t("ui.close", "Close") }}</button>
      </div>
      <p class="muted">
        {{ $t("batch_move.scope_label", { scope: scopeLabel }, "Scope: {scope}") }}
        {{ $t("batch_move.item_count", { count: itemCount }, "Items: {count}") }}
      </p>
      <p class="muted">{{ $t("batch_move.conflict_note", "Name conflicts are resolved automatically with numeric suffixes.") }}</p>
      <div class="picker-shell">
        <folder-tree
          :selected-rel-path="selectedRelPath"
          :can-create="true"
          @select="onSelect"
          @clear="clearSelection"
        />
      </div>
      <p class="muted">
        <strong>{{ $t("batch_move.selected_destination", "Selected destination") }}:</strong>
        <span>{{ selectedRelPath || $t("batch_move.destination_none", "No folder selected") }}</span>
      </p>
      <p v-if="error" class="error">{{ error }}</p>
      <div class="modal-actions">
        <button class="inline" type="button" @click="submit" :disabled="saving || !selectedRelPath">
          {{ saving ? $t("batch_move.in_progress", "Moving...") : $t("batch_move.confirm", "Move now") }}
        </button>
        <button class="inline" type="button" @click="close" :disabled="saving">{{ $t("ui.cancel", "Cancel") }}</button>
      </div>
      <div v-if="summary" class="batch-summary">
        <h4>{{ $t("batch_move.summary_title", "Batch move summary") }}</h4>
        <p class="muted">
          {{ $t("batch_move.summary.moved", { count: summary.moved_count || 0 }, "Moved: {count}") }},
          {{ $t("batch_move.summary.renamed", { count: summary.renamed_count || 0 }, "Renamed: {count}") }},
          {{ $t("batch_move.summary.blocked", { count: summary.blocked_count || 0 }, "Blocked: {count}") }},
          {{ $t("batch_move.summary.failed", { count: summary.failed_count || 0 }, "Failed: {count}") }}
        </p>
        <div v-if="Array.isArray(summary.results) && summary.results.length" class="batch-summary-list">
          <article
            v-for="item in summary.results"
            :key="`batch-move:${item.position}:${item.id}`"
            class="batch-summary-item"
          >
            <div class="batch-summary-head">
              <strong>{{ item.old_rel_path || item.new_rel_path || `#${item.id}` }}</strong>
              <span class="pill">{{ statusLabel(item.status) }}</span>
            </div>
            <div v-if="item.new_rel_path" class="muted">{{ item.new_rel_path }}</div>
            <div v-if="item.error" class="error">{{ errorLabel(item.error) }}</div>
          </article>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import FolderTree from "./FolderTree.vue";
import { apiErrorMessage } from "../api-errors";

export default {
  name: "BatchMoveModal",
  components: { FolderTree },
  props: {
    isOpen: { type: Boolean, required: true },
    saving: { type: Boolean, default: false },
    error: { type: String, default: "" },
    scopeLabel: { type: String, default: "" },
    itemCount: { type: Number, default: 0 },
    summary: { type: Object, default: null }
  },
  emits: ["close", "confirm"],
  data() {
    return {
      selectedRelPath: ""
    };
  },
  watch: {
    isOpen(value) {
      if (value) {
        this.selectedRelPath = "";
      }
    }
  },
  methods: {
    onSelect(row) {
      this.selectedRelPath = row && row.rel_path ? String(row.rel_path) : "";
    },
    clearSelection() {
      this.selectedRelPath = "";
    },
    close() {
      if (this.saving) {
        return;
      }
      this.$emit("close");
    },
    submit() {
      if (!this.selectedRelPath) {
        return;
      }
      const ok = window.confirm(
        this.$t(
          "batch_move.confirm_message",
          { count: this.itemCount, destination: this.selectedRelPath },
          "Move {count} item(s) to {destination}?"
        )
      );
      if (!ok) {
        return;
      }
      this.$emit("confirm", { targetRelPath: this.selectedRelPath });
    },
    statusLabel(status) {
      if (status === "moved") return this.$t("batch_move.status.moved", "Moved");
      if (status === "moved_with_rename") return this.$t("batch_move.status.renamed", "Moved with rename");
      if (status === "blocked") return this.$t("batch_move.status.blocked", "Blocked");
      return this.$t("batch_move.status.failed", "Failed");
    },
    errorLabel(error) {
      return apiErrorMessage(error, "batch_move.failed", "Batch move failed");
    }
  }
};
</script>

<style scoped>
.modal-backdrop {
  padding: 16px;
}

.batch-move-modal {
  width: min(760px, 92vw);
  max-height: calc(100vh - 32px);
  display: flex;
  flex-direction: column;
  overflow: auto;
  margin: auto;
}

.picker-shell {
  margin: 12px 0;
  max-height: min(42vh, 320px);
  overflow: auto;
  flex: 0 0 auto;
}

.batch-summary {
  margin-top: 16px;
  padding-top: 12px;
  border-top: 1px solid rgba(255, 255, 255, 0.12);
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.batch-summary-list {
  display: grid;
  gap: 10px;
  max-height: min(32vh, 320px);
  overflow: auto;
  min-height: 0;
}

.batch-summary-item {
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 10px;
  padding: 10px;
}

.batch-summary-head {
  display: flex;
  justify-content: space-between;
  gap: 12px;
}
</style>
