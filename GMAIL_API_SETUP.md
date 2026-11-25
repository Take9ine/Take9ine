# Gmail API password reset setup

Customer password-reset codes are sent through the Gmail API. Configure these
values in the private project `.env` for local work or as protected server
environment variables for hosting:

- `GMAIL_SENDER_EMAIL` (the authorized sender mailbox)
- `GMAIL_CLIENT_ID`
- `GMAIL_CLIENT_SECRET`
- `GMAIL_REFRESH_TOKEN`

In Google Cloud Console, enable the Gmail API and add
`https://www.googleapis.com/auth/gmail.send` under **Google Auth Platform >
Data access**. For localhost testing, keep the consent screen in **Testing**
and add `take9ine254@gmail.com` as a test user.

Create a **Web application** OAuth client and add this authorized redirect URI:

```text
https://developers.google.com/oauthplayground
```

In Google's OAuth 2.0 Playground, open its settings, enable **Use your own
OAuth credentials**, and enter that client's ID and secret. Request the
`https://www.googleapis.com/auth/gmail.send` scope, authorize as
`take9ine254@gmail.com`, then exchange the authorization code for tokens. Put
the resulting refresh token into the server environment with the client ID and
secret. Do not paste credentials into chat, source control, or client-side code;
keep them outside the web root.

While the consent screen is in Testing, only listed test users can authorize
the app, and refresh tokens for non-basic scopes can expire after seven days.

On XAMPP, the application loads the root `.env` file automatically. For hosting,
prefer configuring the variables in the hosting provider's protected
environment settings. Apache `SetEnv` values take precedence over `.env`.
Ensure PHP's `curl` extension is enabled and apply the `password_reset_tokens`
table definition in `database/schema.sql` to the existing database. Reset codes
are stored as password hashes, expire after 10 minutes,
are limited to five failed attempts, and can be requested once per minute per
account. Reset requests use the same response for registered and unregistered
email addresses. A successful reset updates the user password hash and
invalidates that user's outstanding reset codes.

For example, Apache can pass these values to PHP from its server configuration
(replace the placeholders with credentials from the Google Cloud project):

```apache
SetEnv GMAIL_CLIENT_ID "your-oauth-client-id"
SetEnv GMAIL_CLIENT_SECRET "your-oauth-client-secret"
SetEnv GMAIL_REFRESH_TOKEN "your-oauth-refresh-token"
SetEnv GMAIL_SENDER_EMAIL "authorized-sender@example.com"
```

Keep the real values outside the website directory and never commit them.

## Google Cloud publication

In the Google Cloud project, enable the Gmail API, set up the OAuth consent
screen and create OAuth client credentials. Authorize
`take9ine254@gmail.com` for the `https://www.googleapis.com/auth/gmail.send`
scope and keep the resulting refresh token private. Publishing the consent
screen as In production and any Google verification must be completed by a
project owner in Google Cloud Console; this code change cannot publish or verify
the project.

