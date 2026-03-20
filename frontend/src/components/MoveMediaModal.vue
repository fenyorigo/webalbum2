<template>
  <div v-if="isOpen" class="modal-backdrop" @click.self="close">
    <div class="modal move-media-modal">
      <div class="modal-header">
        <h3>{{ tr("title") }}</h3>
        <button class="inline" type="button" @click="close" :disabled="saving">{{ $t("ui.close") }}</button>
      </div>
      <p class="muted">
        <strong>{{ tr("current_path") }}:</strong>
        <span :title="currentRelPath">{{ currentRelPath }}</span>
      </p>
      <p class="muted">{{ tr("destination_hint") }}</p>
      <div class="picker-shell">
        <folder-tree
          :selected-rel-path="selectedRelPath"
          :can-create="true"
          @select="onSelect"
          @clear="clearSelection"
        />
      </div>
      <p class="muted">
        <strong>{{ tr("selected_destination") }}:</strong>
        <span>{{ selectedRelPath || tr("destination_none") }}</span>
      </p>
      <p v-if="error" class="error">{{ error }}</p>
      <div class="modal-actions">
        <button class="inline" type="button" @click="submit" :disabled="saving || !selectedRelPath || sameFolder">
          {{ saving ? tr("in_progress") : tr("confirm") }}
        </button>
        <button class="inline" type="button" @click="close" :disabled="saving">{{ $t("ui.cancel") }}</button>
      </div>
    </div>
  </div>
</template>

<script>
import FolderTree from "./FolderTree.vue";

export default {
  name: "MoveMediaModal",
  components: { FolderTree },
  props: {
    isOpen: { type: Boolean, required: true },
    currentRelPath: { type: String, default: "" },
    currentName: { type: String, default: "" },
    saving: { type: Boolean, default: false },
    i18nPrefix: { type: String, default: "move" }
  },
  emits: ["close", "confirm"],
  data() {
    return {
      selectedRelPath: "",
      error: ""
    };
  },
  computed: {
    currentFolder() {
      const value = String(this.currentRelPath || "").replace(/\\/g, "/").replace(/^\/+|\/+$/g, "");
      if (!value.includes("/")) {
        return "";
      }
      return value.split("/").slice(0, -1).join("/");
    },
    sameFolder() {
      return !!this.selectedRelPath && this.selectedRelPath === this.currentFolder;
    }
  },
  watch: {
    isOpen(value) {
      if (value) {
        this.selectedRelPath = "";
        this.error = "";
      }
    }
  },
  methods: {
    tr(suffix) {
      const key = `${this.i18nPrefix}.${suffix}`;
      return this.$t(key);
    },
    onSelect(row) {
      this.selectedRelPath = row && row.rel_path ? String(row.rel_path) : "";
      this.error = "";
    },
    clearSelection() {
      this.selectedRelPath = "";
      this.error = "";
    },
    close() {
      if (this.saving) {
        return;
      }
      this.$emit("close");
    },
    submit() {
      if (!this.selectedRelPath) {
        this.error = this.tr("select_destination_required");
        return;
      }
      if (this.sameFolder) {
        this.error = this.tr("same_folder");
        return;
      }
      const ok = window.confirm(
        this.trWithParams("confirm_message", {
          name: this.currentName || this.currentRelPath,
          destination: this.selectedRelPath
        })
      );
      if (!ok) {
        return;
      }
      this.$emit("confirm", { targetRelPath: this.selectedRelPath });
    },
    trWithParams(suffix, params) {
      const key = `${this.i18nPrefix}.${suffix}`;
      return this.$t(key, params);
    }
  }
};
</script>

<style scoped>
.move-media-modal {
  width: min(760px, 92vw);
}

.picker-shell {
  margin: 12px 0;
  max-height: 52vh;
  overflow: auto;
}
</style>
