<template>
  <div class="folder-tree panel">
    <div class="folder-head">
      <h3>{{ $t("folders.title", "Folders") }}</h3>
      <div class="folder-actions">
        <button v-if="canCreate" class="inline" type="button" @click="toggleCreate" :disabled="loading || !selectedRelPath">
          {{ creating ? $t("folders.create.cancel", "Cancel") : $t("folders.create", "Create folder") }}
        </button>
        <button v-if="canDelete && selectedRelPath" class="clear" type="button" @click="submitDelete" :disabled="deleteBusy">
          {{ deleteBusy ? $t("folders.delete.in_progress", "Deleting...") : $t("folders.delete", "Delete folder") }}
        </button>
        <button v-if="selectedRelPath" class="clear" type="button" @click="clearSelection">{{ $t("ui.clear", "Clear") }}</button>
      </div>
    </div>

    <p v-if="selectedRelPath" class="selected" :title="selectedRelPath">{{ selectedRelPath }}</p>
    <p v-if="actionError" class="error">{{ actionError }}</p>
    <p v-else-if="actionMessage" class="muted">{{ actionMessage }}</p>
    <div v-if="canCreate && creating" class="create-box">
      <p class="muted">
        {{ $t("folders.create.parent", { path: selectedRelPath || '' }, "Create under: {path}") }}
      </p>
      <label>
        {{ $t("folders.create.name", "Folder name") }}
        <input
          v-model.trim="newFolderName"
          type="text"
          :placeholder="$t('folders.create.placeholder', 'New folder name')"
          :disabled="creatingBusy"
          @keydown.enter.prevent="submitCreate"
        />
      </label>
      <div class="modal-actions">
        <button class="inline" type="button" @click="submitCreate" :disabled="creatingBusy || !selectedRelPath">
          {{ creatingBusy ? $t("folders.create.in_progress", "Creating...") : $t("folders.create.confirm", "Create") }}
        </button>
      </div>
    </div>
    <p v-if="loading" class="muted">{{ $t("folders.loading", "Loading folders...") }}</p>
    <p v-else-if="error" class="error">{{ error }}</p>
    <p v-else-if="rows.length === 0" class="muted">{{ $t("folders.empty", "No folders indexed") }}</p>

    <div v-else class="tree-list">
      <div
        v-for="row in rows"
        :key="row.key || row.rel_path"
        class="tree-row"
        :class="{ active: selectedRelPath === row.rel_path }"
        :style="{ paddingLeft: `${Math.max(0, (row.depth - 1) * 14 + 8)}px` }"
      >
        <button
          class="expander"
          type="button"
          :disabled="!row.has_children"
          @click="toggle(row)"
          :aria-label="expanded[row.key || row.rel_path] ? $t('folders.collapse', 'Collapse folder') : $t('folders.expand', 'Expand folder')"
        >
          <span v-if="row.has_children">{{ expanded[row.key || row.rel_path] ? '▾' : '▸' }}</span>
        </button>
        <button class="name" type="button" :title="row.rel_path" @click="selectRow(row)">
          {{ row.name }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { apiErrorMessage } from "../api-errors";

export default {
  name: "FolderTree",
  emits: ["select", "clear", "created", "deleted"],
  props: {
    selectedRelPath: { type: String, default: "" },
    canCreate: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false }
  },
  data() {
    return {
      roots: [],
      childrenByParent: {},
      expanded: {},
      loading: false,
      error: "",
      creating: false,
      creatingBusy: false,
      deleteBusy: false,
      newFolderName: "",
      actionError: "",
      actionMessage: ""
    };
  },
  computed: {
    rows() {
      const out = [];
      const visit = (nodes) => {
        nodes.forEach((node) => {
          out.push(node);
          const nodeKey = node.key || node.rel_path;
          if (this.expanded[nodeKey]) {
            visit(this.childrenByParent[nodeKey] || []);
          }
        });
      };
      visit(this.roots);
      return out;
    }
  },
  mounted() {
    this.loadRoots();
  },
  methods: {
    async loadRoots() {
      this.loading = true;
      this.error = "";
      try {
        const res = await fetch("/api/tree/roots");
        const data = await res.json();
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "folders.load_failed", "Failed to load folders");
          return;
        }
        this.roots = Array.isArray(data) ? data : [];
      } catch (err) {
        this.error = this.$t("folders.load_failed", "Failed to load folders");
      } finally {
        this.loading = false;
      }
    },
    toggleCreate() {
      if (!this.canCreate) {
        return;
      }
      this.creating = !this.creating;
      this.newFolderName = "";
      this.actionError = "";
      this.actionMessage = "";
    },
    async loadChildren(parentRelPath) {
      if (this.childrenByParent[parentRelPath]) {
        return;
      }
      const qs = new URLSearchParams({ parent_rel_path: String(parentRelPath) });
      const res = await fetch(`/api/tree?${qs.toString()}`);
      const data = await res.json();
      if (!res.ok) {
        throw new Error(apiErrorMessage(data.error, "folders.children_load_failed", "Failed to load child folders"));
      }
      this.childrenByParent = {
        ...this.childrenByParent,
        [parentRelPath]: Array.isArray(data) ? data : []
      };
    },
    async reloadParent(parentRelPath) {
      if (!parentRelPath) {
        await this.loadRoots();
        return;
      }
      const qs = new URLSearchParams({ parent_rel_path: String(parentRelPath) });
      const res = await fetch(`/api/tree?${qs.toString()}`);
      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        throw new Error(apiErrorMessage(data.error, "folders.children_load_failed", "Failed to load child folders"));
      }
      this.childrenByParent = {
        ...this.childrenByParent,
        [parentRelPath]: Array.isArray(data) ? data : []
      };
    },
    async toggle(row) {
      if (!row.has_children) {
        return;
      }
      const nodeKey = row.key || row.rel_path;
      if (this.expanded[nodeKey]) {
        this.expanded = { ...this.expanded, [nodeKey]: false };
        return;
      }
      try {
        await this.loadChildren(row.rel_path);
        this.expanded = { ...this.expanded, [nodeKey]: true };
      } catch (err) {
        this.error = err.message || this.$t("folders.children_load_failed", "Failed to load child folders");
      }
    },
    async submitCreate() {
      if (!this.canCreate) {
        return;
      }
      if (!this.selectedRelPath) {
        this.actionError = this.$t("folders.create.select_parent_first", "Select a parent folder first");
        return;
      }
      if (!this.newFolderName) {
        this.actionError = this.$t("folders.create.invalid_name", "Invalid folder name");
        return;
      }
      this.creatingBusy = true;
      this.actionError = "";
      this.actionMessage = "";
      try {
        const res = await fetch("/api/admin/tree/folder", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            parent_rel_path: this.selectedRelPath,
            folder_name: this.newFolderName
          })
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.actionError = apiErrorMessage(data.error, "folders.create.failed", "Failed to create folder");
          return;
        }
        await this.reloadParent(this.selectedRelPath);
        this.expanded = {
          ...this.expanded,
          [this.selectedRelPath]: true
        };
        if (data && data.folder && data.folder.rel_path) {
          this.$emit("created", {
            id: data.folder.id,
            rel_path: data.folder.rel_path,
            name: data.folder.name
          });
          this.$emit("select", {
            id: data.folder.id,
            rel_path: data.folder.rel_path,
            name: data.folder.name
          });
        }
        this.creating = false;
        this.newFolderName = "";
        this.actionError = "";
        this.actionMessage = this.$t("folders.create.success", "Folder created");
      } catch (_err) {
        this.actionError = this.$t("folders.create.failed", "Failed to create folder");
      } finally {
        this.creatingBusy = false;
      }
    },
    async submitDelete() {
      if (!this.canDelete || !this.selectedRelPath || this.deleteBusy) {
        return;
      }
      const deletedRelPath = String(this.selectedRelPath);
      const parentRelPath = this.parentRelPath(deletedRelPath);
      const ok = window.confirm(
        this.$t("folders.delete.confirm", { path: deletedRelPath }, 'Delete folder "{path}"?')
      );
      if (!ok) {
        return;
      }
      this.deleteBusy = true;
      this.actionError = "";
      this.actionMessage = "";
      try {
        const res = await fetch("/api/admin/tree/folder", {
          method: "DELETE",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ rel_path: deletedRelPath })
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.actionError = apiErrorMessage(data.error, "folders.delete.failed", "Failed to delete folder");
          return;
        }
        const nextChildren = { ...this.childrenByParent };
        delete nextChildren[deletedRelPath];
        if (parentRelPath && Array.isArray(nextChildren[parentRelPath])) {
          nextChildren[parentRelPath] = nextChildren[parentRelPath].filter(
            (item) => item && item.rel_path !== deletedRelPath
          );
        }
        this.childrenByParent = nextChildren;
        if (parentRelPath) {
          await this.reloadParent(parentRelPath);
          this.expanded = { ...this.expanded, [parentRelPath]: true };
        } else {
          await this.loadRoots();
        }
        this.$emit("clear");
        this.$emit("deleted", { rel_path: deletedRelPath });
        this.actionMessage = this.$t("folders.delete.success", "Folder deleted");
      } catch (_err) {
        this.actionError = this.$t("folders.delete.failed", "Failed to delete folder");
      } finally {
        this.deleteBusy = false;
      }
    },
    selectRow(row) {
      this.actionError = "";
      this.actionMessage = "";
      this.$emit("select", {
        id: row.id,
        rel_path: row.rel_path,
        name: row.name
      });
    },
    clearSelection() {
      this.actionError = "";
      this.actionMessage = "";
      this.$emit("clear");
    },
    parentRelPath(relPath) {
      const value = String(relPath || "").replace(/\\/g, "/").replace(/^\/+|\/+$/g, "");
      if (!value.includes("/")) {
        return "";
      }
      return value.split("/").slice(0, -1).join("/");
    }
  }
};
</script>

<style scoped>
.folder-tree {
  padding: 10px;
}
.folder-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 6px;
}
.folder-actions {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 8px;
}
.folder-head h3 {
  margin: 0;
  font-size: 16px;
}
.create-box {
  display: grid;
  gap: 8px;
  margin: 0 0 10px;
  padding: 10px;
  border: 1px solid #e3dccf;
  border-radius: 8px;
  background: #fffaf2;
}
.selected {
  margin: 0 0 8px;
  font-size: 12px;
  color: var(--muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.tree-list {
  display: grid;
  gap: 2px;
  max-height: calc(100vh - 250px);
  overflow: auto;
}
.tree-row {
  display: flex;
  align-items: center;
  min-height: 28px;
  border-radius: 6px;
}
.tree-row.active {
  background: #f1eadf;
}
.expander {
  width: 22px;
  height: 24px;
  background: transparent;
  border: none;
  color: var(--ink);
  padding: 0;
}
.expander:disabled {
  opacity: 0.35;
}
.name {
  border: none;
  background: transparent;
  color: var(--ink);
  text-align: left;
  width: 100%;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  padding: 4px 6px;
}
.name:hover {
  text-decoration: underline;
}
</style>
