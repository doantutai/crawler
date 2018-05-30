<?php

class WP_Crawler_Cron {
    function run($sourceIds = array()) {
        if (empty($sourceIds)) {
            $sources = get_posts(array('post_type' => 'wp-crawler-source'));
            foreach ($sources as $source) {
                $this->run(array($source->ID));
            }
        }
        foreach ($sourceIds as $sourceId) {
            $_POST['crawler_source'] = $sourceId;
            wp_crawler_run();
        }
    }
}

if (class_exists('WP_CLI')) {
    WP_CLI::add_command( 'wp_crawler_cron', 'WP_Crawler_Cron' );
}
