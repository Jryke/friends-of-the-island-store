<?php
/**
 * Build a sort URL for a column header.
 * If already sorting by this column, toggles direction.
 * Otherwise uses the column's default first-click direction.
 */
function fs_sort_url($base_url, $col, $current_sort, $current_dir, $default_dir, $form_filter)
{
    if ($current_sort === $col) {
        $next_dir = ($current_dir === 'asc') ? 'desc' : 'asc';
    } else {
        $next_dir = $default_dir;
    }

    $url = $base_url . '&sort=' . urlencode($col) . '&dir=' . $next_dir;
    if ($form_filter !== '') {
        $url .= '&form_name=' . urlencode($form_filter);
    }
    return $url;
}

/**
 * Return a sort indicator arrow for a column header.
 */
function fs_sort_indicator($col, $current_sort, $current_dir)
{
    if ($current_sort !== $col) {
        return '<span style="color:#ccc;font-size:0.8em;"> &#8597;</span>';
    }
    return $current_dir === 'asc'
        ? '<span style="font-size:0.8em;"> &#8593;</span>'
        : '<span style="font-size:0.8em;"> &#8595;</span>';
}
?>
<style>
    .fs-table thead th a             { color: inherit; text-decoration: none; display: block; }
    .fs-table thead th a:hover       { text-decoration: underline; }
    .fs-table tbody tr:nth-child(odd)  { background-color: #f9f9f9; }
    .fs-table tbody tr:nth-child(even) { background-color: #ffffff; }
    .fs-table tbody tr:hover           { background-color: #e8f0fe; cursor: pointer; }
    .fs-table tbody tr td              { padding: 10px 12px; }
</style>

<div class="panel">
    <div class="panel-heading">
        <div class="title-bar">
            <h3 class="title-bar__title"><?= lang('fs_submissions') ?></h3>
            <div class="title-bar__extra-tools">
                <a href="<?= ee('CP/URL', 'addons/settings/form_submission/settings') ?>" class="button button--secondary button--small">
                    <?= lang('fs_nav_settings') ?>
                </a>
            </div>
        </div>
    </div>

    <div class="panel-body">
        <?= ee('CP/Alert')->get('form-submission-deleted') ?>

        <?php if ( ! empty($form_names)): ?>
        <div style="margin-bottom:16px;">
            <label for="form_name_filter" style="margin-right:6px;"><?= lang('fs_filter_by_form') ?></label>
            <select id="form_name_filter">
                <option value=""><?= lang('fs_all_forms') ?></option>
                <?php foreach ($form_names as $fn): ?>
                <option value="<?= htmlspecialchars($fn) ?>" <?= ($form_filter === $fn) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($fn) ?>
                </option>
                <?php endforeach ?>
            </select>
        </div>
        <script>
            document.getElementById('form_name_filter').addEventListener('change', function () {
                var base = <?= json_encode($list_url) ?>;
                var val  = this.value;
                window.location.href = val ? base + '&form_name=' + encodeURIComponent(val) : base;
            });
        </script>
        <?php endif ?>

        <?php if (empty($submissions)): ?>
        <p><?= lang('fs_no_submissions') ?></p>
        <?php else: ?>

        <table class="data mainTable fs-table" cellspacing="0">
            <thead>
                <tr>
                    <?php
                    $cols = [
                        'submission_id'   => '#',
                        'first_name'      => lang('fs_first_name'),
                        'last_name'       => lang('fs_last_name'),
                        'email'           => lang('fs_email'),
                        'subject'         => lang('fs_subject'),
                        'referral'        => lang('fs_referral'),
                        'form_name'       => lang('fs_form_name'),
                        'submission_date' => lang('fs_date'),
                        'email_sent'      => lang('fs_email_sent'),
                    ];
                    foreach ($cols as $col_key => $col_label):
                        $href = fs_sort_url($list_url, $col_key, $sort, $dir, $sortable[$col_key], $form_filter);
                    ?>
                    <th>
                        <a href="<?= $href ?>">
                            <?= $col_label ?><?= fs_sort_indicator($col_key, $sort, $dir) ?>
                        </a>
                    </th>
                    <?php endforeach ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($submissions as $s): ?>
                <tr onclick="window.location='<?= htmlspecialchars($s['detail_url'], ENT_QUOTES) ?>'">
                    <td><?= (int) $s['submission_id'] ?></td>
                    <td><?= htmlspecialchars($s['first_name']) ?></td>
                    <td><?= htmlspecialchars($s['last_name']) ?></td>
                    <td><?= htmlspecialchars($s['email']) ?></td>
                    <td><?= htmlspecialchars($s['subject']) ?></td>
                    <td><?= htmlspecialchars((string) $s['referral']) ?></td>
                    <td><?= htmlspecialchars($s['form_name']) ?></td>
                    <td><?= htmlspecialchars($s['formatted_date']) ?></td>
                    <td>
                        <?php if ($s['email_sent']): ?>
                            <span style="color:#2e7d32;">&#10003; <?= lang('fs_yes') ?></span>
                        <?php else: ?>
                            <span style="color:#c62828;">&#10007; <?= lang('fs_no') ?></span>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>

        <?php if ($pagination): ?>
        <div class="paginate"><?= $pagination ?></div>
        <?php endif ?>

        <p style="margin-top:12px;color:#666;"><?= sprintf(lang('fs_total_submissions'), (int) $total) ?></p>

        <?php endif ?>
    </div>
</div>
