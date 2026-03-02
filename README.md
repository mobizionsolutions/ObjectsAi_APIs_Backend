# Objects AI Versioned APIs

A Laravel-based backend providing versioned REST APIs for managing AI content, models, and related metadata. This project exposes comprehensive API endpoints that organize and serve AI art and object detection content across multiple platforms (iOS, Android).

## About This Project

**Objects AI Versioned APIs** is a production-ready backend service built with Laravel that manages:
- **AI Art Contents:** Curated AI-generated art and creative assets.
- **Object Detection Models:** Pre-trained models for detecting objects in images.
- **Versioned Endpoints:** API versioning support for backward compatibility and controlled evolution.
- **Multi-Platform Support:** Dedicated content stores for iOS and Android clients.

The project provides a clean, RESTful API interface with built-in testing, version management, and comprehensive documentation via Postman.

## Key Features

- ✅ **Versioned RESTful APIs** – Multiple API versions with backward compatibility
- ✅ **Content Management** – Organize and serve AI art and object detection models
- ✅ **GitHub Integration** – `GitHubService` for version control workflows
- ✅ **API Documentation** – Postman collection and Markdown docs included
- ✅ **Testing Ready** – PHPUnit/Pest configured for unit and feature tests
- ✅ **Modern Tooling** – Vite for frontend assets, Composer for dependencies

## Tech Stack

- **Framework:** Laravel (PHP web framework)
- **Language:** PHP 8.x
- **Database:** MySQL/SQLite (configurable)
- **Build Tools:** Vite (frontend assets), Composer (PHP dependencies)
- **Testing:** PHPUnit/Pest
- **API Documentation:** Postman Collection, Markdown Docs
- **Authentication:** Laravel Sanctum (for API token-based auth)

## Quick Start

### Prerequisites

- PHP 8.0 or higher
- Composer
- Node.js 16+ (for Vite)
- MySQL or SQLite

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/YOUR_USER/ObjectAi_Versioned_APIs.git
   cd ObjectAi_Versioned_APIs
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Set up environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database** (edit `.env` with your database credentials)
   ```bash
   php artisan migrate
   ```

5. **Install Node dependencies** (optional, for frontend assets)
   ```bash
   npm install
   npm run dev
   ```

6. **Start the development server**
   ```bash
   php artisan serve
   ```

Server runs at `http://127.0.0.1:8000`

## API Documentation

- **Postman Collection:** `ObjectAi_Versioned_APIs.postman_collection.json` – Import into Postman to explore all endpoints
- **Detailed Docs:** See [VERSIONED_API_DOCS.md](VERSIONED_API_DOCS.md) for endpoint descriptions and examples

### Example Endpoints

- `GET /api/v1/models` – Retrieve available object detection models
- `GET /api/v1/ai-art-contents` – List AI art contents
- `GET /api/v1/ios-contents` – Get iOS-specific content
- `GET /api/v1/android-contents` – Get Android-specific content

(See Postman collection and API docs for full endpoint list and request/response examples)

## Project Structure

```
.
├── app/
│   ├── Http/
│   │   └── Controllers/       # API controllers
│   ├── Models/                # Eloquent models (User, etc.)
│   └── Providers/
│       ├── AppServiceProvider.php
│       └── GitHubService.php  # GitHub integration service
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── database.php
│   └── ...                    # Laravel config files
├── database/
│   ├── migrations/            # Database schema migrations
│   ├── factories/
│   └── seeders/
├── routes/
│   ├── api.php                # API routes (versioned)
│   ├── web.php
│   └── console.php
├── storage/
│   ├── app/
│   │   ├── ai_art_contents.json
│   │   ├── models.json
│   │   ├── object_ai_contents_android.json
│   │   ├── object_ai_contents_ios.json
│   │   └── versioned/         # Versioned content storage
│   └── ...
├── tests/
│   ├── Feature/               # Feature tests
│   ├── Unit/                  # Unit tests
│   └── Pest.php
├── composer.json              # PHP dependencies
├── package.json               # Node dependencies
└── README.md                  # This file
```

## Testing

Run tests using PHPUnit or Pest:

```bash
php artisan test
```

Or with coverage:

```bash
php artisan test --coverage
```

## Development

### Database Migrations

Create a new migration:
```bash
php artisan make:migration create_table_name
```

Run migrations:
```bash
php artisan migrate
```

### Creating Controllers

```bash
php artisan make:controller Api/YourController
```

### Running with File Watcher

Watch for changes and compile assets:
```bash
npm run watch
```

## Environment Configuration

Key environment variables (in `.env`):

```
APP_NAME="Objects AI APIs"
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=objects_ai
DB_USERNAME=root
DB_PASSWORD=
```

Refer to `.env.example` for all available options.

## Deployment

### Build for Production

```bash
composer install --optimize-autoloader --no-dev
npm run build
php artisan config:cache
php artisan route:cache
```

### Recommended Hosting

- Laravel Forge, DigitalOcean App Platform, Heroku, or any PHP 8.0+ hosting

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Commit your changes: `git commit -m "Add your feature"`
4. Push to the branch: `git push origin feature/your-feature`
5. Open a Pull Request

## Security

If you discover a security vulnerability, please email `security@example.com` instead of using the issue tracker.

## License

This project is open-source software licensed under the [MIT license](LICENSE).

## Support

For questions or issues:
- Check the [Postman collection](ObjectAi_Versioned_APIs.postman_collection.json) for API examples
- Review [VERSIONED_API_DOCS.md](VERSIONED_API_DOCS.md) for detailed endpoint documentation
- Open an issue on GitHub

---

**Built with ❤️ using Laravel**
