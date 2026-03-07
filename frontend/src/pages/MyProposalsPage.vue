<template>
  <div class="page">
    <header class="hero">
      <h1>My Proposals</h1>
      <p>Your submitted object change proposals.</p>
    </header>

    <section class="panel">
      <div class="row">
        <label>
          Status
          <select v-model="status" @change="load">
            <option value="all">All</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </label>
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
            <th>Type</th>
            <th>Status</th>
            <th>Created</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in items" :key="row.id">
            <td>{{ row.id }}</td>
            <td><code>{{ row.sha256 }}</code></td>
            <td>{{ row.proposal_type }}</td>
            <td>{{ row.status }}</td>
            <td>{{ row.created_at }}</td>
            <td>
              <button
                v-if="row.status === 'pending'"
                type="button"
                class="inline"
                @click="cancelProposal(row.id)"
                :disabled="loading"
              >
                Cancel
              </button>
              <router-link class="inline-link" :to="{ path: '/object', query: { sha256: row.sha256 } }">
                Open object
              </router-link>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else class="muted">No proposals in this filter.</p>
    </section>
  </div>
</template>

<script>
export default {
  name: "MyProposalsPage",
  data() {
    return {
      loading: false,
      error: "",
      status: "all",
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
        const qs = new URLSearchParams();
        qs.set("status", this.status);
        const res = await fetch(`/api/objects/proposals/mine?${qs.toString()}`);
        if (res.status === 401 || res.status === 403) {
          window.dispatchEvent(new CustomEvent("wa-auth-changed", { detail: null }));
          this.$router.push("/login");
          return;
        }
        const data = await res.json();
        if (!res.ok) {
          this.error = data.error || "Failed to load proposals";
          return;
        }
        this.items = Array.isArray(data.items) ? data.items : [];
      } catch (_e) {
        this.error = "Failed to load proposals";
      } finally {
        this.loading = false;
      }
    },
    async cancelProposal(id) {
      if (!window.confirm(`Cancel proposal #${id}?`)) {
        return;
      }
      this.loading = true;
      this.error = "";
      try {
        const res = await fetch(`/api/objects/proposals/${id}/cancel`, { method: "POST" });
        const data = await res.json();
        if (!res.ok) {
          this.error = data.error || "Failed to cancel proposal";
          return;
        }
        await this.load();
      } catch (_e) {
        this.error = "Failed to cancel proposal";
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>
