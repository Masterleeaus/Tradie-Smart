#!/usr/bin/env python3
"""Helper to generate boilerplate FSM module files."""
import os, json
from pathlib import Path

BASE = Path('/home/user/Worksuite-Saas---Project-Management-System_Laravel/Modules')

def write(path, content):
    path = Path(path)
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(content)

def make_module_json(name, alias, description, requires=None):
    requires = requires or []
    return json.dumps({
        "name": name, "alias": alias, "description": description,
        "keywords": ["fsm", "field-service", "cleansmartOS"],
        "active": 1, "order": 0,
        "providers": [f"Modules\\\\{name}\\\\Providers\\\\{name}ServiceProvider",
                      f"Modules\\\\{name}\\\\Providers\\\\RouteServiceProvider"],
        "aliases": {}, "files": [], "requires": requires, "version": "1.0.0"
    }, indent=2)

def make_provider(name, alias):
    return f'''<?php

namespace Modules\\{name}\\Providers;

use Illuminate\\Support\\ServiceProvider;
use Illuminate\\Support\\Facades\\Schema;

class {name}ServiceProvider extends ServiceProvider
{{
    public function boot(): void
    {{
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path('{name}', 'Database/Migrations'));
    }}

    public function register(): void
    {{
        $this->app->register(RouteServiceProvider::class);
    }}

    protected function registerConfig(): void
    {{
        $this->mergeConfigFrom(module_path('{name}', 'Config/config.php'), '{alias}');
    }}

    protected function registerViews(): void
    {{
        $this->loadViewsFrom(module_path('{name}', 'Resources/views'), '{alias}');
    }}

    protected function registerTranslations(): void
    {{
        $this->loadTranslationsFrom(module_path('{name}', 'Resources/lang'), '{alias}');
    }}
}}
'''

def make_route_provider(name, alias):
    return f'''<?php

namespace Modules\\{name}\\Providers;

use Illuminate\\Support\\Facades\\Route;
use Illuminate\\Foundation\\Support\\Providers\\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{{
    protected string $moduleNamespace = 'Modules\\\\{name}\\\\Http\\\\Controllers';

    public function boot(): void
    {{
        parent::boot();
    }}

    public function map(): void
    {{
        $this->mapWebRoutes();
        $this->mapApiRoutes();
    }}

    protected function mapWebRoutes(): void
    {{
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('{name}', 'Routes/web.php'));
    }}

    protected function mapApiRoutes(): void
    {{
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('{name}', 'Routes/api.php'));
    }}
}}
'''

def make_routes(name, alias, resource_name, route_prefix):
    return f'''<?php

use Illuminate\\Support\\Facades\\Route;
use Modules\\{name}\\Http\\Controllers\\{resource_name}Controller;

Route::middleware(['web', 'auth'])->prefix('{route_prefix}')->name('{alias}.')->group(function () {{
    Route::resource('/', {resource_name}Controller::class)->parameters(['' => '{alias}']);
}});
''', f'''<?php

use Illuminate\\Support\\Facades\\Route;

Route::middleware('auth:sanctum')->prefix('v1/{route_prefix}')->name('api.{alias}.')->group(function () {{
    // API routes
}});
'''

def make_lang(name):
    return f'''<?php

return [
    'name'        => '{name}',
    'menu'        => '{name.replace("FSM", "FSM ")}',
    'created'     => '{name} record created successfully.',
    'updated'     => '{name} record updated.',
    'deleted'     => '{name} record deleted.',
    'not_found'   => '{name} record not found.',
];
'''

# ── Module definitions ──────────────────────────────────────────────────────

MODULES = {}

for name, cfg in MODULES.items():
    alias = cfg['alias']
    mod_path = BASE / name

    write(mod_path / 'module.json', make_module_json(name, alias, cfg['description'], cfg['requires']))
    write(mod_path / 'Config/config.php', f"<?php\n\n{cfg['config']}\n")
    write(mod_path / 'Providers' / f'{name}ServiceProvider.php', make_provider(name, alias))
    write(mod_path / 'Providers/RouteServiceProvider.php', make_route_provider(name, alias))
    web, api = make_routes(name, alias, cfg['resource'], cfg['route_prefix'])
    write(mod_path / 'Routes/web.php', web)
    write(mod_path / 'Routes/api.php', api)
    write(mod_path / 'Resources/lang/eng/app.php', f"<?php\n\n{make_lang(name)}\n")
    print(f"  scaffolded {name}")

print("Done.")
