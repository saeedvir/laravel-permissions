# Package Directory Structure

Complete visual representation of the Laravel Permissions package structure.

```
packages/saeedvir/laravel-permissions/
│
├── 📁 config/
│   └── 📄 permissions.php                          # Main configuration file
│
├── 📁 database/
│   ├── 📁 migrations/
│   │   ├── 📄 2024_01_01_000001_create_roles_table.php
│   │   ├── 📄 2024_01_01_000002_create_permissions_table.php
│   │   ├── 📄 2024_01_01_000003_create_role_has_permissions_table.php
│   │   ├── 📄 2024_01_01_000004_create_model_has_roles_table.php
│   │   └── 📄 2024_01_01_000005_create_model_has_permissions_table.php
│   └── 📁 seeders/
│       └── 📄 PermissionsSeeder.php               # Example seeder
│
├── 📁 examples/
│   ├── 📄 ExampleUsageController.php              # Controller examples
│   └── 📄 routes-example.php                      # Route examples
│
├── 📁 src/
│   ├── 📁 Middleware/
│   │   ├── 📄 CheckAuth.php                       # Authentication middleware
│   │   ├── 📄 CheckRole.php                       # Role checking middleware
│   │   └── 📄 CheckPermission.php                 # Permission checking middleware
│   │
│   ├── 📁 Models/
│   │   ├── 📄 Role.php                            # Role model
│   │   └── 📄 Permission.php                      # Permission model
│   │
│   ├── 📁 Services/
│   │   └── 📄 PermissionCache.php                 # Cache management service
│   │
│   ├── 📁 Traits/
│   │   └── 📄 HasRolesAndPermissions.php          # Main trait for User model
│   │
│   └── 📄 PermissionServiceProvider.php           # Service provider
│
├── 📄 .gitignore                                   # Git ignore file
├── 📄 composer.json                                # Composer configuration
├── 📄 LICENSE                                      # MIT License
├── 📄 README.md                                    # Main documentation
├── 📄 INSTALLATION.md                              # Installation guide
├── 📄 QUICKSTART.md                                # Quick start guide
├── 📄 PACKAGE-SUMMARY.md                           # Package summary
└── 📄 STRUCTURE.md                                 # This file
```

## File Descriptions

### Configuration
| File | Purpose |
|------|---------|
| `config/permissions.php` | Main package configuration with database, cache, and middleware settings |

### Migrations
| File | Creates Table | Description |
|------|--------------|-------------|
| `000001_create_roles_table.php` | `roles` | Stores role definitions |
| `000002_create_permissions_table.php` | `permissions` | Stores permission definitions |
| `000003_create_role_has_permissions_table.php` | `role_has_permissions` | Links roles to permissions |
| `000004_create_model_has_roles_table.php` | `model_has_roles` | Links users (models) to roles |
| `000005_create_model_has_permissions_table.php` | `model_has_permissions` | Links users to direct permissions |

### Core Source Files
| File | Purpose |
|------|---------|
| `PermissionServiceProvider.php` | Registers package services, middleware, and Blade directives |
| `PermissionCache.php` | Handles all caching operations with Redis/file cache support |
| `Role.php` | Role model with permission management methods |
| `Permission.php` | Permission model with role relationships |
| `HasRolesAndPermissions.php` | Trait providing role/permission methods to User model |

### Middleware
| File | Purpose |
|------|---------|
| `CheckAuth.php` | Verifies user authentication |
| `CheckRole.php` | Verifies user has required role(s) |
| `CheckPermission.php` | Verifies user has required permission(s) |

### Documentation
| File | Purpose |
|------|---------|
| `README.md` | Complete usage documentation |
| `INSTALLATION.md` | Step-by-step installation instructions |
| `QUICKSTART.md` | 5-minute quick start guide |
| `PACKAGE-SUMMARY.md` | Feature summary and overview |
| `STRUCTURE.md` | This file - directory structure |

### Examples
| File | Purpose |
|------|---------|
| `ExampleUsageController.php` | Controller showing all features |
| `routes-example.php` | Route protection examples |
| `PermissionsSeeder.php` | Database seeder example |

## Database Schema

### Tables Created

```
┌─────────────────────────────────────────────────────────┐
│                         ROLES                           │
├─────────────────┬───────────────────────────────────────┤
│ id              │ Primary Key                           │
│ name            │ Display name (e.g., "Administrator")  │
│ slug            │ Unique identifier (e.g., "admin")     │
│ description     │ Role description                      │
│ created_at      │ Timestamp                             │
│ updated_at      │ Timestamp                             │
└─────────────────┴───────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│                     PERMISSIONS                         │
├─────────────────┬───────────────────────────────────────┤
│ id              │ Primary Key                           │
│ name            │ Display name (e.g., "Create Post")    │
│ slug            │ Unique identifier (e.g., "create-post")│
│ description     │ Permission description                │
│ created_at      │ Timestamp                             │
│ updated_at      │ Timestamp                             │
└─────────────────┴───────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│               ROLE_HAS_PERMISSIONS                      │
├─────────────────┬───────────────────────────────────────┤
│ id              │ Primary Key                           │
│ role_id         │ Foreign Key → roles.id                │
│ permission_id   │ Foreign Key → permissions.id          │
│ created_at      │ Timestamp                             │
│ updated_at      │ Timestamp                             │
└─────────────────┴───────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│                 MODEL_HAS_ROLES                         │
├─────────────────┬───────────────────────────────────────┤
│ id              │ Primary Key                           │
│ role_id         │ Foreign Key → roles.id                │
│ model_type      │ Polymorphic (e.g., "App\Models\User") │
│ model_id        │ Polymorphic ID                        │
│ created_at      │ Timestamp                             │
│ updated_at      │ Timestamp                             │
└─────────────────┴───────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│             MODEL_HAS_PERMISSIONS                       │
├─────────────────┬───────────────────────────────────────┤
│ id              │ Primary Key                           │
│ permission_id   │ Foreign Key → permissions.id          │
│ model_type      │ Polymorphic (e.g., "App\Models\User") │
│ model_id        │ Polymorphic ID                        │
│ created_at      │ Timestamp                             │
│ updated_at      │ Timestamp                             │
└─────────────────┴───────────────────────────────────────┘
```

## Class Relationships

```
┌──────────────────────┐
│    User Model        │
│  (uses trait)        │
├──────────────────────┤
│ + assignRole()       │
│ + removeRole()       │
│ + hasRole()          │
│ + givePermissionTo() │
│ + hasPermission()    │
└──────────┬───────────┘
           │
           ├─── belongsToMany ──→ ┌──────────────────┐
           │                       │   Role Model     │
           │                       ├──────────────────┤
           │                       │ + givePermission()│
           │                       │ + revokePermission()│
           │                       └────────┬─────────┘
           │                                │
           │                                │ belongsToMany
           │                                ↓
           │                       ┌──────────────────┐
           │                       │Permission Model  │
           │                       ├──────────────────┤
           │                       │ + slug           │
           │                       │ + name           │
           └─── belongsToMany ──→ └──────────────────┘
                (direct permissions)
```

## Usage Flow

```
1. Install Package
   ↓
2. Publish Config & Migrations
   ↓
3. Configure .env
   ↓
4. Run Migrations
   ↓
5. Add Trait to User Model
   ↓
6. Create Roles & Permissions
   ↓
7. Assign to Users
   ↓
8. Protect Routes with Middleware
   ↓
9. Use in Controllers & Blade
```

## File Sizes (Approximate)

| Component | Files | Lines of Code |
|-----------|-------|---------------|
| Models | 2 | ~300 |
| Trait | 1 | ~350 |
| Middleware | 3 | ~250 |
| Cache Service | 1 | ~200 |
| Service Provider | 1 | ~140 |
| Migrations | 5 | ~300 |
| **Total** | **13** | **~1,540** |

## Key Features by File

### HasRolesAndPermissions.php (350 lines)
- ✅ 10+ methods for role/permission management
- ✅ Automatic caching
- ✅ Support for arrays and single values
- ✅ Polymorphic relationships

### PermissionCache.php (200 lines)
- ✅ Redis/File cache support
- ✅ Automatic invalidation
- ✅ Configurable TTL
- ✅ Manual cache management

### Middleware (3 files, 250 lines)
- ✅ 3 types: Auth, Role, Permission
- ✅ Configurable responses
- ✅ Multiple role/permission support
- ✅ OR logic with pipe separator

### Models (2 files, 300 lines)
- ✅ Full Eloquent relationships
- ✅ Cache integration
- ✅ Helper methods
- ✅ Automatic cache clearing

---

**Package Statistics**
- Total Files: 25+
- Lines of Code: ~1,540 (core)
- Documentation Pages: 5
- Example Files: 3
- Migrations: 5
- Tests: Ready for implementation
