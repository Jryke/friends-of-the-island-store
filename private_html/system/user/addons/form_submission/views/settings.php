<div class="panel">
    <div class="panel-heading">
        <div class="title-bar">
            <h3 class="title-bar__title"><?= lang('fs_nav_settings') ?></h3>
        </div>
    </div>

    <div class="panel-body">
        <?= ee('CP/Alert')->get('form-submission-settings-saved') ?>

        <?= form_open($save_url, ['class' => 'settings']) ?>

        <!-- Decoy fields: browsers autofill the first visible username/password-shaped
             inputs on a page with saved CP login credentials. These absorb that autofill
             so it doesn't land in the real smtp_user/smtp_pass fields below. -->
        <input type="text" name="fakeusernameremembered" style="display:none" tabindex="-1" autocomplete="off" />
        <input type="password" name="fakepasswordremembered" style="display:none" tabindex="-1" autocomplete="off" />

        <h4><?= lang('fs_smtp_heading') ?></h4>

        <fieldset class="col-group">
            <div class="setting-txt">
                <label for="smtp_host"><?= lang('fs_smtp_host') ?></label>
                <div class="field-instruct"><em><?= lang('fs_smtp_host_desc') ?></em></div>
            </div>
            <div class="setting-field">
                <input type="text"
                       name="smtp_host"
                       id="smtp_host"
                       value="<?= htmlspecialchars($smtp_host) ?>"
                       style="width:100%;max-width:320px;" />
            </div>
        </fieldset>

        <fieldset class="col-group">
            <div class="setting-txt">
                <label for="smtp_port"><?= lang('fs_smtp_port') ?></label>
                <div class="field-instruct"><em><?= lang('fs_smtp_port_desc') ?></em></div>
            </div>
            <div class="setting-field">
                <input type="number"
                       name="smtp_port"
                       id="smtp_port"
                       value="<?= htmlspecialchars($smtp_port) ?>"
                       style="width:100px;" />
            </div>
        </fieldset>

        <fieldset class="col-group">
            <div class="setting-txt">
                <label for="smtp_user"><?= lang('fs_smtp_user') ?></label>
                <div class="field-instruct"><em><?= lang('fs_smtp_user_desc') ?></em></div>
            </div>
            <div class="setting-field">
                <div style="position:relative;display:inline-block;width:100%;max-width:400px;">
                    <input type="text"
                           name="smtp_user"
                           id="smtp_user"
                           value=""
                           autocomplete="off"
                           placeholder="<?= $smtp_user_set ? '(saved — leave blank to keep)' : '' ?>"
                           style="width:100%;box-sizing:border-box;padding-right:2.4em;" />
                    <?php if ($smtp_user_set): ?>
                    <a href="#" class="fs-reveal-btn" data-key="smtp_user" title="View saved value"
                       style="position:absolute;right:6px;top:50%;transform:translateY(-50%);margin:0;line-height:1;opacity:0.45;">
                        <img src="/themes/ee/asset/img/eye-open.svg" width="16" height="16" alt="View saved value" style="display:block;" />
                    </a>
                    <?php endif ?>
                </div>
                <?php if ($smtp_user_set): ?>
                <code id="smtp_user_revealed"
                      style="display:none;margin-top:6px;padding:4px 8px;background:#f4f4f4;border:1px solid #ddd;border-radius:3px;font-size:0.9em;word-break:break-all;"></code>
                <?php endif ?>
            </div>
        </fieldset>

        <fieldset class="col-group">
            <div class="setting-txt">
                <label for="smtp_pass"><?= lang('fs_smtp_pass') ?></label>
                <div class="field-instruct"><em><?= lang('fs_smtp_pass_desc') ?></em></div>
            </div>
            <div class="setting-field">
                <div style="position:relative;display:inline-block;width:100%;max-width:400px;">
                    <input type="password"
                           name="smtp_pass"
                           id="smtp_pass"
                           value=""
                           autocomplete="new-password"
                           placeholder="<?= $smtp_pass_set ? '(saved — leave blank to keep)' : '' ?>"
                           style="width:100%;box-sizing:border-box;padding-right:2.4em;" />
                    <style>
                        #smtp_pass::-ms-reveal,
                        #smtp_pass::-ms-clear { display:none; }
                        #smtp_pass::-webkit-contacts-auto-fill-button,
                        #smtp_pass::-webkit-credentials-auto-fill-button { visibility:hidden; pointer-events:none; }
                        #smtp_pass ~ .eye.js-show-password { display:none; }
                    </style>
                    <?php if ($smtp_pass_set): ?>
                    <a href="#" class="fs-reveal-btn" data-key="smtp_pass" title="View saved value"
                       style="position:absolute;right:6px;top:50%;transform:translateY(-50%);margin:0;line-height:1;opacity:0.45;">
                        <img src="/themes/ee/asset/img/eye-open.svg" width="16" height="16" alt="View saved value" style="display:block;" />
                    </a>
                    <?php endif ?>
                </div>
                <?php if ($smtp_pass_set): ?>
                <code id="smtp_pass_revealed"
                      style="display:none;margin-top:6px;padding:4px 8px;background:#f4f4f4;border:1px solid #ddd;border-radius:3px;font-size:0.9em;word-break:break-all;"></code>
                <?php endif ?>
            </div>
        </fieldset>

        <h4><?= lang('fs_sender_heading') ?></h4>

        <fieldset class="col-group">
            <div class="setting-txt">
                <label for="from_email"><?= lang('fs_from_email') ?></label>
                <div class="field-instruct"><em><?= lang('fs_from_email_desc') ?></em></div>
            </div>
            <div class="setting-field">
                <input type="email"
                       name="from_email"
                       id="from_email"
                       value="<?= htmlspecialchars($from_email) ?>"
                       style="width:100%;max-width:320px;" />
            </div>
        </fieldset>

        <fieldset class="col-group">
            <div class="setting-txt">
                <label for="from_name"><?= lang('fs_from_name') ?></label>
                <div class="field-instruct"><em><?= lang('fs_from_name_desc') ?></em></div>
            </div>
            <div class="setting-field">
                <input type="text"
                       name="from_name"
                       id="from_name"
                       value="<?= htmlspecialchars($from_name) ?>"
                       style="width:100%;max-width:320px;" />
            </div>
        </fieldset>

        <fieldset class="col-group">
            <div class="setting-txt">
                <label for="recipient_email"><?= lang('fs_recipient_email') ?></label>
                <div class="field-instruct"><em><?= lang('fs_recipient_email_desc') ?></em></div>
            </div>
            <div class="setting-field">
                <input type="email"
                       name="recipient_email"
                       id="recipient_email"
                       value="<?= htmlspecialchars($recipient_email) ?>"
                       style="width:100%;max-width:320px;" />
            </div>
        </fieldset>

        <h4><?= lang('fs_captcha_heading') ?></h4>

        <fieldset class="col-group">
            <div class="setting-txt">
                <label for="captcha_provider"><?= lang('fs_captcha_provider') ?></label>
                <div class="field-instruct"><em><?= lang('fs_captcha_provider_desc') ?></em></div>
            </div>
            <div class="setting-field">
                <select name="captcha_provider" id="captcha_provider">
                    <option value="none"<?= $captcha_provider === 'none'          ? ' selected' : '' ?>><?= lang('fs_captcha_none') ?></option>
                    <option value="recaptcha_v2"<?= $captcha_provider === 'recaptcha_v2' ? ' selected' : '' ?>><?= lang('fs_captcha_recaptcha_v2') ?></option>
                    <option value="recaptcha_v3"<?= $captcha_provider === 'recaptcha_v3' ? ' selected' : '' ?>><?= lang('fs_captcha_recaptcha_v3') ?></option>
                    <option value="hcaptcha"<?= $captcha_provider === 'hcaptcha'    ? ' selected' : '' ?>><?= lang('fs_captcha_hcaptcha') ?></option>
                </select>
            </div>
        </fieldset>

        <fieldset class="col-group">
            <div class="setting-txt">
                <label for="captcha_site_key"><?= lang('fs_captcha_site_key') ?></label>
                <div class="field-instruct"><em><?= lang('fs_captcha_site_key_desc') ?></em></div>
            </div>
            <div class="setting-field">
                <input type="text"
                       name="captcha_site_key"
                       id="captcha_site_key"
                       value="<?= htmlspecialchars($captcha_site_key) ?>"
                       style="width:100%;max-width:400px;" />
            </div>
        </fieldset>

        <fieldset class="col-group" id="fs-v3-threshold-row">
            <div class="setting-txt">
                <label for="captcha_v3_threshold"><?= lang('fs_captcha_v3_threshold') ?></label>
                <div class="field-instruct"><em><?= lang('fs_captcha_v3_threshold_desc') ?></em></div>
            </div>
            <div class="setting-field">
                <input type="number"
                       name="captcha_v3_threshold"
                       id="captcha_v3_threshold"
                       min="0" max="1" step="0.1"
                       value="<?= htmlspecialchars($captcha_v3_threshold) ?>"
                       style="width:100%;max-width:100px;" />
            </div>
        </fieldset>

        <fieldset class="col-group">
            <div class="setting-txt">
                <label for="captcha_secret_key"><?= lang('fs_captcha_secret_key') ?></label>
                <div class="field-instruct"><em><?= lang('fs_captcha_secret_key_desc') ?></em></div>
            </div>
            <div class="setting-field">
                <div style="position:relative;display:inline-block;width:100%;max-width:400px;">
                    <input type="password"
                           name="captcha_secret_key"
                           id="captcha_secret_key"
                           value=""
                           autocomplete="new-password"
                           placeholder="<?= $captcha_secret_key_set ? '(saved — leave blank to keep)' : '' ?>"
                           style="width:100%;box-sizing:border-box;padding-right:2.4em;" />
                    <style>
                        #captcha_secret_key::-ms-reveal,
                        #captcha_secret_key::-ms-clear { display:none; }
                        #captcha_secret_key::-webkit-contacts-auto-fill-button,
                        #captcha_secret_key::-webkit-credentials-auto-fill-button { visibility:hidden; pointer-events:none; }
                        #captcha_secret_key ~ .eye.js-show-password { display:none; }
                    </style>
                    <?php if ($captcha_secret_key_set): ?>
                    <a href="#" class="fs-reveal-btn" data-key="captcha_secret_key" title="View saved value"
                       style="position:absolute;right:6px;top:50%;transform:translateY(-50%);margin:0;line-height:1;opacity:0.45;">
                        <img src="/themes/ee/asset/img/eye-open.svg" width="16" height="16" alt="View saved value" style="display:block;" />
                    </a>
                    <?php endif ?>
                </div>
                <?php if ($captcha_secret_key_set): ?>
                <code id="captcha_secret_key_revealed"
                      style="display:none;margin-top:6px;padding:4px 8px;background:#f4f4f4;border:1px solid #ddd;border-radius:3px;font-size:0.9em;word-break:break-all;"></code>
                <?php endif ?>
            </div>
        </fieldset>

        <fieldset class="form-ctrls">
            <input class="btn" type="submit" value="<?= lang('fs_save_settings') ?>" />
        </fieldset>

        <?= form_close() ?>

        <script>
        // Only show the v3 score threshold when reCAPTCHA v3 is selected
        (function () {
            var sel = document.getElementById('captcha_provider');
            var row = document.getElementById('fs-v3-threshold-row');
            function sync() { row.style.display = sel.value === 'recaptcha_v3' ? '' : 'none'; }
            sel.addEventListener('change', sync);
            sync();
        })();
        </script>

        <script>
        (function () {
            var REVEAL_URL = <?= json_encode($reveal_url) ?>;
            var timers     = {};

            document.querySelectorAll('.fs-reveal-btn').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();

                    var key     = this.dataset.key;
                    var display = document.getElementById(key + '_revealed');
                    var icon    = this.querySelector('img');

                    // Toggle hide
                    if (this.dataset.revealed === '1') {
                        display.textContent       = '';
                        display.style.display     = 'none';
                        this.style.opacity        = '0.45';
                        this.dataset.revealed     = '0';
                        clearTimeout(timers[key]);
                        return;
                    }

                    var self = this;
                    fetch(REVEAL_URL + '&key=' + encodeURIComponent(key), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.value) {
                            display.textContent   = data.value;
                            display.style.display = 'block';
                            self.style.opacity    = '1';
                            self.dataset.revealed = '1';

                            // Auto-hide after 30 seconds
                            timers[key] = setTimeout(function () {
                                if (self.dataset.revealed === '1') self.click();
                            }, 30000);
                        }
                    })
                    .catch(function () {
                        display.textContent   = 'Could not retrieve value.';
                        display.style.display = 'block';
                    });
                });
            });
        })();
        </script>
    </div>
</div>
