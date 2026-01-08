# Velocix CLI Documentation

Complete guide to using the Velocix command-line interface (CLI) tool.

[![Packagist](https://img.shields.io/packagist/v/trioagus/velocix-framework.svg)](https://packagist.org/packages/trioagus/velocix-framework)

## Table of Contents

- [Introduction](#introduction)
- [Basic Usage](#basic-usage)
- [Available Commands](#available-commands)
  - [Server Commands](#server-commands)
  - [Make Commands](#make-commands)
  - [Database Commands](#database-commands)
  - [Cache Commands](#cache-commands)
  - [Optimization Commands](#optimization-commands)
  - [Storage Commands](#storage-commands)
  - [Authentication Commands](#authentication-commands)
- [Command Reference](#command-reference)

---

## Introduction

The Velocix CLI (`velocix`) is a powerful command-line tool that helps you manage your Velocix application. It provides commands for generating code, running migrations, managing cache, and much more.

## Basic Usage

All Velocix commands follow this syntax:

```bash
php velocix <command> [arguments] [options]
```

To see all available commands:

```bash
php velocix help
```

---

## Available Commands

### Server Commands

#### `serve`

Start the built-in PHP development server.

**Syntax:**
```bash
php velocix serve [host] [port]
```

**Arguments:**
- `host` - Server host (default: `localhost`)
- `port` - Server port (default: `8000`)

**Examples:**
```bash
# Start on default (localhost:8000)
php velocix serve

# Custom host and port
php velocix serve 127.0.0.1 3000

# Run on all interfaces
php velocix serve 0.0.0.0 8000
```

**Output:**
```
⚡ Velocix development server started
   Server running at: http://localhost:8000
   Press Ctrl+C to stop
```

---

### Make Commands

#### `make:controller`

Create a new controller class.

**Syntax:**
```bash
php velocix make:controller <ControllerName>
```

**Arguments:**
- `ControllerName` - Name of the controller (e.g., `UserController`)

**Examples:**
```bash
# Basic controller
php velocix make:controller UserController

# Namespaced controller
php velocix make:controller Api/UserController
php velocix make:controller Admin/DashboardController
```

**Generated File:**
```
app/Http/Controllers/UserController.php
app/Http/Controllers/Api/UserController.php
```

**Generated Code:**
```php
<?php

namespace App\Http\Controllers;

use Velocix\Http\Controller;
use Velocix\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Implementation
    }

    public function show(Request $request, $id)
    {
        // Implementation
    }

    // ... other methods
}
```

---

#### `make:model`

Create a new model class with optional migration.

**Syntax:**
```bash
php velocix make:model <ModelName> [options]
```

**Arguments:**
- `ModelName` - Name of the model (e.g., `User`, `Post`)

**Options:**
- `-m` - Create migration file along with model
- `--uuid` - Use UUID as primary key
- `--ulid` - Use ULID as primary key

**Examples:**
```bash
# Basic model
php velocix make:model User

# Model with migration
php velocix make:model Post -m

# Model with UUID
php velocix make:model Product --uuid -m

# Model with ULID
php velocix make:model Order --ulid -m
```

**Generated Files:**
```
app/Models/User.php
database/migrations/2026_01_08_120000_create_users_table.php
```

---

#### `make:migration`

Create a new database migration file.

**Syntax:**
```bash
php velocix make:migration <migration_name> [options]
```

**Arguments:**
- `migration_name` - Name of the migration (snake_case)

**Options:**
- `--schema="field:type:modifier"` - Define table schema

**Examples:**
```bash
# Create table migration
php velocix make:migration create_posts_table

# Add column migration
php velocix make:migration add_status_to_users

# Drop table migration
php velocix make:migration drop_old_posts_table

# With schema definition
php velocix make:migration create_products_table --schema="name:string,price:decimal:nullable,stock:integer:unsigned"

# Complex schema
php velocix make:migration create_users_table --schema="name:string:length=100,email:string:unique,status:enum:values=pending|active|inactive,age:integer:nullable"
```

**Schema Modifiers:**
- `nullable` - Allow NULL values
- `unique` - Add unique constraint
- `unsigned` - For numeric fields (positive only)
- `index` - Add index
- `default=value` - Set default value
- `length=n` - For string length
- `values=a|b|c` - For enum values (separated by |)

**Generated File:**
```
database/migrations/2026_01_08_120000_create_posts_table.php
```

### Advanced Migration Examples

#### 1. Basic Table with Various Types

```bash
php velocix make:migration create_users_table --schema="name:string,email:string:unique,age:integer:nullable"
```

**Generated:**
```php
$this->createTable('users', function($table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->integer('age')->nullable();
    $table->timestamps();
});
```

#### 2. String with Custom Length

```bash
php velocix make:migration create_posts_table --schema="title:string:length=255,slug:string:length=100:unique"
```

**Generated:**
```php
$table->string('title', 255);
$table->string('slug', 100)->unique();
```

#### 3. Enum Type

```bash
php velocix make:migration create_orders_table --schema="status:enum:values=pending|processing|completed|cancelled"
```

**Generated:**
```php
$table->enum('status', ['pending', 'processing', 'completed', 'cancelled']);
```

#### 4. Text and JSON

```bash
php velocix make:migration create_products_table --schema="name:string,description:text:nullable,specifications:json:nullable"
```

**Generated:**
```php
$table->string('name');
$table->text('description')->nullable();
$table->json('specifications')->nullable();
```

#### 5. Integer with Modifiers

```bash
php velocix make:migration create_articles_table --schema="views:integer:unsigned:default=0,likes:integer:unsigned:default=0:index"
```

**Generated:**
```php
$table->integer('views')->unsigned()->default(0);
$table->integer('likes')->unsigned()->default(0)->index();
```

#### 6. Decimal/Money Fields

```bash
php velocix make:migration create_products_table --schema="price:decimal:default=0,discount:decimal:nullable"
```

**Generated:**
```php
$table->decimal('price')->default(0);
$table->decimal('discount')->nullable();
```

#### 7. Complete E-commerce Product Table

```bash
php velocix make:migration create_products_table --schema="name:string:length=255,description:text:nullable,price:decimal:default=0,stock:integer:unsigned:default=0,status:enum:values=active|inactive|draft,metadata:json:nullable,slug:string:unique"
```

**Generated:**
```php
$table->id();
$table->string('name', 255);
$table->text('description')->nullable();
$table->decimal('price')->default(0);
$table->integer('stock')->unsigned()->default(0);
$table->enum('status', ['active', 'inactive', 'draft']);
$table->json('metadata')->nullable();
$table->string('slug')->unique();
$table->timestamps();
```

#### 8. Foreign Keys and Relations

**Create related tables:**

```bash
# Users table
php velocix make:migration create_users_table --schema="name:string,email:string:unique,password:string"

# Posts table with foreign key
php velocix make:migration create_posts_table --schema="user_id:integer:unsigned:index,title:string,content:text,published_at:timestamp:nullable"

# Comments table with foreign keys
php velocix make:migration create_comments_table --schema="post_id:integer:unsigned:index,user_id:integer:unsigned:index,content:text,approved:boolean:default=false"
```

**Add foreign keys manually in migration:**
```php
// In create_posts_table migration
$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

// In create_comments_table migration
$table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
```

#### 9. Pivot/Junction Table (Many-to-Many)

```bash
php velocix make:migration create_post_tag_table --schema="post_id:integer:unsigned:index,tag_id:integer:unsigned:index"
```

**Add composite unique and foreign keys:**
```php
$table->id();
$table->integer('post_id')->unsigned()->index();
$table->integer('tag_id')->unsigned()->index();
$table->timestamps();

// Composite unique constraint
$table->unique(['post_id', 'tag_id']);

// Foreign keys
$table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
$table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
```

#### 10. Soft Deletes

```bash
php velocix make:migration create_users_table --schema="name:string,email:string:unique,deleted_at:timestamp:nullable"
```

Or add manually:
```php
$table->softDeletes(); // Adds deleted_at column
```

---

#### `make:middleware`

Create a new middleware class.

**Syntax:**
```bash
php velocix make:middleware <MiddlewareName>
```

**Examples:**
```bash
php velocix make:middleware CheckRole
php velocix make:middleware RateLimiter
php velocix make:middleware ApiAuthentication
```

**Generated File:**
```
app/Http/Middleware/CheckRole.php
```

**Generated Code:**
```php
<?php

namespace App\Http\Middleware;

use Velocix\Http\Middleware;
use Velocix\Http\Request;

class CheckRole extends Middleware
{
    public function handle(Request $request, $next)
    {
        // Middleware logic here
        
        return $next($request);
    }
}
```

---

#### `make:seeder`

Create a new database seeder class.

**Syntax:**
```bash
php velocix make:seeder <SeederName>
```

**Arguments:**
- `SeederName` - Name of the seeder (will auto-append "Seeder" if not present)

**Examples:**
```bash
php velocix make:seeder UserSeeder
php velocix make:seeder ProductSeeder
php velocix make:seeder DatabaseSeeder
```

**Generated File:**
```
database/seeders/UserSeeder.php
```

**Generated Code:**
```php
<?php

namespace Database\Seeders;

use Velocix\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Seeding logic here
        echo "Seeded: UserSeeder\n";
    }
}
```

---

#### `make:request`

Create a new form request validation class.

**Syntax:**
```bash
php velocix make:request <RequestName>
```

**Examples:**
```bash
php velocix make:request CreateUserRequest
php velocix make:request UpdatePostRequest
php velocix make:request LoginRequest
```

**Generated File:**
```
app/Http/Requests/CreateUserRequest.php
```

**Generated Code:**
```php
<?php

namespace App\Http\Requests;

use Velocix\Validation\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function rules()
    {
        return [
            // 'field' => 'required|email',
        ];
    }

    public function messages()
    {
        return [
            // 'field.required' => 'Custom error message',
        ];
    }

    public function authorize()
    {
        return true;
    }
}
```

---

#### `make:service`

Create a new service class with optional repository pattern.

**Syntax:**
```bash
php velocix make:service <ServiceName> [options]
```

**Options:**
- `--model=ModelName` - Link service to a specific model
- `--repository` - Generate repository interface and implementation

**Examples:**
```bash
# Basic service
php velocix make:service UserService

# Service with model
php velocix make:service UserService --model=User

# Service with repository pattern
php velocix make:service UserService --model=User --repository
```

**Generated Files:**
```
app/Services/UserService.php
app/Repositories/UserRepository.php
app/Repositories/Contracts/UserRepositoryInterface.php
```

**Usage in Controller:**
```php
<?php

namespace App\Http\Controllers;

use App\Services\UserService;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        $users = $this->userService->getAll(15);
        return $this->json($users);
    }
}
```

---

#### `make:auth`

Scaffold complete authentication system (controllers, views, migrations, middleware).

**Syntax:**
```bash
php velocix make:auth
```

**What it creates:**
- `app/Models/User.php` - User model
- `app/Http/Controllers/Auth/LoginController.php` - Login controller
- `app/Http/Controllers/Auth/RegisterController.php` - Register controller
- `database/migrations/*_create_users_table.php` - Users migration
- `resources/views/auth/login.vlx.php` - Login view
- `resources/views/auth/register.vlx.php` - Register view
- `routes/auth.php` - Authentication routes
- `app/Http/Middleware/AuthMiddleware.php` - Auth middleware
- `app/Http/Middleware/GuestMiddleware.php` - Guest middleware

**Example:**
```bash
php velocix make:auth
```

**Output:**
```
Scaffolding authentication...

✓ Created User model
✓ Created LoginController
✓ Created RegisterController
✓ Created users migration
✓ Created login view
✓ Created register view
✓ Created auth routes
✓ Created AuthMiddleware
✓ Created GuestMiddleware

✓ Authentication scaffolding completed!

Next steps:
  1. Run: php velocix migrate
  2. Include routes/auth.php in routes/web.php
  3. Visit /login to test
```

---

### Database Commands

#### `migrate`

Run all pending database migrations.

**Syntax:**
```bash
php velocix migrate
```

**Example:**
```bash
php velocix migrate
```

**Output:**
```
Running migrations...
Migrating: 2026_01_08_000000_create_users_table
Migrated:  2026_01_08_000000_create_users_table
Migrating: 2026_01_08_000001_create_posts_table
Migrated:  2026_01_08_000001_create_posts_table

Migrated 2 migration(s) successfully!
```

---

#### `migrate:rollback`

Rollback the last batch of migrations.

**Syntax:**
```bash
php velocix migrate:rollback
```

**Example:**
```bash
php velocix migrate:rollback
```

**Output:**
```
Rolling back migrations...
✓ Rolled back: 2026_01_08_000001_create_posts_table
✓ Rolled back: 2026_01_08_000000_create_users_table

Rollback completed! (2 migrations)
```

---

#### `migrate:fresh`

Drop all tables and re-run all migrations.

**Syntax:**
```bash
php velocix migrate:fresh [options]
```

**Options:**
- `--seed` - Run database seeders after migration

**Example:**
```bash
# Fresh migration
php velocix migrate:fresh

# Fresh migration with seeding
php velocix migrate:fresh --seed
```

**Output:**
```
This will drop all tables. Are you sure? (yes/no): yes
Dropping all tables...
  Database file deleted
  New database file created

Running migrations...
  Migrating: 2026_01_08_000000_create_users_table.php
  Migrated: 2026_01_08_000000_create_users_table.php

Seeding database...

✓ Migration completed successfully!
```

---

#### `db:seed`

Seed the database with sample data.

**Syntax:**
```bash
php velocix db:seed [options]
```

**Options:**
- `--class=SeederClass` - Run specific seeder class
- `--verbose` - Show detailed error output

**Examples:**
```bash
# Run default DatabaseSeeder
php velocix db:seed

# Run specific seeder
php velocix db:seed --class=UserSeeder

# With verbose output
php velocix db:seed --class=UserSeeder --verbose
```

**Output:**
```
Seeding database...

✓ Database seeded successfully!
```

---

### Cache Commands

#### `cache:clear`

Clear all application cache (view, route, config, app cache).

**Syntax:**
```bash
php velocix cache:clear
```

**Example:**
```bash
php velocix cache:clear
```

**Output:**
```
✓ Cache cleared successfully!
  - View cache
  - Route cache
  - Config cache
  - Application cache
```

---

### Optimization Commands

#### `optimize`

Cache configuration and routes for better performance.

**Syntax:**
```bash
php velocix optimize
```

**What it does:**
1. Clears existing cache
2. Caches all configuration files
3. Caches routes
4. Optimizes views

**Example:**
```bash
php velocix optimize
```

**Output:**
```
Optimizing application...

✓ Cache cleared successfully!
  - View cache
  - Route cache
  - Config cache
  - Application cache

Caching configuration...
  ✓ Configuration cached
Caching routes...
  ✓ Routes cached
Optimizing views...
  ✓ Views optimized

✓ Application optimized successfully!
```

**When to use:**
- Before deploying to production
- After updating configuration files
- To improve application performance

---

### Storage Commands

#### `storage:link`

Create symbolic link from `public/storage` to `storage/app/public`.

**Syntax:**
```bash
php velocix storage:link
```

**What it does:**
Creates a symbolic link so that publicly accessible files stored in `storage/app/public` can be accessed via URL.

**Example:**
```bash
php velocix storage:link
```

**Output:**
```
✓ Symbolic link created successfully!
  From: /path/to/project/public/storage
  To: /path/to/project/storage/app/public
```

**Usage:**
```php
// Store file
$path = storage_path('app/public/images/photo.jpg');
file_put_contents($path, $imageData);

// Access via URL
// http://localhost:8000/storage/images/photo.jpg
```

---

### Log Commands

#### `log:clear`

Clear application log files.

**Syntax:**
```bash
php velocix log:clear [channel]
```

**Arguments:**
- `channel` (optional) - Specific log channel to clear

**Examples:**
```bash
# Clear all logs
php velocix log:clear

# Clear specific channel
php velocix log:clear velocix
php velocix log:clear auth
```

**Output:**
```
Found 5 log file(s). Clearing...
✓ All log files cleared successfully!
```

---

### Key Commands

#### `key:generate`

Generate a new application encryption key.

**Syntax:**
```bash
php velocix key:generate
```

**What it does:**
Generates a new random encryption key and updates the `APP_KEY` in your `.env` file.

**Example:**
```bash
php velocix key:generate
```

**Output:**
```
Application key set successfully!
Key: base64:Hs8oR3h2k4M6vTy9xQz1nP7wB5fGjLcV2iUaE4dS8mA=
```

**When to use:**
- During initial installation
- After cloning a project
- When security requires key rotation

---

## Command Cheat Sheet

### Common Workflows

**New Project Setup:**
```bash
composer create-project velocix/velocix my-app
cd my-app
php velocix key:generate
php velocix migrate
php velocix serve
```

**Create Feature:**
```bash
# Create model with migration
php velocix make:model Product -m

# Create controller
php velocix make:controller ProductController

# Create service
php velocix make:service ProductService --model=Product
```

**Database Management:**
```bash
# Run migrations
php velocix migrate

# Rollback
php velocix migrate:rollback

# Fresh start with seed
php velocix migrate:fresh --seed

# Run seeders
php velocix db:seed
php velocix db:seed --class=UserSeeder
```

**Performance Optimization:**
```bash
# Before deployment
php velocix optimize
php velocix cache:clear

# Create storage link
php velocix storage:link
```

**Development:**
```bash
# Start server
php velocix serve

# Clear logs
php velocix log:clear

# Clear cache
php velocix cache:clear
```

---

## Tips & Best Practices

### 1. Naming Conventions

**Controllers:**
- Use PascalCase with "Controller" suffix
- Examples: `UserController`, `PostController`, `Api/UserController`

**Models:**
- Use singular PascalCase
- Examples: `User`, `Post`, `OrderItem`

**Migrations:**
- Use snake_case with descriptive names
- Examples: `create_users_table`, `add_status_to_posts`

**Seeders:**
- Use PascalCase with "Seeder" suffix
- Examples: `UserSeeder`, `DatabaseSeeder`

### 2. Migration Best Practices

**Always review generated migrations before running:**
```bash
php velocix make:migration create_products_table
# Edit database/migrations/*_create_products_table.php
php velocix migrate
```

**Use schema shorthand for faster development:**
```bash
php velocix make:migration create_products_table --schema="name:string,price:decimal,stock:integer:unsigned:default=0,is_active:boolean:default=true"
```

### 3. Service Layer Pattern

**When building complex applications, use services:**
```bash
# Create service with repository
php velocix make:service UserService --model=User --repository

# Use in controller
$this->userService->create($validatedData);
```

### 4. Authentication Scaffolding

**Quick auth setup:**
```bash
php velocix make:auth
php velocix migrate
# Include routes/auth.php in routes/web.php
```

### 5. Performance Optimization

**Before production deployment:**
```bash
php velocix cache:clear
php velocix optimize
php velocix storage:link
```

---

## Troubleshooting

### Command not found

```bash
chmod +x velocix
php velocix help
```

### Permission denied

```bash
sudo php velocix storage:link
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Database connection error

Check your `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=velocix_db
DB_USERNAME=root
DB_PASSWORD=
```

### Migration fails

```bash
# Check migrations table
php velocix migrate:rollback

# Fresh start
php velocix migrate:fresh
```

---

## Getting Help

- **CLI Help:** `php velocix help`
- **Documentation:** https://velocix.dev/docs
- **GitHub Issues:** https://github.com/velocix/framework/issues
- **Community:** https://discord.gg/velocix

---

## Model Relationships

Velocix supports common database relationships for building complex applications.

### One-to-One Relationship

**Scenario:** User has one Profile

**Migration:**
```bash
php velocix make:migration create_users_table --schema="name:string,email:string:unique"
php velocix make:migration create_profiles_table --schema="user_id:integer:unsigned:unique,bio:text:nullable,avatar:string:nullable"
```

**Models:**
```php
// app/Models/User.php
<?php

namespace App\Models;

use Velocix\Database\Model;

class User extends Model
{
    protected $fillable = ['name', 'email'];

    // One-to-One: User has one Profile
    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id', 'id');
    }
}

// app/Models/Profile.php
<?php

namespace App\Models;

use Velocix\Database\Model;

class Profile extends Model
{
    protected $fillable = ['user_id', 'bio', 'avatar'];

    // Inverse: Profile belongs to User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
```

**Usage:**
```php
// Get user with profile
$user = User::find(1);
$profile = $user->profile();

// Get user from profile
$profile = Profile::find(1);
$user = $profile->user();
```

---

### One-to-Many Relationship

**Scenario:** User has many Posts

**Migration:**
```bash
php velocix make:migration create_posts_table --schema="user_id:integer:unsigned:index,title:string,content:text,published_at:timestamp:nullable"
```

**Models:**
```php
// app/Models/User.php
<?php

namespace App\Models;

use Velocix\Database\Model;

class User extends Model
{
    protected $fillable = ['name', 'email'];

    // One-to-Many: User has many Posts
    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id', 'id');
    }

    // Get only published posts
    public function publishedPosts()
    {
        return $this->hasMany(Post::class, 'user_id', 'id')
                    ->whereNotNull('published_at');
    }
}

// app/Models/Post.php
<?php

namespace App\Models;

use Velocix\Database\Model;

class Post extends Model
{
    protected $fillable = ['user_id', 'title', 'content', 'published_at'];

    // Inverse: Post belongs to User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // Post has many Comments
    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id', 'id');
    }
}
```

**Usage:**
```php
// Get all posts by user
$user = User::find(1);
$posts = $user->posts();

// Get post author
$post = Post::find(1);
$author = $post->user();

// Count user's posts
$postCount = count($user->posts());
```

---

### Many-to-Many Relationship

**Scenario:** Posts have many Tags, Tags have many Posts

**Migration:**
```bash
php velocix make:migration create_tags_table --schema="name:string:unique,slug:string:unique"
php velocix make:migration create_post_tag_table --schema="post_id:integer:unsigned:index,tag_id:integer:unsigned:index"
```

**Models:**
```php
// app/Models/Post.php
<?php

namespace App\Models;

use Velocix\Database\Model;

class Post extends Model
{
    protected $fillable = ['user_id', 'title', 'content'];

    // Many-to-Many: Post has many Tags
    public function tags()
    {
        return $this->belongsToMany(
            Tag::class,
            'post_tag',      // Pivot table name
            'post_id',       // Foreign key on pivot table
            'tag_id'         // Related key on pivot table
        );
    }
}

// app/Models/Tag.php
<?php

namespace App\Models;

use Velocix\Database\Model;

class Tag extends Model
{
    protected $fillable = ['name', 'slug'];

    // Many-to-Many: Tag belongs to many Posts
    public function posts()
    {
        return $this->belongsToMany(
            Post::class,
            'post_tag',
            'tag_id',
            'post_id'
        );
    }
}
```

**Usage:**
```php
// Get post tags
$post = Post::find(1);
$tags = $post->tags();

// Get posts by tag
$tag = Tag::where('slug', 'php')->first();
$posts = $tag->posts();

// Attach tags to post
$post->tags()->attach([1, 2, 3]); // Tag IDs

// Detach tags
$post->tags()->detach([2]); // Remove tag ID 2

// Sync tags (replace all)
$post->tags()->sync([1, 3, 5]);
```

---

### Has-Many-Through Relationship

**Scenario:** Country has many Posts through Users

**Migration:**
```bash
php velocix make:migration create_countries_table --schema="name:string:unique,code:string:unique"
php velocix make:migration add_country_id_to_users --schema="country_id:integer:unsigned:index"
```

**Models:**
```php
// app/Models/Country.php
<?php

namespace App\Models;

use Velocix\Database\Model;

class Country extends Model
{
    protected $fillable = ['name', 'code'];

    // Has-Many-Through: Country has many Posts through Users
    public function posts()
    {
        return $this->hasManyThrough(
            Post::class,      // Final model
            User::class,      // Intermediate model
            'country_id',     // Foreign key on users table
            'user_id',        // Foreign key on posts table
            'id',             // Local key on countries table
            'id'              // Local key on users table
        );
    }

    public function users()
    {
        return $this->hasMany(User::class, 'country_id', 'id');
    }
}
```

**Usage:**
```php
$country = Country::where('code', 'US')->first();
$posts = $country->posts(); // Get all posts from users in this country
```

---

### Polymorphic Relationship

**Scenario:** Comments can belong to Posts or Videos

**Migration:**
```bash
php velocix make:migration create_comments_table --schema="commentable_id:integer:unsigned,commentable_type:string,content:text"
```

**Models:**
```php
// app/Models/Comment.php
<?php

namespace App\Models;

use Velocix\Database\Model;

class Comment extends Model
{
    protected $fillable = ['commentable_id', 'commentable_type', 'content'];

    // Polymorphic: Comment belongs to Post or Video
    public function commentable()
    {
        return $this->morphTo('commentable', 'commentable_type', 'commentable_id');
    }
}

// app/Models/Post.php
class Post extends Model
{
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

// app/Models/Video.php
class Video extends Model
{
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
```

**Usage:**
```php
// Get comments for a post
$post = Post::find(1);
$comments = $post->comments();

// Get parent of comment
$comment = Comment::find(1);
$parent = $comment->commentable(); // Returns Post or Video instance
```

---

## Query Examples (Service Layer)

Best practice: Put complex queries in Service classes, not Controllers.

### Basic Service with Queries

```php
<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;

class PostService
{
    /**
     * Get all posts with pagination
     */
    public function getAllPosts($perPage = 15, $filters = [])
    {
        $query = Post::query();

        // Apply filters
        if (!empty($filters['search'])) {
            $search = htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8');
            $query->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('content', 'LIKE', "%{$search}%");
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderBy('created_at', 'DESC')
                     ->limit($perPage)
                     ->get();
    }

    /**
     * Get post with author and comments
     */
    public function getPostWithRelations($id)
    {
        $post = Post::find($id);
        
        if (!$post) {
            throw new \Exception('Post not found');
        }

        // Eager load relationships
        $post->author = $post->user();
        $post->comments = $post->comments();
        $post->tags = $post->tags();

        return $post;
    }

    /**
     * Get posts by tag
     */
    public function getPostsByTag($tagSlug, $perPage = 15)
    {
        $tag = Tag::where('slug', $tagSlug)->first();
        
        if (!$tag) {
            throw new \Exception('Tag not found');
        }

        return $tag->posts()
                   ->orderBy('published_at', 'DESC')
                   ->limit($perPage)
                   ->get();
    }

    /**
     * Get trending posts (most comments in last 7 days)
     */
    public function getTrendingPosts($limit = 10)
    {
        $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));

        // Using raw query with JOINs
        $query = Post::query()
            ->select('posts.*', 'COUNT(comments.id) as comments_count')
            ->leftJoin('comments', 'posts.id', '=', 'comments.post_id')
            ->where('posts.published_at', '>=', $sevenDaysAgo')
            ->whereNotNull('posts.published_at')
            ->groupBy('posts.id')
            ->orderBy('comments_count', 'DESC')
            ->limit($limit);

        return $query->get();
    }

    /**
     * Create new post with tags
     */
    public function createPost($data)
    {
        // Validate and sanitize
        $validated = $this->validatePostData($data);

        // Create post
        $post = Post::create([
            'user_id' => $validated['user_id'],
            'title' => $validated['title'],
            'content' => $validated['content'],
            'status' => $validated['status'] ?? 'draft',
        ]);

        // Attach tags if provided
        if (!empty($validated['tags'])) {
            $post->tags()->sync($validated['tags']);
        }

        return $post;
    }

    /**
     * Update post
     */
    public function updatePost($id, $data)
    {
        $post = Post::find($id);
        
        if (!$post) {
            throw new \Exception('Post not found');
        }

        // Validate and sanitize
        $validated = $this->validatePostData($data);

        // Update post
        $post->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'status' => $validated['status'],
        ]);

        // Sync tags
        if (isset($validated['tags'])) {
            $post->tags()->sync($validated['tags']);
        }

        return $post;
    }

    /**
     * XSS Protection: Sanitize input
     */
    protected function validatePostData($data)
    {
        return [
            'user_id' => (int) ($data['user_id'] ?? 0),
            'title' => htmlspecialchars($data['title'] ?? '', ENT_QUOTES, 'UTF-8'),
            'content' => htmlspecialchars($data['content'] ?? '', ENT_QUOTES, 'UTF-8'),
            'status' => in_array($data['status'] ?? 'draft', ['draft', 'published']) 
                       ? $data['status'] : 'draft',
            'tags' => array_map('intval', $data['tags'] ?? []),
        ];
    }
}
```

---

### Advanced Queries with JOIN

```php
<?php

namespace App\Services;

use Velocix\Database\QueryBuilder;

class AnalyticsService
{
    protected $connection;

    public function __construct()
    {
        $this->connection = Model::getConnection();
    }

    /**
     * Get users with post count and latest post date
     */
    public function getUsersWithPostStats()
    {
        $query = (new QueryBuilder($this->connection))
            ->table('users')
            ->select(
                'users.*',
                'COUNT(posts.id) as posts_count',
                'MAX(posts.published_at) as latest_post_date'
            )
            ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
            ->groupBy('users.id')
            ->orderBy('posts_count', 'DESC');

        return $query->get();
    }

    /**
     * Get posts with author and comment count
     */
    public function getPostsWithStats($limit = 20)
    {
        $query = (new QueryBuilder($this->connection))
            ->table('posts')
            ->select(
                'posts.*',
                'users.name as author_name',
                'users.email as author_email',
                'COUNT(comments.id) as comments_count'
            )
            ->leftJoin('users', 'posts.user_id', '=', 'users.id')
            ->leftJoin('comments', 'posts.id', '=', 'comments.post_id')
            ->groupBy('posts.id', 'users.id')
            ->orderBy('posts.published_at', 'DESC')
            ->limit($limit);

        return $query->get();
    }

    /**
     * Complex query: Posts with multiple JOINs
     */
    public function getDetailedPostAnalytics($postId)
    {
        $query = (new QueryBuilder($this->connection))
            ->table('posts')
            ->select(
                'posts.*',
                'users.name as author_name',
                'COUNT(DISTINCT comments.id) as comments_count',
                'COUNT(DISTINCT likes.id) as likes_count',
                'COUNT(DISTINCT post_views.id) as views_count'
            )
            ->leftJoin('users', 'posts.user_id', '=', 'users.id')
            ->leftJoin('comments', 'posts.id', '=', 'comments.post_id')
            ->leftJoin('likes', 'posts.id', '=', 'likes.post_id')
            ->leftJoin('post_views', 'posts.id', '=', 'post_views.post_id')
            ->where('posts.id', $postId)
            ->groupBy('posts.id', 'users.id');

        return $query->first();
    }

    /**
     * Search across multiple tables
     */
    public function globalSearch($keyword, $limit = 50)
    {
        $keyword = htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8');
        
        // Search in posts
        $posts = (new QueryBuilder($this->connection))
            ->table('posts')
            ->select('id', 'title', "'post' as type")
            ->where('title', 'LIKE', "%{$keyword}%")
            ->orWhere('content', 'LIKE', "%{$keyword}%")
            ->limit($limit)
            ->get();

        // Search in users
        $users = (new QueryBuilder($this->connection))
            ->table('users')
            ->select('id', 'name as title', "'user' as type")
            ->where('name', 'LIKE', "%{$keyword}%")
            ->orWhere('email', 'LIKE', "%{$keyword}%")
            ->limit($limit)
            ->get();

        // Merge results
        return array_merge($posts, $users);
    }
}
```

---

### Controller Usage (Best Practice)

```php
<?php

namespace App\Http\Controllers;

use Velocix\Http\Controller;
use Velocix\Http\Request;
use App\Services\PostService;

class PostController extends Controller
{
    protected $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    /**
     * List all posts
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'user_id' => $request->input('user_id'),
        ];

        $posts = $this->postService->getAllPosts(15, $filters);

        return $this->json([
            'data' => $posts,
            'meta' => [
                'total' => count($posts),
                'filters' => $filters
            ]
        ]);
    }

    /**
     * Show single post with relations
     */
    public function show(Request $request, $id)
    {
        try {
            $post = $this->postService->getPostWithRelations($id);
            
            return $this->json([
                'data' => $post
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Create new post
     */
    public function store(Request $request)
    {
        try {
            $post = $this->postService->createPost($request->all());
            
            return $this->json([
                'message' => 'Post created successfully',
                'data' => $post
            ], 201);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get trending posts
     */
    public function trending(Request $request)
    {
        $limit = $request->input('limit', 10);
        $posts = $this->postService->getTrendingPosts($limit);

        return $this->json([
            'data' => $posts
        ]);
    }
}
```

---

## SPA (Single Page Application) Support

Velocix has built-in SPA support with seamless navigation without page reloads.

### Controller with SPA Support

```php
<?php

namespace App\Http\Controllers;

use Velocix\Http\Controller;
use Velocix\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $data = [
            'title' => 'Home - Velocix',
            'message' => 'Welcome to Velocix'
        ];

        // For AJAX/SPA requests - return JSON with HTML
        if ($request->ajax() || $request->wantsJson()) {
            return $this->json([
                'html' => view('home', $data)->render(),
                'title' => $data['title']
            ]);
        }

        // For full page load (SSR) - return view
        return $this->view('home', $data);
    }

    public function about(Request $request)
    {
        $data = [
            'title' => 'About Us',
            'team' => ['Alice', 'Bob', 'Charlie']
        ];

        if ($request->ajax()) {
            return $this->json([
                'html' => view('about', $data)->render(),
                'title' => $data['title']
            ]);
        }

        return $this->view('about', $data);
    }
}
```

### Views with SPA Links

```html
<!-- resources/views/home.vlx.php -->
@extends('layouts.app')

@section('content')
    <h1>{{ $message }}</h1>
    
    <!-- SPA link - no page reload -->
    <a href="/about" data-velocix-link>About Us</a>
    
    <!-- Regular link - full page reload -->
    <a href="/external" target="_blank">External Link</a>
@endsection
```

### CSRF Protection

```php
<?php

namespace App\Http\Middleware;

class VerifyCsrfToken
{
    public function handle($request, $next)
    {
        // Skip CSRF for GET requests
        if ($request->method() === 'GET') {
            return $next($request);
        }

        // Get CSRF token
        $token = $request->input('_token') ?? 
                 $request->header('X-CSRF-TOKEN');

        // Get session token
        $sessionToken = $request->session()->token();

        // Verify tokens match
        if (!$this->tokensMatch($token, $sessionToken)) {
            return $this->json([
                'error' => 'CSRF token mismatch'
            ], 419);
        }

        return $next($request);
    }

    protected function tokensMatch($token1, $token2)
    {
        return hash_equals((string) $token1, (string) $token2);
    }
}
```

### Forms with CSRF

```html
<form action="/posts" method="POST" data-velocix-form>
    {!! csrf_field() !!}
    
    <input type="text" name="title" required>
    <textarea name="content" required></textarea>
    
    <button type="submit">Submit</button>
</form>
```

---

*Last updated: January 8, 2026*