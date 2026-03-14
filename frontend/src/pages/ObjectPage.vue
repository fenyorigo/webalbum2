<template>
  <div class="page object-page">
    <header class="hero">
      <h1>{{ $t("object.title", "Object") }}</h1>
      <p>{{ $t("object.description", "Collaborative notes and change proposals by SHA-256 identity.") }}</p>
      <div class="row actions">
        <button type="button" class="inline" @click="closeObjectView" :disabled="loading">{{ $t("ui.cancel", "Cancel") }}</button>
      </div>
    </header>

    <section class="panel">
      <div class="row">
        <label>
          {{ $t("object.sha256", "SHA-256") }}
          <input v-model.trim="manualSha" type="text" placeholder="64 hex chars" />
        </label>
        <button type="button" class="inline" @click="loadBySha" :disabled="loading">{{ $t("common.load", "Load") }}</button>
      </div>
      <p v-if="resolved">
        <strong>{{ $t("object.sha", "SHA") }}:</strong> <code>{{ resolved.sha256 }}</code><br />
        <strong>{{ $t("object.status", "Status") }}:</strong> {{ resolved.object ? resolved.object.status : $t("object.not_in_registry", "not in registry") }}<br />
        <strong>{{ $t("common.path", "Path") }}:</strong> {{ resolved.rel_path || "—" }}<br />
        <strong>{{ $t("object.entity", "Entity") }}:</strong> {{ resolved.entity || "—" }}
      </p>
      <p v-if="error" class="error">{{ error }}</p>
    </section>

    <section class="panel" v-if="resolved && resolved.sha256">
      <h3>{{ $t("object.notes", "Notes") }}</h3>
      <div class="row">
        <label class="wide">
          {{ $t("object.new_note", "New note") }}
          <textarea v-model.trim="newNote" rows="3" :placeholder="$t('object.write_note', 'Write an object note')"></textarea>
        </label>
      </div>
      <div class="row actions">
        <button type="button" @click="createNote" :disabled="loading">{{ $t("object.save_note", "Save note") }}</button>
        <button type="button" class="inline" @click="cancelDraftNote" :disabled="loading || !newNote">{{ $t("ui.cancel", "Cancel") }}</button>
        <button type="button" class="inline" @click="refreshAll" :disabled="loading">{{ $t("ui.refresh", "Refresh") }}</button>
      </div>
      <table class="results-table" v-if="notes.length">
        <thead>
          <tr>
            <th>{{ $t("common.id", "ID") }}</th>
            <th>{{ $t("common.author", "Author") }}</th>
            <th>{{ $t("common.note", "Note") }}</th>
            <th>{{ $t("object.created", "Created") }}</th>
            <th>{{ $t("object.action", "Action") }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in notes" :key="row.id">
            <td>{{ row.id }}</td>
            <td>{{ row.author_display_name || row.author_username || "—" }}</td>
            <td>
              <template v-if="editingNoteId === row.id">
                <textarea v-model.trim="editNoteText" rows="3"></textarea>
              </template>
              <template v-else>
                {{ row.note_text }}
              </template>
            </td>
            <td>{{ row.created_at }}</td>
            <td>
              <template v-if="canEditNote(row)">
                <button
                  v-if="editingNoteId !== row.id"
                  type="button"
                  class="inline"
                  @click="startEdit(row)"
                  :disabled="loading"
                >
                  {{ $t("common.edit", "Edit") }}
                </button>
                <button
                  v-if="editingNoteId === row.id"
                  type="button"
                  @click="saveEdit(row.id)"
                  :disabled="loading"
                >
                  {{ $t("ui.save", "Save") }}
                </button>
                <button
                  v-if="editingNoteId === row.id"
                  type="button"
                  class="inline"
                  @click="cancelEdit"
                  :disabled="loading"
                >
                  {{ $t("ui.cancel", "Cancel") }}
                </button>
                <button type="button" class="inline" @click="deleteNote(row.id)" :disabled="loading">{{ $t("common.delete", "Delete") }}</button>
              </template>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else class="muted">{{ $t("notes.empty", "No notes yet.") }}</p>
    </section>

    <section class="panel" v-if="resolved && resolved.sha256">
      <h3>{{ $t("object.change_proposals", "Change Proposals") }}</h3>
      <div class="row">
        <label>
          {{ $t("object.proposal_type", "Proposal type") }}
          <select v-model="proposalType">
            <option value="retag">{{ $t("proposal.type.retag", "Retag") }}</option>
            <option value="rotate_left">{{ $t("proposal.type.rotate_left", "Rotate left") }}</option>
            <option value="rotate_right">{{ $t("proposal.type.rotate_right", "Rotate right") }}</option>
            <option value="annotate">{{ $t("proposal.type.annotate", "Annotate") }}</option>
            <option value="transform">{{ $t("proposal.type.transform", "Transform") }}</option>
            <option value="restore_metadata">{{ $t("proposal.type.restore_metadata", "Restore metadata") }}</option>
          </select>
        </label>
      </div>
      <div class="row">
        <label class="wide">
          {{ $t("object.details_optional", "Details (optional)") }}
          <textarea v-model.trim="proposalDetails" rows="4" :placeholder="$t('object.proposal_details_placeholder', 'Describe what should change and why')"></textarea>
        </label>
      </div>
      <div class="row actions">
        <button type="button" @click="submitProposal" :disabled="loading">{{ $t("object.submit_proposal", "Submit proposal") }}</button>
        <button type="button" class="inline" @click="cancelDraftProposal" :disabled="loading || !proposalDetails">{{ $t("ui.cancel", "Cancel") }}</button>
      </div>
      <table class="results-table" v-if="proposals.length">
        <thead>
          <tr>
            <th>{{ $t("common.id", "ID") }}</th>
            <th>{{ $t("common.type", "Type") }}</th>
            <th>{{ $t("object.status", "Status") }}</th>
            <th>{{ $t("common.proposer", "Proposer") }}</th>
            <th>{{ $t("object.created", "Created") }}</th>
            <th>{{ $t("object.action", "Action") }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in proposals" :key="row.id">
            <td>{{ row.id }}</td>
            <td>{{ row.proposal_type }}</td>
            <td>{{ row.status }}</td>
            <td>{{ row.proposer_username || row.proposer_user_id || "—" }}</td>
            <td>{{ row.created_at }}</td>
            <td>
              <button
                v-if="canCancelProposal(row)"
                type="button"
                class="inline"
                @click="cancelProposal(row.id)"
                :disabled="loading"
              >
                {{ $t("ui.cancel", "Cancel") }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else class="muted">{{ $t("proposals.empty", "No proposals in this filter.") }}</p>
    </section>

    <section class="panel" v-if="resolved && resolved.sha256">
      <h3>{{ $t("object.transform_jobs", "Transform Jobs") }}</h3>
      <p class="muted">
        queued: {{ objectJobCounts.queued }} |
        running: {{ objectJobCounts.running }} |
        done: {{ objectJobCounts.done }} |
        error: {{ objectJobCounts.error }}
      </p>
      <table class="results-table" v-if="objectJobs.length">
        <thead>
          <tr>
            <th>{{ $t("common.id", "ID") }}</th>
            <th>{{ $t("object.status", "Status") }}</th>
            <th>{{ $t("object.action", "Action") }}</th>
            <th>{{ $t("common.attempts", "Attempts") }}</th>
            <th>{{ $t("object.created", "Created") }}</th>
            <th>{{ $t("common.completed", "Completed") }}</th>
            <th>{{ $t("common.error", "Error") }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in objectJobs" :key="`job-${row.id}`">
            <td>{{ row.id }}</td>
            <td>{{ row.status }}</td>
            <td>{{ row.proposal_type || row.proposal_id || "—" }}</td>
            <td>{{ row.attempts }}</td>
            <td>{{ row.created_at }}</td>
            <td>{{ row.completed_at || "—" }}</td>
            <td :title="row.last_error || ''">{{ row.last_error || "—" }}</td>
          </tr>
        </tbody>
      </table>
      <p v-else class="muted">{{ $t("object.no_transform_jobs", "No transform jobs for this object yet.") }}</p>
    </section>

    <section class="panel">
      <h3>{{ $t("proposals.title", "My Proposals") }}</h3>
      <div class="row">
        <label>
          {{ $t("object.status", "Status") }}
          <select v-model="myProposalStatus" @change="loadMyProposals">
            <option value="all">{{ $t("status.all", "All") }}</option>
            <option value="pending">{{ $t("status.pending", "Pending") }}</option>
            <option value="approved">{{ $t("status.approved", "Approved") }}</option>
            <option value="rejected">{{ $t("status.rejected", "Rejected") }}</option>
            <option value="cancelled">{{ $t("status.cancelled", "Cancelled") }}</option>
          </select>
        </label>
        <button type="button" class="inline" @click="loadMyProposals" :disabled="loading">{{ $t("ui.refresh", "Refresh") }}</button>
      </div>
      <table class="results-table" v-if="myProposals.length">
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
          <tr v-for="row in myProposals" :key="`mine-${row.id}`">
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
  name: "ObjectPage",
  data() {
    return {
      loading: false,
      error: "",
      resolved: null,
      manualSha: "",
      notes: [],
      proposals: [],
      objectJobs: [],
      objectJobCounts: { queued: 0, running: 0, done: 0, error: 0, cancelled: 0 },
      myProposals: [],
      myProposalStatus: "all",
      newNote: "",
      editingNoteId: null,
      editNoteText: "",
      proposalType: "retag",
      proposalDetails: ""
    };
  },
  computed: {
    currentUser() {
      return window.__wa_current_user || null;
    },
    hasDraftChanges() {
      return !!(
        (this.newNote && this.newNote.trim() !== "") ||
        this.editingNoteId !== null ||
        (this.proposalDetails && this.proposalDetails.trim() !== "")
      );
    }
  },
  mounted() {
    this.loadFromRoute();
    this.loadMyProposals();
  },
  watch: {
    "$route.query": {
      handler() {
        this.loadFromRoute();
      },
      deep: true
    }
  },
  methods: {
    canEditNote(row) {
      const user = this.currentUser;
      if (!user || !row) return false;
      if (user.is_admin) return true;
      return Number(row.author_user_id || 0) === Number(user.id || 0);
    },
    canCancelProposal(row) {
      const user = this.currentUser;
      if (!user || !row) return false;
      return row.status === "pending" && Number(row.proposer_user_id || 0) === Number(user.id || 0);
    },
    loadFromRoute() {
      const q = this.$route.query || {};
      if (q.sha256) {
        this.manualSha = String(q.sha256);
        this.loadBySha();
        return;
      }
      if (q.file_id || q.asset_id) {
        this.resolveFromRef();
      }
    },
    async resolveFromRef() {
      this.loading = true;
      this.error = "";
      this.resolved = null;
      try {
        const q = this.$route.query || {};
        const qs = new URLSearchParams();
        if (q.file_id) qs.set("file_id", String(q.file_id));
        if (q.asset_id) qs.set("asset_id", String(q.asset_id));
        const res = await fetch(`/api/objects/resolve?${qs.toString()}`);
        if (res.status === 401 || res.status === 403) {
          window.dispatchEvent(new CustomEvent("wa-auth-changed", { detail: null }));
          this.$router.push("/login");
          return;
        }
        const data = await res.json();
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "object.resolve_failed", "Object resolve failed");
          return;
        }
        this.resolved = data;
        this.manualSha = data.sha256 || "";
        await this.refreshAll();
      } catch (_e) {
        this.error = this.$t("object.resolve_failed", "Object resolve failed");
      } finally {
        this.loading = false;
      }
    },
    async loadBySha() {
      const sha = this.manualSha.trim().toLowerCase();
      if (!/^[a-f0-9]{64}$/.test(sha)) {
        this.error = this.$t("object.invalid_sha", "Invalid SHA-256");
        return;
      }
      this.resolved = { sha256: sha, entity: "", rel_path: "", object: null };
      this.$router.replace({ path: "/object", query: { sha256: sha } });
      await this.refreshAll();
    },
    async refreshAll() {
      if (!this.resolved || !this.resolved.sha256) return;
      this.loading = true;
      this.error = "";
      try {
        const qs = new URLSearchParams();
        qs.set("sha256", this.resolved.sha256);
        const [notesRes, proposalsRes, jobsRes, resolveRes] = await Promise.all([
          fetch(`/api/objects/notes?${qs.toString()}`),
          fetch(`/api/objects/proposals?${qs.toString()}`),
          fetch(`/api/objects/jobs?${qs.toString()}`),
          fetch(`/api/objects/resolve?${qs.toString()}`)
        ]);

        if (notesRes.status === 401 || proposalsRes.status === 401 || jobsRes.status === 401 || resolveRes.status === 401) {
          window.dispatchEvent(new CustomEvent("wa-auth-changed", { detail: null }));
          this.$router.push("/login");
          return;
        }

        const notesData = await notesRes.json();
        const propData = await proposalsRes.json();
        const jobsData = await jobsRes.json();
        const resData = await resolveRes.json();
        if (!notesRes.ok) {
          this.error = apiErrorMessage(notesData.error, "notes.load_failed", "Failed to load notes");
          return;
        }
        if (!proposalsRes.ok) {
          this.error = apiErrorMessage(propData.error, "proposals.load_failed", "Failed to load proposals");
          return;
        }
        if (!jobsRes.ok) {
          this.error = apiErrorMessage(jobsData.error, "jobs.load_failed", "Failed to load object jobs");
          return;
        }
        if (resolveRes.ok && resData && resData.sha256) {
          this.resolved = resData;
        }
        this.notes = Array.isArray(notesData.notes) ? notesData.notes : [];
        this.proposals = Array.isArray(propData.items) ? propData.items : [];
        this.objectJobs = Array.isArray(jobsData.items) ? jobsData.items : [];
        this.objectJobCounts = jobsData && typeof jobsData.counts === "object"
          ? { queued: 0, running: 0, done: 0, error: 0, cancelled: 0, ...jobsData.counts }
          : { queued: 0, running: 0, done: 0, error: 0, cancelled: 0 };
        await this.loadMyProposals();
      } catch (_e) {
        this.error = this.$t("object.load_failed", "Failed to load object data");
      } finally {
        this.loading = false;
      }
    },
    async loadMyProposals() {
      try {
        const qs = new URLSearchParams();
        qs.set("status", this.myProposalStatus);
        const res = await fetch(`/api/objects/proposals/mine?${qs.toString()}`);
        if (res.status === 401 || res.status === 403) {
          window.dispatchEvent(new CustomEvent("wa-auth-changed", { detail: null }));
          this.$router.push("/login");
          return;
        }
        const data = await res.json();
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "proposals.load_mine_failed", "Failed to load my proposals");
          return;
        }
        this.myProposals = Array.isArray(data.items) ? data.items : [];
      } catch (_e) {
        this.error = this.$t("proposals.load_mine_failed", "Failed to load my proposals");
      }
    },
    async createNote() {
      if (!this.resolved || !this.resolved.sha256) return;
      if (!this.newNote.trim()) {
        this.error = this.$t("common.note_required", "Note is required");
        return;
      }
      this.loading = true;
      this.error = "";
      try {
        const res = await fetch("/api/objects/notes", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            sha256: this.resolved.sha256,
            note_text: this.newNote
          })
        });
        const data = await res.json();
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "notes.create_failed", "Failed to create note");
          return;
        }
        this.newNote = "";
        await this.refreshAll();
      } catch (_e) {
        this.error = this.$t("notes.create_failed", "Failed to create note");
      } finally {
        this.loading = false;
      }
    },
    startEdit(row) {
      this.editingNoteId = row.id;
      this.editNoteText = row.note_text || "";
    },
    cancelEdit() {
      this.editingNoteId = null;
      this.editNoteText = "";
    },
    async saveEdit(id) {
      if (!this.editNoteText.trim()) {
        this.error = this.$t("common.note_required", "Note is required");
        return;
      }
      this.loading = true;
      this.error = "";
      try {
        const res = await fetch(`/api/objects/notes/${id}`, {
          method: "PUT",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ note_text: this.editNoteText })
        });
        const data = await res.json();
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "notes.update_failed", "Failed to update note");
          return;
        }
        this.cancelEdit();
        await this.refreshAll();
      } catch (_e) {
        this.error = this.$t("notes.update_failed", "Failed to update note");
      } finally {
        this.loading = false;
      }
    },
    async deleteNote(id) {
      if (!window.confirm(this.$t("object.delete_note_confirm", { id }, "Delete note #{id}?"))) return;
      this.loading = true;
      this.error = "";
      try {
        const res = await fetch(`/api/objects/notes/${id}`, { method: "DELETE" });
        const data = await res.json();
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "notes.delete_failed", "Failed to delete note");
          return;
        }
        await this.refreshAll();
      } catch (_e) {
        this.error = this.$t("notes.delete_failed", "Failed to delete note");
      } finally {
        this.loading = false;
      }
    },
    async submitProposal() {
      if (!this.resolved || !this.resolved.sha256) return;
      if (!this.proposalType.trim()) {
        this.error = this.$t("proposal.type_required", "Proposal type is required");
        return;
      }
      const details = (this.proposalDetails || "").trim();
      const payload = details === "" ? null : { details };
      this.loading = true;
      this.error = "";
      try {
        const res = await fetch("/api/objects/proposals", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            sha256: this.resolved.sha256,
            proposal_type: this.proposalType,
            payload
          })
        });
        const data = await res.json();
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "proposals.submit_failed", "Failed to submit proposal");
          return;
        }
        this.proposalType = "retag";
        this.proposalDetails = "";
        await this.refreshAll();
      } catch (_e) {
        this.error = this.$t("proposals.submit_failed", "Failed to submit proposal");
      } finally {
        this.loading = false;
      }
    },
    async cancelProposal(id) {
      if (!window.confirm(this.$t("object.cancel_proposal_confirm", { id }, "Cancel proposal #{id}?"))) {
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
        await this.refreshAll();
      } catch (_e) {
        this.error = this.$t("proposals.cancel_failed", "Failed to cancel proposal");
      } finally {
        this.loading = false;
      }
    },
    cancelDraftNote() {
      this.newNote = "";
      this.cancelEdit();
      this.error = "";
    },
    cancelDraftProposal() {
      this.proposalDetails = "";
      this.error = "";
    },
    closeObjectView() {
      if (this.hasDraftChanges) {
        const ok = window.confirm(this.$t("object.discard_changes_confirm", "Discard unsaved object changes and close?"));
        if (!ok) {
          return;
        }
      }
      if (window.history.length > 1) {
        this.$router.back();
      } else {
        this.$router.push("/");
      }
    }
  }
};
</script>

<style scoped>
.object-page {
  min-height: calc(100vh - 140px);
  overflow: auto;
}
.wide {
  width: 100%;
}
</style>
