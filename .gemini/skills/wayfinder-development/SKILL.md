---
name: wayfinder-development
description: >-
  Activates whenever referencing backend routes in frontend components. Use when
  importing from @/actions or @/routes, calling Laravel routes from TypeScript,
  or working with Wayfinder route functions.
---

# Wayfinder Development

## When to Apply

Activate whenever referencing backend routes in frontend components:
- Importing from `@/actions/` or `@/routes/`
- Calling Laravel routes from TypeScript/JavaScript
- Creating links or navigation to backend endpoints

## Documentation

Use `search-docs` for detailed Wayfinder patterns and documentation.

## Quick Reference

### Generate Routes

Run after route changes if Vite plugin isn't installed:

php artisan wayfinder:generate --no-interaction

For form helpers, use `--with-form` flag:

php artisan wayfinder:generate --with-form --no-interaction

### Import Patterns

<code-snippet name="Controller Action Imports" lang="typescript">

// Named imports for tree-shaking (preferred)...
import { show, store, update } from '@/actions/App/Http/Controllers/PostController'

// Named route imports...
import { show as postShow } from '@/routes/post'

</code-snippet>

### Common Methods

<code-snippet name="Wayfinder Methods" lang="typescript">

// Get route object...
show(1) // { url: "/posts/1", method: "get" }

// Get URL string...
show.url(1) // "/posts/1"

// Specific HTTP methods...
show.get(1)
store.post()
update.patch(1)
destroy.delete(1)

// Form attributes for HTML forms...
store.form() // { action: "/posts", method: "post" }

// Query parameters...
show(1, { query: { page: 1 } }) // "/posts/1?page=1"

</code-snippet>

## Wayfinder + Inertia

Use Wayfinder with `useForm`:

<code-snippet name="Wayfinder useForm" lang="typescript">

import { store } from "@/actions/App/Http/Controllers/ExampleController";

const form = useForm({ name: "My Big Post" });
form.submit(store());

</code-snippet>

## Verification

1. Run `php artisan wayfinder:generate` to regenerate routes if Vite plugin isn't installed
2. Check TypeScript imports resolve correctly
3. Verify route URLs match expected paths

## Common Pitfalls

- Using default imports instead of named imports (breaks tree-shaking)
- Forgetting to regenerate after route changes
- Not using type-safe parameter objects for route model binding

## Application Context & Architecture

### Authentication & Authorization

**User Types** (defined in `app/Enums/UserType.php`):
- `super-admin` - Full system access, bypasses all permission checks
- `admin` - Administrative users with role-based permissions
- `customer` - Customer-facing users

**Role-Based Access Control (RBAC)**:
- Permissions are defined in `app/Constants/Permissions.php` using a structured array
- Roles are stored in `roles` table with many-to-many relationships to both users and permissions
- Pivot tables: `user_roles`, `role_permissions`
- Permission checks use **direct database queries** (not Eloquent relationships) due to relationship hydration issues
- Gates are dynamically registered in `AppServiceProvider` for all permissions
- Frontend permission checks via `$page.props.auth.permissions` array shared through `HandleInertiaRequests`

**Important**: The `Role::permissions()` Eloquent relationship has known issues. Always use direct DB queries:
```php
DB::table('user_roles')
    ->join('role_permissions', 'user_roles.role_id', '=', 'role_permissions.role_id')
    ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
    ->where('user_roles.user_id', $userId)
    ->pluck('permissions.name');
```

### Multilingual Support

**Language Configuration**:
- Supported languages: English (en), Arabic (ar)
- Translation files: `lang/en.json`, `lang/ar.json`
- Current locale shared via `$page.props.locale`
- Translations shared via `$page.props.translations`
- Use `__('key')` helper in Vue components for translations
- All static text must be translatable for both English and Arabic
- RTL support is built-in for Arabic language

### Branding & Theme Configuration

**Centralized Configuration** (`config/branding/`):
- `theme.php` - Main branding configuration (logo, favicon, admin panel text)
- `colors.php` - Color scheme configuration based on primary brand color
- All branding settings stored in `business_settings` table as key-value pairs
- Retrieved via `BusinessHelper::get('key')` or `config('branding.theme.key')`

**Color System**:
- Primary color drives the entire color palette
- CSS variables are dynamically generated based on primary color
- Admin panel colors (sidebar, header, active states) derive from primary color
- Dark mode variants are automatically calculated

**File Upload Flow**:
- Handled exclusively through `app/Traits/UploadMedia.php` trait
- Supports: logo, favicon, profile images
- Automatic optimization and storage management
- **Do not implement file upload logic elsewhere** - use the trait for centralized control

### Shared Frontend Data

Via `HandleInertiaRequests.php`, all pages receive:
```typescript
{
  name: string;              // App name
  auth: {
    user: User | null;
    is_super_admin: boolean;
    permissions: string[];   // Array of permission names or ['*'] for super admin
  };
  branding: {
    theme: object;           // From config/branding/theme.php
  };
  locale: string;            // Current language (en/ar)
  translations: object;      // All translations for current locale
  flash: {
    success?: string;
    error?: string;
    warning?: string;
  };
}
```

### Database Structure

**Key Tables**:
- `users` - User accounts with `type` enum field
- `roles` - Role definitions
- `permissions` - Permission definitions
- `user_roles` - User-to-role assignments (many-to-many)
- `role_permissions` - Role-to-permission assignments (many-to-many)
- `admin_profiles` - Extended profile data for admin users
- `customer_profiles` - Extended profile data for customers
- `business_settings` - Key-value store for all business configuration

### Eloquent Models

**Base Model Pattern**:
- All models should extend `App\Models\BaseModel` instead of `Illuminate\Database\Eloquent\Model`.
- `BaseModel` automatically includes `HasFactory` and `LogsActivity` traits.
- **Exceptions**:
  - `User`: Extends `Authenticatable`.
  - `ActivityLog`: Extends `Model` (to prevent infinite recursion in logging).
- Do strictly **NOT** add `use HasFactory;` or `use LogsActivity;` manually in models extending `BaseModel`.

### Service Layer Architecture

**Pattern**: Service-Repository (implemented via Services)
- **Controllers**: Handle request validation, parameter mapping, and response formatting (Inertia/JSON). Do NOT contain business logic or DB queries.
- **Services**: Contain all business logic, database interactions, transaction management, and logging.
- **Dependency Injection**: Services are injected into Controllers.
- **Naming Convention**: `EntityService` for `Entity` model (e.g., `UserService`, `RoleService`).
- **Goal**: Thin controllers, fat services, reusable logic.

### Validation Strategy

- **Form Requests**: All validation rules must be extracted into dedicated `FormRequest` classes in `app/Http/Requests`.
- **Inline Validation**: Strict prohibition of `$request->validate([...])` in controllers.
- **Naming**: `ActionEntityRequest` (e.g., `StoreUserRequest`, `UpdateProfileRequest`).

### Best Practices

1. **Always use Wayfinder** for route generation in frontend
2. **Always use `__()` helper** for translatable text
3. **Always check permissions** on both frontend (UI) and backend (controllers)
4. **Never hardcode branding** - use `config('branding.theme.key')`
5. **Never implement custom file upload** - use `UploadMedia` trait
6. **Use direct DB queries** for permission checks (Eloquent relationship is broken)
7. **TypeScript types** for auth are defined in `resources/js/types/auth.ts`
8. **Always use FormRequests** for validation, never inline logic in controllers

## Dynamic Sidebar Architecture

### Overview

The admin sidebar is **dynamically generated** based on the permission structure defined in `app/Constants/Permissions.php`. This ensures the navigation always reflects the current permission system without manual updates.

### Data Flow

1. **Backend** (`app/Constants/Permissions.php`):
   - Defines all permissions in a structured array with metadata (label, description, route, icon)
   - Each section has `sub_modules` containing individual permissions
   - Example structure:
   ```php
   self::SECTION_BUSINESS => [
       'label' => 'business_settings',
       'icon' => 'Building2',
       'sub_modules' => [
           self::BUSINESS_BRANDING => [
               'label' => 'business_branding',
               'description' => 'Manage organization visual identity',
               'route' => 'admin.business.branding',
               'icon' => 'Palette',
           ],
       ]
   ]
   ```

2. **Middleware** (`app/Http/Middleware/HandleInertiaRequests.php`):
   - Reads `Permissions::getAll()` structure
   - Resolves route names to actual URLs using `route()` helper
   - Wraps in try-catch to prevent crashes from undefined routes
   - Shares enhanced structure via `$page.props.auth.available_permissions`

3. **Frontend** (`resources/js/Components/Admin/Sidebar.vue`):
   - Receives permissions structure from Inertia props
   - Dynamically maps icon names to `lucide-vue-next` components
   - Iterates over sections to build navigation
   - Uses `can(permission_key)` to check user access
   - Pre-resolved URLs from backend for navigation links

### Key Implementation Details

**Icon Mapping** (Sidebar.vue):
```typescript
const icons: Record<string, any> = {
    LayoutDashboard, Activity, Users, Shield,
    Building2, Palette, Scale, Settings, // etc.
};
```

**Route Resolution** (HandleInertiaRequests.php):
```php
'available_permissions' => collect(Permissions::getAll())->map(function ($section) {
    if (isset($section['sub_modules'])) {
        $section['sub_modules'] = collect($section['sub_modules'])->map(function ($module) {
            if (isset($module['route'])) {
                try {
                    $module['url'] = route($module['route']);
                } catch (\Exception $e) {
                    $module['url'] = '#'; // Fallback to prevent crash
                }
            }
            return $module;
        })->toArray();
    }
    return $section;
})->toArray(),
```

**Permission Checking** (Sidebar.vue):
```vue
<template v-for="(module, key) in activeSection.sub_modules" :key="key">
    <Link 
        v-if="can(String(key))" 
        :href="module.url"
    >
        <component :is="icons[module.icon]" />
        <span>{{ __(t(String(module.label))) }}</span>
    </Link>
</template>
```

### Adding New Sidebar Items

1. **Define Permission Constant** in `Permissions.php`:
   ```php
   public const NEW_FEATURE = 'new_feature';
   ```

2. **Add to Structure** in `getAll()` method:
   ```php
   self::NEW_FEATURE => [
       'label' => 'new_feature',
       'description' => 'Manage new feature',
       'route' => 'admin.new_feature.index',
       'icon' => 'IconName', // Must exist in lucide-vue-next
   ],
   ```

3. **Run Seeder** to create permission in database:
   ```bash
   php artisan db:seed --class=RoleSeeder
   ```

4. **Add Icon Import** (if new) in `Sidebar.vue`:
   ```typescript
   import { IconName } from 'lucide-vue-next';
   // Add to icons object
   const icons = { ..., IconName };
   ```

### Important Notes

- **Unique Keys Required**: PHP arrays require unique keys. Cannot have duplicate permission constants.
- **Permission Database Sync**: New permissions must be seeded via `RoleSeeder` to create database records.
- **Route Names**: Must match actual route definitions in `routes/admin.php`.
- **Icon Names**: Must be valid `lucide-vue-next` component names (PascalCase).
- **Translations**: All `label` and `description` values should have corresponding entries in `lang/en.json` and `lang/ar.json`.

### Troubleshooting

**Sidebar item not appearing:**
1. Check permission exists in database: `php artisan tinker --execute="App\Models\Permission::pluck('name')"`
2. Verify user has permission assigned to their role
3. Ensure route name is correct and route exists
4. Check icon name is imported and mapped in Sidebar.vue

**White screen after adding item:**
- Route resolution failed - check try-catch in `HandleInertiaRequests.php`
- Icon component not imported - add to imports and icon mapping

**Permission checkboxes not working in role forms:**
- Use `:checked` and `@change` instead of `v-model` for array-based permissions
- See `resources/js/Pages/Admin/Roles/Edit.vue` and `Create.vue` for correct implementation

## Compact UI Standards ("Zoomy Resize")

To maintain a "de-zoomed", data-dense, and professional appearance, use the following standardized Tailwind classes for UI elements. Avoid large, "zoomed-in" default sizes.

### Page Headers & Actions
- **Main Page Title**: `text-xl font-extrabold` (Avoid `text-2xl`+)
- **Description Text**: `text-sm text-slate-500`
- **Primary Action Buttons**: `px-4 py-2 text-sm font-bold` (Avoid `px-6 py-3`)

### Cards & Containers
- **Card Padding**: `p-5` (Avoid `p-6` or larger)
- **Card Radius**: `rounded-[2rem]` (Standard for this app)
- **Inner Section Spacing**: `mb-6` or `space-y-6`
- **Stats Icons**: `p-2` container (Avoid `p-3`)

### Typography
- **Card/Section Titles**: `text-base font-bold` (Avoid `text-lg` or `text-xl` for sub-headers)
- **Value/Stats Text**: `text-xl` or `text-2xl` (Reduced from `text-3xl`+)
- **Table Headers**: `text-[10px] uppercase tracking-widest`

### Forms & Inputs
- **Input Fields**: `px-4 py-2 text-sm` (Avoid `px-5 py-3`)
- **Textareas**: `p-5` (Avoid `p-6`)
- **Checkbox Rows**: `p-3` (Avoid `p-4`)

