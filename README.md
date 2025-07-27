# Laravel Solid Maker

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
- Auto-binds interfaces to implementations in:
  - `RepositoryServiceProvider`
  - `bootstrap/app.php` (for modular Laravel)

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
php artisan make:solid-files --model=YourModelName [--module=YourModuleName] [--view]
```

### Options

| Option     | Required | Description |
|------------|----------|-------------|
| `--model`  | ✅ Yes   | Name of the Eloquent model (e.g., `Post`) |
| `--module` | ❌ No    | Module name (for modular Laravel projects) |
| `--view`   | ❌ No    | Generate `web.php` routes instead of `api.php` |

### Example

```bash
php artisan make:solid-files --model=Post --module=Blog
```

This will generate and register:

- **Database Layer**: Model, Migration, Factory, Seeder  
- **Request Layer**: Store & Update Form Requests  
- **Presentation Layer**: Controller, Resource, Policy, Routes  
- **Business Logic**: Service, Repository, Interface  
- **Testing**: Unit Test, Feature Test  
- **Bindings**: Registered in `RepositoryServiceProvider`

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

You can quickly search these markers (`Ctrl+F`) to locate auto-generated code and **remove them after review**.

---

## 📄 License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).