<template>
  <div class="page">
    <header class="hero">
      <h1 v-if="!setupRequired">{{ $t("login.title", "Login") }}</h1>
      <h1 v-else>{{ $t("login.setup_title", "Create admin user") }}</h1>
      <p v-if="!setupRequired">{{ $t("login.subtitle", "Sign in to access Family memories.") }}</p>
      <p v-else>{{ $t("login.setup_subtitle", "Set up the initial admin account.") }}</p>
    </header>

    <section class="panel login-panel" v-if="!setupRequired">
      <label>
        {{ $t("login.username", "Username") }}
        <input v-model.trim="username" type="text" autocomplete="username" />
      </label>
      <label>
        {{ $t("login.password", "Password") }}
        <input v-model="password" type="password" autocomplete="current-password" />
      </label>
      <button @click="submit" :disabled="loading">{{ $t("login.button", "Login") }}</button>
      <p v-if="error" class="error">{{ error }}</p>
    </section>

    <div v-else class="modal-backdrop">
      <div class="modal">
        <h3>{{ $t("login.setup_title", "Create admin user") }}</h3>
        <label>
          {{ $t("login.setup_username", "Admin username") }}
          <input v-model.trim="setup.username" type="text" autocomplete="username" />
        </label>
        <label>
          {{ $t("login.password", "Password") }}
          <input v-model="setup.password" type="password" autocomplete="new-password" />
        </label>
        <label>
          {{ $t("profile.confirm_password", "Confirm password") }}
          <input v-model="setup.confirm" type="password" autocomplete="new-password" />
        </label>
        <div class="modal-actions">
          <button class="inline" @click="submitSetup" :disabled="loading">{{ $t("login.setup_button", "Create admin") }}</button>
        </div>
        <p v-if="setupError" class="error">{{ setupError }}</p>
      </div>
    </div>

  </div>
</template>

<script>
import { applyI18nBundle } from "../i18n";
import { apiErrorMessage } from "../api-errors";

export default {
  name: "LoginPage",
  data() {
    return {
      username: "",
      password: "",
      loading: false,
      error: "",
      setupRequired: false,
      setup: {
        username: "",
        password: "",
        confirm: ""
      },
      setupError: "",
      forceChange: false,
      force: {
        newPassword: "",
        confirm: ""
      },
      forceError: "",
      lastLoginPassword: ""
    };
  },
  mounted() {
    this.checkSetup();
    this.loadI18n();
  },
  methods: {
    async loadI18n() {
      try {
        const res = await fetch("/api/i18n");
        if (!res.ok) {
          return;
        }
        const data = await res.json();
        applyI18nBundle(data);
      } catch (_err) {
        // ignore
      }
    },
    async checkSetup() {
      try {
        const res = await fetch("/api/setup/status");
        if (!res.ok) {
          return;
        }
        const data = await res.json();
        this.setupRequired = !!data.setup_required;
      } catch (err) {
        // ignore
      }
    },
    async submit() {
      this.error = "";
      if (!this.username || !this.password) {
        this.error = "Username and password are required";
        return;
      }
      this.loading = true;
      try {
        const res = await fetch("/api/auth/login", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ username: this.username, password: this.password })
        });
        const data = await res.json();
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "login.failed", "Login failed");
          return;
        }
        const user = data.user || null;
        if (user) {
          window.dispatchEvent(new CustomEvent("wa-auth-changed", { detail: user }));
        }
        this.$router.push("/");
      } catch (err) {
        this.error = "Login failed";
      } finally {
        this.loading = false;
      }
    },
    async submitSetup() {
      this.setupError = "";
      if (!this.setup.username) {
        this.setupError = "Username is required";
        return;
      }
      const error = this.validateStrongPassword(this.setup.password);
      if (error) {
        this.setupError = error;
        return;
      }
      if (this.setup.password !== this.setup.confirm) {
        this.setupError = "Passwords do not match";
        return;
      }
      this.loading = true;
      try {
        const res = await fetch("/api/setup", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ username: this.setup.username, password: this.setup.password })
        });
        const data = await res.json();
        if (!res.ok) {
          this.setupError = apiErrorMessage(data.error, "setup.failed", "Setup failed");
          return;
        }
        this.setupRequired = false;
        this.$router.push("/login");
      } catch (err) {
        this.setupError = "Setup failed";
      } finally {
        this.loading = false;
      }
    },
    validateStrongPassword(password) {
      if (!password || password.length < 12) {
        return "Password must be at least 12 characters";
      }
      if (!/[a-z]/.test(password)) {
        return "Password must include a lowercase letter";
      }
      if (!/[A-Z]/.test(password)) {
        return "Password must include an uppercase letter";
      }
      if (!/[0-9]/.test(password)) {
        return "Password must include a number";
      }
      if (!/[^A-Za-z0-9]/.test(password)) {
        return "Password must include a special character";
      }
      return "";
    }
  }
};
</script>
