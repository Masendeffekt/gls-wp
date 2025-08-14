<?php
add_action('admin_menu', 'gls_add_label_archive_page');

function gls_add_label_archive_page() {
    add_submenu_page(
        'woocommerce',
        'GLS Label Archive',
        'GLS Label Archive',
        'manage_woocommerce',
        'gls-label-archive',
        'gls_render_label_archive_page'
    );
}

function gls_render_label_archive_page() {
    $upload_dir = wp_upload_dir();
    $base_path = $upload_dir['basedir'];
    $base_url  = $upload_dir['baseurl'];

    // Default filter to current month if none provided
    $selected_year = isset($_GET['year']) ? sanitize_text_field($_GET['year']) : date('Y');
    $selected_month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : date('m');

    // Find all label files recursively
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base_path));
    $label_files = [];

    foreach ($files as $file) {
        if ($file->isFile() && preg_match('/shipping_label_.*\.pdf$/', $file->getFilename())) {
            $timestamp = $file->getMTime();
            $year = date('Y', $timestamp);
            $month = date('m', $timestamp);

            if (($selected_year && $year !== $selected_year) || ($selected_month && $month !== $selected_month)) {
                continue;
            }

            $label_files[] = [
                'path' => $file->getPathname(),
                'time' => $timestamp,
                'year' => $year,
                'month' => $month,
            ];
        }
    }

    // Sort newest first
    usort($label_files, fn($a, $b) => $b['time'] - $a['time']);

    // Pagination setup
    $per_page = 20;
    $total = count($label_files);
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($paged - 1) * $per_page;
    $paginated_files = array_slice($label_files, $offset, $per_page);
    $total_pages = ceil($total / $per_page);

    echo '<div class="wrap"><h1>GLS Label Archive</h1>';

    // Filter form with reset button
    echo '<form method="get" style="margin-bottom:20px; display: flex; align-items: center; gap: 10px;">
        <input type="hidden" name="page" value="gls-label-archive" />
        <label for="year">Year:</label>
        <select name="year" id="year">
            <option value="">All</option>';
    for ($y = date('Y'); $y >= 2022; $y--) {
        $selected = $selected_year == $y ? ' selected' : '';
        echo "<option value='$y'$selected>$y</option>";
    }
    echo '</select>
        <label for="month">Month:</label>
        <select name="month" id="month">
            <option value="">All</option>';
    for ($m = 1; $m <= 12; $m++) {
        $val = str_pad($m, 2, '0', STR_PAD_LEFT);
        $selected = $selected_month == $val ? ' selected' : '';
        echo "<option value='$val'$selected>$val</option>";
    }
    echo '</select>
        <button class="button">Filter</button>
        <a href="' . admin_url('admin.php?page=gls-label-archive') . '" class="button">Reset Filters</a>
    </form>';

    if (!$label_files) {
        echo '<p>No bulk label PDFs found for this selection.</p></div>';
        return;
    }

    echo '<table class="widefat fixed striped"><thead><tr><th>File Name</th><th>Created</th><th>Download</th></tr></thead><tbody>';
    foreach ($paginated_files as $file) {
        $file_path = $file['path'];
        $relative_path = str_replace($base_path, '', $file_path);
        $file_url = $base_url . $relative_path;
        $file_name = basename($file_path);
        $created = date("Y-m-d H:i", $file['time']);

        echo '<tr>';
        echo '<td>' . esc_html($file_name) . '</td>';
        echo '<td>' . esc_html($created) . '</td>';
        echo '<td><a class="button" href="' . esc_url($file_url) . '" target="_blank">Download PDF</a></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';

    // Pagination links
    if ($total_pages > 1) {
        echo '<div class="tablenav"><div class="tablenav-pages">';
        for ($i = 1; $i <= $total_pages; $i++) {
            $args = array_merge($_GET, ['paged' => $i]);
            $url = esc_url(add_query_arg($args, admin_url('admin.php')));
            $class = ($i === $paged) ? ' class="current"' : '';
            echo '<a' . $class . ' href="' . $url . '">' . $i . '</a> ';
        }
        echo '</div></div>';
    }

    echo '</div>';
}