# ICCommand

Internal web application for Eastern Michigan University that consolidates management of campus digital services into a single platform.

![Symfony](https://img.shields.io/badge/Symfony-8.0-000000?style=flat-square&logo=symfony&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?style=flat-square&logo=php&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3.2-4FC08D?style=flat-square&logo=vue.js&logoColor=white)

---

## Applications

ICCommand is composed of several sub-applications, each serving a different area of university operations. Users see only the applications they have been granted access to.

### Map & Directory

#### Campus Map
Manages all points of interest on the EMU campus, powering the public interactive map at [emich.edu/maps](https://www.emich.edu/maps).

- **10 item types**: Buildings, bathrooms, bus stops, dining, emergency devices (AEDs, blue light phones, fire extinguishers), exhibits, parking lots, services (ATMs, mailboxes, printing), and dispensers
- **Nested relationships**: Bathrooms, dining options, emergency devices, exhibits, services, and dispensers can be placed inside buildings
- **Image management**: Multiple images per item with drag-and-drop reordering
- **Dual coordinate systems**: Illustration map and satellite map coordinates
- **Admissions tour integration**: Flag items as tour stops with position ordering
- **Aliases**: URL-friendly identifiers for direct linking (e.g., emich.edu/maps?poi=student-center)

#### Department Directory
Manages department information for the university-wide directory at [emich.edu/directory](https://www.emich.edu/directory).

- Department contact information (phone, email, website)
- Physical locations linked to map buildings
- Hours of operation
- Organizational grouping by college/division

### Campus Safety

#### Emergency Banner and Notices
Controls the emergency banner that displays above the header across all EMU websites during emergencies, weather closures, or important campus-wide notices.

- Configurable severity levels (critical, warning, info)
- Scheduled activation/deactivation with start and end dates
- Immediate visibility across all emich.edu sites via public API

#### DPS Crime Log
Allows Department of Public Safety staff to upload the Daily Crime Log CSV for public display at [emich.edu/police](https://www.emich.edu/police/crime-alerts-stats/daily-crime-log.php).

- Bulk CSV upload that replaces existing entries
- Automatic Fire Log generation from fire-related crime codes (L5170, 2005, 2009, 2072, 2073, 2099)
- Validation and error reporting for rejected entries

### Photography

#### Photo Requests
Manages photography and headshot requests submitted by the EMU community at [emich.edu/photorequest](https://www.emich.edu/photorequest).

- Request types: photoshoots and headshots
- Workflow tracking: submitted, assigned, completed, declined
- Photographer assignment for users with the photographer role
- Filtering by status and category

### Web Services

#### Redirect Application
Manages 301 redirects and vanity URLs for the entire EMU web presence, including broken link tracking.

- **Two redirect types**: broken links (legacy URL redirects) and shortened links (vanity URLs)
- **Uncaught URL tracking**: Captures 404 errors from across emich.edu and suggests new redirects
- **Bulk CSV import**: Upload multiple redirects at once
- **Usage analytics**: Visit counts and last-visited timestamps
- **Link validation**: Automatic checking of destination URLs
- **Rate limiting**: Bot detection and rate limiting on external API endpoints

#### Degrees & Programs Manager
Manages academic program information displayed at [emich.edu/degrees](https://www.emich.edu/degrees), sourced from the Acalog course catalog system.

- Separate views for undergraduate and graduate programs
- Program metadata: marketing website URLs, delivery modes (online/hybrid/on-campus), search keywords
- Catalog year tracking

#### External Application Links
A curated directory of links to admin panels and front-ends for external applications used across EMU (scholarships, advising tools, etc.). No special permissions required.

### Administration

#### User Management
Manages ICCommand user accounts and role-based access control.

- Per-module role hierarchy: VIEW, CREATE, EDIT, DELETE, ADMIN
- Global admin role with access to all modules
- LDAP authentication when on the EMU network, with local form login fallback for remote development
- Per-application user management interfaces

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Symfony 8.0 / PHP 8.5 |
| Frontend | Vue 3 (Options API) with Webpack Encore |
| Database | MySQL/MariaDB (3 connections: default, programs, dps) |
| ORM | Doctrine with Gedmo extensions (timestamps, slugs, blameable) |
| UI | Bootstrap 4.6, Font Awesome |
| Auth | LDAP / local form login with hierarchical role system |
| Rich Text | CKEditor 5 |
| Images | Liip Imagine Bundle |
| HTTP | Axios with CSRF protection |
| CORS | Nelmio CORS Bundle |
| Containers | Docker Compose (web, db, mailhog) |

---

## Development

### Prerequisites

- Docker & Docker Compose
- Node.js & npm
- Composer

### Setup

```bash
docker compose up -d              # Start services (web :8080, db :3306, mailhog :8027)
composer install                   # Install PHP dependencies
npm install                        # Install JS dependencies
npm run watch                      # Build assets with file watching
```

### Common Commands

```bash
php bin/console cache:clear                        # Clear Symfony cache
php bin/console doctrine:migrations:migrate        # Run database migrations
npm run build                                      # Production asset build
php bin/phpunit                                     # Run all tests
php bin/phpunit tests/path/to/TestFile.php          # Run a single test file
```
