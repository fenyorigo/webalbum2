<template>
  <div class="page">
    <header class="hero">
      <h1>My Notes</h1>
      <p>Your object notes across the gallery.</p>
    </header>

    <section class="panel">
      <div class="row">
        <button type="button" class="inline" @click="load" :disabled="loading">Refresh</button>
      </div>
      <p v-if="error" class="error">{{ error }}</p>
    </section>

    <section class="results">
      <table class="results-table" v-if="items.length">
        <thead>
          <tr>
            <th>ID</th>
            <th>SHA</th>
            <th>Object Status</th>
            <th>Note</th>
            <th>Created</th>
            <th>Action</th>
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
                Open object
              </router-link>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else class="muted">No notes yet.</p>
    </section>
  </div>
</template>

<script>
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
          this.error = data.error || "Failed to load notes";
          return;
        }
        this.items = Array.isArray(data.items) ? data.items : [];
      } catch (_e) {
        this.error = "Failed to load notes";
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>
