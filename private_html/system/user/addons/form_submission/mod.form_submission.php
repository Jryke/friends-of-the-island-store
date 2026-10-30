<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Form Submission Module
 *
 * Wraps HTML forms to capture submissions, save them to the database,
 * and send email notifications via MailerSend SMTP.
 *
 * Template tag usage (params: form_name, return, id):
 *
 *   {exp:form_submission:form
 *       form_name="contact"
 *       return="contact"
 *       id="contact-form"
 *   }
 *       <input type="text" name="first_name" />
 *       <input type="text" name="last_name" />
 *       <input type="email" name="email" />
 *       <input type="text" name="subject" />
 *       <textarea name="message"></textarea>
 *       <input type="submit" value="Submit" />
 *   {/exp:form_submission:form}
 */
class Form_submission
{
    // POST keys that belong to the framework, not the user's form fields
    private $reserved_keys = ['ACT', 'RET', 'URI', 'form_name', 'XID', 'submit', 'csrf_token', 'site_id',
                               'g-recaptcha-response', 'h-captcha-response', 'redirect', 'ref', 'es_ref_fb',
                               'fs_website_url', 'fs_ts'];

    // Anti-spam: honeypot + time-trap
    const HONEYPOT_FIELD   = 'fs_website_url';  // decoy input; humans never fill it
    const TIMETRAP_FIELD   = 'fs_ts';           // "<unix_ts>.<hmac>" written at render time
    const MIN_FILL_SECONDS = 3;                 // faster than this = bot
    const MAX_FORM_AGE     = 7200;              // older than 2 hours = stale/replayed

    // Caps on collected user fields (guards against oversized/abusive POSTs)
    const MAX_FIELDS       = 25;                // max user fields stored/emailed
    const MAX_VALUE_LENGTH = 5000;              // max characters per field value

    // ----------------------------------------------------------------
    // Template tag: {exp:form_submission:form} ... {/exp:form_submission:form}
    // ----------------------------------------------------------------

    /**
     * Wrap template content in a <form> tag pre-loaded with the hidden
     * fields EE needs to route the submission to process().
     */
    public function form()
    {
        $tagdata  = ee()->TMPL->tagdata;
        $form_name = ee()->TMPL->fetch_param('form_name', 'default');
        $return    = ee()->TMPL->fetch_param('return', ee()->uri->uri_string);

        ee()->load->helper('form');

        // Parse {member_email} and {member_name} inside the tag pair
        foreach (ee()->TMPL->var_single as $key => $val) {
            if ($key === 'member_email') {
                $email = ee()->session->userdata['email'] ?? '';
                $tagdata = ee()->TMPL->swap_var_single($key, form_prep($email), $tagdata);
            }
            if ($key === 'member_name') {
                $name = ee()->session->userdata['screen_name'] ?? ee()->session->userdata['username'] ?? '';
                $tagdata = ee()->TMPL->swap_var_single($key, form_prep($name), $tagdata);
            }
            if (strncmp($key, 'current_time', 12) === 0) {
                $tagdata = ee()->TMPL->swap_var_single($key, ee()->localize->format_date($val), $tagdata);
            }
        }

        $data = [
            'id'    => (ee()->TMPL->form_id != '') ? ee()->TMPL->form_id : ee()->TMPL->fetch_param('form_id', 'form-submission'),
            'class' => ee()->TMPL->form_class,
            'hidden_fields' => [
                'ACT'      => ee()->functions->fetch_action_id('Form_submission', 'process'),
                'RET'      => $return,
                'URI'      => (ee()->uri->uri_string != '') ? ee()->uri->uri_string : 'index',
                'form_name' => $form_name,
            ],
        ];

        $form_id = $data['id'];

        $res  = ee()->functions->form_declaration($data);
        $res .= stripslashes($tagdata);

        // Inject CAPTCHA widget if a provider and site key are configured
        $captcha_provider = $this->_get_setting('captcha_provider') ?: 'none';
        if ($captcha_provider !== 'none') {
            $captcha_site_key = $this->_get_setting('captcha_site_key') ?: '';
            if ($captcha_site_key !== '') {
                $res .= $this->_captcha_widget($captcha_provider, $captcha_site_key, $form_id);
            }
        }

        // Anti-spam fields: off-screen honeypot + HMAC-signed render timestamp
        $res .= $this->_antispam_fields();

        $res .= '</form>';

        return $res;
    }

    // ----------------------------------------------------------------
    // EE Action: called when the form is submitted (ACT hidden field)
    // ----------------------------------------------------------------

    /**
     * Process a form submission:
     *   1. Collect and sanitise POST fields
     *   2. Save to exp_form_submissions
     *   3. Send notification email via MailerSend
     *   4. Redirect / show thank-you message
     */
    public function process()
    {
        if (empty($_POST)) {
            return;
        }

        // Gather user-supplied fields (strip framework keys)
        $fields = [];
        foreach ($_POST as $key => $val) {
            if (in_array($key, $this->reserved_keys, true)) {
                continue;
            }
            // Skip array/non-scalar POST values (e.g. message[]=x) and cap the field count
            if ( ! is_scalar($val) || count($fields) >= self::MAX_FIELDS) {
                continue;
            }
            $clean = (string) ee()->input->post($key, true);
            $fields[$key] = function_exists('mb_substr')
                ? mb_substr($clean, 0, self::MAX_VALUE_LENGTH, 'UTF-8')
                : substr($clean, 0, self::MAX_VALUE_LENGTH);
        }

        $form_name = ee()->input->post('form_name', true) ?: 'default';

        // Honeypot: a filled decoy field means a bot. Fake success (no save, no email)
        // so the bot isn't tipped off.
        if ((string) ee()->input->post(self::HONEYPOT_FIELD) !== '') {
            $this->_redirect_success();
        }

        // Time-trap: signed timestamp must be valid, not too fresh, not too old.
        if ( ! $this->_timetrap_ok()) {
            ee()->lang->loadfile('form_submission');
            ee()->output->show_message([
                'title'    => lang('fs_timetrap_failed'),
                'heading'  => lang('fs_timetrap_failed'),
                'content'  => lang('fs_timetrap_failed_desc'),
                'redirect' => '',
                'link'     => [ee()->functions->form_backtrack(1), lang('fs_timetrap_go_back')],
            ]);
            return;
        }

        // Verify CAPTCHA token if a provider is configured
        $captcha_provider = $this->_get_setting('captcha_provider') ?: 'none';
        if ($captcha_provider !== 'none' && ! $this->_verify_captcha($captcha_provider)) {
            ee()->lang->loadfile('form_submission');
            ee()->output->show_message([
                'title'    => lang('fs_captcha_failed'),
                'heading'  => lang('fs_captcha_failed'),
                'content'  => lang('fs_captcha_failed_desc'),
                'redirect' => '',
                'link'     => [ee()->functions->form_backtrack(1), lang('fs_captcha_go_back')],
            ]);
            return;
        }

        // Validate email field if present
        if (isset($fields['email']) && $fields['email'] !== '') {
            if ( ! filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
                $fields['email'] = '';
            }
        }

        // Attach the affiliate referral from the Affiliates addon's cookie. If the visitor
        // blocks cookies, fall back to its es_ref_fb hidden field (never a free-form 'ref' field).
        $ref = (string) ee()->input->cookie('es_ref');
        if ($ref === '') {
            $ref = (string) ee()->input->post('es_ref_fb', true);
        }
        if ($ref !== '' && preg_match('/^[a-z0-9_-]{1,50}\z/D', $ref)) {
            // If the Affiliates addon is installed, only credit a registered, enabled affiliate
            // (the cookie and hidden field are client-controlled). Without it, keep the value as-is.
            $registered = true;
            if (ee()->db->table_exists('affiliates')) {
                $registered = ee()->db->where('site_id', (int) ee()->config->item('site_id'))
                                      ->where('slug', $ref)
                                      ->where('enabled', 1)
                                      ->count_all_results('affiliates') > 0;
            }
            if ($registered) {
                $fields['ref'] = $ref;
            }
        }

        // ---- Save submission to the database ----
        $row = [
            'form_name'       => $form_name,
            'submission_date' => ee()->localize->now,
            'sender_ip'       => ee()->input->ip_address(),
            'fields'          => json_encode($fields, JSON_UNESCAPED_UNICODE),
            'email_sent'      => 0,
        ];

        ee()->db->insert('exp_form_submissions', $row);
        $submission_id = ee()->db->insert_id();

        // ---- Send email via MailerSend ----
        $email_sent = $this->_send_mailersend($form_name, $fields);

        if ($email_sent) {
            ee()->db->where('submission_id', $submission_id)
                    ->update('exp_form_submissions', ['email_sent' => 1]);
        }

        $this->_redirect_success();
    }

    /**
     * Build the return URL from RET and 303-redirect. Never returns.
     * Shared by the normal success path and the silent honeypot path.
     */
    private function _redirect_success()
    {
        // ---- Build return URL ----
        $ret_raw = ee()->input->post('RET', true);
        $x = explode('|', (string) $ret_raw);

        if (is_numeric($x[0])) {
            $return_link = ee()->functions->form_backtrack($x[0]);
        } elseif ($x[0] === '') {
            $return_link = ee()->functions->form_backtrack(1);
        } elseif ( ! preg_match('{^https?://}i', $x[0])) {
            $return_link = ee()->functions->create_url($x[0]);
        } else {
            // Only allow absolute redirects to the same host as the site —
            // prevents open-redirect abuse if RET is tampered with directly.
            $site_host = parse_url(ee()->config->item('site_url'), PHP_URL_HOST);
            $ret_host  = parse_url($x[0], PHP_URL_HOST);
            $return_link = ($ret_host && $ret_host === $site_host)
                ? $x[0]
                : ee()->functions->form_backtrack(1);
        }

        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Location: ' . $return_link, true, 303);
        exit;
    }

    // ----------------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------------

    /** HMAC of a timestamp string, keyed with EE's session_crypt_key. */
    private function _timetrap_sig($ts)
    {
        return hash_hmac('sha256', (string) $ts, (string) ee()->config->item('session_crypt_key'));
    }

    /**
     * Hidden honeypot (off-screen, not display:none) + signed timestamp field.
     */
    private function _antispam_fields()
    {
        $ts    = time();
        $token = $ts . '.' . $this->_timetrap_sig($ts);

        return '<div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;" aria-hidden="true">'
             . '<input type="text" name="' . self::HONEYPOT_FIELD . '" value="" tabindex="-1" autocomplete="off" aria-hidden="true">'
             . '</div>'
             . '<input type="hidden" name="' . self::TIMETRAP_FIELD . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Verify the signed render timestamp and the elapsed time.
     * @return bool false if missing, forged, too fast, or too old
     */
    private function _timetrap_ok()
    {
        $raw = (string) ee()->input->post(self::TIMETRAP_FIELD);
        if ( ! preg_match('/^(\d{1,12})\.([a-f0-9]{64})\z/D', $raw, $m)) {
            return false;
        }
        if ( ! hash_equals($this->_timetrap_sig($m[1]), $m[2])) {
            return false;
        }
        $elapsed = time() - (int) $m[1];
        return $elapsed >= self::MIN_FILL_SECONDS && $elapsed <= self::MAX_FORM_AGE;
    }

    /**
     * Send a notification email via MailerSend SMTP (STARTTLS on 587/2525, SSL on 465).
     *
     * Uses EE's built-in email library initialised with the SMTP credentials
     * stored in the add-on settings. Return-Path is explicitly set to the
     * from address, which MailerSend requires to accept the message.
     *
     * @param  string $form_name  Human-readable form identifier
     * @param  array  $fields     Sanitised form fields
     * @return bool               true on success
     */
    private function _send_mailersend($form_name, array $fields)
    {
        $smtp_host       = $this->_get_setting('smtp_host')       ?: 'smtp.mailersend.net';
        $smtp_port       = (int) ($this->_get_setting('smtp_port') ?: 587);
        $smtp_user       = $this->_get_setting('smtp_user')       ?: '';
        $smtp_pass       = $this->_get_setting('smtp_pass')       ?: '';
        $from_email      = $this->_get_setting('from_email')      ?: '';
        $from_name       = $this->_get_setting('from_name')       ?: '';
        $recipient_email = $this->_get_setting('recipient_email') ?: '';

        if (empty($smtp_user) || empty($smtp_pass) || empty($from_email) || empty($recipient_email)) {
            return false;
        }

        // Build subject — strip CRLF to prevent email header injection
        $subject = 'New ' . ucwords(str_replace(['_', '-'], ' ', $form_name)) . ' Submission';
        if ( ! empty($fields['subject'])) {
            $subject = str_replace(["\r", "\n"], '', $fields['subject']);
        }

        // Build plain-text body
        $first_name = trim($fields['first_name'] ?? '');
        $last_name  = trim($fields['last_name']  ?? '');
        $email      = trim($fields['email']       ?? '');
        $message    = trim($fields['message']     ?? '');

        $site_name = ee()->config->item('site_name') ?: 'website';

        $lines = [];
        $lines[] = trim("{$first_name} {$last_name}");
        $lines[] = $email;
        if ($message !== '') {
            $lines[] = '';
            $lines[] = $message;
        }
        if (($fields['ref'] ?? '') !== '') {
            $lines[] = '';
            $lines[] = 'Referral: ' . $fields['ref'];
        }
        $lines[] = '';
        $lines[] = "Submitted from {$site_name} website";
        $text_body = implode("\n", $lines);

        // Initialise EE's email library with our SMTP config, overriding
        // any global EE mail settings.
        $smtp_crypto = ($smtp_port === 465) ? 'ssl' : 'tls';   // 465 = implicit SSL; 587/2525 = STARTTLS

        ee()->load->library('email');
        ee()->email->initialize([
            'protocol'    => 'smtp',
            'smtp_host'   => $smtp_host,
            'smtp_port'   => $smtp_port,
            'smtp_user'   => $smtp_user,
            'smtp_pass'   => $smtp_pass,
            'smtp_crypto' => $smtp_crypto,
            'wordwrap'    => true,
            'mailtype'    => 'text',
            'charset'     => 'utf-8',
            'newline'     => "\r\n",
            'crlf'        => "\r\n",
        ]);

        // Third argument sets Return-Path — required by MailerSend
        ee()->email->from($from_email, $from_name, $from_email);
        ee()->email->to($recipient_email);
        ee()->email->subject($subject);
        ee()->email->message($text_body);

        return (bool) ee()->email->send(false);
    }

    /**
     * Return the HTML + JS snippet that renders a CAPTCHA widget inside the form.
     *
     * For reCAPTCHA v3 the widget is invisible: a hidden input is populated with
     * a token via a submit-event listener so the token is fresh when the form posts.
     *
     * @param  string $provider   'recaptcha_v2' | 'recaptcha_v3' | 'hcaptcha'
     * @param  string $site_key   Public site key
     * @param  string $form_id    HTML id of the enclosing <form> element
     * @return string
     */
    private function _captcha_widget($provider, $site_key, $form_id)
    {
        $key_attr = htmlspecialchars($site_key, ENT_QUOTES);
        $key_js   = json_encode($site_key);
        $id_js    = json_encode($form_id);

        switch ($provider) {

            case 'recaptcha_v2':
                return '<div class="g-recaptcha" data-sitekey="' . $key_attr . '" style="margin:12px 0;"></div>'
                     . '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';

            case 'recaptcha_v3':
                // Intercept the form submit, obtain a fresh token, then re-submit.
                return '<input type="hidden" name="g-recaptcha-response" id="fs-grecaptcha-token" value="">'
                     . '<script src="https://www.google.com/recaptcha/api.js?render=' . urlencode($site_key) . '"></script>'
                     . '<script>'
                     . '(function(){'
                     .   'var f=document.getElementById(' . $id_js . ');'
                     .   'if(!f)return;'
                     .   'f.addEventListener("submit",function(e){'
                     .     'var t=document.getElementById("fs-grecaptcha-token");'
                     .     'if(t&&t.value!=="")return;'  // token already set, allow submit
                     .     'e.preventDefault();'
                     .     'try{'
                     .       'grecaptcha.ready(function(){'
                     .         'grecaptcha.execute(' . $key_js . ',{action:"submit"}).then(function(token){'
                     .           'if(t)t.value=token;'
                     .           'f.submit();'
                     .         '}).catch(function(){ f.submit(); });'  // reCAPTCHA failed — submit anyway, server will reject
                     .       '});'
                     .     '}catch(err){ f.submit(); }'  // grecaptcha not defined — submit anyway
                     .   '});'
                     . '})();'
                     . '</script>';

            case 'hcaptcha':
                return '<div class="h-captcha" data-sitekey="' . $key_attr . '" style="margin:12px 0;"></div>'
                     . '<script src="https://js.hcaptcha.com/1/api.js" async defer></script>';
        }

        return '';
    }

    /**
     * Verify a CAPTCHA token against the provider's server-side API.
     *
     * @param  string $provider
     * @return bool   true if verified, or if CAPTCHA isn't fully configured (no site/secret key)
     */
    private function _verify_captcha($provider)
    {
        $secret = $this->_get_setting('captcha_secret_key') ?: '';

        // Fail open: if the keys aren't fully set up, skip verification. The widget
        // is only rendered when a site key exists, so a missing key would otherwise
        // reject every submission.
        if ($secret === '' || ($this->_get_setting('captcha_site_key') ?: '') === '') {
            return true;
        }

        switch ($provider) {
            case 'recaptcha_v2':
            case 'recaptcha_v3':
                $token    = (string) (ee()->input->post('g-recaptcha-response') ?: '');
                $endpoint = 'https://www.google.com/recaptcha/api/siteverify';
                break;
            case 'hcaptcha':
                $token    = (string) (ee()->input->post('h-captcha-response') ?: '');
                $endpoint = 'https://hcaptcha.com/siteverify';
                break;
            default:
                return true;
        }

        if ($token === '') {
            return false;
        }

        $raw = $this->_http_post($endpoint, ['secret' => $secret, 'response' => $token]);
        if ($raw === false) {
            return false;
        }

        $data = json_decode($raw, true);
        if (empty($data['success'])) {
            return false;
        }

        // reCAPTCHA v3 returns a score (0.0–1.0); require the configured minimum (default 0.5).
        if ($provider === 'recaptcha_v3') {
            $threshold = $this->_get_setting('captcha_v3_threshold');
            $threshold = is_numeric($threshold) ? (float) $threshold : 0.5;
            return (float) ($data['score'] ?? 0) >= $threshold;
        }

        return true;
    }

    /**
     * Make a simple HTTPS POST request using cURL.
     *
     * @param  string $url
     * @param  array  $fields
     * @return string|false  Response body, or false on failure
     */
    private function _http_post($url, array $fields)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * Retrieve a setting value from exp_form_submission_settings.
     * Sensitive keys are transparently decrypted using EE's Encrypt service.
     *
     * @param  string $key
     * @return string|null
     */
    private function _get_setting($key)
    {
        // Per-request memo of raw rows for the current site, loaded in ONE query.
        // Settings are only read (never written) during form()/process().
        static $cache = null;
        static $cache_site = null;

        // These keys are stored encrypted
        $sensitive = ['smtp_user', 'smtp_pass', 'captcha_secret_key'];

        $site_id = (int) ee()->config->item('site_id');

        if ($cache === null || $cache_site !== $site_id) {
            $cache = [];
            $cache_site = $site_id;
            $query = ee()->db->select('setting_key, setting_value')
                             ->where('site_id', $site_id)
                             ->get('exp_form_submission_settings');
            foreach ($query->result_array() as $row) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        }

        if ( ! array_key_exists($key, $cache)) {
            return null;
        }

        $value = $cache[$key];

        if (in_array($key, $sensitive) && $value !== '') {
            $decrypted = ee('Encrypt')->decode($value, ee()->config->item('session_crypt_key'));
            // Fall back to raw value if decryption fails (e.g. pre-encryption data)
            return ($decrypted !== false && $decrypted !== '') ? $decrypted : $value;
        }

        return $value;
    }
}
// END CLASS

// EOF
