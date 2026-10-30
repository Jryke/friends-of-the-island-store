<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Form Submission Module Control Panel
 *
 * Provides three sections in the EE CP:
 *   - Submissions  : paginated list of all captured submissions
 *   - Detail       : view / delete a single submission
 *   - Settings     : configure MailerSend SMTP credentials, sender, recipient
 */
class Form_submission_mcp
{
    /** Items per page on the submissions list */
    const PER_PAGE = 25;

    public function __construct()
    {
        ee()->lang->loadfile('form_submission');
        ee()->load->helper('form');

        // Build sidebar
        $sidebar = ee('CP/Sidebar')->make();
        $sidebar->addItem(lang('fs_nav_submissions'), ee('CP/URL', 'addons/settings/form_submission'))
                ->withIcon('envelope');
        $sidebar->addItem(lang('fs_nav_settings'), ee('CP/URL', 'addons/settings/form_submission/settings'))
                ->withIcon('cog');
    }

    // ----------------------------------------------------------------
    // index() — submissions list
    // ----------------------------------------------------------------

    public function index()
    {
        $page        = max(1, (int) ee()->input->get('page'));
        $start       = ($page - 1) * self::PER_PAGE;
        $form_filter = ee()->input->get('form_name') ?: '';

        // Sortable columns: key => [db expression, first-click default direction]
        // JSON fields use MySQL JSON_EXTRACT; CI passes expressions with () through unescaped.
        $sortable = [
            'submission_id'   => ['submission_id',                                           'desc'],
            'first_name'      => ["JSON_UNQUOTE(JSON_EXTRACT(fields, '$.first_name'))",      'asc'],
            'last_name'       => ["JSON_UNQUOTE(JSON_EXTRACT(fields, '$.last_name'))",       'asc'],
            'email'           => ["JSON_UNQUOTE(JSON_EXTRACT(fields, '$.email'))",           'asc'],
            'subject'         => ["JSON_UNQUOTE(JSON_EXTRACT(fields, '$.subject'))",         'asc'],
            'referral'        => ["JSON_UNQUOTE(JSON_EXTRACT(fields, '$.ref'))",             'asc'],
            'form_name'       => ['form_name',                                               'asc'],
            'submission_date' => ['submission_date',                                         'desc'],
            'email_sent'      => ['email_sent',                                              'asc'],
        ];

        $sort = ee()->input->get('sort') ?: 'submission_id';
        $dir  = ee()->input->get('dir')  ?: 'desc';

        if ( ! array_key_exists($sort, $sortable)) {
            $sort = 'submission_date';
        }
        $dir = ($dir === 'asc') ? 'asc' : 'desc';

        // Count query
        $count_db = ee()->db;
        if ($form_filter !== '') {
            $count_db->where('form_name', $form_filter);
        }
        $total = $count_db->count_all_results('exp_form_submissions');

        // Data query — built as a raw SQL string so that JSON_EXTRACT expressions
        // are not mangled by CI2's identifier-escaping in order_by().
        // All variable parts are either whitelisted server-side or cast/escaped.
        $where_sql = $form_filter !== ''
            ? 'WHERE form_name = ' . ee()->db->escape($form_filter)
            : '';

        $order_expr = $sortable[$sort][0];
        $dir_sql    = $dir === 'asc' ? 'ASC' : 'DESC';

        $sql = "SELECT submission_id, form_name, submission_date, sender_ip, email_sent, fields
                FROM exp_form_submissions
                {$where_sql}
                ORDER BY {$order_expr} {$dir_sql}
                LIMIT " . (int) self::PER_PAGE . " OFFSET " . (int) $start;

        $query = ee()->db->query($sql);

        // Unique form names for the filter dropdown
        $forms_q = ee()->db->select('form_name')->distinct()
                           ->order_by('form_name', 'asc')
                           ->get('exp_form_submissions');

        $form_names = array_column($forms_q->result_array(), 'form_name');

        $submissions = [];
        foreach ($query->result_array() as $row) {
            $fields = json_decode($row['fields'] ?? '{}', true) ?: [];
            $row['formatted_date'] = ee()->localize->human_time($row['submission_date']);
            $row['detail_url']     = ee('CP/URL', 'addons/settings/form_submission/detail/' . $row['submission_id'])->compile();
            $row['first_name']     = $fields['first_name'] ?? '';
            $row['last_name']      = $fields['last_name']  ?? '';
            $row['email']          = $fields['email']      ?? '';
            $row['subject']        = $fields['subject']    ?? '';
            $row['referral']       = $fields['ref']        ?? '';
            $submissions[]         = $row;
        }

        $list_url = ee('CP/URL', 'addons/settings/form_submission')->compile();

        // Pagination — preserve sort/filter params in page links
        $pagination = '';
        if ($total > self::PER_PAGE) {
            $page_base = $list_url
                . '&sort=' . urlencode($sort)
                . '&dir='  . urlencode($dir)
                . ($form_filter !== '' ? '&form_name=' . urlencode($form_filter) : '');

            ee()->load->library('pagination');
            $config = [
                'base_url'             => $page_base,
                'total_rows'           => $total,
                'per_page'             => self::PER_PAGE,
                'cur_page'             => $start,
                'uri_segment'          => 0,
                'query_string_segment' => 'page',
            ];
            ee()->pagination->initialize($config);
            $pagination = ee()->pagination->create_links();
        }

        $vars = [
            'submissions'  => $submissions,
            'total'        => $total,
            'form_names'   => $form_names,
            'form_filter'  => $form_filter,
            'pagination'   => $pagination,
            'list_url'     => $list_url,
            'sort'         => $sort,
            'dir'          => $dir,
            'sortable'     => array_map(fn($v) => $v[1], $sortable),
        ];

        return ee('View')->make('form_submission:index')->render($vars);
    }

    // ----------------------------------------------------------------
    // detail($id) — single submission
    // ----------------------------------------------------------------

    public function detail()
    {
        $segments = ee()->uri->segment_array();
        $id = (int) end($segments);

        if ( ! $id) {
            ee()->functions->redirect(ee('CP/URL', 'addons/settings/form_submission'));
        }

        $query = ee()->db->where('submission_id', $id)
                         ->get('exp_form_submissions');

        if ($query->num_rows() === 0) {
            ee()->functions->redirect(ee('CP/URL', 'addons/settings/form_submission'));
        }

        $row    = $query->row_array();
        $fields = json_decode($row['fields'], true) ?: [];

        $vars = [
            'submission'     => $row,
            'fields'         => $fields,
            'formatted_date' => ee()->localize->human_time($row['submission_date']),
            'list_url'       => ee('CP/URL', 'addons/settings/form_submission')->compile(),
            'delete_url'     => ee('CP/URL', 'addons/settings/form_submission/delete_submission/' . $id)->compile(),
        ];

        return ee('View')->make('form_submission:detail')->render($vars);
    }

    // ----------------------------------------------------------------
    // delete_submission() — delete and redirect
    // ----------------------------------------------------------------

    public function delete_submission()
    {
        // State-changing: POST only. EE core validates the CSRF token on CP POSTs.
        if (ee()->input->method() !== 'post') {
            ee()->functions->redirect(ee('CP/URL', 'addons/settings/form_submission'));
        }

        $segments = ee()->uri->segment_array();
        $id = (int) end($segments);

        if ($id) {
            ee()->db->where('submission_id', $id)->delete('exp_form_submissions');

            ee('CP/Alert')->makeInline('form-submission-deleted')
                         ->asSuccess()
                         ->withTitle(lang('fs_deleted'))
                         ->addToBody(lang('fs_submission_deleted'))
                         ->defer();
        }

        ee()->functions->redirect(ee('CP/URL', 'addons/settings/form_submission'));
    }

    // ----------------------------------------------------------------
    // settings() — MailerSend configuration
    // ----------------------------------------------------------------

    public function settings()
    {
        $site_id = (int) ee()->config->item('site_id');

        $settings_q = ee()->db->where('site_id', $site_id)
                               ->get('exp_form_submission_settings');

        $current = [];
        foreach ($settings_q->result_array() as $s) {
            $current[$s['setting_key']] = $s['setting_value'];
        }

        $vars = [
            'smtp_host'              => $current['smtp_host']              ?? 'smtp.mailersend.net',
            'smtp_port'              => $current['smtp_port']              ?? '587',
            'smtp_user_set'          => ! empty($current['smtp_user']),
            'smtp_pass_set'          => ! empty($current['smtp_pass']),
            'from_email'             => $current['from_email']             ?? '',
            'from_name'              => $current['from_name']              ?? '',
            'recipient_email'        => $current['recipient_email']        ?? '',
            'captcha_provider'       => $current['captcha_provider']       ?? 'none',
            'captcha_site_key'       => $current['captcha_site_key']       ?? '',
            'captcha_secret_key_set' => ! empty($current['captcha_secret_key']),
            'captcha_v3_threshold'   => $current['captcha_v3_threshold']   ?? '0.5',
            'save_url'               => ee('CP/URL', 'addons/settings/form_submission/save_settings')->compile(),
            'reveal_url'             => ee('CP/URL', 'addons/settings/form_submission/reveal_setting')->compile(),
        ];

        return ee('View')->make('form_submission:settings')->render($vars);
    }

    // ----------------------------------------------------------------
    // save_settings() — persist MailerSend configuration
    // ----------------------------------------------------------------

    public function save_settings()
    {
        // State-changing: POST only. EE core validates the CSRF token on CP POSTs.
        if (ee()->input->method() !== 'post') {
            ee()->functions->redirect(ee('CP/URL', 'addons/settings/form_submission'));
        }

        $site_id = (int) ee()->config->item('site_id');

        // These values are encrypted at rest using EE's Encrypt service
        $sensitive = ['smtp_user', 'smtp_pass', 'captcha_secret_key'];

        // Keys left blank that should preserve their existing stored value
        $keep_if_blank = ['smtp_user', 'smtp_pass', 'captcha_secret_key'];

        $keys = ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'from_email', 'from_name', 'recipient_email',
                 'captcha_provider', 'captcha_site_key', 'captcha_secret_key', 'captcha_v3_threshold'];

        foreach ($keys as $key) {
            $value = ee()->input->post($key, true) ?: '';

            // reCAPTCHA v3 minimum score: clamp to 0.0–1.0, fall back to 0.5 if not numeric
            if ($key === 'captcha_v3_threshold') {
                $value = is_numeric($value) ? (string) max(0, min(1, (float) $value)) : '0.5';
            }

            // Skip saving sensitive keys if left blank — preserves the existing stored value
            if (in_array($key, $keep_if_blank) && $value === '') {
                continue;
            }

            if (in_array($key, $sensitive) && $value !== '') {
                $value = ee('Encrypt')->encode($value, ee()->config->item('session_crypt_key'));
            }

            ee()->db->replace('exp_form_submission_settings', [
                'site_id'       => $site_id,
                'setting_key'   => $key,
                'setting_value' => $value,
            ]);
        }

        ee('CP/Alert')->makeInline('form-submission-settings-saved')
                     ->asSuccess()
                     ->withTitle(lang('fs_settings_saved'))
                     ->addToBody(lang('fs_settings_saved_desc'))
                     ->defer();

        ee()->functions->redirect(ee('CP/URL', 'addons/settings/form_submission/settings'));
    }

    // ----------------------------------------------------------------
    // reveal_setting() — return a decrypted setting value as JSON
    // Only callable by a logged-in CP user via an authenticated XHR.
    // ----------------------------------------------------------------

    public function reveal_setting()
    {
        // EE builds the CP page inside output buffers. Discard all of them so
        // only our JSON reaches the browser — otherwise the response is CP HTML
        // wrapped around the JSON and json() in the fetch fails to parse.
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json');

        // Only respond to XHR requests — rejects direct URL access in a browser tab.
        if (ee()->input->server('HTTP_X_REQUESTED_WITH') !== 'XMLHttpRequest') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }

        $allowed = ['smtp_user', 'smtp_pass', 'captcha_secret_key'];
        $key     = ee()->input->get('key', true);

        if ( ! in_array($key, $allowed, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid key']);
            exit;
        }

        $site_id = (int) ee()->config->item('site_id');
        $q = ee()->db->select('setting_value')
                     ->where('site_id', $site_id)
                     ->where('setting_key', $key)
                     ->get('exp_form_submission_settings');

        if ($q->num_rows() === 0) {
            echo json_encode(['value' => '']);
            exit;
        }

        $stored    = $q->row('setting_value');
        $decrypted = ee('Encrypt')->decode($stored, ee()->config->item('session_crypt_key'));
        $value     = ($decrypted !== false && $decrypted !== '') ? $decrypted : $stored;

        echo json_encode(['value' => $value]);
        exit;
    }
}
// END CLASS

// EOF
