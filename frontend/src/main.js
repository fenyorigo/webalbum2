import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import i18nPlugin from "./i18n";

createApp(App).use(router).use(i18nPlugin).mount("#app");
