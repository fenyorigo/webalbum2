<template>
  <div class="tag-tree-node">
    <div
      class="tree-row"
      :class="{ matched: isMatched, inactive: !Number(node.is_active || 0) }"
      :style="{ paddingLeft: `${depth * 1.05}rem` }"
    >
      <button
        v-if="hasChildren"
        type="button"
        class="toggle"
        @click="$emit('toggle', node.id)"
        :aria-label="expanded ? $t('tag_tree.collapse', 'Collapse') : $t('tag_tree.expand', 'Expand')"
      >
        {{ expanded ? "▾" : "▸" }}
      </button>
      <span v-else class="toggle spacer"></span>
      <button type="button" class="name-button" @click="$emit('select', node)">
        <span class="name">{{ node.name }}</span>
      </button>
      <span v-if="showUsage" class="usage">({{ directUsageCount }})</span>
      <span class="type">{{ typeLabel(node.tag_type) }}</span>
      <span v-if="!readOnly" class="actions">
        <button type="button" class="inline" @click="$emit('add-child', node)">{{ $t("tag_tree.add_child", "Add child") }}</button>
        <button type="button" class="inline" @click="$emit('edit', node)">{{ $t("tag_tree.edit", "Edit") }}</button>
        <button type="button" class="inline" @click="$emit('delete', node)">{{ $t("tag_tree.delete", "Delete") }}</button>
      </span>
    </div>
    <div v-if="hasChildren && expanded" class="children">
      <SemanticTagTreeNode
        v-for="child in children"
        :key="child.id"
        :node="child"
        :depth="depth + 1"
        :expanded-ids="expandedIds"
        :match-ids="matchIds"
        :children-map="childrenMap"
        :show-usage="showUsage"
        :read-only="readOnly"
        @toggle="$emit('toggle', $event)"
        @select="$emit('select', $event)"
        @add-child="$emit('add-child', $event)"
        @edit="$emit('edit', $event)"
        @delete="$emit('delete', $event)"
      />
    </div>
  </div>
</template>

<script>
export default {
  name: "SemanticTagTreeNode",
  props: {
    node: { type: Object, required: true },
    depth: { type: Number, default: 0 },
    expandedIds: { type: Object, required: true },
    matchIds: { type: Object, required: true },
    childrenMap: { type: Object, required: true },
    showUsage: { type: Boolean, default: true },
    readOnly: { type: Boolean, default: false }
  },
  emits: ["toggle", "select", "add-child", "edit", "delete"],
  computed: {
    children() {
      return this.childrenMap[this.node.id] || [];
    },
    hasChildren() {
      return this.children.length > 0;
    },
    expanded() {
      return !!this.expandedIds[this.node.id];
    },
    isMatched() {
      return !!this.matchIds[this.node.id];
    },
    directUsageCount() {
      return Number(this.node.direct_usage_count ?? this.node.usage_count ?? 0);
    }
  },
  methods: {
    typeLabel(type) {
      if (type === "person") return this.$t("semantic_tags.type_person", "Person");
      if (type === "event") return this.$t("semantic_tags.type_event", "Event");
      if (type === "category") return this.$t("semantic_tags.type_category", "Category");
      return this.$t("semantic_tags.type_generic", "Generic");
    }
  }
};
</script>

<style scoped>
.tree-row {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  padding: 0.4rem 0.2rem;
  border-radius: 0.45rem;
}

.tree-row.matched {
  background: rgba(229, 209, 130, 0.22);
}

.tree-row.inactive .name,
.tree-row.inactive .type,
.tree-row.inactive .usage {
  opacity: 0.7;
}

.toggle {
  width: 2.5rem;
  min-width: 2.5rem;
  height: 2.5rem;
  padding: 0;
  border: none;
  background: transparent;
  color: inherit;
  cursor: pointer;
  font-size: 1.45rem;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.5rem;
}

.toggle:hover {
  background: rgba(255, 255, 255, 0.08);
}

.toggle.spacer {
  display: inline-block;
}

.name {
  font-weight: 600;
}

.name-button {
  border: none;
  background: transparent;
  color: inherit;
  padding: 0;
  cursor: pointer;
  text-align: left;
}

.name-button:hover .name {
  text-decoration: underline;
}

.type,
.usage {
  color: var(--muted);
  font-size: 0.92rem;
}

.usage {
  min-width: 2.5rem;
}

.actions {
  margin-left: auto;
  display: flex;
  gap: 0.45rem;
}
</style>
