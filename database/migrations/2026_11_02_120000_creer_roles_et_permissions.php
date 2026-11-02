<?php

use Database\Seeders\RolesEtPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Rôles et permissions Spatie créés par une migration : une base existante
 * (déjà seedée avant Spatie) les reçoit au prochain « migrate », sans seed manuel,
 * et ses comptes sont alignés sur la colonne users.role.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new RolesEtPermissionsSeeder)->run();
    }

    public function down(): void
    {
        // Tables Spatie supprimées par la migration create_permission_tables
    }
};
