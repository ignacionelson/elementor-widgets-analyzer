jQuery(document).ready(function($) {
    
    // Analyze button click
    $('#ewa-analyze-btn').on('click', function() {
        var $btn = $(this);
        var $progress = $('#ewa-progress');
        
        $btn.prop('disabled', true).text(ewa_ajax.strings.analyzing);
        $progress.show();
        
        $.ajax({
            url: ewa_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ewa_analyze_content',
                nonce: ewa_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(ewa_ajax.strings.error);
                }
            },
            error: function() {
                alert(ewa_ajax.strings.error);
            },
            complete: function() {
                $btn.prop('disabled', false).text('Run Analysis');
                $progress.hide();
            }
        });
    });
    
    // Clear data button click
    $('#ewa-clear-btn').on('click', function() {
        if (confirm('Are you sure you want to clear all analysis data?')) {
            var $btn = $(this);
            $btn.prop('disabled', true).text('Clearing...');
            
            $.ajax({
                url: ewa_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ewa_clear_data',
                    nonce: ewa_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(ewa_ajax.strings.error);
                    }
                },
                error: function() {
                    alert(ewa_ajax.strings.error);
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Clear Data');
                }
            });
        }
    });
    
    // Export button click (dashboard)
    $('#ewa-export-btn').on('click', function() {
        // Redirect to export tab
        window.location.href = window.location.href.replace(/&tab=[^&]*/, '') + '&tab=export';
    });
    
    // Export download button click
    $('#ewa-export-download-btn').on('click', function() {
        var $btn = $(this);
        var $status = $('#ewa-export-status');
        var format = $('input[name="export_format"]:checked').val();
        var type = $('input[name="export_type"]:checked').val();
        
        if (!format || !type) {
            alert('Please select both export format and type.');
            return;
        }
        
        $btn.prop('disabled', true);
        $status.show();
        
        // Create a form to submit the export request
        var $form = $('<form>', {
            method: 'POST',
            action: ewa_ajax.ajax_url,
            target: '_blank'
        });
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'action',
            value: 'ewa_export_data'
        }));
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'nonce',
            value: ewa_ajax.nonce
        }));
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'format',
            value: format
        }));
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'type',
            value: type
        }));
        
        $('body').append($form);
        $form.submit();
        $form.remove();
        
        // Reset button and status after a short delay
        setTimeout(function() {
            $btn.prop('disabled', false);
            $status.hide();
        }, 2000);
    });
    
    // View widget details
    $('.ewa-view-details').on('click', function() {
        var widgetName = $(this).data('widget');
        var $modal = $('#ewa-modal');
        var $title = $('#ewa-modal-title');
        var $body = $('#ewa-modal-body');
        
        $title.text('Loading...');
        $body.html('<p>Loading content details...</p>');
        $modal.show();
        
        $.ajax({
            url: ewa_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ewa_get_content_details',
                widget_name: widgetName,
                nonce: ewa_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    var html = '<table class="wp-list-table widefat fixed striped">';
                    html += '<thead><tr><th>Content Title</th><th>Content Type</th><th>Widget Count</th><th>Analysis Date</th></tr></thead>';
                    html += '<tbody>';
                    
                    data.forEach(function(item) {
                        html += '<tr>';
                        html += '<td><a href="' + getEditUrl(item.post_id, item.post_type) + '" target="_blank">' + item.post_title + '</a></td>';
                        html += '<td>' + getContentTypeName(item.post_type) + '</td>';
                        html += '<td>' + item.widget_count + '</td>';
                        html += '<td>' + formatDate(item.analysis_date) + '</td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table>';
                    
                    $title.text('Content using "' + widgetName + '"');
                    $body.html(html);
                } else {
                    $title.text('Error');
                    $body.html('<p>Failed to load content details.</p>');
                }
            },
            error: function() {
                $title.text('Error');
                $body.html('<p>Failed to load content details.</p>');
            }
        });
    });
    
    // View content type widgets
    $('.ewa-view-content-type').on('click', function() {
        var postType = $(this).data('post-type');
        var $modal = $('#ewa-modal');
        var $title = $('#ewa-modal-title');
        var $body = $('#ewa-modal-body');
        
        $title.text('Loading...');
        $body.html('<p>Loading widget details...</p>');
        $modal.show();
        
        $.ajax({
            url: ewa_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ewa_get_content_type_widgets',
                post_type: postType,
                nonce: ewa_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    var html = '<table class="wp-list-table widefat fixed striped">';
                    html += '<thead><tr><th>Widget</th><th>Content Count</th><th>Total Usage</th></tr></thead>';
                    html += '<tbody>';
                    
                    data.forEach(function(item) {
                        html += '<tr>';
                        html += '<td>' + item.widget_name + '</td>';
                        html += '<td>' + item.content_count + '</td>';
                        html += '<td>' + item.total_usage + '</td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table>';
                    
                    $title.text('Widgets in "' + getContentTypeName(postType) + '"');
                    $body.html(html);
                } else {
                    $title.text('Error');
                    $body.html('<p>Failed to load widget details.</p>');
                }
            },
            error: function() {
                $title.text('Error');
                $body.html('<p>Failed to load widget details.</p>');
            }
        });
    });
    
    // Modal close
    $('.ewa-modal-close').on('click', function() {
        $('#ewa-modal').hide();
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(event) {
        var modal = $('#ewa-modal');
        if (event.target === modal[0]) {
            modal.hide();
        }
    });
    
    // Helper functions
    function getEditUrl(postId, postType) {
        // Construct proper WordPress admin edit URL
        var adminUrl = ajaxurl.replace('admin-ajax.php', '');
        return adminUrl + 'post.php?action=edit&post=' + postId + '&post_type=' + postType;
    }
    
    function getContentTypeName(postType) {
        var names = {
            'post': 'Post',
            'page': 'Page',
            'elementor_library': 'Elementor Template',
            'elementor-hf': 'Elementor Header/Footer'
        };
        return names[postType] || postType;
    }
    
    function formatDate(dateString) {
        var date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }
    
    // Charts functionality
    var ewaCharts = {};
    
    // Initialize charts when on charts tab
    if (window.location.search.includes('tab=charts')) {
        $(document).ready(function() {
            initializeCharts();
        });
    }
    
    // Initialize dashboard chart
    if (window.location.search.includes('tab=dashboard') || !window.location.search.includes('tab=')) {
        $(document).ready(function() {
            if ($('#ewa-dashboard-overview-chart').length > 0) {
                loadChartData('top_widgets', 'ewa-dashboard-overview-chart', 'bar');
            }
        });
    }
    
    // Initialize all charts
    function initializeCharts() {
        loadChartData('widget_usage', 'ewa-widget-usage-chart', 'bar');
        loadChartData('content_types', 'ewa-content-types-chart', 'doughnut');
        loadChartData('widget_distribution', 'ewa-widget-distribution-chart', 'pie');
        loadChartData('top_widgets', 'ewa-top-widgets-chart', 'bar');
    }
    
    // Load chart data from server
    function loadChartData(chartType, canvasId, chartTypeName) {
        var $canvas = $('#' + canvasId);
        if ($canvas.length === 0) return;
        
        var $loading = $('#ewa-charts-loading');
        $loading.show();
        
        $.ajax({
            url: ewa_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ewa_get_chart_data',
                chart_type: chartType,
                nonce: ewa_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    createChart(canvasId, chartTypeName, response.data);
                } else {
                    console.error('Failed to load chart data:', response.data);
                }
            },
            error: function() {
                console.error('Failed to load chart data');
            },
            complete: function() {
                $loading.hide();
            }
        });
    }
    
    // Create chart using Chart.js
    function createChart(canvasId, type, data) {
        var ctx = document.getElementById(canvasId);
        if (!ctx) return;
        
        // Destroy existing chart if it exists
        if (ewaCharts[canvasId]) {
            ewaCharts[canvasId].destroy();
        }
        
        var config = {
            type: type,
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y || context.parsed;
                                return label;
                            }
                        }
                    }
                },
                scales: type === 'bar' ? {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                } : {}
            }
        };
        
        ewaCharts[canvasId] = new Chart(ctx, config);
    }
    
    // Refresh charts button
    $('#ewa-refresh-charts').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Refreshing...');
        
        // Destroy all existing charts
        Object.keys(ewaCharts).forEach(function(canvasId) {
            if (ewaCharts[canvasId]) {
                ewaCharts[canvasId].destroy();
            }
        });
        ewaCharts = {};
        
        // Reinitialize charts
        setTimeout(function() {
            initializeCharts();
            $btn.prop('disabled', false).text('Refresh Charts');
        }, 500);
    });
    
    // Initialize sorting and filtering for Widget Statistics tab
    if (window.location.search.includes('tab=widgets')) {
        initializeWidgetsTable();
    }
    
    // Initialize sorting and filtering for Content Types tab
    if (window.location.search.includes('tab=content-types')) {
        initializeContentTypesTable();
    }
    
    // Also initialize when tabs are switched via JavaScript
    $(document).on('click', '.nav-tab', function() {
        setTimeout(function() {
            if ($('#ewa-widgets-table').length && !$('#ewa-widgets-table').data('initialized')) {
                initializeWidgetsTable();
            }
            if ($('#ewa-content-types-table').length && !$('#ewa-content-types-table').data('initialized')) {
                initializeContentTypesTable();
            }
        }, 100);
    });

    // Widget Statistics Table Sorting and Filtering
    function initializeWidgetsTable() {
        var $table = $('#ewa-widgets-table');
        var $search = $('#ewa-widget-search');
        var $sort = $('#ewa-widget-sort');
        var $order = $('#ewa-widget-order');
        var $reset = $('#ewa-widget-reset-filters');
        
        console.log('Initializing widgets table:', {
            tableExists: $table.length,
            searchExists: $search.length,
            sortExists: $sort.length,
            orderExists: $order.length,
            resetExists: $reset.length,
            alreadyInitialized: $table.data('initialized')
        });
        
        // Prevent double initialization
        if ($table.data('initialized')) {
            return;
        }
        
        var originalData = [];
        var filteredData = [];
        
        // Store original data
        $table.find('tbody tr').each(function() {
            var $row = $(this);
            var rowData = {
                element: $row,
                widget_name: $row.find('td:first strong').text().toLowerCase(),
                widget_name_raw: $row.find('td:first strong').text(),
                content_count: parseInt($row.find('td:nth-child(2)').text()) || 0,
                total_usage: parseInt($row.find('td:nth-child(3)').text()) || 0,
                post_types: $row.find('td:nth-child(4)').text()
            };
            originalData.push(rowData);
        });
        
        filteredData = originalData.slice();
        
        // Search functionality
        $search.off('input').on('input', function() {
            filterAndSortData();
        });
        
        // Sort functionality
        $sort.off('change').on('change', function() {
            filterAndSortData();
        });
        
        $order.off('change').on('change', function() {
            filterAndSortData();
        });
        
        // Reset filters
        $reset.off('click').on('click', function() {
            $search.val('');
            $sort.val('widget_name');
            $order.val('asc');
            filteredData = originalData.slice();
            updateTable();
            updateSortIndicators();
        });
        
        // Column header sorting
        $table.find('.ewa-sortable').off('click').on('click', function() {
            var sortField = $(this).data('sort');
            var currentSort = $sort.val();
            var currentOrder = $order.val();
            
            if (currentSort === sortField) {
                // Toggle order if same column
                $order.val(currentOrder === 'asc' ? 'desc' : 'asc');
            } else {
                // Set new sort column
                $sort.val(sortField);
                $order.val('asc');
            }
            
            filterAndSortData();
        });
        
        // Mark as initialized
        $table.data('initialized', true);
        console.log('Widgets table initialization complete');
        
        function filterAndSortData() {
            var searchTerm = $search.val().toLowerCase();
            var sortField = $sort.val();
            var sortOrder = $order.val();
            
            console.log('Filtering widgets:', { searchTerm, sortField, sortOrder });
            
            // Filter data
            filteredData = originalData.filter(function(item) {
                return item.widget_name.includes(searchTerm) || 
                    item.widget_name_raw.toLowerCase().includes(searchTerm);
            });
            
            console.log('Filtered data count:', filteredData.length);
            
            // Sort data
            filteredData.sort(function(a, b) {
                var aVal = a[sortField];
                var bVal = b[sortField];
                
                if (typeof aVal === 'string') {
                    aVal = aVal.toLowerCase();
                    bVal = bVal.toLowerCase();
                }
                
                if (aVal < bVal) return sortOrder === 'asc' ? -1 : 1;
                if (aVal > bVal) return sortOrder === 'asc' ? 1 : -1;
                return 0;
            });
            
            updateTable();
            updateSortIndicators();
        }
        
        function updateTable() {
            var $tbody = $table.find('tbody');
            $tbody.empty();
            
            filteredData.forEach(function(item) {
                $tbody.append(item.element.clone());
            });
            
            // Reattach event handlers for view details buttons
            $tbody.find('.ewa-view-details').on('click', function() {
                var widgetName = $(this).data('widget');
                showWidgetDetails(widgetName);
            });
        }
        
        function updateSortIndicators() {
            $table.find('.ewa-sortable').removeClass('ewa-sorted-asc ewa-sorted-desc');
            var sortField = $sort.val();
            var sortOrder = $order.val();
            $table.find('.ewa-sortable[data-sort="' + sortField + '"]').addClass('ewa-sorted-' + sortOrder);
        }
        
        function showWidgetDetails(widgetName) {
            var $modal = $('#ewa-modal');
            var $title = $('#ewa-modal-title');
            var $body = $('#ewa-modal-body');
            
            $title.text('Loading...');
            $body.html('<p>Loading content details...</p>');
            $modal.show();
            
            $.ajax({
                url: ewa_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ewa_get_content_details',
                    widget_name: widgetName,
                    nonce: ewa_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        var html = '<table class="wp-list-table widefat fixed striped">';
                        html += '<thead><tr><th>Content Title</th><th>Content Type</th><th>Widget Count</th><th>Analysis Date</th></tr></thead>';
                        html += '<tbody>';
                        
                        data.forEach(function(item) {
                            html += '<tr>';
                            html += '<td><a href="' + getEditUrl(item.post_id, item.post_type) + '" target="_blank">' + item.post_title + '</a></td>';
                            html += '<td>' + getContentTypeName(item.post_type) + '</td>';
                            html += '<td>' + item.widget_count + '</td>';
                            html += '<td>' + formatDate(item.analysis_date) + '</td>';
                            html += '</tr>';
                        });
                        
                        html += '</tbody></table>';
                        
                        $title.text('Content using "' + widgetName + '"');
                        $body.html(html);
                    } else {
                        $title.text('Error');
                        $body.html('<p>Failed to load content details.</p>');
                    }
                },
                error: function() {
                    $title.text('Error');
                    $body.html('<p>Failed to load content details.</p>');
                }
            });
        }
    }

    // Content Types Table Sorting and Filtering
    function initializeContentTypesTable() {
        var $table = $('#ewa-content-types-table');
        var $search = $('#ewa-content-type-search');
        var $sort = $('#ewa-content-type-sort');
        var $order = $('#ewa-content-type-order');
        var $reset = $('#ewa-content-type-reset-filters');
        
        console.log('Initializing content types table:', {
            tableExists: $table.length,
            searchExists: $search.length,
            sortExists: $sort.length,
            orderExists: $order.length,
            resetExists: $reset.length,
            alreadyInitialized: $table.data('initialized')
        });
        
        // Prevent double initialization
        if ($table.data('initialized')) {
            return;
        }
        
        var originalData = [];
        var filteredData = [];
        
        // Store original data
        $table.find('tbody tr').each(function() {
            var $row = $(this);
            var rowData = {
                element: $row,
                post_type: $row.find('td:first strong').text().toLowerCase(),
                post_type_raw: $row.find('td:first strong').text(),
                content_count: parseInt($row.find('td:nth-child(2)').text()) || 0,
                unique_widgets: parseInt($row.find('td:nth-child(3)').text()) || 0,
                total_widgets: parseInt($row.find('td:nth-child(4)').text()) || 0
            };
            originalData.push(rowData);
        });
        
        filteredData = originalData.slice();
        
        // Search functionality
        $search.off('input').on('input', function() {
            filterAndSortData();
        });
        
        // Sort functionality
        $sort.off('change').on('change', function() {
            filterAndSortData();
        });
        
        $order.off('change').on('change', function() {
            filterAndSortData();
        });
        
        // Reset filters
        $reset.off('click').on('click', function() {
            $search.val('');
            $sort.val('post_type');
            $order.val('asc');
            filteredData = originalData.slice();
            updateTable();
            updateSortIndicators();
        });
        
        // Column header sorting
        $table.find('.ewa-sortable').off('click').on('click', function() {
            var sortField = $(this).data('sort');
            var currentSort = $sort.val();
            var currentOrder = $order.val();
            
            if (currentSort === sortField) {
                // Toggle order if same column
                $order.val(currentOrder === 'asc' ? 'desc' : 'asc');
            } else {
                // Set new sort column
                $sort.val(sortField);
                $order.val('asc');
            }
            
            filterAndSortData();
        });
        
        // Mark as initialized
        $table.data('initialized', true);
        console.log('Content types table initialization complete');
        
        function filterAndSortData() {
            var searchTerm = $search.val().toLowerCase();
            var sortField = $sort.val();
            var sortOrder = $order.val();
            
            console.log('Filtering content types:', { searchTerm, sortField, sortOrder });
            
            // Filter data
            filteredData = originalData.filter(function(item) {
                return item.post_type.includes(searchTerm) || 
                    item.post_type_raw.toLowerCase().includes(searchTerm);
            });
            
            console.log('Filtered content types count:', filteredData.length);
            
            // Sort data
            filteredData.sort(function(a, b) {
                var aVal = a[sortField];
                var bVal = b[sortField];
                
                if (typeof aVal === 'string') {
                    aVal = aVal.toLowerCase();
                    bVal = bVal.toLowerCase();
                }
                
                if (aVal < bVal) return sortOrder === 'asc' ? -1 : 1;
                if (aVal > bVal) return sortOrder === 'asc' ? 1 : -1;
                return 0;
            });
            
            updateTable();
            updateSortIndicators();
        }
        
        function updateTable() {
            var $tbody = $table.find('tbody');
            $tbody.empty();
            
            filteredData.forEach(function(item) {
                $tbody.append(item.element.clone());
            });
            
            // Reattach event handlers for view widgets buttons
            $tbody.find('.ewa-view-content-type').on('click', function() {
                var postType = $(this).data('post-type');
                showContentTypeWidgets(postType);
            });
        }
        
        function updateSortIndicators() {
            $table.find('.ewa-sortable').removeClass('ewa-sorted-asc ewa-sorted-desc');
            var sortField = $sort.val();
            var sortOrder = $order.val();
            $table.find('.ewa-sortable[data-sort="' + sortField + '"]').addClass('ewa-sorted-' + sortOrder);
        }
        
        function showContentTypeWidgets(postType) {
            var $modal = $('#ewa-modal');
            var $title = $('#ewa-modal-title');
            var $body = $('#ewa-modal-body');
            
            $title.text('Loading...');
            $body.html('<p>Loading widget details...</p>');
            $modal.show();
            
            $.ajax({
                url: ewa_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ewa_get_content_type_widgets',
                    post_type: postType,
                    nonce: ewa_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        var html = '<table class="wp-list-table widefat fixed striped">';
                        html += '<thead><tr><th>Widget</th><th>Usage Count</th><th>Content Count</th></tr></thead>';
                        html += '<tbody>';
                        
                        data.forEach(function(item) {
                            html += '<tr>';
                            html += '<td><strong>' + item.widget_name + '</strong></td>';
                            html += '<td>' + item.usage_count + '</td>';
                            html += '<td>' + item.content_count + '</td>';
                            html += '</tr>';
                        });
                        
                        html += '</tbody></table>';
                        
                        $title.text('Widgets used in "' + postType + '"');
                        $body.html(html);
                    } else {
                        $title.text('Error');
                        $body.html('<p>Failed to load widget details.</p>');
                    }
                },
                error: function() {
                    $title.text('Error');
                    $body.html('<p>Failed to load widget details.</p>');
                }
            });
        }
    }
});
