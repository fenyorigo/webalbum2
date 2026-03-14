<template>
  <div class="page">
    <header class="hero">
      <h1>{{ $t("admin.object_proposals", "Object proposals") }}</h1>
      <p>{{ $t("proposals.description", "Your submitted object change proposals.") }}</p>
    </header>

    <section class="panel">
      <div class="row">
        <label>
          {{ $t("object.status", "Status") }}
          <select v-model="status">
            <option value="pending">{{ $t("status.pending", "Pending") }}</option>
            <option value="approved">{{ $t("status.approved", "Approved") }}</option>
            <option value="done">{{ $t("status.done", "Done") }}</option>
            <option value="rejected">{{ $t("status.rejected", "Rejected") }}</option>
            <option value="cancelled">{{ $t("status.cancelled", "Cancelled") }}</option>
            <option value="all">{{ $t("status.all", "All") }}</option>
          </select>
        </label>
        <button type="button" @click="refreshAll" :disabled="loading">{{ $t("ui.refresh", "Refresh") }}</button>
      </div>
      <p v-if="error" class="error">{{ error }}</p>
    </section>

    <section class="panel">
      <h3>{{ $t("admin.job_status", "Job status") }}</h3>
      <p class="muted">
        {{ $t("status.queued", "Queued") }}: {{ objectJobCounts.queued }} |
        {{ $t("status.running", "Running") }}: {{ objectJobCounts.running }} |
        {{ $t("status.done", "Done") }}: {{ objectJobCounts.done }} |
        {{ $t("common.error", "Error") }}: {{ objectJobCounts.error }}
      </p>
      <div class="row">
        <label>
          {{ $t("jobs.filter", "Job filter") }}
          <select v-model="jobStatus" @change="loadObjectJobs">
            <option value="active">{{ $t("jobs.filter_active", "Active (queued/running)") }}</option>
            <option value="queued">{{ $t("status.queued", "Queued") }}</option>
            <option value="running">{{ $t("status.running", "Running") }}</option>
            <option value="done">{{ $t("status.done", "Done") }}</option>
            <option value="error">{{ $t("common.error", "Error") }}</option>
            <option value="cancelled">{{ $t("status.cancelled", "Cancelled") }}</option>
            <option value="all">{{ $t("status.all", "All") }}</option>
          </select>
        </label>
      </div>
      <table class="results-table" v-if="objectJobs.length">
        <thead>
          <tr>
            <th>{{ $t("common.id", "ID") }}</th>
            <th>{{ $t("object.sha256", "SHA-256") }}</th>
            <th>{{ $t("object.status", "Status") }}</th>
            <th>{{ $t("common.type", "Type") }}</th>
            <th>{{ $t("common.attempts", "Attempts") }}</th>
            <th>{{ $t("object.created", "Created") }}</th>
            <th>{{ $t("common.completed", "Completed") }}</th>
            <th>{{ $t("common.error", "Error") }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in objectJobs" :key="`obj-job-${row.id}`">
            <td>{{ row.id }}</td>
            <td><code>{{ row.sha256 }}</code></td>
            <td>{{ row.status }}</td>
            <td>{{ row.proposal_type || row.job_type }}</td>
            <td>{{ row.attempts }}</td>
            <td>{{ row.created_at }}</td>
            <td>{{ row.completed_at || "—" }}</td>
            <td :title="row.last_error || ''">{{ row.last_error || "—" }}</td>
          </tr>
        </tbody>
      </table>
      <p v-else class="muted">{{ $t("jobs.empty", "No object transform jobs in this filter.") }}</p>
    </section>

    <section class="results">
      <table class="results-table" v-if="items.length">
        <thead>
          <tr>
            <th>{{ $t("common.id", "ID") }}</th>
            <th>{{ $t("object.sha256", "SHA-256") }}</th>
            <th>{{ $t("common.type", "Type") }}</th>
            <th>{{ $t("object.status", "Status") }}</th>
            <th>{{ $t("common.proposer", "Proposer") }}</th>
            <th>{{ $t("object.created", "Created") }}</th>
            <th>{{ $t("admin.review", "Review") }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in items" :key="row.id">
            <td>{{ row.id }}</td>
            <td><code>{{ row.sha256 }}</code></td>
            <td>{{ row.proposal_type }}</td>
            <td>{{ row.effective_status || row.status }}</td>
            <td>{{ row.proposer_username || row.proposer_user_id || "—" }}</td>
            <td>{{ row.created_at }}</td>
            <td>
              <button
                v-if="row.status === 'pending'"
                class="inline"
                type="button"
                @click="startReview(row, 'approved')"
                :disabled="loading"
              >
                {{ $t("status.approve_action", "Approve") }}
              </button>
              <button
                v-if="row.status === 'pending'"
                class="inline"
                type="button"
                @click="startReview(row, 'rejected')"
                :disabled="loading"
              >
                {{ $t("status.reject_action", "Reject") }}
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

    <div v-if="reviewOpen" class="modal-backdrop" @click.self="closeReview">
      <div class="modal">
        <h3>{{ reviewDecision === "approved" ? $t("proposals.review_approve_title", "Approve proposal") : $t("proposals.review_reject_title", "Reject proposal") }}</h3>
        <p>{{ $t("proposals.review_target", { id: reviewTarget && reviewTarget.id, type: reviewTarget && reviewTarget.proposal_type }, "Proposal #{id} ({type})") }}</p>
        <label>
          {{ $t("proposals.review_note", "Review note") }}
          <textarea v-model.trim="reviewNote" rows="4" :placeholder="$t('proposals.review_note_placeholder', 'Optional rationale')"></textarea>
        </label>
        <div class="modal-actions">
          <button type="button" @click="submitReview" :disabled="loading">
            {{ reviewDecision === "approved" ? $t("status.approve_action", "Approve") : $t("status.reject_action", "Reject") }}
          </button>
          <button type="button" class="inline" @click="closeReview" :disabled="loading">{{ $t("ui.cancel", "Cancel") }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { apiErrorMessage } from "../api-errors";

export default {
  name: "ObjectProposalsAdminPage",
  data() {
    return {
      loading: false,
      error: "",
      status: "pending",
      items: [],
      jobStatus: "active",
      objectJobs: [],
      objectJobCounts: { queued: 0, running: 0, done: 0, error: 0, cancelled: 0 },
      reviewOpen: false,
      reviewTarget: null,
      reviewDecision: "approved",
      reviewNote: ""
    };
  },
  mounted() {
    this.refreshAll();
  },
  methods: {
    async refreshAll() {
      this.loading = true;
      this.error = "";
      try {
        await Promise.all([this.loadCore(false), this.loadObjectJobs(false)]);
      } finally {
        this.loading = false;
      }
    },
    async load() {
      return this.loadCore(true);
    },
    async loadCore(manageLoading) {
      if (manageLoading) {
        this.loading = true;
        this.error = "";
      }
      try {
        const qs = new URLSearchParams();
        qs.set("status", this.status);
        const res = await fetch(`/api/admin/objects/proposals?${qs.toString()}`);
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
        if (manageLoading) {
          this.loading = false;
        }
      }
    },
    async loadObjectJobs(manageLoading = true) {
      if (manageLoading) {
        this.loading = true;
        this.error = "";
      }
      try {
        const qs = new URLSearchParams();
        qs.set("status", this.jobStatus);
        qs.set("limit", "100");
        const res = await fetch(`/api/admin/objects/jobs?${qs.toString()}`);
        if (res.status === 401 || res.status === 403) {
          window.dispatchEvent(new CustomEvent("wa-auth-changed", { detail: null }));
          this.$router.push("/login");
          return;
        }
        const data = await res.json();
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "jobs.load_failed", "Failed to load transform jobs");
          return;
        }
        this.objectJobs = Array.isArray(data.items) ? data.items : [];
        this.objectJobCounts = data && typeof data.counts === "object"
          ? { queued: 0, running: 0, done: 0, error: 0, cancelled: 0, ...data.counts }
          : { queued: 0, running: 0, done: 0, error: 0, cancelled: 0 };
      } catch (_e) {
        this.error = this.$t("jobs.load_failed", "Failed to load transform jobs");
      } finally {
        if (manageLoading) {
          this.loading = false;
        }
      }
    },
    startReview(row, decision) {
      this.reviewTarget = row;
      this.reviewDecision = decision;
      this.reviewNote = "";
      this.reviewOpen = true;
    },
    closeReview() {
      this.reviewOpen = false;
      this.reviewTarget = null;
      this.reviewDecision = "approved";
      this.reviewNote = "";
    },
    async submitReview() {
      if (!this.reviewTarget) return;
      this.loading = true;
      this.error = "";
      try {
        const res = await fetch(`/api/admin/objects/proposals/${this.reviewTarget.id}/review`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            decision: this.reviewDecision,
            review_note: this.reviewNote
          })
        });
        const data = await res.json();
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "proposals.review_failed", "Failed to review proposal");
          return;
        }
        this.closeReview();
        await this.refreshAll();
      } catch (_e) {
        this.error = this.$t("proposals.review_failed", "Failed to review proposal");
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>
