# BramhaLock Web Portal API

PHP-based API backend for the BramhaLock secure screen lock application.

## Features

- Device management and authentication
- Remote command execution
- Event logging and monitoring
- Profile synchronization
- Admin dashboard

## Deployment

### Render Deployment

1. Fork this repository to your GitHub account
2. Connect your GitHub repository to Render
3. Render will automatically detect the `render.yaml` configuration
4. PostgreSQL database will be provisioned automatically
5. Environment variables will be set from the database connection

### Local Development

1. Install PHP 8.1+
2. Copy `.env.example` to `.env` and configure database
3. Run: `php -S localhost:8000`

## API Endpoints

- `POST /api/sync-profile.php` - Sync device profile
- `POST /api/get-commands.php` - Poll for remote commands
- `POST /api/log-event.php` - Log device events

## Database

Uses PostgreSQL on Render with automatic migrations via `migrate-to-postgresql.sql`.

## CORS

Configured for cross-origin requests from the BramhaLock desktop application.
