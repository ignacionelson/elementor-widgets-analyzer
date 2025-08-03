# Build Instructions

This document explains how to build and manage dependencies for the Widgets Analyzer for Elementor plugin.

## Prerequisites

- Node.js (version 14 or higher)
- npm (comes with Node.js)

## Dependencies

The plugin uses the following JavaScript dependencies:

- **Chart.js**: For creating interactive charts and graphs

## Build Process

### Initial Setup

1. **Install Dependencies**
   ```bash
   npm install
   ```

2. **Copy Chart.js to Assets**
   ```bash
   npm run update-chartjs
   ```

### Development Workflow

1. **Update Dependencies**
   ```bash
   npm update
   npm run update-chartjs
   ```

2. **Full Build**
   ```bash
   npm run build
   ```

### Available Scripts

- `npm run install-deps`: Install all dependencies
- `npm run update-chartjs`: Copy the latest Chart.js from node_modules to assets
- `npm run build`: Install dependencies and update Chart.js

## File Structure

```
widgets-analyzer-for-elementor/
├── assets/
│   ├── js/
│   │   ├── admin.js          # Main admin JavaScript
│   │   └── chart.js          # Local copy of Chart.js
│   └── css/
│       └── admin.css         # Admin styles
├── includes/                  # PHP classes
├── node_modules/             # npm dependencies (gitignored)
├── package.json              # npm configuration
├── .gitignore               # Git ignore rules
└── BUILD.md                 # This file
```

## WordPress Compliance

- All external libraries are loaded locally (no CDN dependencies)
- Chart.js is bundled with the plugin
- No external network requests for JavaScript libraries

## Distribution

When distributing the plugin:

1. Run `npm run build` to ensure all dependencies are up to date
2. The `node_modules/` directory should not be included in the distribution
3. The `assets/js/chart.js` file should be included in the distribution

## Troubleshooting

### Chart.js Not Loading

1. Check that `assets/js/chart.js` exists
2. Run `npm run update-chartjs` to copy the latest version
3. Clear browser cache and WordPress cache

### Build Errors

1. Ensure Node.js and npm are installed
2. Delete `node_modules/` and run `npm install`
3. Check that all file paths are correct 