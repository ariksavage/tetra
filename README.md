# Tetra

The shared framework behind Arik Savage's PHP and Angular apps: login and
sessions, users, roles and permissions, configuration, migrations, a MySQL
query builder, and the Angular layout, form fields, services and guards that
sit on top. An app includes Tetra as a git submodule and adds its own
resources beside it.

## Versions

| Version | Used by | Shape |
| --- | --- | --- |
| `v1` (tag) | ABSA | `core/` with `api/`, `models/` and a `plugins/` folder for app code; the older Angular `app/` |
| v2 (this branch) | Work, and Thyme once it moves over | One folder per resource in `core/`; the app's own `core/` is checked first; Thyme's Angular |

v2 is not a drop-in upgrade from v1: v1 apps keep their tag until they are
moved over.

## Layout

| Path | What |
| --- | --- |
| `core/` | The PHP API. `Core\Users\Models\User` lives at `core/Users/User.model.php`. |
| `migrations/` | Tetra's own migrations, such as its base tables. |
| `app/`, `classes/`, `components/`, `guards/`, `pages/`, `services/`, `testing/` | The Angular half, imported as `@tetra/...`. |
| `styles/_theme.scss` | Default values for every CSS variable Tetra's components read. |
| `styles/_base.scss` | Base styles for forms, inputs, buttons, tables and panels, built on the theme. |

## Using Tetra in an app

1. **Add the submodule:** `git submodule add git@github.com:ariksavage/tetra.git tetra`, then
   check out the version you want.
2. **Point PHP at the app:** Tetra's `core/` sits at `tetra/core`, one level below the app, so
   set `TETRA_SERVER_ROOT` to the app's root (in DDEV's `web_environment`, or with Apache's
   `SetEnv`). A deploy that copies everything into one folder doesn't need it.
3. **Config:** `config/db.config` (YAML: `host`, `user`, `password`, `name`, `port`) in the
   app root or any folder above it. Keep it outside the web root in production. Never commit it.
4. **Angular:** map `"@tetra/*": ["./tetra/*"]` in `tsconfig.json`, and start the app's routes
   from `TETRA_ROUTES` (`@tetra/app/tetra.routes`).
5. **Styles:** at the top of the app's global stylesheet:

   ```scss
   @use '../tetra/styles/theme';
   @use '../tetra/styles/base';
   ```

   `base` styles forms, inputs, buttons, tables and panels for Tetra's pages (and any of the
   app's) from the theme variables, so setting colours and fonts restyles all of it:

   ```scss
   :root {
     --primary: #eac11c;
     --primary-text: #282828;
     --font-body: Georgia, serif;
     --panel-shadow: 4px 4px 0 #000;
   }
   ```

   Every base rule sits in `:where()`, which has no specificity, so any rule the app writes wins
   with no `!important`: `button { ... }` replaces Tetra's buttons outright. Tetra's pages use a
   shared set of class names (`panel`, `data-table`, `table-wrap`, `button-row`, `filters`,
   `form-grid`, `label.inline`, `checkbox-label`, `checkbox-list`, `tag`, `muted`, `empty`);
   style those and Tetra's pages match the app's. The full list is at the top of `_base.scss`.

   Tetra's icon-only buttons (show password, the account and messages toggles, dismissing a
   message) are flat. Their hover and focus look is `--flat-icon-hover-background` (a colour or
   a gradient) and `--flat-icon-hover-color`.

6. **Icons:** `npm install @fortawesome/fontawesome-free`, then `@use '../tetra/styles/icons';` in
   the same stylesheet. Tetra's buttons and notifications use its solid and regular icons.
7. **User menu:** provide the app's links with `USER_MENU_ITEMS`
   (`@tetra/components/user-menu/user-menu-items`). Each can require a permission.
8. **Admin pages:** Tetra brings People (`/admin/people`), Roles (`/admin/roles`),
   Permissions (`/admin/permissions`) and Configuration (`/admin/config`). The user menu lists
   the ones the logged-in user may open, under Administration: People needs `users VIEW`, Roles
   `roles VIEW`, Permissions `permissions VIEW`, Configuration `config EDIT`. Admins get all four.
   To add the app's own admin pages to that list, provide `ADMIN_MENU_ITEMS`
   (`@tetra/components/user-menu/admin-menu-items`) as `[...TETRA_ADMIN_PAGES, ...yours]`;
   `visibleMenuItems(items, user)` filters any list by permission, for the app's own navigation.
   The admin role always keeps full control of users, roles and permissions, and no one can
   deactivate, delete or remove their own access to manage users, so the site can't be locked.

## The app's own API

A request to `/core/{type}/{action}/{id}/{id2}` loads `\Core\{Type}\API\{Type}` and calls
`{action}{METHOD}`, such as `Clients::listGET()`. The class loader looks in the app's own
`core/` first, then Tetra's, so an app adds a type by creating `core/Clients/Clients.api.php`,
and can override any Tetra class the same way.

## Migrations

A migration is a class with `isComplete()` and `execute()`, in a file named
`{timestamp}-{ClassName}.migration`. `php tetra/core/cli/maintenance.php` runs Tetra's
`migrations/` first, then the app's, skipping any that are already complete, so every
migration must be safe to meet again.

## Changing Tetra from an app

1. In the app's `tetra/` folder, branch, make the change and commit it.
2. Push it to this repository and merge it.
3. Commit the new submodule pointer in the app. Other apps pick the change up when they next
   update their submodule.

**This repository is public.** Keep config, credentials, client details and anything
app-specific in the app's own repository.
