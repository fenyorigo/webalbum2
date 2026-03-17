<template>
  <div v-if="isOpen" class="modal-backdrop" @click.self="close">
    <div class="modal semantic-tag-modal">
      <h3>{{ editMode ? $t("semantic_tags.edit_title", "Edit typed tag") : $t("semantic_tags.create_title", "Create typed tag") }}</h3>
      <label>
        {{ $t("semantic_tags.name", "Tag name") }}
        <input v-model.trim="form.name" type="text" />
      </label>
      <label>
        {{ $t("semantic_tags.type", "Tag type") }}
        <select v-model="form.tag_type">
          <option value="person">{{ $t("semantic_tags.type_person", "Person") }}</option>
          <option value="event">{{ $t("semantic_tags.type_event", "Event") }}</option>
          <option value="category">{{ $t("semantic_tags.type_category", "Category") }}</option>
          <option value="generic">{{ $t("semantic_tags.type_generic", "Generic") }}</option>
        </select>
      </label>
      <label>
        {{ $t("semantic_tags.parent", "Parent tag") }}
        <input
          v-model.trim="parentInput"
          type="text"
          :placeholder="$t('semantic_tags.parent_placeholder', 'Optional parent tag')"
          @input="onParentInput"
        />
      </label>
      <div v-if="parentSuggestions.length" class="suggestions">
        <button
          v-for="item in parentSuggestions"
          :key="item.id"
          type="button"
          class="suggestion"
          @click="selectParent(item)"
        >
          <span class="name">{{ item.name }}</span>
          <span class="count">{{ displayType(item.tag_type) }}</span>
        </button>
      </div>
      <p v-if="selectedParent" class="muted">
        {{ $t("semantic_tags.parent_selected", { name: selectedParent.name }, "Parent: {name}") }}
      </p>
      <label class="checkbox">
        <input v-model="form.is_active" type="checkbox" />
        {{ $t("semantic_tags.active", "Active") }}
      </label>
      <div class="modal-actions">
        <button type="button" @click="save" :disabled="loading">{{ $t("ui.save", "Save") }}</button>
        <button type="button" class="inline" @click="close" :disabled="loading">{{ $t("ui.cancel", "Cancel") }}</button>
      </div>
      <p v-if="error" class="error">{{ error }}</p>
    </div>
  </div>
</template>

<script>
import { apiErrorMessage } from "../api-errors";

function emptyForm() {
  return {
    id: null,
    name: "",
    tag_type: "generic",
    parent_tag_id: null,
    is_active: true
  };
}

export default {
  name: "SemanticTagCreateModal",
  props: {
    isOpen: { type: Boolean, required: true },
    initialName: { type: String, default: "" },
    editItem: { type: Object, default: null }
  },
  emits: ["close", "created"],
  data() {
    return {
      loading: false,
      error: "",
      form: emptyForm(),
      parentInput: "",
      selectedParent: null,
      parentSuggestions: [],
      parentTimer: null
    };
  },
  watch: {
    isOpen(value) {
      if (value) {
        this.reset();
      }
    },
    initialName(value) {
      if (this.isOpen && !this.editMode) {
        this.form.name = value || "";
      }
    },
    editItem: {
      handler() {
        if (this.isOpen) {
          this.reset();
        }
      },
      deep: true
    }
  },
  computed: {
    editMode() {
      return !!(this.editItem && this.editItem.id);
    }
  },
  methods: {
    reset() {
      this.loading = false;
      this.error = "";
      this.parentSuggestions = [];
      if (this.parentTimer) {
        clearTimeout(this.parentTimer);
        this.parentTimer = null;
      }
      if (this.editMode) {
        this.form = {
          id: this.editItem.id,
          name: this.editItem.name || "",
          tag_type: this.editItem.tag_type || "generic",
          parent_tag_id: this.editItem.parent_tag_id || null,
          is_active: !!Number(this.editItem.is_active ?? 1)
        };
        this.selectedParent = this.editItem.parent_tag_id
          ? {
              id: this.editItem.parent_tag_id,
              name: this.editItem.parent_tag_name || "",
              tag_type: ""
            }
          : null;
        this.parentInput = this.editItem.parent_tag_name || "";
        return;
      }
      this.form = emptyForm();
      this.form.name = this.initialName || "";
      this.selectedParent = null;
      this.parentInput = "";
    },
    displayType(type) {
      if (type === "person") return this.$t("semantic_tags.type_person", "Person");
      if (type === "event") return this.$t("semantic_tags.type_event", "Event");
      if (type === "category") return this.$t("semantic_tags.type_category", "Category");
      return this.$t("semantic_tags.type_generic", "Generic");
    },
    close() {
      this.$emit("close");
    },
    onParentInput() {
      this.selectedParent = null;
      this.form.parent_tag_id = null;
      if (this.parentTimer) {
        clearTimeout(this.parentTimer);
      }
      const q = this.parentInput.trim();
      if (q.length < 2) {
        this.parentSuggestions = [];
        return;
      }
      this.parentTimer = setTimeout(() => this.fetchParentSuggestions(q), 150);
    },
    async fetchParentSuggestions(q) {
      try {
        const qs = new URLSearchParams({ q, limit: "10" });
        const res = await fetch(`/api/admin/semantic-tags/lookup?${qs.toString()}`);
        if (res.status === 401 || res.status === 403) {
          window.dispatchEvent(new CustomEvent("wa-auth-changed", { detail: null }));
          this.$router.push("/login");
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.parentSuggestions = [];
          return;
        }
        const ownId = this.editMode ? Number(this.editItem.id || 0) : 0;
        this.parentSuggestions = Array.isArray(data.items)
          ? data.items.filter((item) => Number(item.id || 0) !== ownId)
          : [];
      } catch (_e) {
        this.parentSuggestions = [];
      }
    },
    selectParent(item) {
      this.selectedParent = item;
      this.form.parent_tag_id = item.id;
      this.parentInput = item.name;
      this.parentSuggestions = [];
    },
    async save() {
      this.loading = true;
      this.error = "";
      try {
        const url = this.editMode
          ? `/api/admin/semantic-tags/${this.editItem.id}`
          : "/api/admin/semantic-tags";
        const res = await fetch(url, {
          method: this.editMode ? "PUT" : "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            name: this.form.name,
            tag_type: this.form.tag_type,
            parent_tag_id: this.form.parent_tag_id,
            parent_tag_name: this.form.parent_tag_id ? "" : this.parentInput.trim(),
            is_active: this.form.is_active ? 1 : 0
          })
        });
        if (res.status === 401 || res.status === 403) {
          window.dispatchEvent(new CustomEvent("wa-auth-changed", { detail: null }));
          this.$router.push("/login");
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.error = apiErrorMessage(
            data.error,
            this.editMode ? "semantic_tags.save_failed" : "semantic_tags.create_failed",
            this.editMode ? "Failed to save typed tag" : "Failed to create typed tag"
          );
          return;
        }
        this.$emit("created", data.item || null);
      } catch (_e) {
        this.error = this.$t(
          this.editMode ? "semantic_tags.save_failed" : "semantic_tags.create_failed",
          this.editMode ? "Failed to save typed tag" : "Failed to create typed tag"
        );
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
.semantic-tag-modal {
  width: min(34rem, 96vw);
}

.checkbox {
  display: flex;
  align-items: center;
  gap: 0.55rem;
}
</style>
