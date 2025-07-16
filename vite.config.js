import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
  build: {
    outDir: 'resources/dist',
    emptyOutDir: true,

    // Build optimizations
    sourcemap: false,
    cssCodeSplit: false,
    assetsInlineLimit: 0, // No base64 inlining for assets
    cssMinify: true,

    rollupOptions: {
      input: {
        app: path.resolve(__dirname, 'resources/js/app.js'),
      },
      output: {
        format: 'iife', // Immediately Invoked Function Expression (classic style)
        entryFileNames: 'app.js',

        assetFileNames: ({ name }) => {
          if (!name) return '[name][extname]';

          if (name.endsWith('.css')) {
            return 'style.css'; // Bundle all CSS into a single file
          }

          return 'assets/[name][extname]'; // Put everything else in assets/
        },

        manualChunks: undefined, // Prevent Vite/Rollup from code splitting
      },
    },
  },

  resolve: {
    alias: {
      '~bootstrap': path.resolve(__dirname, 'node_modules/bootstrap'),
      '@': path.resolve(__dirname, 'resources/js'), // Optional: cleaner imports
    },
  },
});
