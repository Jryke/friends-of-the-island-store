<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Form Submission Extension
 *
 * Hooks into template_post_parse to inject a JS redirect countdown into
 * any page that receives the redirect_after / redirect_to query parameters
 * set by mod.form_submission.php when redirect="N" is used on the tag.
 *
 * No template changes are required — the script is injected automatically
 * before </body> on the success page.
 */
class Form_submission_ext
{
    public $version = '1.0.0';

    public function template_post_parse($final_template, $is_partial, $site_id)
    {
        // If another extension ran first, start from its output
        if (ee()->extensions->last_call !== false) {
            $final_template = ee()->extensions->last_call;
        }

        // Only act on the fully-rendered page, not embedded partials
        if ($is_partial) {
            return $final_template;
        }

        // Read directly from $_GET to avoid XSS cleaning modifying the URL
        $redirect_after = isset($_GET['redirect_after']) ? (int) $_GET['redirect_after'] : 0;
        $redirect_to    = isset($_GET['redirect_to'])    ? (string) $_GET['redirect_to'] : '';

        if ($redirect_after > 0 && $redirect_to !== '') {
            $script = '<script>setTimeout(function(){'
                    . 'window.location=' . json_encode($redirect_to) . ';'
                    . '},' . ($redirect_after * 1000) . ');</script>';

            // Case-insensitive replace to handle </BODY> or </Body>
            $modified = preg_replace('/<\/body>/i', $script . '</body>', $final_template, 1);

            // Fallback: try </html> if </body> was absent
            if ($modified === null || $modified === $final_template) {
                $modified = preg_replace('/<\/html>/i', $script . '</html>', $final_template, 1);
            }

            // Last resort: append to end of output
            if ($modified === null || $modified === $final_template) {
                $modified = $final_template . $script;
            }

            $final_template = $modified;
        }

        return $final_template;
    }
}
// END CLASS

// EOF
