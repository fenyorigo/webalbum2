import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import pkg from "./package.json" with { type: "json" };

export default defineConfig({
  plugins: [vue()],
  define: {
    __APP_VERSION__: JSON.stringify(pkg.version)
  },
  base: "/dist/",
  build: {
    outDir: "../backend/public/dist",
    emptyOutDir: true
  },
  server: {
    proxy: {
      "/api": {
        target: "https://localhost:8445",
        changeOrigin: true,
        secure: false
      }
    }
  }
});
