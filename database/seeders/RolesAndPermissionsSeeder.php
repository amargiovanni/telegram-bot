<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Bot Management
            'view_bots',
            'create_bots',
            'edit_bots',
            'delete_bots',
            'manage_bot_webhooks',

            // Chat Management
            'view_chats',
            'edit_chats',
            'delete_chats',

            // RSS Feeds
            'view_rss_feeds',
            'create_rss_feeds',
            'edit_rss_feeds',
            'delete_rss_feeds',

            // Reminders
            'view_reminders',
            'create_reminders',
            'edit_reminders',
            'delete_reminders',

            // Shopping Lists
            'view_shopping_lists',
            'create_shopping_lists',
            'edit_shopping_lists',
            'delete_shopping_lists',

            // Auto Responses
            'view_auto_responses',
            'create_auto_responses',
            'edit_auto_responses',
            'delete_auto_responses',

            // Bot Commands
            'view_commands',
            'create_commands',
            'edit_commands',
            'delete_commands',

            // Logs
            'view_logs',
            'delete_logs',
            'export_logs',

            // URL Shortener
            'view_shortened_urls',
            'create_shortened_urls',
            'edit_shortened_urls',
            'delete_shortened_urls',

            // Analytics & Reports
            'view_analytics',
            'export_analytics',

            // System
            'manage_users',
            'manage_roles',
            'view_health_check',
            'manage_backups',
            'view_settings',
            'edit_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin - Full access
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Bot Manager - Manage bots, chats, and configurations
        $botManager = Role::create(['name' => 'bot_manager']);
        $botManager->givePermissionTo([
            'view_bots', 'create_bots', 'edit_bots', 'delete_bots', 'manage_bot_webhooks',
            'view_chats', 'edit_chats', 'delete_chats',
            'view_commands', 'create_commands', 'edit_commands', 'delete_commands',
            'view_logs',
            'view_analytics',
            'view_health_check',
            'view_settings',
        ]);

        // Content Manager - Manage RSS, responses, lists
        $contentManager = Role::create(['name' => 'content_manager']);
        $contentManager->givePermissionTo([
            'view_bots', 'view_chats',
            'view_rss_feeds', 'create_rss_feeds', 'edit_rss_feeds', 'delete_rss_feeds',
            'view_reminders', 'create_reminders', 'edit_reminders', 'delete_reminders',
            'view_shopping_lists', 'create_shopping_lists', 'edit_shopping_lists', 'delete_shopping_lists',
            'view_auto_responses', 'create_auto_responses', 'edit_auto_responses', 'delete_auto_responses',
            'view_shortened_urls', 'create_shortened_urls', 'edit_shortened_urls', 'delete_shortened_urls',
            'view_logs',
            'view_analytics',
        ]);

        // Moderator - View and moderate content
        $moderator = Role::create(['name' => 'moderator']);
        $moderator->givePermissionTo([
            'view_bots', 'view_chats',
            'view_rss_feeds', 'edit_rss_feeds',
            'view_reminders', 'edit_reminders',
            'view_shopping_lists', 'edit_shopping_lists',
            'view_auto_responses', 'edit_auto_responses',
            'view_logs',
            'view_analytics',
        ]);

        // Viewer - Read-only access
        $viewer = Role::create(['name' => 'viewer']);
        $viewer->givePermissionTo([
            'view_bots', 'view_chats',
            'view_rss_feeds',
            'view_reminders',
            'view_shopping_lists',
            'view_auto_responses',
            'view_commands',
            'view_logs',
            'view_shortened_urls',
            'view_analytics',
        ]);

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
