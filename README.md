# Laravel Solid Maker

[![Latest Version on Packagist](https://img.shields.io/packagist/v/triquang/laravel-solid-maker.svg?style=flat-square)](https://packagist.org/packages/triquang/laravel-solid-maker)
[![Total Downloads](https://img.shields.io/packagist/dt/triquang/laravel-solid-maker.svg?style=flat-square)](https://packagist.org/packages/triquang/laravel-solid-maker)
[![License](https://img.shields.io/packagist/l/triquang/laravel-solid-maker.svg?style=flat-square)](https://github.com/ntquangkk/laravel-solid-maker?tab=MIT-1-ov-file)

**Laravel Solid Maker** is a developer-friendly package for Laravel that allows you to quickly scaffold SOLID-structured boilerplate code across key architectural layers.

It supports both **standard Laravel** and **modular Laravel** architectures (e.g., using `nwidart/laravel-modules`).

---

## ✨ What It Generates

### Database Layer
- Model
- Migration
- Factory
- Seeder

### Flow Layer
- Controller
- Form Request (Store, Update)
- Resource
- Policy

### Business Logic Layer
- Service
- Repository
- Repository Interface

### Testing Layer
- Unit Test
- Feature Test

### Binding

- **Interface bindings**
  - `*RepositoryServiceProvider`  
    - **Standard Laravel**: Register in `bootstrap/providers.php` (first-time only, if needed).  
    - **Modular Laravel**: Register in `module.json` (first-time only, if needed).

- **Auto-registrations**
  - `*DatabaseSeeder`
  - `*routes/api.php`
  - `*Policy`
  - `*PHPUnit` test suites

---

## 📦 Installation

This package is intended for development only.  
Please install it using the `--dev` flag:

Install via Composer:

```bash
composer require triquang/laravel-solid-maker --dev
```

The service provider will be automatically registered.

(Optional) Publish the stubs if you want to customize the file templates:

```bash
php artisan vendor:publish --tag=solid-stubs
```

This will publish stub files to:

```bash
stubs/vendor/triquang/laravel-solid-maker
```

---

## 🚀 Usage

Generate a full set of SOLID-style files with:

```bash
php artisan make:solid-scaffold --model=YourModelName [--module=YourModuleName] [--view]
```

### Options

| Option     | Required | Description |
|------------|----------|-------------|
| `--model`  | ✅ Yes   | Name of the Eloquent model (e.g., `Post`) |
| `--module` | ❌ No    | Module name (for modular Laravel projects) |
| `--view`   | ❌ No    | Generate `web.php` routes instead of `api.php` |

### Example

```bash
php artisan make:solid-scaffold --model=Post --module=Blog
```

This will generate and register:

- **Database Layer**: Model, Migration, Factory, Seeder  
- **Request Layer**: Store & Update Form Requests  
- **Presentation Layer**: Controller, Resource, Policy  
- **Business Logic**: Service, Repository, Interface  
- **Testing**: Unit Test, Feature Test  
- **Bindings & Registrations**: 
  - `RepositoryServiceProvider`
  - `BlogDatabaseSeeder`
  - `module.json`
  - `routes/api.php`

#### 📁 Folder Structure

Create `Post` SOLID scaffold in `Modules\Blog`

```bash
Modules
└── Blog
    ├── app
    │   ├── Http
    │   │   ├── Controllers
    │   │   │   └── PostController.php
    │   │   ├── Requests
    │   │   │   ├── StorePostRequest.php
    │   │   │   └── UpdatePostRequest.php
    │   │   └── Resources
    │   │       └── PostResource.php
    │   ├── Models
    │   │   └── Post.php
    │   ├── Policies
    │   │   └── PostPolicy.php
    │   ├── Providers
    │   │   └── RepositoryServiceProvider.php          // register or create
    │   ├── Repositories
    │   │   ├── Contracts
    │   │   │   └── PostRepositoryInterface.php
    │   │   └── Eloquent
    │   │       └── PostRepository.php
    │   └── Services
    │       └── PostService.php
    ├── database
    │   ├── factories
    │   │   └── PostFactory.php
    │   ├── migrations
    │   │   └── YYYY_mm_dd_His_create_posts_table.php
    │   └── seeders
    │       ├── BlogDatabaseSeeder.php                  // register or create
    │       └── PostSeeder.php
    ├── module.json                                     // register or create
    ├── routes
    │   └── api.php                                     // register or create
    └── tests
        ├── Feature
        │   └── PostTest.php
        └── Unit
            └── PostServiceTest.php
```

---

## 🛠 Customizing the Stubs

Once published, you can edit stub templates at:

```bash
stubs/vendor/triquang/laravel-solid-maker
```

Your future generated files will reflect those customizations.

---

## 🧭 Auto-Generated Code Markers

This package adds **clear flags** in generated code to help developers easily find and review them.

### Example

```php
// AUTO-GEN: Placeholder
public function getAll()
{
    return $this->repository->getAll();
}
```

### Available Markers

- `// AUTO-GEN-4-SOLID`
- `// AUTO-GEN: Placeholder`
- `AUTO-GEN: Placeholder - Incomplete test.`

You can quickly search these markers (`Ctrl/Cmd+Shift+F`) to locate auto-generated code and **remove them after review**.

---

## ✅ Requirements

- PHP >= 8.0
- Laravel 11 / 12
- Composer

---

## 📄 License

MIT © [Nguyễn Trí Quang](mailto:ntquangkk@gmail.com)

---

## 🙌 Contributing

PRs are welcome! Feel free to improve functionality or report issues via GitHub Issues.

---

## 📬 Contact

- GitHub: [github.com/ntquangkk](https://github.com/ntquangkk)
- Email: [ntquangkk@gmail.com](mailto:ntquangkk@gmail.com)