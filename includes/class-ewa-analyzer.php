<?php
/**
 * Content analyzer class
 */
class EWA_Analyzer {
    
    private $database;
    
    public function __construct() {
        $this->database = new EWA_Database();
    }
    
    /**
     * Analyze all content types
     */
    public function analyze_all_content() {
        // Clear existing data
        $this->database->clear_analysis_data();
        
        $results = array(
            'total_content' => 0,
            'total_widgets' => 0,
            'content_types' => array()
        );
        
        // Get all post types
        $post_types = $this->get_all_post_types();
        
        foreach ($post_types as $post_type) {
            $content_results = $this->analyze_content_type($post_type);
            $results['content_types'][$post_type] = $content_results;
            $results['total_content'] += $content_results['content_count'];
            $results['total_widgets'] += $content_results['widget_count'];
        }
        
        return $results;
    }
    
    /**
     * Get all post types including custom post types
     */
    private function get_all_post_types() {
        $post_types = get_post_types(array('public' => true), 'names');
        
        // Add common post types that might be private
        $additional_types = array('elementor_library', 'elementor-hf', 'elementor_font');
        foreach ($additional_types as $type) {
            if (post_type_exists($type)) {
                $post_types[] = $type;
            }
        }
        
        return array_unique($post_types);
    }
    
    /**
     * Analyze a specific content type
     */
    private function analyze_content_type($post_type) {
        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_elementor_edit_mode',
                    'value' => 'builder',
                    'compare' => '='
                )
            )
        );
        
        $query = new WP_Query($args);
        $content_count = 0;
        $widget_count = 0;
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $post_title = get_the_title();
                
                $widgets = $this->analyze_single_content($post_id, $post_title, $post_type);
                $content_count++;
                $widget_count += count($widgets);
            }
        }
        
        wp_reset_postdata();
        
        return array(
            'content_count' => $content_count,
            'widget_count' => $widget_count
        );
    }
    
    /**
     * Analyze a single content item
     */
    private function analyze_single_content($post_id, $post_title, $post_type) {
        $widgets = array();
        
        // Get Elementor data
        $elementor_data = get_post_meta($post_id, '_elementor_data', true);
        
        if (!empty($elementor_data)) {
            $elementor_data = json_decode($elementor_data, true);
            if (is_array($elementor_data)) {
                $widgets = $this->extract_widgets_from_data($elementor_data);
                
                // Store widgets in database
                foreach ($widgets as $widget_name => $count) {
                    $this->database->insert_widget_data($post_id, $post_type, $post_title, $widget_name, $count);
                }
            }
        }
        
        return $widgets;
    }
    
    /**
     * Extract widgets from Elementor data recursively
     */
    private function extract_widgets_from_data($data, $widgets = array()) {
        if (!is_array($data)) {
            return $widgets;
        }
        
        foreach ($data as $element) {
            if (isset($element['widgetType'])) {
                $widget_type = $element['widgetType'];
                if (!isset($widgets[$widget_type])) {
                    $widgets[$widget_type] = 0;
                }
                $widgets[$widget_type]++;
            }
            
            // Check for nested elements
            if (isset($element['elements']) && is_array($element['elements'])) {
                $widgets = $this->extract_widgets_from_data($element['elements'], $widgets);
            }
        }
        
        return $widgets;
    }
    
    /**
     * Get widget display name
     */
    public function get_widget_display_name($widget_type) {
        // Try to get widget instance
        if (class_exists('\Elementor\Plugin')) {
            $widget_manager = \Elementor\Plugin::$instance->widgets_manager;
            $widget = $widget_manager->get_widget_types($widget_type);
            
            if ($widget) {
                return $widget->get_title();
            }
        }
        
        // Fallback to formatted widget type
        return $this->format_widget_name($widget_type);
    }
    
    /**
     * Format widget name for display
     */
    private function format_widget_name($widget_type) {
        // Remove common prefixes
        $name = str_replace(array('widget-', 'elementor-'), '', $widget_type);
        
        // Convert to title case
        $name = str_replace(array('-', '_'), ' ', $name);
        $name = ucwords($name);
        
        return $name;
    }
    
    /**
     * Get content type display name
     */
    public function get_content_type_display_name($post_type) {
        $post_type_obj = get_post_type_object($post_type);
        
        if ($post_type_obj) {
            return $post_type_obj->labels->name;
        }
        
        // Fallback for custom post types
        return ucwords(str_replace(array('-', '_'), ' ', $post_type));
    }

    /**
     * Get all registered Elementor widgets.
     * @return \Elementor\Widget_Base[]
     */
    public function get_all_registered_widgets() {
        if (!class_exists('\Elementor\Plugin')) {
            return [];
        }
        
        $widget_manager = \Elementor\Plugin::$instance->widgets_manager;
        return $widget_manager->get_widget_types();
    }
    
    /**
     * Get analysis progress
     */
    public function get_analysis_progress() {
        $post_types = $this->get_all_post_types();
        $total_posts = 0;
        $analyzed_posts = 0;
        
        foreach ($post_types as $post_type) {
            $args = array(
                'post_type' => $post_type,
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_elementor_edit_mode',
                        'value' => 'builder',
                        'compare' => '='
                    )
                )
            );
            
            $query = new WP_Query($args);
            $total_posts += $query->found_posts;
            
            // Count analyzed posts from database
            global $wpdb;
            $table_name = $wpdb->prefix . 'ewa_widget_analysis';
            $analyzed = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT post_id) FROM {$table_name} WHERE post_type = %s",
                $post_type
            ));
            $analyzed_posts += (int) $analyzed;
        }
        
        return array(
            'total' => $total_posts,
            'analyzed' => $analyzed_posts,
            'percentage' => $total_posts > 0 ? round(($analyzed_posts / $total_posts) * 100, 2) : 0
        );
    }
} 