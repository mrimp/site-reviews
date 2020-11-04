<?php

namespace GeminiLabs\SiteReviews;

use GeminiLabs\SiteReviews\Database;
use GeminiLabs\SiteReviews\Database\Query;
use GeminiLabs\SiteReviews\Database\SqlSchema;

class Install
{
    /**
     * @param bool $dropAll
     * @return void
     */
    public function dropTables($dropAll = true)
    {
        $tables = $this->tables();
        if (is_multisite() && $dropAll) {
            $sites = get_sites([
                'fields' => 'ids',
                'network_id' => get_current_network_id(),
            ]);
            foreach ($sites as $siteId) {
                switch_to_blog($siteId);
                $tables = array_unique(array_merge($tables, $this->tables()));
                delete_option('glsr_db_version');
                restore_current_blog();
            }
        }
        foreach ($tables as $table) {
            glsr(Database::class)->dbQuery(
                glsr(Query::class)->sql("DROP TABLE IF EXISTS {$table}")
            );
        }
        delete_option('glsr_db_version');
    }

    /**
     * @return void
     */
    public function run()
    {
        require_once ABSPATH.'/wp-admin/includes/plugin.php';
        if (is_plugin_active_for_network(glsr()->file)) {
            $sites = get_sites([
                'fields' => 'ids',
                'network_id' => get_current_network_id(),
            ]);
            foreach ($sites as $siteId) {
                $this->runOnSite($siteId);
            }
            return;
        }
        $this->install();
    }

    /**
     * @param int $siteId
     * @return void
     */
    public function runOnSite($siteId)
    {
        switch_to_blog($siteId);
        $this->install();
        restore_current_blog();
    }

    /**
     * @return void
     */
    protected function createRoleCapabilities()
    {
        glsr(Role::class)->resetAll();
    }

    /**
     * @return void
     */
    protected function createTables()
    {
        glsr(SqlSchema::class)->createTables();
        glsr(SqlSchema::class)->addTableConstraints();
    }

    /**
     * @return void
     */
    protected function install()
    {
        $this->createRoleCapabilities();
        $this->createTables();
    }

    /**
     * @return array
     */
    protected function tables()
    {
        return [
            glsr(SqlSchema::class)->table('assigned_posts'),
            glsr(SqlSchema::class)->table('assigned_terms'),
            glsr(SqlSchema::class)->table('assigned_users'),
            glsr(SqlSchema::class)->table('ratings'),
        ];
    }
}