<?php

/**
 * Plugin Name: RapidDB Viewer',
 * Plugin URI: 
 * Description: Display database tables content in frontend with custom query support
 * Version: 1.0
 * Author: Samuel peters (Bieefilled)
 * Author URI: https://github.com/Petsamuel
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class DB_Table_Viewer
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('admin_menu', array($this, 'add_plugin_menu'));
        add_shortcode('display_table', array($this, 'handle_shortcode'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_ajax_get_table_columns', array($this, 'get_table_columns'));
        add_action('wp_ajax_preview_table_data', array($this, 'preview_table_data'));
        add_action('wp_ajax_execute_custom_query', array($this, 'execute_custom_query'));
    }

    public function add_plugin_menu()
    {
        add_menu_page(
            'RapidDB Viewer',
            'RapidDB Viewer',
            'manage_options',
            'db-table-viewer',
            array($this, 'render_admin_page'),
            'dashicons-database'
        );
    }

    public function render_admin_page()
    {
        global $wpdb;
        $tables = $wpdb->get_results("SHOW TABLES FROM `" . DB_NAME . "`", ARRAY_N);
?>

        <!-- Tabs -->
        <div class="border-b border-gray-200 dark:border-gray-700 mt-3">
            <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500 dark:text-gray-400 gap-2">

                <li class="me-2">
                    <a href="#simple-view" class="inline-flex items-center justify-center p-4  hover:text-gray-600 dark:hover:text-gray-300 gap-2 group nav-tab nav-tab-active">
                        <svg class="w-4 h-4 me-2 text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 11.424V1a1 1 0 1 0-2 0v10.424a3.228 3.228 0 0 0 0 6.152V19a1 1 0 1 0 2 0v-1.424a3.228 3.228 0 0 0 0-6.152ZM19.25 14.5A3.243 3.243 0 0 0 17 11.424V1a1 1 0 0 0-2 0v10.424a3.227 3.227 0 0 0 0 6.152V19a1 1 0 1 0 2 0v-1.424a3.243 3.243 0 0 0 2.25-3.076Zm-6-9A3.243 3.243 0 0 0 11 2.424V1a1 1 0 0 0-2 0v1.424a3.228 3.228 0 0 0 0 6.152V19a1 1 0 1 0 2 0V8.576A3.243 3.243 0 0 0 13.25 5.5Z" />
                        </svg>Simple View
                    </a>
                </li>
                <li class="me-2">
                    <a href="#custom-query" class="inline-flex items-center justify-center p-4  hover:text-gray-600 dark:hover:text-gray-300 gap-2 group nav-tab ">
                        <svg class="w-4 h-4 me-2 text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 11.424V1a1 1 0 1 0-2 0v10.424a3.228 3.228 0 0 0 0 6.152V19a1 1 0 1 0 2 0v-1.424a3.228 3.228 0 0 0 0-6.152ZM19.25 14.5A3.243 3.243 0 0 0 17 11.424V1a1 1 0 0 0-2 0v10.424a3.227 3.227 0 0 0 0 6.152V19a1 1 0 1 0 2 0v-1.424a3.243 3.243 0 0 0 2.25-3.076Zm-6-9A3.243 3.243 0 0 0 11 2.424V1a1 1 0 0 0-2 0v1.424a3.228 3.228 0 0 0 0 6.152V19a1 1 0 1 0 2 0V8.576A3.243 3.243 0 0 0 13.25 5.5Z" />
                        </svg>Custom Query
                    </a>
                </li>


            </ul>
        </div>

        <div class="wrap p-6 bg-gray-100 rounded-lg shadow-md">


            <h1 class="flex items-center cursor-pointer dark:text-white text-4xl">RapidDB<span class="bg-blue-100 text-blue-800 text-xl font-semibold me-2 px-2.5 py-0.5 rounded dark:bg-blue-200 dark:text-blue-800 ms-2">View</span></h1>

            <p class="text-sm font-normal text-gray-500 dark:text-gray-400 mb-6">Managing and displaying data on WordPress has never been easier!</p>

            <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 hidden" role="alert" id="ajax-response">
                <span class="font-medium ">Success alert!</span> Change a few things up and try submitting again.
            </div>

            <div class="text-red-500 mt-2"></div>

            <!-- Simple View Tab -->
            <div id="simple-view" class="tab-content mt-4">
                <div class="space-y-4">
                    <div class=" max-w-sm">
                        <label for="db_table_select" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Select an option</label>
                        <select id="db_table_select" name="db_table_viewer[selected_table]" class=" bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            <option value="">Select a table</option>
                            <?php
                            foreach ($tables as $table) {
                                echo '<option value="' . esc_attr($table[0]) . '">' . esc_html($table[0]) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <!-- dasboard -->


                    <!-- Sorting Options -->
                    <div id="sorting_options" class=" space-y-2">
                        <label class="block font-medium">Sorting Options</label>
                        <div class="flex space-x-4">
                            <select id="sort_column" class="p-2 border-gray-300 rounded-md">
                                <option value="">Select column</option>
                            </select>
                            <select id="sort_direction" class="p-2 border-gray-300 rounded-md">
                                <option value="ASC">Ascending</option>
                                <option value="DESC">Descending</option>
                            </select>
                        </div>
                    </div>

                    <!-- Pagination Options -->
                    <div id="pagination_options" class=" space-y-2">
                        <label class="block font-medium">Rows per page</label>
                        <select id="rows_per_page" class="p-2 border-gray-300 rounded-md">

                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium">Select Columns</label>
                        <div id="column_selection" class="flex flex-col gap-2 mt-2 "></div>
                    </div>
                    <div>
                        <label class="block font-medium">Preview</label>
                        <div id="data_preview" class="overflow-auto border border-gray-300 p-4 rounded-md my-8 bg-white shadow-md"></div>
                    </div>
                    <div>
                        <label class="block font-medium mb-2">Shortcode</label>
                        <code id="shortcode_display" class="bg-gray-200 p-2 rounded-md my-4">[display_table]</code>
                        <p class="text-sm text-gray-500 mt-2">Use this shortcode to display the table in your posts or pages.</p>
                    </div>
                </div>
            </div>

            <!-- Custom Query Tab -->
            <div id="custom-query" class="tab-content mt-4 hidden">
                <div class="custom-query-wrapper space-y-4">
                    <h3 class="text-xl font-semibold">Custom SQL Query</h3>
                    <p class="text-gray-500">Write your custom SQL query here. Use {prefix} for the WordPress table prefix.</p>
                    <textarea id="custom_query" rows="6" class="w-full border-gray-300 rounded-md p-2" placeholder="SELECT * FROM {prefix}posts LIMIT 5"></textarea>
                    <button class="button button-primary bg-blue-500 text-white py-2 px-4 rounded-md" id="execute_query">Execute Query</button>
                    <div class="query-notice p-4 bg-yellow-50 border-l-4 border-yellow-400 mt-4">
                        <p class="font-medium"><strong>Note:</strong> For security reasons, only SELECT queries are allowed.</p>
                    </div>
                    <div id="query_result" class="mt-4"></div>
                    <div id="query_shortcode" class="mt-4"></div>
                </div>
            </div>
        </div>

    <?php
    }

    public function admin_scripts($hook)
    {
        if ('toplevel_page_db-table-viewer' !== $hook) {
            return;
        }
        wp_enqueue_script('jquery');
        wp_enqueue_style('tailwindcss', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');

        wp_add_inline_script('jquery', '
    jQuery(document).ready(function($) {
        let currentPage = 1;
        
        // Tab handling
        $(".nav-tab").on("click", function(e) {
            e.preventDefault();
            $(".nav-tab").removeClass("nav-tab-active ").addClass("text-gray-500");
            $(this).addClass("nav-tab-active ");
            $(".tab-content").hide();
            $($(this).attr("href")).show();
        });
        

        // Table selection handling
        $("#db_table_select").on("change", function() {
            var selectedTable = $(this).val();
            if (selectedTable) {
                $("#ajax-response").html("Loading columns...");
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "get_table_columns",
                        table: selectedTable,
                        nonce: "' . wp_create_nonce('get_table_columns') . '"
                    },
                    success: function(response) {
                        $("#column_selection").html(response);
                        updateSortingOptions();
                        updatePreview();
                    },
                    error: function(xhr, status, error) {
                        $("#ajax-response").html("Error: " + error);
                    }
                });
            }
        });

        function updatePreview() {
            var selectedTable = $("#db_table_select").val();
            if (!selectedTable) return;

            var columns = [];
            $("input[name=\"columns[]\"]").each(function() {
                if ($(this).is(":checked")) {
                    columns.push($(this).val());
                }
            });
            
            var perPage = $("#rows_per_page").val();
            var sortColumn = $("#sort_column").val();
            var sortDirection = $("#sort_direction").val();

            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    action: "preview_table_data",
                    table: selectedTable,
                    columns: columns.length > 0 ? columns.join(",") : "*",
                    page: currentPage,
                    per_page: perPage,
                    sort_column: sortColumn,
                    sort_direction: sortDirection,
                    nonce: "' . wp_create_nonce('preview_table_data') . '"
                },
                success: function(response) {
                    $("#data_preview").html(response);
                    updateShortcode();
                }
            });
        }

        // Update sorting options when columns are loaded
        function updateSortingOptions() {
            var $sortColumn = $("#sort_column");
            $sortColumn.empty().append("<option value=\"\">Select column</option>");
            
            $("input[name=\"columns[]\"]").each(function() {
                var columnName = $(this).val();
                $sortColumn.append("<option value=\"" + columnName + "\">" + columnName + "</option>");
            });
        }

        // Update shortcode
        function updateShortcode() {
            var table = $("#db_table_select").val();
            var columns = [];
            $("input[name=\"columns[]\"]").each(function() {
                if ($(this).is(":checked")) {
                    columns.push($(this).val());
                }
            });
            var sortColumn = $("#sort_column").val();
            var sortDirection = $("#sort_direction").val();
            var perPage = $("#rows_per_page").val();

            var shortcode = "[display_table " +
                "table=\"" + table + "\" " +
                "columns=\"" + columns.join(",") + "\" " +
                "sort_column=\"" + sortColumn + "\" " +
                "sort_direction=\"" + sortDirection + "\" " +
                "per_page=\"" + perPage + "\"]";
        
            $("#shortcode_display").text(shortcode);
        }

        // Event Handlers
        $(document).on("change", "input[name=\"columns[]\"]", updatePreview);
        $("#sort_column, #sort_direction, #rows_per_page").on("change", updatePreview);

        // Handle pagination clicks
        $(document).on("click", ".page-button", function(e) {
            e.preventDefault();
            currentPage = $(this).data("page");
            updatePreview();
        });
        
        // Handle column header clicks for sorting
        $(document).on("click", "th[data-column]", function() {
            var column = $(this).data("column");
            var currentSort = $("#sort_column").val();
            var currentDirection = $("#sort_direction").val();
            
            if (column === currentSort) {
                $("#sort_direction").val(currentDirection === "ASC" ? "DESC" : "ASC");
            } else {
                $("#sort_column").val(column);
                $("#sort_direction").val("ASC");
            }
            
            updatePreview();
        });
        

        // Handle custom query execution
        $("#execute_query").on("click", function() {
            var query = $("#custom_query").val();
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    action: "execute_custom_query",
                    query: query,
                    nonce: "' . wp_create_nonce('execute_custom_query') . '"
                },
                success: function(response) {
                    $("#query_result").html(response.data);
                    if(response.success) {
                        var shortcode = \'[display_table query="\' + query.replace(/"/g, \'&quot;\') + \'"]\';
                        $("#query_shortcode").html(
                            \'<div class="shortcode-display">\' +
                            \'<h4>Shortcode:</h4>\' +
                            \'<code>\' + shortcode + \'</code>\' +
                            \'</div>\'
                        );
                    }
                }
            });
        });
    });
    ');
    }


    public function get_table_columns()
    {
        check_ajax_referer('get_table_columns', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        global $wpdb;
        $table = sanitize_text_field($_POST['table']);

        $tables = $wpdb->get_results("SHOW TABLES FROM `" . DB_NAME . "`", ARRAY_N);
        $table_exists = false;
        foreach ($tables as $db_table) {
            if ($db_table[0] === $table) {
                $table_exists = true;
                break;
            }
        }

        if (!$table_exists) {
            wp_send_json_error('Table does not exist');
            return;
        }

        $columns = $wpdb->get_results("SHOW COLUMNS FROM `$table`");

        $output = '<div class="columns-wrapper space-y-2">';
        if ($columns) {
            foreach ($columns as $column) {
                $output .= '<label class="flex items-center space-x-2">
                <input type="checkbox" name="columns[]" value="' . esc_attr($column->Field) . '" class="text-indigo-600 focus:ring-indigo-500 rounded border-gray-300"> 
                <span class="text-sm text-gray-700">' . esc_html($column->Field) . '</span>
            </label>';
            }
        } else {
            $output .= '<p class="text-sm text-red-500">No columns found</p>';
        }
        $output .= '</div>';

        echo $output;
        wp_die();
    }

    public function preview_table_data()
    {
        check_ajax_referer('preview_table_data', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        global $wpdb;
        $table = sanitize_text_field($_POST['table']);
        $columns = isset($_POST['columns']) && $_POST['columns'] !== '*' ?
            array_map('sanitize_text_field', explode(',', $_POST['columns'])) :
            '*';

        // Pagination parameters
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        $offset = ($page - 1) * $per_page;

        // Sorting parameters
        $sort_column = isset($_POST['sort_column']) ? sanitize_text_field($_POST['sort_column']) : '';
        $sort_direction = isset($_POST['sort_direction']) ? sanitize_text_field($_POST['sort_direction']) : 'ASC';

        // Build query
        $columns_sql = is_array($columns) ? '`' . implode('`, `', $columns) . '`' : '*';
        $query = "SELECT SQL_CALC_FOUND_ROWS $columns_sql FROM `$table`";

        if ($sort_column) {
            $query .= " ORDER BY `$sort_column` $sort_direction";
        }

        $query .= " LIMIT $offset, $per_page";

        $results = $wpdb->get_results($query);
        $total_items = $wpdb->get_var("SELECT FOUND_ROWS()");
        $total_pages = ceil($total_items / $per_page);

        if (empty($results)) {
            echo '<p class="text-sm text-gray-500">No data found</p>';
            wp_die();
        }

        $output = '<div class="preview-wrapper overflow-x-auto mt-4">';
        $output .= '<table class="min-w-full border border-gray-300 divide-y divide-gray-200 text-sm text-left">';

        // Headers with sort indicators
        $output .= '<thead class="bg-gray-50">';
        $output .= '<tr>';
        foreach (array_keys(get_object_vars($results[0])) as $header) {
            $is_sorted = $sort_column === $header;
            $sort_indicator = $is_sorted ? ($sort_direction === 'ASC' ? '↑' : '↓') : '';
            $output .= sprintf(
                '<th class="px-3 py-2 font-semibold text-gray-700 cursor-pointer" data-column="%s">%s %s</th>',
                esc_attr($header),
                esc_html($header),
                $sort_indicator
            );
        }
        $output .= '</tr></thead>';

        // Data rows
        $output .= '<tbody class="bg-white divide-y divide-gray-200">';
        foreach ($results as $row) {
            $output .= '<tr>';
            foreach ($row as $value) {
                $output .= '<td class="px-3 py-2 text-gray-600">' . esc_html($value) . '</td>';
            }
            $output .= '</tr>';
        }
        $output .= '</tbody></table>';

        // Pagination controls
        if ($total_pages > 1) {
            $output .= '<div class="pagination-controls flex justify-center space-x-2 mt-4">';

            // Previous page
            if ($page > 1) {
                $output .= '<button class="page-button px-3 py-1 border rounded" data-page="' . ($page - 1) . '">Previous</button>';
            }

            // Page numbers
            for ($i = 1; $i <= $total_pages; $i++) {
                $active_class = $i === $page ? 'bg-blue-500 text-white' : 'bg-white';
                $output .= '<button class="page-button px-3 py-1 border rounded ' . $active_class . '" data-page="' . $i . '">' . $i . '</button>';
            }

            // Next page
            if ($page < $total_pages) {
                $output .= '<button class="page-button px-3 py-1 border rounded" data-page="' . ($page + 1) . '">Next</button>';
            }

            $output .= '</div>';
        }

        $output .= '</div>';

        echo $output;
        wp_die();
    }

    public function handle_shortcode($atts)
    {
        $atts = shortcode_atts(
            array(
                'table' => '',
                'columns' => '*',
                'query' => '',
                'sort_column' => '',
                'sort_direction' => 'ASC',
                'per_page' => 5
            ),
            $atts
        );

        global $wpdb;
        $current_page = max(1, get_query_var('paged') ? get_query_var('paged') : 1);
        $offset = ($current_page - 1) * intval($atts['per_page']);

        if (!empty($atts['query'])) {
            $query = str_replace('{prefix}', $wpdb->prefix, $atts['query']);
            if (stripos(trim($query), 'select') !== 0) {
                return '<p class="text-red-500">Only SELECT queries are allowed.</p>';
            }
        } else {
            if (empty($atts['table'])) {
                return '<p class="text-red-500">Please specify a table.</p>';
            }

            $table = sanitize_text_field($atts['table']);
            $columns = $atts['columns'] === '*' ? '*' :
                '`' . implode('`, `', array_map('sanitize_text_field', explode(',', $atts['columns']))) . '`';

            $query = "SELECT SQL_CALC_FOUND_ROWS $columns FROM `$table`";

            if (!empty($atts['sort_column'])) {
                $sort_column = sanitize_text_field($atts['sort_column']);
                $sort_direction = in_array(strtoupper($atts['sort_direction']), ['ASC', 'DESC'])
                    ? strtoupper($atts['sort_direction'])
                    : 'ASC';
                $query .= " ORDER BY `$sort_column` $sort_direction";
            }

            $query .= " LIMIT $offset, " . intval($atts['per_page']);
        }

        $results = $wpdb->get_results($query);
        $total_items = $wpdb->get_var("SELECT FOUND_ROWS()");
        $total_pages = ceil($total_items / intval($atts['per_page']));

        if (empty($results)) {
            return '<p class="text-gray-500">No data found.</p>';
        }

        ob_start();
    ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <?php foreach (array_keys(get_object_vars($results[0])) as $header): ?>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide border border-gray-200">
                                <?php echo esc_html($header); ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($results as $row): ?>
                        <tr class="hover:bg-gray-100 cursor-pointer">
                            <?php foreach ($row as $value): ?>
                                <td class="px-4 py-3 text-sm text-gray-700 border border-gray-200 whitespace-nowrap">
                                    <?php echo esc_html($value); ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($total_pages > 1): ?>
                <div class="flex items-center justify-between px-4 py-3 bg-white border-t border-gray-200 ">
                    <div class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to
                        <span class="font-medium"><?php echo min($offset + intval($atts['per_page']), $total_items); ?></span> of
                        <span class="font-medium"><?php echo $total_items; ?></span> results
                    </div>
                    <div class="pagination flex justify-center gap-2">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('<span class="text-blue-500 text-sm ">&laquo; Previous</span>'),
                            'next_text' => __('<span class="text-blue-500 text-sm " >Next &raquo;</span>'),
                            'total' => $total_pages,
                            'current' => $current_page,
                            'type' => 'plain'
                        ));
                        ?>
                    </div>

                </div>
            <?php endif; ?>
        </div>
<?php
        return ob_get_clean();
    }

    public function enqueue_frontend_styles()
    {
        wp_enqueue_style('tailwindcss', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');
        echo 'Tailwind is enqueued on the frontend!';
        // error


    }
}

// sorting



// Initialize the plugin
function db_table_viewer_init()
{
    DB_Table_Viewer::get_instance();
}
add_action('plugins_loaded', 'db_table_viewer_init');
