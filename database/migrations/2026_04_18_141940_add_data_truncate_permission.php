<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(
            ['slug' => 'data.truncate'],
            [
                'slug'       => 'data.truncate',
                'label'      => 'Clear / Truncate Table Data',
                'module'     => 'Danger Zone',
                'sort_order' => 99,
            ]
        );
    }

    public function down(): void
    {
        Permission::where('slug', 'data.truncate')->delete();
    }
};
