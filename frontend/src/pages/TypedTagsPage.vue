<template>
  <div class="page">
    <header class="hero">
      <h1>{{ $t("browse_tags.title", "Typed Tags") }}</h1>
      <p>{{ $t("browse_tags.description", "Browse categories and events, then open search from the selected tag.") }}</p>
    </header>

    <section class="panel">
      <div class="row filters">
        <label class="grow">
          {{ $t("browse_tags.search", "Search tags") }}
          <input
            v-model.trim="search"
            type="text"
            :placeholder="$t('browse_tags.search_placeholder', 'Find categories and events...')"
          />
        </label>
      </div>
    </section>

    <section class="panel">
      <div class="meta">
        <span v-if="loading">{{ $t("common.loading", "Loading...") }}</span>
        <span v-else>{{ filteredCount }} {{ $t("browse_tags.nodes", "nodes") }}</span>
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
          :read-only="true"
          :show-usage="true"
          @toggle="toggleNode"
          @select="selectTag"
        />
      </div>
      <p v-else-if="!loading" class="muted">{{ $t("browse_tags.empty", "No typed tags available.") }}</p>
      <p v-if="error" class="error">{{ error }}</p>
    </section>
  </div>
</template>

<script>
import SemanticTagTreeNode from "../components/SemanticTagTreeNode.vue";
import { apiErrorMessage } from "../api-errors";

export default {
  name: "TypedTagsPage",
  components: { SemanticTagTreeNode },
  data() {
    return {
      loading: false,
      error: "",
      search: "",
      items: [],
      expandedIds: {}
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
        const candidateParentId = row.parent_tag_id ? Number(row.parent_tag_id) : 0;
        const parentId = candidateParentId && this.itemsById[candidateParentId] ? candidateParentId : 0;
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
        const res = await fetch("/api/semantic-tags/tree");
        if (res.status === 401 || res.status === 403) {
          window.dispatchEvent(new CustomEvent("wa-auth-changed", { detail: null }));
          this.$router.push("/login");
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "browse_tags.load_failed", "Failed to load typed tags");
          return;
        }
        this.items = Array.isArray(data.items) ? data.items : [];
        const next = {};
        for (const row of this.items) {
          if (Number(row.child_count || 0) > 0 && !row.parent_tag_id) {
            next[row.id] = true;
          }
        }
        this.expandedIds = next;
      } catch (_e) {
        this.error = this.$t("browse_tags.load_failed", "Failed to load typed tags");
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
    selectTag(node) {
      const query = {
        semantic_tag: String(node.id),
        semantic_tag_name: String(node.name || ""),
        semantic_tag_descendants: "1",
        run: "1"
      };
      this.$router.push({ path: "/", query });
    }
  }
};
</script>

<style scoped>
.filters .grow {
  flex: 1;
}

.meta {
  margin-bottom: 0.9rem;
  color: var(--muted);
}

.tree-wrap {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}
</style>
