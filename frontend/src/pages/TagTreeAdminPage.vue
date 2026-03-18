<template>
  <div class="page">
    <header class="hero">
      <h1>{{ $t("admin.tag_tree", "Tag Tree") }}</h1>
      <p>{{ $t("tag_tree.description", "Manage the semantic hierarchy of typed tags.") }}</p>
    </header>

    <section class="panel">
      <div class="row filters">
        <label class="grow">
          {{ $t("tag_tree.search", "Search tags") }}
          <input
            v-model.trim="search"
            type="text"
            :placeholder="$t('tag_tree.search_placeholder', 'Find typed tags...')"
          />
        </label>
      </div>
      <div class="row actions">
        <button type="button" @click="openRootCreate">{{ $t("tag_tree.add_tag", "Add Tag") }}</button>
        <button type="button" class="inline" @click="expandMatches">{{ $t("tag_tree.expand_matches", "Expand matches") }}</button>
        <button type="button" class="inline" @click="collapseAll">{{ $t("tag_tree.collapse_all", "Collapse all") }}</button>
      </div>
      <p v-if="error" class="error">{{ error }}</p>
    </section>

    <section class="panel">
      <div class="meta">
        <span v-if="loading">{{ $t("common.loading", "Loading...") }}</span>
        <span v-else>{{ filteredCount }} {{ $t("tag_tree.nodes", "nodes") }}</span>
      </div>
      <div v-if="treeRoots.length" class="tree-wrap">
        <SemanticTagTreeNode
          v-for="node in treeRoots"
          :key="node.id"
          :node="node"
          :depth="0"
          :expanded-ids="expandedIds"
          :match-ids="matchIds"
          :children-map="childrenMap"
          @toggle="toggleNode"
          @select="openEdit"
          @add-child="openChildCreate"
          @edit="openEdit"
          @delete="confirmDelete"
        />
      </div>
      <p v-else-if="!loading" class="muted">{{ $t("tag_tree.empty", "No typed tags found.") }}</p>
    </section>

    <SemanticTagCreateModal
      :is-open="modalOpen"
      :initial-name="createName"
      :initial-parent-id="createParentId"
      :initial-parent-name="createParentName"
      :edit-item="editItem"
      @close="closeModal"
      @created="handleSaved"
    />

    <div v-if="deleteOpen" class="modal-backdrop" @click.self="closeDelete">
      <div class="modal localization-modal">
        <h3>{{ $t("tag_tree.delete", "Delete Tag") }}</h3>
        <p>{{ $t("tag_tree.delete_confirm", { name: deleteTarget ? deleteTarget.name : '' }, 'Delete tag \"{name}\"?') }}</p>
        <p v-if="deleteTarget && Number(deleteTarget.usage_count || 0) > 0" class="muted">
          {{ $t("tag_tree.delete_usage_warning", { count: Number(deleteTarget.usage_count || 0) }, "This tag is currently linked to {count} items.") }}
        </p>
        <div class="modal-actions">
          <button type="button" @click="deleteTag" :disabled="loading">{{ $t("tag_tree.delete", "Delete Tag") }}</button>
          <button type="button" class="inline" @click="closeDelete" :disabled="loading">{{ $t("ui.cancel", "Cancel") }}</button>
        </div>
      </div>
    </div>

    <div v-if="toast" class="toast">{{ toast }}</div>
  </div>
</template>

<script>
import SemanticTagCreateModal from "../components/SemanticTagCreateModal.vue";
import SemanticTagTreeNode from "../components/SemanticTagTreeNode.vue";
import { apiErrorMessage } from "../api-errors";

export default {
  name: "TagTreeAdminPage",
  components: {
    SemanticTagCreateModal,
    SemanticTagTreeNode
  },
  data() {
    return {
      loading: false,
      error: "",
      toast: "",
      search: "",
      items: [],
      expandedIds: {},
      modalOpen: false,
      editItem: null,
      createName: "",
      createParentId: null,
      createParentName: "",
      deleteOpen: false,
      deleteTarget: null
    };
  },
  computed: {
    itemsById() {
      const map = {};
      for (const row of this.items) {
        map[row.id] = row;
      }
      return map;
    },
    childrenMap() {
      const out = {};
      for (const row of this.items) {
        const parentId = row.parent_tag_id ? Number(row.parent_tag_id) : 0;
        if (!out[parentId]) out[parentId] = [];
        out[parentId].push(row);
      }
      for (const key of Object.keys(out)) {
        out[key].sort((a, b) => String(a.name || "").localeCompare(String(b.name || ""), undefined, { sensitivity: "base" }));
      }
      return out;
    },
    matchIds() {
      const q = this.search.trim().toLocaleLowerCase();
      const matches = {};
      if (!q) return matches;
      for (const row of this.items) {
        const name = String(row.name || "").toLocaleLowerCase();
        if (name.includes(q)) {
          matches[row.id] = true;
        }
      }
      return matches;
    },
    visibleIds() {
      if (!this.search.trim()) {
        const all = {};
        for (const row of this.items) all[row.id] = true;
        return all;
      }
      const visible = {};
      const walkParents = (id) => {
        let current = this.itemsById[id];
        while (current) {
          visible[current.id] = true;
          const parentId = current.parent_tag_id ? Number(current.parent_tag_id) : 0;
          current = parentId ? this.itemsById[parentId] : null;
        }
      };
      for (const id of Object.keys(this.matchIds)) {
        walkParents(Number(id));
      }
      return visible;
    },
    treeRoots() {
      return (this.childrenMap[0] || []).filter((row) => this.visibleIds[row.id]);
    },
    filteredCount() {
      return Object.keys(this.visibleIds).length;
    }
  },
  watch: {
    search() {
      this.expandMatches();
    }
  },
  mounted() {
    this.fetchTree();
  },
  methods: {
    async fetchTree() {
      this.loading = true;
      this.error = "";
      try {
        const res = await fetch("/api/admin/semantic-tags/tree");
        if (res.status === 401 || res.status === 403) {
          window.dispatchEvent(new CustomEvent("wa-auth-changed", { detail: null }));
          this.$router.push("/login");
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "tag_tree.load_failed", "Failed to load tag tree");
          return;
        }
        this.items = Array.isArray(data.items) ? data.items : [];
        if (!this.search.trim()) {
          const next = {};
          for (const row of this.items) {
            if (Number(row.child_count || 0) > 0 && !row.parent_tag_id) {
              next[row.id] = true;
            }
          }
          this.expandedIds = next;
        } else {
          this.expandMatches();
        }
      } catch (_e) {
        this.error = this.$t("tag_tree.load_failed", "Failed to load tag tree");
      } finally {
        this.loading = false;
      }
    },
    toggleNode(id) {
      this.expandedIds = { ...this.expandedIds, [id]: !this.expandedIds[id] };
    },
    expandMatches() {
      const next = {};
      for (const id of Object.keys(this.matchIds)) {
        let current = this.itemsById[Number(id)];
        while (current && current.parent_tag_id) {
          next[current.parent_tag_id] = true;
          current = this.itemsById[Number(current.parent_tag_id)] || null;
        }
      }
      this.expandedIds = next;
    },
    collapseAll() {
      this.expandedIds = {};
    },
    openRootCreate() {
      this.editItem = null;
      this.createName = "";
      this.createParentId = null;
      this.createParentName = "";
      this.modalOpen = true;
    },
    openChildCreate(node) {
      this.editItem = null;
      this.createName = "";
      this.createParentId = node.id;
      this.createParentName = node.name || "";
      this.modalOpen = true;
      this.expandedIds = { ...this.expandedIds, [node.id]: true };
    },
    openEdit(node) {
      this.createName = "";
      this.createParentId = null;
      this.createParentName = "";
      this.editItem = { ...node };
      this.modalOpen = true;
    },
    closeModal() {
      this.modalOpen = false;
      this.editItem = null;
      this.createName = "";
      this.createParentId = null;
      this.createParentName = "";
    },
    async handleSaved(item) {
      this.closeModal();
      await this.fetchTree();
      if (item && item.parent_tag_id) {
        this.expandedIds = { ...this.expandedIds, [Number(item.parent_tag_id)]: true };
      }
      this.toast = this.$t("tag_tree.saved", "Tag tree updated.");
      window.setTimeout(() => { this.toast = ""; }, 2500);
    },
    confirmDelete(node) {
      if (Number(node.child_count || 0) > 0) {
        window.alert(this.$t("tag_tree.delete_children_blocked", "Delete child tags first."));
        return;
      }
      this.deleteTarget = node;
      this.deleteOpen = true;
    },
    closeDelete() {
      this.deleteOpen = false;
      this.deleteTarget = null;
    },
    async deleteTag() {
      if (!this.deleteTarget) return;
      this.loading = true;
      this.error = "";
      try {
        const res = await fetch(`/api/admin/semantic-tags/${this.deleteTarget.id}`, { method: "DELETE" });
        if (res.status === 401 || res.status === 403) {
          window.dispatchEvent(new CustomEvent("wa-auth-changed", { detail: null }));
          this.$router.push("/login");
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "tag_tree.delete_failed", "Failed to delete tag");
          return;
        }
        this.closeDelete();
        await this.fetchTree();
        this.toast = this.$t("tag_tree.deleted", "Tag deleted.");
        window.setTimeout(() => { this.toast = ""; }, 2500);
      } catch (_e) {
        this.error = this.$t("tag_tree.delete_failed", "Failed to delete tag");
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
.tree-wrap {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.filters .grow {
  flex: 1;
}

.actions {
  margin-top: 0.75rem;
}

.meta {
  margin-bottom: 0.9rem;
  color: var(--muted);
}
</style>
