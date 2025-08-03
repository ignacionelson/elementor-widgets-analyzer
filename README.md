# Elementor Widgets Analyzer

A comprehensive WordPress plugin that analyzes and tracks Elementor widget usage across all content types in your WordPress site.

> **Note**: This plugin was built as an experiment using [Cursor](https://cursor.sh), an AI-powered code editor, to explore the capabilities of AI-assisted development for WordPress plugins.

## Features

### 🔍 **Comprehensive Analysis**
- Analyzes all posts, pages, and custom post types
- Automatically detects and lists all available custom post types
- Scans Elementor builder content for widget usage
- Tracks widget instances and frequency

### 📊 **Dashboard Interface**
- **Dashboard Tab**: Overview with summary cards and analysis controls
- **Widget Statistics Tab**: Detailed widget usage statistics
- **Content Types Tab**: Analysis breakdown by content type

### 📈 **Detailed Statistics**
- Total content analyzed
- Unique widgets found
- Total widget instances
- Widget usage per content type
- Content count per widget

### 🔍 **Detailed Views**
- View all content using a specific widget
- See widget usage within specific content types
- Modal popups for detailed information
- Direct links to edit content

## Installation

1. **Upload the Plugin**
   - Upload the `elementor-widgets-analyzer` folder to your `/wp-content/plugins/` directory
   - Or zip the folder and upload via WordPress admin

2. **Activate the Plugin**
   - Go to **Plugins** > **Installed Plugins**
   - Find "Elementor Widgets Analyzer" and click **Activate**

3. **Access the Dashboard**
   - Navigate to **Widgets Analyzer** in your WordPress admin menu
   - The plugin requires Elementor to be installed and activated

## Usage

### Running Analysis

1. **Access the Dashboard**
   - Go to **Widgets Analyzer** in your admin menu
   - You'll see the main dashboard with summary cards

2. **Run Analysis**
   - Click the **"Run Analysis"** button
   - The plugin will scan all your content
   - Progress will be shown with an animated progress bar
   - Results will be stored in a custom database table

3. **View Results**
   - **Dashboard**: Overview of analysis results
   - **Widget Statistics**: See which widgets are used most
   - **Content Types**: Breakdown by post type

### Understanding the Results

#### Dashboard Overview
- **Total Content Analyzed**: Number of posts/pages with Elementor content
- **Unique Widgets Found**: Different widget types discovered
- **Total Widget Instances**: Total count of all widget usages
- **Content Types**: Number of different post types analyzed

#### Widget Statistics
- **Widget Name**: Display name and technical name
- **Content Count**: How many pieces of content use this widget
- **Total Usage**: Total instances of this widget across all content
- **Content Types**: Which post types use this widget
- **View Details**: Click to see all content using this widget

#### Content Types Analysis
- **Content Type**: Post type name and technical name
- **Content Count**: Number of items of this type analyzed
- **Unique Widgets**: Different widget types used in this content type
- **Total Widgets**: Total widget instances in this content type
- **View Widgets**: Click to see widgets used in this content type

## Database Structure

The plugin creates a custom table `{prefix}_ewa_widget_analysis` with the following structure:

```sql
CREATE TABLE {prefix}_ewa_widget_analysis (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    post_id bigint(20) NOT NULL,
    post_type varchar(50) NOT NULL,
    post_title varchar(255) NOT NULL,
    widget_name varchar(100) NOT NULL,
    widget_count int(11) DEFAULT 1,
    analysis_date datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
```

## Requirements

- **WordPress**: 5.0 or higher
- **Elementor**: Any version (plugin checks for Elementor activation)
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher

## Security

- All AJAX requests are protected with nonces
- User capability checks for admin functions
- Data sanitization and validation
- SQL prepared statements to prevent injection

## Troubleshooting

### Plugin Not Working
1. **Check Elementor**: Ensure Elementor is installed and activated
2. **Check Permissions**: Make sure you have administrator privileges
3. **Check PHP Version**: Ensure PHP 7.4 or higher is installed

### No Data Found
1. **Run Analysis**: Click "Run Analysis" to scan your content
2. **Check Content**: Ensure you have posts/pages with Elementor content
3. **Check Post Status**: Only published content is analyzed

### Performance Issues
1. **Large Sites**: Analysis may take time on sites with many posts
2. **Server Resources**: Ensure adequate memory and processing time
3. **Database**: Check if your database can handle the custom table

## Support

For support and feature requests, please contact the plugin developer.

## Changelog

### Version 1.0.0
- Initial release
- Comprehensive widget analysis
- Dashboard interface with tabs
- Detailed statistics and reporting
- Modal popups for detailed views
- Responsive design
- Security features and data validation

## License

This plugin is licensed under the GPL v2 or later.

---

**Note**: This plugin is designed to work with Elementor and will show a notice if Elementor is not active. Make sure Elementor is installed and activated before using this plugin. 