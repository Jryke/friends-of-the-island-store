<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$lang = array(

    // Navigation
    'fs_nav_submissions'       => 'Submissions',
    'fs_nav_settings'          => 'Settings',

    // List view
    'fs_submissions'           => 'Forms — Submissions',
    'fs_no_submissions'        => 'No submissions found.',
    'fs_total_submissions'     => '%d total submission(s)',
    'fs_filter_by_form'        => 'Filter by form:',
    'fs_all_forms'             => 'All forms',
    'fs_first_name'            => 'First Name',
    'fs_last_name'             => 'Last Name',
    'fs_email'                 => 'Email',
    'fs_subject'               => 'Subject',
    'fs_form_name'             => 'Form',
    'fs_date'                  => 'Date',
    'fs_sender_ip'             => 'Sender IP',
    'fs_email_sent'            => 'Email Sent',
    'fs_referral'              => 'Referral',
    'fs_yes'                   => 'Yes',
    'fs_no'                    => 'No',

    // Detail view
    'fs_submission'            => 'Submission',
    'fs_back_to_list'          => 'Back to list',
    'fs_delete_submission'     => 'Delete Submission',
    'fs_confirm_delete'        => 'Are you sure you want to delete this submission?',
    'fs_deleted'               => 'Deleted',
    'fs_submission_deleted'    => 'The submission has been deleted.',

    // Settings view — SMTP
    'fs_smtp_heading'          => 'SMTP Connection',
    'fs_smtp_host'             => 'SMTP Host',
    'fs_smtp_host_desc'        => 'e.g. smtp.mailersend.net',
    'fs_smtp_port'             => 'SMTP Port',
    'fs_smtp_port_desc'        => '587 for STARTTLS (recommended), 465 for SSL.',
    'fs_smtp_user'             => 'SMTP Username',
    'fs_smtp_user_desc'        => 'The SMTP username provided by MailerSend.',
    'fs_smtp_pass'             => 'SMTP Password',
    'fs_smtp_pass_desc'        => 'The SMTP password provided by MailerSend.',

    // Settings view — sender / recipient
    'fs_sender_heading'        => 'Sender &amp; Recipient',
    'fs_from_email'            => 'From Email',
    'fs_from_email_desc'       => 'Must be a verified sender domain in MailerSend. Also used as Return-Path.',
    'fs_from_name'             => 'From Name',
    'fs_from_name_desc'        => 'Display name shown in the notification email.',
    'fs_recipient_email'       => 'Recipient Email',
    'fs_recipient_email_desc'  => 'Email address that will receive form submission notifications.',
    'fs_save_settings'         => 'Save Settings',
    'fs_settings_saved'        => 'Settings Saved',
    'fs_settings_saved_desc'   => 'Your Forms settings have been saved.',

    // Settings view — CAPTCHA
    'fs_captcha_heading'              => 'CAPTCHA',
    'fs_captcha_provider'             => 'Provider',
    'fs_captcha_provider_desc'        => 'Select a CAPTCHA provider, or leave as None to disable.',
    'fs_captcha_none'                 => 'None (disabled)',
    'fs_captcha_recaptcha_v2'         => 'reCAPTCHA v2 (checkbox)',
    'fs_captcha_recaptcha_v3'         => 'reCAPTCHA v3 (invisible / score)',
    'fs_captcha_hcaptcha'             => 'hCaptcha',
    'fs_captcha_site_key'             => 'Site Key',
    'fs_captcha_site_key_desc'        => 'Public key — embedded in the frontend widget.',
    'fs_captcha_secret_key'           => 'Secret Key',
    'fs_captcha_secret_key_desc'      => 'Private key used for server-side token verification.',
    'fs_captcha_v3_threshold'         => 'v3 Minimum Score',
    'fs_captcha_v3_threshold_desc'    => 'reCAPTCHA v3 only. Submissions scoring below this (0.0 = bot, 1.0 = human) are rejected. Default 0.5; raise it to block more spam, lower it if real visitors are being rejected.',
    'fs_captcha_failed'               => 'Verification Failed',
    'fs_captcha_failed_desc'          => 'CAPTCHA verification failed. Please go back and try again.',
    'fs_captcha_go_back'              => 'Go Back',
    'fs_timetrap_failed'              => 'Please Try Again',
    'fs_timetrap_failed_desc'         => 'Your message was submitted too quickly or the form has expired. Please go back, wait a moment, and send it again.',
    'fs_timetrap_go_back'             => 'Go Back',

    // Required by EE
    ''                         => '',
);

// EOF
