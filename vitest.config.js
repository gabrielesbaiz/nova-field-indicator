import vue from "@vitejs/plugin-vue";
import { fileURLToPath, URL } from "node:url";
import { defineConfig } from "vitest/config";

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            // Only a running Nova app provides this module, and the bundle
            // keeps it external, so tests substitute a stub.
            "laravel-nova-ui": fileURLToPath(
                new URL("./tests/js/stubs/laravel-nova-ui.js", import.meta.url),
            ),
        },
    },
    test: {
        environment: "happy-dom",
        include: ["tests/js/**/*.test.js"],
        globals: true,
    },
});
