# Reference list of every environment value the site reads. NO real values in this file.
# Copy to private_html/.env.php (same folder as system/) on each environment and replace each **** with the real value.
# private_html/.env.php is NOT tracked by git. Comments must start with # (not //). Wrap values in double quotes.

# --- Database ---
# Database server host name
EE_DB_HOST="****"
# Database name
EE_DB_DATABASE="****"
# Database user
EE_DB_USERNAME="****"
# Database password
EE_DB_PASSWORD="****"

# --- EE security keys (40-character strings; must match the keys the database was built with) ---
EE_ENCRYPTION_KEY="****"
EE_SESSION_CRYPT_KEY="****"

# --- Site URL (with trailing slash), e.g. the live https:// address of this environment ---
EE_BASE_URL="****"

# --- Outgoing email (contact form) ---
# Mail protocol (smtp)
EE_MAIL_PROTOCOL="****"
# SMTP server host name and port
EE_SMTP_HOST="****"
EE_SMTP_PORT="****"
# SMTP username and password (from the mail provider's SMTP user)
EE_SMTP_USER="****"
EE_SMTP_PASS="****"
# SMTP encryption: tls, ssl, or empty
EE_SMTP_CRYPTO="****"
# "From" address and name on contact-form emails (address must be on the mail provider's verified domain)
EE_WEBMASTER_EMAIL="****"
EE_WEBMASTER_NAME="****"
