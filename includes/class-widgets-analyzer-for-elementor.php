<?php
/**
 * Main plugin class
 */
class Widgets_Analyzer_For_Elementor {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_ewa_analyze_content', array($this, 'ajax_analyze_content'));
        add_action('wp_ajax_ewa_get_widget_stats', array($this, 'ajax_get_widget_stats'));
        add_action('wp_ajax_ewa_get_content_details', array($this, 'ajax_get_content_details'));
        add_action('wp_ajax_ewa_clear_data', array($this, 'ajax_clear_data'));
        add_action('wp_ajax_ewa_get_content_type_widgets', array($this, 'ajax_get_content_type_widgets'));
        add_action('wp_ajax_ewa_export_data', array($this, 'ajax_export_data'));
        add_action('wp_ajax_ewa_get_chart_data', array($this, 'ajax_get_chart_data'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Widgets Analyzer for Elementor', 'widgets-analyzer-for-elementor'),
            __('Widgets Analyzer', 'widgets-analyzer-for-elementor'),
            'manage_options',
            'widgets-analyzer-for-elementor',
            array($this, 'admin_page'),
            'dashicons-chart-area',
            30
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_widgets-analyzer-for-elementor') {
            return;
        }
        
        // Enqueue Chart.js locally
        wp_enqueue_script(
            'chartjs',
            EWA_PLUGIN_URL . 'assets/js/chart.js',
            array(),
            '4.4.0',
            true
        );
        
        wp_enqueue_script(
            'ewa-admin-js',
            EWA_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'chartjs'),
            EWA_PLUGIN_VERSION,
            true
        );
        
        wp_enqueue_style(
            'ewa-admin-css',
            EWA_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            EWA_PLUGIN_VERSION
        );
        
        wp_localize_script('ewa-admin-js', 'ewa_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ewa_nonce'),
            'strings' => array(
                'analyzing' => __('Analyzing...', 'widgets-analyzer-for-elementor'),
                'analysis_complete' => __('Analysis complete!', 'widgets-analyzer-for-elementor'),
                'error' => __('An error occurred.', 'widgets-analyzer-for-elementor')
            )
        ));
    }
    
    /**
     * Admin page callback
     */
    public function admin_page() {
        $admin = new EWA_Admin();
        $admin->render_page();
    }
    
    /**
     * AJAX handler for content analysis
     */
    public function ajax_analyze_content() {
        check_ajax_referer('ewa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'widgets-analyzer-for-elementor'));
        }
        
        $analyzer = new EWA_Analyzer();
        $result = $analyzer->analyze_all_content();
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX handler for widget statistics
     */
    public function ajax_get_widget_stats() {
        check_ajax_referer('ewa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'widgets-analyzer-for-elementor'));
        }
        
        $database = new EWA_Database();
        $stats = $database->get_widget_statistics();
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX handler for content details
     */
    public function ajax_get_content_details() {
        check_ajax_referer('ewa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'widgets-analyzer-for-elementor'));
        }
        
        $widget_name = sanitize_text_field($_POST['widget_name']);
        $database = new EWA_Database();
        $details = $database->get_content_by_widget($widget_name);
        
        wp_send_json_success($details);
    }
    
    /**
     * AJAX handler for clearing data
     */
    public function ajax_clear_data() {
        check_ajax_referer('ewa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'widgets-analyzer-for-elementor'));
        }
        
        $database = new EWA_Database();
        $database->clear_analysis_data();
        
        wp_send_json_success();
    }
    
    /**
     * AJAX handler for getting content type widgets
     */
    public function ajax_get_content_type_widgets() {
        check_ajax_referer('ewa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'widgets-analyzer-for-elementor'));
        }
        
        $post_type = sanitize_text_field($_POST['post_type']);
        $database = new EWA_Database();
        $widgets = $database->get_widgets_by_content_type($post_type);
        
        wp_send_json_success($widgets);
    }
    
    /**
     * AJAX handler for exporting data
     */
    public function ajax_export_data() {
        check_ajax_referer('ewa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'widgets-analyzer-for-elementor'));
        }
        
        $format = sanitize_text_field($_POST['format']);
        $type = sanitize_text_field($_POST['type']);
        
        $database = new EWA_Database();
        $analyzer = new EWA_Analyzer();
        
        $data = array();
        $filename = 'ewa-export-' . date('Y-m-d-H-i-s');
        
        switch ($type) {
            case 'all':
                $data = $database->get_all_analysis_data();
                $filename .= '-all-data';
                break;
            case 'widgets':
                $data = $database->get_widget_statistics();
                $filename .= '-widget-statistics';
                break;
            case 'content_types':
                $data = $database->get_content_types_stats();
                $filename .= '-content-types';
                break;
            default:
                wp_send_json_error(__('Invalid export type.', 'widgets-analyzer-for-elementor'));
        }
        
        if (empty($data)) {
            wp_send_json_error(__('No data available for export.', 'widgets-analyzer-for-elementor'));
        }
        
        // Process data for export
        $export_data = $this->process_export_data($data, $type, $analyzer);
        
        if ($format === 'csv') {
            $this->export_as_csv($export_data, $filename);
        } else {
            $this->export_as_json($export_data, $filename);
        }
    }
    
    /**
     * Process data for export
     */
    private function process_export_data($data, $type, $analyzer) {
        $processed_data = array();
        
        switch ($type) {
            case 'all':
                foreach ($data as $row) {
                    $processed_data[] = array(
                        'post_id' => $row->post_id,
                        'post_type' => $row->post_type,
                        'post_title' => $row->post_title,
                        'widget_name' => $row->widget_name,
                        'widget_display_name' => $analyzer->get_widget_display_name($row->widget_name),
                        'widget_count' => $row->widget_count,
                        'analysis_date' => $row->analysis_date
                    );
                }
                break;
            case 'widgets':
                foreach ($data as $row) {
                    $processed_data[] = array(
                        'widget_name' => $row->widget_name,
                        'widget_display_name' => $analyzer->get_widget_display_name($row->widget_name),
                        'content_count' => $row->content_count,
                        'total_usage' => $row->total_usage,
                        'post_types' => $row->post_types
                    );
                }
                break;
            case 'content_types':
                foreach ($data as $row) {
                    $processed_data[] = array(
                        'post_type' => $row->post_type,
                        'content_type_display_name' => $analyzer->get_content_type_display_name($row->post_type),
                        'content_count' => $row->content_count,
                        'unique_widgets' => $row->unique_widgets,
                        'total_widgets' => $row->total_widgets
                    );
                }
                break;
        }
        
        return $processed_data;
    }
    
    /**
     * Export data as CSV
     */
    private function export_as_csv($data, $filename) {
        if (empty($data)) {
            wp_send_json_error(__('No data to export.', 'widgets-analyzer-for-elementor'));
        }
        
        // Get headers from first row
        $headers = array_keys($data[0]);
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export data as JSON
     */
    private function export_as_json($data, $filename) {
        if (empty($data)) {
            wp_send_json_error(__('No data to export.', 'widgets-analyzer-for-elementor'));
        }
        
        $json_data = array(
            'export_info' => array(
                'export_date' => current_time('Y-m-d H:i:s'),
                'total_records' => count($data),
                'export_type' => 'widgets_analyzer_for_elementor'
            ),
            'data' => $data
        );
        
        // Set headers for JSON download
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.json"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * AJAX handler for chart data
     */
    public function ajax_get_chart_data() {
        check_ajax_referer('ewa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'widgets-analyzer-for-elementor'));
        }
        
        $chart_type = sanitize_text_field($_POST['chart_type']);
        $database = new EWA_Database();
        $analyzer = new EWA_Analyzer();
        
        $data = array();
        
        switch ($chart_type) {
            case 'widget_usage':
                $data = $this->get_widget_usage_chart_data($database, $analyzer);
                break;
            case 'content_types':
                $data = $this->get_content_types_chart_data($database, $analyzer);
                break;
            case 'widget_distribution':
                $data = $this->get_widget_distribution_chart_data($database, $analyzer);
                break;
            case 'top_widgets':
                $data = $this->get_top_widgets_chart_data($database, $analyzer);
                break;
            default:
                wp_send_json_error(__('Invalid chart type.', 'widgets-analyzer-for-elementor'));
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Get widget usage chart data
     */
    private function get_widget_usage_chart_data($database, $analyzer) {
        $widget_stats = $database->get_widget_statistics();
        
        $labels = array();
        $data = array();
        $colors = array();
        
        foreach ($widget_stats as $widget) {
            $labels[] = $analyzer->get_widget_display_name($widget->widget_name);
            $data[] = $widget->total_usage;
            $colors[] = $this->get_random_color();
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Widget Usage', 'widgets-analyzer-for-elementor'),
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 1
                )
            )
        );
    }
    
    /**
     * Get content types chart data
     */
    private function get_content_types_chart_data($database, $analyzer) {
        $content_types_stats = $database->get_content_types_stats();
        
        $labels = array();
        $data = array();
        $colors = array();
        
        foreach ($content_types_stats as $content_type) {
            $labels[] = $analyzer->get_content_type_display_name($content_type->post_type);
            $data[] = $content_type->content_count;
            $colors[] = $this->get_random_color();
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Content Count', 'widgets-analyzer-for-elementor'),
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 1
                )
            )
        );
    }
    
    /**
     * Get widget distribution chart data
     */
    private function get_widget_distribution_chart_data($database, $analyzer) {
        $widget_stats = $database->get_widget_statistics();
        
        // Get top 10 widgets by usage
        $top_widgets = array_slice($widget_stats, 0, 10);
        $other_usage = 0;
        
        if (count($widget_stats) > 10) {
            for ($i = 10; $i < count($widget_stats); $i++) {
                $other_usage += $widget_stats[$i]->total_usage;
            }
        }
        
        $labels = array();
        $data = array();
        $colors = array();
        
        foreach ($top_widgets as $widget) {
            $labels[] = $analyzer->get_widget_display_name($widget->widget_name);
            $data[] = $widget->total_usage;
            $colors[] = $this->get_random_color();
        }
        
        if ($other_usage > 0) {
            $labels[] = __('Others', 'widgets-analyzer-for-elementor');
            $data[] = $other_usage;
            $colors[] = '#cccccc';
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Widget Distribution', 'widgets-analyzer-for-elementor'),
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 1
                )
            )
        );
    }
    
    /**
     * Get top widgets chart data
     */
    private function get_top_widgets_chart_data($database, $analyzer) {
        $widget_stats = $database->get_widget_statistics();
        
        // Get top 15 widgets by usage
        $top_widgets = array_slice($widget_stats, 0, 15);
        
        $labels = array();
        $data = array();
        $colors = array();
        
        foreach ($top_widgets as $widget) {
            $labels[] = $analyzer->get_widget_display_name($widget->widget_name);
            $data[] = $widget->total_usage;
            $colors[] = $this->get_random_color();
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Top Widgets', 'widgets-analyzer-for-elementor'),
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 1
                )
            )
        );
    }
    
    /**
     * Generate random color for charts
     */
    private function get_random_color() {
        $colors = array(
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
            '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384',
            '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
        );
        
        return $colors[array_rand($colors)];
    }
} 