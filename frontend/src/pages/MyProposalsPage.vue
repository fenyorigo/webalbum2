<template>
  <div class="page">
    <header class="hero">
      <h1>{{ $t("proposals.title", "My Proposals") }}</h1>
      <p>{{ $t("proposals.description", "Your submitted object change proposals.") }}</p>
    </header>

    <section class="panel">
      <div class="row">
        <label>
          {{ $t("object.status", "Status") }}
          <select v-model="status" @change="load">
            <option value="all">{{ $t("status.all", "All") }}</option>
            <option value="pending">{{ $t("status.pending", "Pending") }}</option>
            <option value="approved">{{ $t("status.approved", "Approved") }}</option>
            <option value="rejected">{{ $t("status.rejected", "Rejected") }}</option>
            <option value="cancelled">{{ $t("status.cancelled", "Cancelled") }}</option>
          </select>
        </label>
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
            <th>{{ $t("common.type", "Type") }}</th>
            <th>{{ $t("object.status", "Status") }}</th>
            <th>{{ $t("object.created", "Created") }}</th>
            <th>{{ $t("object.action", "Action") }}</th>
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
                {{ $t("ui.cancel", "Cancel") }}
              </button>
              <router-link class="inline-link" :to="{ path: '/object', query: { sha256: row.sha256 } }">
                {{ $t("object.open", "Open object") }}
              </router-link>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else class="muted">{{ $t("proposals.empty", "No proposals in this filter.") }}</p>
    </section>
  </div>
</template>

<script>
import { apiErrorMessage } from "../api-errors";

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
          this.error = apiErrorMessage(data.error, "proposals.load_failed", "Failed to load proposals");
          return;
        }
        this.items = Array.isArray(data.items) ? data.items : [];
      } catch (_e) {
        this.error = this.$t("proposals.load_failed", "Failed to load proposals");
      } finally {
        this.loading = false;
      }
    },
    async cancelProposal(id) {
      if (!window.confirm(this.$t("proposals.cancel_confirm", { id }, "Cancel proposal #{id}?"))) {
        return;
      }
      this.loading = true;
      this.error = "";
      try {
        const res = await fetch(`/api/objects/proposals/${id}/cancel`, { method: "POST" });
        const data = await res.json();
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "proposals.cancel_failed", "Failed to cancel proposal");
          return;
        }
        await this.load();
      } catch (_e) {
        this.error = this.$t("proposals.cancel_failed", "Failed to cancel proposal");
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>
