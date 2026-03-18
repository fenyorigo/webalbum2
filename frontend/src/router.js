import { createRouter, createWebHistory } from "vue-router";
import SearchPage from "./pages/SearchPage.vue";
import TagsPage from "./pages/TagsPage.vue";
import FavoritesPage from "./pages/FavoritesPage.vue";
import SavedSearchesPage from "./pages/SavedSearchesPage.vue";
import LoginPage from "./pages/LoginPage.vue";
import SetupPage from "./pages/SetupPage.vue";
import ProfilePage from "./pages/ProfilePage.vue";
import TypedTagsPage from "./pages/TypedTagsPage.vue";
import HelpPage from "./pages/HelpPage.vue";
import MyProposalsPage from "./pages/MyProposalsPage.vue";
import MyNotesPage from "./pages/MyNotesPage.vue";
import TrashPage from "./pages/TrashPage.vue";
import AssetsPage from "./pages/AssetsPage.vue";
import ObjectPage from "./pages/ObjectPage.vue";
import ObjectProposalsAdminPage from "./pages/ObjectProposalsAdminPage.vue";
import LocalizationAdminPage from "./pages/LocalizationAdminPage.vue";
import TagTreeAdminPage from "./pages/TagTreeAdminPage.vue";
import TagCleanupAdminPage from "./pages/TagCleanupAdminPage.vue";

const routes = [
  { path: "/login", component: LoginPage },
  { path: "/setup", component: SetupPage },
  { path: "/", component: SearchPage },
  { path: "/help", component: HelpPage },
  { path: "/tags", component: TagsPage },
  { path: "/typed-tags", component: TypedTagsPage },
  { path: "/favorites", component: FavoritesPage },
  { path: "/saved-searches", component: SavedSearchesPage },
  { path: "/profile", component: ProfilePage },
  { path: "/my-proposals", component: MyProposalsPage },
  { path: "/my-notes", component: MyNotesPage },
  { path: "/trash", component: TrashPage },
  { path: "/assets", component: AssetsPage },
  { path: "/object", component: ObjectPage },
  { path: "/admin/object-proposals", component: ObjectProposalsAdminPage },
  { path: "/admin/localization", component: LocalizationAdminPage },
  { path: "/admin/tag-tree", component: TagTreeAdminPage },
  { path: "/admin/tag-cleanup", component: TagCleanupAdminPage }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

async function fetchSetupStatus() {
  try {
    const res = await fetch("/api/setup/status");
    if (!res.ok) {
      return { setup_required: false };
    }
    return await res.json();
  } catch (err) {
    return { setup_required: false };
  }
}

async function fetchMe() {
  try {
    const res = await fetch("/api/auth/me");
    if (!res.ok) {
      return null;
    }
    const data = await res.json();
    return data.user || null;
  } catch (err) {
    return null;
  }
}

router.beforeEach(async (to) => {
  if (to.path === "/setup") {
    const status = await fetchSetupStatus();
    if (!status.setup_required) {
      return "/login";
    }
    return true;
  }
  if (to.path === "/login") {
    return true;
  }
  const user = await fetchMe();
  if (!user) {
    const status = await fetchSetupStatus();
    if (status.setup_required) {
      return "/login";
    }
    return "/login";
  }
  if ((to.path === "/trash" || to.path === "/assets" || to.path === "/admin/object-proposals" || to.path === "/admin/localization" || to.path === "/admin/tag-tree" || to.path === "/admin/tag-cleanup") && !user.is_admin) {
    return "/";
  }
  return true;
});

export default router;
