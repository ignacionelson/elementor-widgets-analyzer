<?php
/**
 * Database operations class
 */
class EWA_Database {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ewa_widget_analysis';
    }
    
    /**
     * Create the custom table
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            post_type varchar(50) NOT NULL,
            post_title varchar(255) NOT NULL,
            widget_name varchar(100) NOT NULL,
            widget_count int(11) DEFAULT 1,
            analysis_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY post_type (post_type),
            KEY widget_name (widget_name),
            KEY analysis_date (analysis_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Clear all analysis data
     */
    public function clear_analysis_data() {
        global $wpdb;
        return $wpdb->query("DELETE FROM {$this->table_name}");
    }
    
    /**
     * Insert widget analysis data
     */
    public function insert_widget_data($post_id, $post_type, $post_title, $widget_name, $widget_count = 1) {
        global $wpdb;
        
        return $wpdb->insert(
            $this->table_name,
            array(
                'post_id' => $post_id,
                'post_type' => $post_type,
                'post_title' => $post_title,
                'widget_name' => $widget_name,
                'widget_count' => $widget_count
            ),
            array('%d', '%s', '%s', '%s', '%d')
        );
    }
    
    /**
     * Get widget statistics
     */
    public function get_widget_statistics() {
        global $wpdb;
        
        $sql = "SELECT 
                    widget_name,
                    COUNT(DISTINCT post_id) as content_count,
                    SUM(widget_count) as total_usage,
                    GROUP_CONCAT(DISTINCT post_type) as post_types
                FROM {$this->table_name}
                GROUP BY widget_name
                ORDER BY total_usage DESC";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get content by widget
     */
    public function get_content_by_widget($widget_name) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT 
                post_id,
                post_type,
                post_title,
                widget_count,
                analysis_date
            FROM {$this->table_name}
            WHERE widget_name = %s
            ORDER BY widget_count DESC, analysis_date DESC",
            $widget_name
        );
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get widgets by content type
     */
    public function get_widgets_by_content_type($post_type) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT 
                widget_name,
                COUNT(DISTINCT post_id) as content_count,
                SUM(widget_count) as total_usage
            FROM {$this->table_name}
            WHERE post_type = %s
            GROUP BY widget_name
            ORDER BY total_usage DESC",
            $post_type
        );
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get content types statistics
     */
    public function get_content_types_stats() {
        global $wpdb;
        
        $sql = "SELECT 
                    post_type,
                    COUNT(DISTINCT post_id) as content_count,
                    COUNT(DISTINCT widget_name) as unique_widgets,
                    SUM(widget_count) as total_widgets
                FROM {$this->table_name}
                GROUP BY post_type
                ORDER BY content_count DESC";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get analysis summary
     */
    public function get_analysis_summary() {
        global $wpdb;
        
        $summary = array();
        
        // Total content analyzed
        $summary['total_content'] = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$this->table_name}");
        
        // Total unique widgets
        $summary['total_widgets'] = $wpdb->get_var("SELECT COUNT(DISTINCT widget_name) FROM {$this->table_name}");
        
        // Total widget instances
        $summary['total_instances'] = $wpdb->get_var("SELECT SUM(widget_count) FROM {$this->table_name}");
        
        // Content types count
        $summary['content_types'] = $wpdb->get_var("SELECT COUNT(DISTINCT post_type) FROM {$this->table_name}");
        
        // Last analysis date
        $summary['last_analysis'] = $wpdb->get_var("SELECT MAX(analysis_date) FROM {$this->table_name}");
        
        return $summary;
    }
    
    /**
     * Check if analysis data exists
     */
    public function has_analysis_data() {
        global $wpdb;
        return (bool) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
    }
    
    /**
     * Get all analysis data
     */
    public function get_all_analysis_data() {
        global $wpdb;
        
        $sql = "SELECT 
                    post_id,
                    post_type,
                    post_title,
                    widget_name,
                    widget_count,
                    analysis_date
                FROM {$this->table_name}
                ORDER BY analysis_date DESC, post_id ASC";
        
        return $wpdb->get_results($sql);
    }
} 