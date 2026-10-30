<div class="panel">
    <div class="panel-heading">
        <div class="title-bar">
            <h3 class="title-bar__title">
                <?= lang('fs_submission') ?> #<?= (int) $submission['submission_id'] ?>
                &mdash; <?= htmlspecialchars($submission['form_name']) ?>
            </h3>
            <div class="title-bar__extra-tools">
                <a href="<?= $list_url ?>" class="button button--secondary button--small">
                    &larr; <?= lang('fs_back_to_list') ?>
                </a>
            </div>
        </div>
    </div>

    <div class="panel-body">

        <table class="data mainTable" cellspacing="0" style="margin-bottom:24px;">
            <tbody>
                <tr>
                    <th style="width:200px;"><?= lang('fs_date') ?></th>
                    <td><?= htmlspecialchars($formatted_date) ?></td>
                </tr>

                <?php foreach ($fields as $key => $value): ?>
                <tr>
                    <th><?= htmlspecialchars(ucwords(str_replace(['_', '-'], ' ', $key))) ?></th>
                    <td style="white-space:pre-wrap;"><?= htmlspecialchars((string) $value) ?></td>
                </tr>
                <?php endforeach ?>

                <tr>
                    <th><?= lang('fs_sender_ip') ?></th>
                    <td><?= htmlspecialchars($submission['sender_ip']) ?></td>
                </tr>
                <tr>
                    <th><?= lang('fs_email_sent') ?></th>
                    <td>
                        <?php if ($submission['email_sent']): ?>
                            <span style="color:#2e7d32;">&#10003; <?= lang('fs_yes') ?></span>
                        <?php else: ?>
                            <span style="color:#c62828;">&#10007; <?= lang('fs_no') ?></span>
                        <?php endif ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <?= form_open($delete_url, ['onsubmit' => 'return confirm(' . htmlspecialchars(json_encode(lang('fs_confirm_delete')), ENT_QUOTES) . ');']) ?>
            <button type="submit" class="button button--danger button--small">
                <?= lang('fs_delete_submission') ?>
            </button>
        <?= form_close() ?>

    </div>
</div>
