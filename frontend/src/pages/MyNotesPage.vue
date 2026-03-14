<template>
  <div class="page">
    <header class="hero">
      <h1>{{ $t("notes.title", "My Notes") }}</h1>
      <p>{{ $t("notes.description", "Your object notes across the gallery.") }}</p>
    </header>

    <section class="panel">
      <div class="row">
        <button type="button" class="inline" @click="load" :disabled="loading">{{ $t("ui.refresh", "Refresh") }}</button>
      </div>
      <p v-if="error" class="error">{{ error }}</p>
    </section>

    <section class="results">
      <table class="results-table" v-if="items.length">
        <thead>
          <tr>
            <th>{{ $t("common.id", "ID") }}</th>
            <th>{{ $t("object.sha", "SHA") }}</th>
            <th>{{ $t("object.status", "Status") }}</th>
            <th>{{ $t("notes.single", "Note") }}</th>
            <th>{{ $t("object.created", "Created") }}</th>
            <th>{{ $t("object.action", "Action") }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in items" :key="row.id">
            <td>{{ row.id }}</td>
            <td><code>{{ row.sha256 }}</code></td>
            <td>{{ row.object_status }}</td>
            <td>{{ row.note_text }}</td>
            <td>{{ row.created_at }}</td>
            <td>
              <router-link class="inline-link" :to="{ path: '/object', query: { sha256: row.sha256 } }">
                {{ $t("object.open", "Open object") }}
              </router-link>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else class="muted">{{ $t("notes.empty", "No notes yet.") }}</p>
    </section>
  </div>
</template>

<script>
import { apiErrorMessage } from "../api-errors";

export default {
  name: "MyNotesPage",
  data() {
    return {
      loading: false,
      error: "",
      items: []
    };
  },
  mounted() {
    this.load();
  },
  methods: {
    async load() {
      this.loading = true;
      this.error = "";
      try {
        const res = await fetch("/api/objects/notes/mine");
        if (res.status === 401 || res.status === 403) {
          window.dispatchEvent(new CustomEvent("wa-auth-changed", { detail: null }));
          this.$router.push("/login");
          return;
        }
        const data = await res.json();
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "notes.load_failed", "Failed to load notes");
          return;
        }
        this.items = Array.isArray(data.items) ? data.items : [];
      } catch (_e) {
        this.error = this.$t("notes.load_failed", "Failed to load notes");
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>
