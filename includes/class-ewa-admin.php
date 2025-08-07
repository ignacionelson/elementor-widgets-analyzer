<?php
/**
 * Admin interface class
 */
class EWA_Admin {
    
    private $database;
    private $analyzer;
    
    public function __construct() {
        $this->database = new EWA_Database();
        $this->analyzer = new EWA_Analyzer();
    }
    
    /**
     * Render the admin page
     */
    public function render_page() {
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
        $summary = $this->database->get_analysis_summary();
        $has_data = $this->database->has_analysis_data();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Widgets Analyzer for Elementor', 'widgets-analyzer-for-elementor'); ?></h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=widgets-analyzer-for-elementor&tab=dashboard" 
                   class="nav-tab <?php echo $current_tab === 'dashboard' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Dashboard', 'widgets-analyzer-for-elementor'); ?>
                </a>
                <a href="?page=widgets-analyzer-for-elementor&tab=widgets" 
                   class="nav-tab <?php echo $current_tab === 'widgets' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Widget Statistics', 'widgets-analyzer-for-elementor'); ?>
                </a>
                <a href="?page=widgets-analyzer-for-elementor&tab=content-types" 
                   class="nav-tab <?php echo $current_tab === 'content-types' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Content Types', 'widgets-analyzer-for-elementor'); ?>
                </a>
                <a href="?page=widgets-analyzer-for-elementor&tab=charts" 
                   class="nav-tab <?php echo $current_tab === 'charts' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Charts & Graphs', 'widgets-analyzer-for-elementor'); ?>
                </a>
                <a href="?page=widgets-analyzer-for-elementor&tab=export" 
                   class="nav-tab <?php echo $current_tab === 'export' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Export Data', 'widgets-analyzer-for-elementor'); ?>
                </a>
            </nav>
            
            <div class="ewa-content">
                <?php
                switch ($current_tab) {
                    case 'dashboard':
                        $this->render_dashboard_tab($summary, $has_data);
                        break;
                    case 'widgets':
                        $this->render_widgets_tab();
                        break;
                    case 'content-types':
                        $this->render_content_types_tab();
                        break;
                    case 'charts':
                        $this->render_charts_tab($has_data);
                        break;
                    case 'export':
                        $this->render_export_tab($has_data);
                        break;
                }
                ?>
            </div>
        </div>
        
        <!-- Modal for widget details -->
        <div id="ewa-modal" class="ewa-modal" style="display: none;">
            <div class="ewa-modal-content">
                <span class="ewa-modal-close">&times;</span>
                <h3 id="ewa-modal-title"></h3>
                <div id="ewa-modal-body"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render dashboard tab
     */
    private function render_dashboard_tab($summary, $has_data) {
        ?>
        <div class="ewa-dashboard">
            <div class="ewa-summary-cards">
                <div class="ewa-card">
                    <h3><?php _e('Total Content Analyzed', 'widgets-analyzer-for-elementor'); ?></h3>
                    <div class="ewa-number"><?php echo $summary['total_content'] ?: 0; ?></div>
                </div>
                <div class="ewa-card">
                    <h3><?php _e('Unique Widgets Found', 'widgets-analyzer-for-elementor'); ?></h3>
                    <div class="ewa-number"><?php echo $summary['total_widgets'] ?: 0; ?></div>
                </div>
                <div class="ewa-card">
                    <h3><?php _e('Total Widget Instances', 'widgets-analyzer-for-elementor'); ?></h3>
                    <div class="ewa-number"><?php echo $summary['total_instances'] ?: 0; ?></div>
                </div>
                <div class="ewa-card">
                    <h3><?php _e('Content Types', 'widgets-analyzer-for-elementor'); ?></h3>
                    <div class="ewa-number"><?php echo $summary['content_types'] ?: 0; ?></div>
                </div>
            </div>
            
            <div class="ewa-actions">
                <button id="ewa-analyze-btn" class="button button-primary button-large">
                    <?php _e('Run Analysis', 'widgets-analyzer-for-elementor'); ?>
                </button>
                <?php if ($has_data): ?>
                    <button id="ewa-clear-btn" class="button button-secondary button-large">
                        <?php _e('Clear Data', 'widgets-analyzer-for-elementor'); ?>
                    </button>
                    <button id="ewa-export-btn" class="button button-secondary button-large">
                        <?php _e('Export Data', 'widgets-analyzer-for-elementor'); ?>
                    </button>
                <?php endif; ?>
            </div>
            
            <div id="ewa-progress" class="ewa-progress" style="display: none;">
                <div class="ewa-progress-bar">
                    <div class="ewa-progress-fill"></div>
                </div>
                <div class="ewa-progress-text"><?php _e('Analyzing content...', 'widgets-analyzer-for-elementor'); ?></div>
            </div>
            
            <?php if ($has_data && $summary['last_analysis']): ?>
                <div class="ewa-last-analysis">
                    <p><?php printf(__('Last analysis: %s', 'widgets-analyzer-for-elementor'), 
                        date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($summary['last_analysis']))); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($has_data): ?>
                <div class="ewa-dashboard-chart">
                    <h3><?php _e('Quick Overview Chart', 'widgets-analyzer-for-elementor'); ?></h3>
                    <div class="ewa-chart-wrapper" style="height: 250px;">
                        <canvas id="ewa-dashboard-overview-chart"></canvas>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render widgets tab
     */
    private function render_widgets_tab() {
        $used_widgets_stats = $this->database->get_widget_statistics();
        $all_registered_widgets = $this->analyzer->get_all_registered_widgets();
        $has_data = !empty($used_widgets_stats);

        $used_widget_names = array_map(function($widget) {
            return $widget->widget_name;
        }, $used_widgets_stats);

        $all_widgets = [];
        foreach ($used_widgets_stats as $widget) {
            $all_widgets[$widget->widget_name] = [
                'display_name' => $this->analyzer->get_widget_display_name($widget->widget_name),
                'name' => $widget->widget_name,
                'content_count' => $widget->content_count,
                'total_usage' => $widget->total_usage,
                'post_types' => str_replace(',', ', ', $widget->post_types),
                'status' => 'used'
            ];
        }

        foreach ($all_registered_widgets as $widget) {
            $widget_name = $widget->get_name();
            if (!in_array($widget_name, $used_widget_names)) {
                $all_widgets[$widget_name] = [
                    'display_name' => $widget->get_title(),
                    'name' => $widget_name,
                    'content_count' => 0,
                    'total_usage' => 0,
                    'post_types' => '—',
                    'status' => 'unused'
                ];
            }
        }

        ?>
        <div class="ewa-widgets-tab">
            <h2><?php _e('Widget Usage Statistics', 'widgets-analyzer-for-elementor'); ?></h2>
            
            <?php if (empty($all_widgets)): ?>
                <p><?php _e('No Elementor widgets found or analysis data available. Please run the analysis first.', 'widgets-analyzer-for-elementor'); ?></p>
            <?php else: ?>
                <div class="ewa-filters">
                    <div class="ewa-filter-group">
                        <label for="ewa-widget-search"><?php _e('Search Widgets:', 'widgets-analyzer-for-elementor'); ?></label>
                        <input type="text" id="ewa-widget-search" placeholder="<?php _e('Search by widget name...', 'widgets-analyzer-for-elementor'); ?>" />
                    </div>
                    <div class="ewa-filter-group">
                        <label for="ewa-widget-status-filter"><?php _e('Show:', 'widgets-analyzer-for-elementor'); ?></label>
                        <select id="ewa-widget-status-filter">
                            <option value="used" <?php selected($has_data, true); ?>><?php _e('Used Widgets', 'widgets-analyzer-for-elementor'); ?></option>
                            <option value="unused"><?php _e('Unused Widgets', 'widgets-analyzer-for-elementor'); ?></option>
                            <option value="all"><?php _e('All Widgets', 'widgets-analyzer-for-elementor'); ?></option>
                        </select>
                    </div>
                    <div class="ewa-filter-group">
                        <label for="ewa-widget-sort"><?php _e('Sort by:', 'widgets-analyzer-for-elementor'); ?></label>
                        <select id="ewa-widget-sort">
                            <option value="widget_name"><?php _e('Widget Name', 'widgets-analyzer-for-elementor'); ?></option>
                            <option value="content_count"><?php _e('Content Count', 'widgets-analyzer-for-elementor'); ?></option>
                            <option value="total_usage"><?php _e('Total Usage', 'widgets-analyzer-for-elementor'); ?></option>
                        </select>
                    </div>
                    <div class="ewa-filter-group">
                        <label for="ewa-widget-order"><?php _e('Order:', 'widgets-analyzer-for-elementor'); ?></label>
                        <select id="ewa-widget-order">
                            <option value="asc"><?php _e('Ascending', 'widgets-analyzer-for-elementor'); ?></option>
                            <option value="desc"><?php _e('Descending', 'widgets-analyzer-for-elementor'); ?></option>
                        </select>
                    </div>
                    <div class="ewa-filter-group">
                        <button id="ewa-widget-reset-filters" class="button"><?php _e('Reset Filters', 'widgets-analyzer-for-elementor'); ?></button>
                    </div>
                </div>
                
                <div class="ewa-table-container">
                    <table id="ewa-widgets-table" class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th class="ewa-sortable" data-sort="widget_name"><?php _e('Widget', 'widgets-analyzer-for-elementor'); ?> <span class="ewa-sort-indicator"></span></th>
                                <th class="ewa-sortable" data-sort="content_count"><?php _e('Content Count', 'widgets-analyzer-for-elementor'); ?> <span class="ewa-sort-indicator"></span></th>
                                <th class="ewa-sortable" data-sort="total_usage"><?php _e('Total Usage', 'widgets-analyzer-for-elementor'); ?> <span class="ewa-sort-indicator"></span></th>
                                <th><?php _e('Content Types', 'widgets-analyzer-for-elementor'); ?></th>
                                <th><?php _e('Actions', 'widgets-analyzer-for-elementor'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_widgets)): ?>
                                <tr>
                                    <td colspan="5"><?php _e('No widgets found.', 'widgets-analyzer-for-elementor'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($all_widgets as $widget): ?>
                                    <tr data-status="<?php echo esc_attr($widget['status']); ?>" style="<?php echo ($has_data && $widget['status'] === 'unused') ? 'display: none;' : ''; ?>">
                                        <td>
                                            <strong><?php echo esc_html($widget['display_name']); ?></strong>
                                            <br><small><?php echo esc_html($widget['name']); ?></small>
                                        </td>
                                        <td><?php echo esc_html($widget['content_count']); ?></td>
                                        <td><?php echo esc_html($widget['total_usage']); ?></td>
                                        <td><?php echo esc_html($widget['post_types']); ?></td>
                                        <td>
                                            <?php if ($widget['status'] === 'used'): ?>
                                                <button class="button button-small ewa-view-details" 
                                                        data-widget="<?php echo esc_attr($widget['name']); ?>">
                                                    <?php _e('View Details', 'widgets-analyzer-for-elementor'); ?>
                                                </button>
                                            <?php else: ?>
                                                <span class="ewa-unused-tag"><?php _e('Unused', 'widgets-analyzer-for-elementor'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render content types tab
     */
    private function render_content_types_tab() {
        $content_types_stats = $this->database->get_content_types_stats();
        ?>
        <div class="ewa-content-types-tab">
            <h2><?php _e('Content Types Analysis', 'widgets-analyzer-for-elementor'); ?></h2>
            
            <?php if (empty($content_types_stats)): ?>
                <p><?php _e('No analysis data available. Please run the analysis first.', 'widgets-analyzer-for-elementor'); ?></p>
            <?php else: ?>
                <div class="ewa-filters">
                    <div class="ewa-filter-group">
                        <label for="ewa-content-type-search"><?php _e('Search Content Types:', 'widgets-analyzer-for-elementor'); ?></label>
                        <input type="text" id="ewa-content-type-search" placeholder="<?php _e('Search by content type...', 'widgets-analyzer-for-elementor'); ?>" />
                    </div>
                    <div class="ewa-filter-group">
                        <label for="ewa-content-type-sort"><?php _e('Sort by:', 'widgets-analyzer-for-elementor'); ?></label>
                        <select id="ewa-content-type-sort">
                            <option value="post_type"><?php _e('Content Type', 'widgets-analyzer-for-elementor'); ?></option>
                            <option value="content_count"><?php _e('Content Count', 'widgets-analyzer-for-elementor'); ?></option>
                            <option value="unique_widgets"><?php _e('Unique Widgets', 'widgets-analyzer-for-elementor'); ?></option>
                            <option value="total_widgets"><?php _e('Total Widgets', 'widgets-analyzer-for-elementor'); ?></option>
                        </select>
                    </div>
                    <div class="ewa-filter-group">
                        <label for="ewa-content-type-order"><?php _e('Order:', 'widgets-analyzer-for-elementor'); ?></label>
                        <select id="ewa-content-type-order">
                            <option value="asc"><?php _e('Ascending', 'widgets-analyzer-for-elementor'); ?></option>
                            <option value="desc"><?php _e('Descending', 'widgets-analyzer-for-elementor'); ?></option>
                        </select>
                    </div>
                    <div class="ewa-filter-group">
                        <button id="ewa-content-type-reset-filters" class="button"><?php _e('Reset Filters', 'widgets-analyzer-for-elementor'); ?></button>
                    </div>
                </div>
                
                <div class="ewa-table-container">
                    <table id="ewa-content-types-table" class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th class="ewa-sortable" data-sort="post_type"><?php _e('Content Type', 'widgets-analyzer-for-elementor'); ?> <span class="ewa-sort-indicator"></span></th>
                                <th class="ewa-sortable" data-sort="content_count"><?php _e('Content Count', 'widgets-analyzer-for-elementor'); ?> <span class="ewa-sort-indicator"></span></th>
                                <th class="ewa-sortable" data-sort="unique_widgets"><?php _e('Unique Widgets', 'widgets-analyzer-for-elementor'); ?> <span class="ewa-sort-indicator"></span></th>
                                <th class="ewa-sortable" data-sort="total_widgets"><?php _e('Total Widgets', 'widgets-analyzer-for-elementor'); ?> <span class="ewa-sort-indicator"></span></th>
                                <th><?php _e('Actions', 'widgets-analyzer-for-elementor'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($content_types_stats as $content_type): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($this->analyzer->get_content_type_display_name($content_type->post_type)); ?></strong>
                                        <br><small><?php echo esc_html($content_type->post_type); ?></small>
                                    </td>
                                    <td><?php echo esc_html($content_type->content_count); ?></td>
                                    <td><?php echo esc_html($content_type->unique_widgets); ?></td>
                                    <td><?php echo esc_html($content_type->total_widgets); ?></td>
                                    <td>
                                        <button class="button button-small ewa-view-content-type" 
                                                data-post-type="<?php echo esc_attr($content_type->post_type); ?>">
                                            <?php _e('View Widgets', 'widgets-analyzer-for-elementor'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render export tab
     */
    private function render_export_tab($has_data) {
        ?>
        <div class="ewa-export-tab">
            <h2><?php _e('Export Analysis Data', 'widgets-analyzer-for-elementor'); ?></h2>
            
            <?php if (!$has_data): ?>
                <div class="ewa-notice ewa-notice-error">
                    <p><?php _e('No analysis data available. Please run the analysis first.', 'widgets-analyzer-for-elementor'); ?></p>
                </div>
            <?php else: ?>
                <div class="ewa-export-options">
                    <h3><?php _e('Export Options', 'widgets-analyzer-for-elementor'); ?></h3>
                    
                    <div class="ewa-export-section">
                        <h4><?php _e('Export Format', 'widgets-analyzer-for-elementor'); ?></h4>
                        <div class="ewa-export-format">
                            <label>
                                <input type="radio" name="export_format" value="csv" checked>
                                <?php _e('CSV (Comma Separated Values)', 'widgets-analyzer-for-elementor'); ?>
                            </label>
                            <label>
                                <input type="radio" name="export_format" value="json">
                                <?php _e('JSON (JavaScript Object Notation)', 'widgets-analyzer-for-elementor'); ?>
                            </label>
                        </div>
                    </div>
                    
                    <div class="ewa-export-section">
                        <h4><?php _e('Export Type', 'widgets-analyzer-for-elementor'); ?></h4>
                        <div class="ewa-export-type">
                            <label>
                                <input type="radio" name="export_type" value="all" checked>
                                <?php _e('All Data (Complete analysis results)', 'widgets-analyzer-for-elementor'); ?>
                            </label>
                            <label>
                                <input type="radio" name="export_type" value="widgets">
                                <?php _e('Widget Statistics Only', 'widgets-analyzer-for-elementor'); ?>
                            </label>
                            <label>
                                <input type="radio" name="export_type" value="content_types">
                                <?php _e('Content Types Analysis Only', 'widgets-analyzer-for-elementor'); ?>
                            </label>
                        </div>
                    </div>
                    
                    <div class="ewa-export-actions">
                        <button id="ewa-export-download-btn" class="button button-primary button-large">
                            <?php _e('Download Export', 'widgets-analyzer-for-elementor'); ?>
                        </button>
                        <div id="ewa-export-status" class="ewa-export-status" style="display: none;">
                            <span class="ewa-export-spinner"></span>
                            <span class="ewa-export-text"><?php _e('Preparing export...', 'widgets-analyzer-for-elementor'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="ewa-export-info">
                    <h4><?php _e('Export Information', 'widgets-analyzer-for-elementor'); ?></h4>
                    <ul>
                        <li><strong><?php _e('CSV Format:', 'widgets-analyzer-for-elementor'); ?></strong> <?php _e('Compatible with Excel, Google Sheets, and other spreadsheet applications.', 'widgets-analyzer-for-elementor'); ?></li>
                        <li><strong><?php _e('JSON Format:', 'widgets-analyzer-for-elementor'); ?></strong> <?php _e('Suitable for data processing, APIs, and custom applications.', 'widgets-analyzer-for-elementor'); ?></li>
                        <li><strong><?php _e('All Data:', 'widgets-analyzer-for-elementor'); ?></strong> <?php _e('Complete analysis results including all content and widget details.', 'widgets-analyzer-for-elementor'); ?></li>
                        <li><strong><?php _e('Widget Statistics:', 'widgets-analyzer-for-elementor'); ?></strong> <?php _e('Summary of widget usage across all content types.', 'widgets-analyzer-for-elementor'); ?></li>
                        <li><strong><?php _e('Content Types:', 'widgets-analyzer-for-elementor'); ?></strong> <?php _e('Analysis breakdown by post type (posts, pages, custom post types).', 'widgets-analyzer-for-elementor'); ?></li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render charts tab
     */
    private function render_charts_tab($has_data) {
        ?>
        <div class="ewa-charts-tab">
            <h2><?php _e('Charts & Graphs', 'widgets-analyzer-for-elementor'); ?></h2>
            
            <?php if (!$has_data): ?>
                <div class="ewa-notice ewa-notice-error">
                    <p><?php _e('No analysis data available. Please run the analysis first.', 'widgets-analyzer-for-elementor'); ?></p>
                </div>
            <?php else: ?>
                <div class="ewa-charts-container">
                    <div class="ewa-chart-section">
                        <h3><?php _e('Widget Usage Overview', 'widgets-analyzer-for-elementor'); ?></h3>
                        <div class="ewa-chart-wrapper">
                            <canvas id="ewa-widget-usage-chart"></canvas>
                        </div>
                    </div>
                    
                    <div class="ewa-chart-section">
                        <h3><?php _e('Content Types Distribution', 'widgets-analyzer-for-elementor'); ?></h3>
                        <div class="ewa-chart-wrapper">
                            <canvas id="ewa-content-types-chart"></canvas>
                        </div>
                    </div>
                    
                    <div class="ewa-chart-section">
                        <h3><?php _e('Widget Distribution (Top 10)', 'widgets-analyzer-for-elementor'); ?></h3>
                        <div class="ewa-chart-wrapper">
                            <canvas id="ewa-widget-distribution-chart"></canvas>
                        </div>
                    </div>
                    
                    <div class="ewa-chart-section">
                        <h3><?php _e('Top Widgets Bar Chart', 'widgets-analyzer-for-elementor'); ?></h3>
                        <div class="ewa-chart-wrapper">
                            <canvas id="ewa-top-widgets-chart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="ewa-charts-controls">
                    <button id="ewa-refresh-charts" class="button button-secondary">
                        <?php _e('Refresh Charts', 'widgets-analyzer-for-elementor'); ?>
                    </button>
                    <div id="ewa-charts-loading" class="ewa-charts-loading" style="display: none;">
                        <span class="ewa-spinner"></span>
                        <span><?php _e('Loading charts...', 'widgets-analyzer-for-elementor'); ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
} 