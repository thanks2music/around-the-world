# Around the world

Database updater and DOM monitoring tool for automated collection of target sites.

## Overview

This tool retrieves product information from target sites I manage and automatically updates the database of an automation system that powers website content.

It also monitors changes in the DOM structure of the target sites.

### Components

- **automatic-updater**: A WP-CLI command-line tool that updates serialized and stored database values
- **dom-monitor**: A system for monitoring DOM changes on target sites

## Features

### automatic-updater
- **WordPress Database Automation**: Bulk update WordPress post data via WP-CLI commands
- **XPath & Selector Management**: Handle changes in web structure with automated updates
- **Safe Execution**: Dry-run mode for risk-free testing before actual database changes
- **Staged Updates**: Step-by-step update process with detailed logging and rollback capabilities
- **Serialized Data Handling**: Work with complex WordPress serialized database structures

### dom-monitor
- **DOM Structure Monitoring**: Continuous monitoring of target website structure changes
- **Real-time Notifications**: Slack integration for immediate change alerts
- **Cloud-Native**: Designed for Google Cloud Run + Cloud Scheduler deployment
- **Development Environment**: Built-in mock pages for testing and development
- **Playwright Integration**: Robust browser automation with error handling

## Architecture

```
├── automatic-updater/          # WordPress Plugin
│   ├── commands/               # WP-CLI Commands
│   │   ├── class-update-xpath-command.php
│   │   ├── class-show-record-command.php
│   │   └── class-update-single-command.php
│   ├── includes/               # Core Classes
│   │   ├── class-base-command.php
│   │   ├── class-data-processor.php
│   │   └── class-xpath-updater.php
│   └── wp-automatic-updater.php
├── dom-monitor/                # DOM Monitoring System
│   ├── src/                    # Application Source
│   │   ├── services/           # Core Services
│   │   └── utils/              # Utilities
│   ├── mockups/                # Test Environment
│   └── cloudbuild.yaml         # Cloud Build Configuration
└── README.md
```

## Prerequisites

### automatic-updater
- WordPress 6.8.2+
- WP-CLI
- PHP 8.4+
- MySQL 8.0.35+

### dom-monitor
- Node.js 18+
- Google Cloud Platform account (for production deployment)

## Configuration

### Environment Variables

Both components use `.env` files for configuration. Sample files are provided:

- `automatic-updater/.env.sample` - WordPress and CLI settings
- `dom-monitor/.env.sample` - Monitoring targets and notification settings

#### Key Configuration Options

```bash
# automatic-updater
WP_CLI_PATH=
WP_LOCAL_PATH=

# dom-monitor
SLACK_WEBHOOK_URL=
MONITORING_TARGET_URL=
NAVIGATION_TIMEOUT=
ELEMENT_TIMEOUT=
```

## Usage

### Basic Commands

```bash
# View help for all commands
wp wp-auto --help

# Display specific record details
wp wp-auto show <record_id>

# Basic XPath bulk update
wp wp-auto update-xpath [--dry-run] [--verbose] [--force]

# Single record update
wp wp-auto update-single <record_id> [--type=default] [--dry-run]
```

### Safety Features

Always use `--dry-run` for testing:

```bash
# Test before executing
wp wp-auto update-xpath --dry-run --verbose

# Execute after verification
wp wp-auto update-xpath --force
```

### Monitoring

```bash
# Start DOM monitoring
cd dom-monitor && pnpm start

# Development mode with verbose logging
pnpm run dev
```

## Cloud Deployment

### Google Cloud Platform

```bash
# Deploy to Cloud Run
gcloud builds submit --config dom-monitor/cloudbuild.yaml

# Setup scheduled monitoring
gcloud scheduler jobs create http dom-monitor-job \
  --schedule="0 */6 * * *" \
  --uri="https://your-cloud-run-url"
```

Note: The source code in this repository was developed for the websites I personally operate.
The repository is named **“Around the world”** 🤖 to reflect the idea of *“endless optimization.”*