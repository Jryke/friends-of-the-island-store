<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Environment-specific and secret values come from private_html/.env.php (EE_* keys, not tracked by git).
// See private_html/.env.example.php for the full list.
$env = function ($key, $default = null) {
    return isset($_ENV[$key]) ? $_ENV[$key] : $default;
};

$config['site_license_key'] = '';
$config['app_version'] = '7.5.27';
$config['encryption_key'] = $env('EE_ENCRYPTION_KEY');
$config['session_crypt_key'] = $env('EE_SESSION_CRYPT_KEY');
$config['database'] = array(
	'expressionengine' => array(
		'hostname' => $env('EE_DB_HOST'),
		'database' => $env('EE_DB_DATABASE'),
		'username' => $env('EE_DB_USERNAME'),
		'password' => $env('EE_DB_PASSWORD'),
		'dbprefix' => 'exp_',
		'char_set' => 'utf8mb4',
		'dbcollat' => 'utf8mb4_unicode_ci',
		'port'     => ''
	),
);
$config['show_ee_news'] = 'y';

// Paths and URLs: derived/env-driven so a DB copied between local and production never carries the other environment's values.
// config.php lives at private_html/system/user/config/ -> web root is ../../../../public_html
$config['base_path'] = rtrim(realpath(__DIR__ . '/../../../../public_html'), '/') . '/';
$config['base_url'] = $env('EE_BASE_URL');
$config['site_url'] = $env('EE_BASE_URL');
$config['cp_url'] = rtrim($env('EE_BASE_URL', ''), '/') . '/admin.php';

// Outgoing email (local = Mailpit; production = MailerSend). Credentials live in .env.php.
$config['mail_protocol'] = $env('EE_MAIL_PROTOCOL', 'smtp');
$config['smtp_server'] = $env('EE_SMTP_HOST');
$config['smtp_port'] = $env('EE_SMTP_PORT', '587');
$config['smtp_username'] = $env('EE_SMTP_USER', '');
$config['smtp_password'] = $env('EE_SMTP_PASS', '');
$config['email_smtp_crypto'] = $env('EE_SMTP_CRYPTO', '');
$config['email_newline'] = "\r\n";
$config['email_crlf'] = "\r\n";
$config['webmaster_email'] = $env('EE_WEBMASTER_EMAIL');
$config['webmaster_name'] = $env('EE_WEBMASTER_NAME');
