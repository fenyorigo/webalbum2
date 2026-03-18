<template>
  <div class="page">
    <header class="hero">
      <h1>{{ $t("admin.cleanup_tags", "Cleanup Tags") }}</h1>
      <p>{{ $t("cleanup_tags.description", "Review generic, root, and unused typed tags that still need structural cleanup.") }}</p>
    </header>

    <section class="panel">
      <div class="row filters">
        <label class="grow">
          {{ $t("cleanup_tags.search", "Search tags") }}
          <input
            v-model.trim="search"
            type="text"
            :placeholder="$t('cleanup_tags.search_placeholder', 'Find typed tags...')"
          />
        </label>
      </div>
      <div class="row filters checkbox-row">
        <label class="checkbox">
          <input v-model="showGenericOnly" type="checkbox" />
          {{ $t("cleanup_tags.generic_only", "Generic tags") }}
        </label>
        <label class="checkbox">
          <input v-model="showRootOnly" type="checkbox" />
          {{ $t("cleanup_tags.root_only", "Root tags") }}
        </label>
        <label class="checkbox">
          <input v-model="showUnusedOnly" type="checkbox" />
          {{ $t("cleanup_tags.unused_only", "Unused tags") }}
        </label>
      </div>
      <p v-if="error" class="error">{{ error }}</p>
    </section>

    <section class="panel">
      <div class="meta">
        <span v-if="loading">{{ $t("common.loading", "Loading...") }}</span>
        <span v-else>{{ filteredRows.length }} {{ $t("cleanup_tags.matching_tags", "matching tags") }}</span>
      </div>

      <table v-if="filteredRows.length" class="results-table">
        <thead>
          <tr>
            <th>{{ $t("semantic_tags.name", "Tag name") }}</th>
            <th>{{ $t("semantic_tags.type", "Tag type") }}</th>
            <th>{{ $t("semantic_tags.parent", "Parent tag") }}</th>
            <th>{{ $t("semantic_tags.usage", "Usage") }}</th>
            <th>{{ $t("cleanup_tags.children", "Children") }}</th>
            <th>{{ $t("object.action", "Action") }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in filteredRows" :key="row.id">
            <td>{{ row.name }}</td>
            <td>{{ typeLabel(row.tag_type) }}</td>
            <td>{{ row.parent_tag_name || "—" }}</td>
            <td>{{ Number(row.direct_usage_count ?? row.usage_count ?? 0) }}</td>
            <td>{{ Number(row.child_count || 0) }}</td>
            <td class="action-cell">
              <button type="button" class="inline" @click="openEdit(row)">{{ $t("tag_tree.edit", "Edit") }}</button>
              <button type="button" class="inline" @click="confirmDelete(row)">{{ $t("tag_tree.delete", "Delete") }}</button>
            </td>
          </tr>
        </tbody>
      </table>

      <p v-else-if="!loading" class="muted">{{ $t("cleanup_tags.empty", "No matching tags.") }}</p>
    </section>

    <SemanticTagCreateModal
      :is-open="modalOpen"
      :initial-name="''"
      :edit-item="editItem"
      @close="closeModal"
      @created="handleSaved"
    />

    <div v-if="deleteOpen" class="modal-backdrop" @click.self="closeDelete">
      <div class="modal localization-modal">
        <h3>{{ $t("tag_tree.delete", "Delete Tag") }}</h3>
        <p>{{ $t("tag_tree.delete_confirm", { name: deleteTarget ? deleteTarget.name : '' }, 'Delete tag "{name}"?') }}</p>
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
import { apiErrorMessage } from "../api-errors";

export default {
  name: "TagCleanupAdminPage",
  components: {
    SemanticTagCreateModal
  },
  data() {
    return {
      loading: false,
      error: "",
      toast: "",
      search: "",
      showGenericOnly: true,
      showRootOnly: false,
      showUnusedOnly: false,
      items: [],
      modalOpen: false,
      editItem: null,
      deleteOpen: false,
      deleteTarget: null
    };
  },
  computed: {
    filteredRows() {
      const q = this.search.trim().toLocaleLowerCase();
      return this.items.filter((row) => {
        if (this.showGenericOnly && row.tag_type !== "generic") {
          return false;
        }
        if (this.showRootOnly && row.parent_tag_id) {
          return false;
        }
        if (this.showUnusedOnly && Number(row.direct_usage_count ?? row.usage_count ?? 0) !== 0) {
          return false;
        }
        if (q) {
          const name = String(row.name || "").toLocaleLowerCase();
          const parent = String(row.parent_tag_name || "").toLocaleLowerCase();
          if (!name.includes(q) && !parent.includes(q)) {
            return false;
          }
        }
        return true;
      }).sort((a, b) => String(a.name || "").localeCompare(String(b.name || ""), undefined, { sensitivity: "base" }));
    }
  },
  mounted() {
    this.fetchItems();
  },
  methods: {
    async fetchItems() {
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
          this.error = apiErrorMessage(data.error, "cleanup_tags.load_failed", "Failed to load cleanup tags");
          return;
        }
        this.items = Array.isArray(data.items) ? data.items : [];
      } catch (_e) {
        this.error = this.$t("cleanup_tags.load_failed", "Failed to load cleanup tags");
      } finally {
        this.loading = false;
      }
    },
    typeLabel(type) {
      if (type === "person") return this.$t("semantic_tags.type_person", "Person");
      if (type === "event") return this.$t("semantic_tags.type_event", "Event");
      if (type === "category") return this.$t("semantic_tags.type_category", "Category");
      return this.$t("semantic_tags.type_generic", "Generic");
    },
    openEdit(row) {
      this.editItem = { ...row };
      this.modalOpen = true;
    },
    closeModal() {
      this.modalOpen = false;
      this.editItem = null;
    },
    async handleSaved() {
      this.closeModal();
      await this.fetchItems();
      this.toast = this.$t("cleanup_tags.saved", "Tag updated.");
      window.setTimeout(() => { this.toast = ""; }, 2500);
    },
    confirmDelete(row) {
      if (Number(row.child_count || 0) > 0) {
        window.alert(this.$t("tag_tree.delete_children_blocked", "Delete child tags first."));
        return;
      }
      this.deleteTarget = row;
      this.deleteOpen = true;
    },
    closeDelete() {
      this.deleteOpen = false;
      this.deleteTarget = null;
    },
    async deleteTag() {
      if (!this.deleteTarget) {
        return;
      }
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
          this.error = apiErrorMessage(data.error, "cleanup_tags.delete_failed", "Failed to delete tag");
          return;
        }
        this.closeDelete();
        await this.fetchItems();
        this.toast = this.$t("cleanup_tags.deleted", "Tag deleted.");
        window.setTimeout(() => { this.toast = ""; }, 2500);
      } catch (_e) {
        this.error = this.$t("cleanup_tags.delete_failed", "Failed to delete tag");
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
.filters .grow {
  flex: 1;
}

.checkbox-row {
  gap: 1rem;
  margin-top: 0.75rem;
}

.meta {
  margin-bottom: 0.9rem;
  color: var(--muted);
}

.action-cell {
  display: flex;
  gap: 0.5rem;
}
</style>
