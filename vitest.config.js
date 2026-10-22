import { defineConfig } from 'vitest/config'

export default defineConfig({
  test: {
    environment: 'happy-dom',
    globals: true,
    include: ['tests/frontend/unit/**/*.test.js'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'html', 'lcov'],
      include: ['resources/js/**/*.js'],
      exclude: ['resources/js/vendor/**', 'node_modules/**']
    }
  },
  resolve: {
    alias: {
      '@': '/resources/js'
    }
  }
})
