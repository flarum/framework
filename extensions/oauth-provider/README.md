# OAuth Provider

Turns Flarum into an OAuth 2.0 authorization server and OpenID Connect provider, so other applications can let users sign in with their forum account and act on their behalf with the scopes they consent to.

## Running behind a reverse proxy

Dynamic client registration (`/oauth/register`, 10 registrations per hour) and client metadata document fetches (20 per hour) are rate limited per client IP address. Flarum takes that address from `REMOTE_ADDR` and does not read `X-Forwarded-For` itself.

Behind a reverse proxy, load balancer or CDN, `REMOTE_ADDR` is the proxy's address unless the web server is told otherwise: every client then shares one rate limit bucket, and a single client can use it up for everyone. Configure the web server to restore the client's address from the forwarding header **sent by your own proxies only**, for example:

- nginx: `set_real_ip_from <proxy address or range>;` and `real_ip_header X-Forwarded-For;` (`ngx_http_realip_module`)
- Apache: `RemoteIPHeader X-Forwarded-For` and `RemoteIPTrustedProxy <proxy address or range>` (`mod_remoteip`)

Never trust the header from any address: clients could then choose their own address and escape the limits.

## Keys

The RSA key that signs access and ID tokens, and the key that encrypts authorization codes and refresh tokens, are generated on first use and stored in `storage/oauth-provider/`, readable by the web server user only. Include that directory in backups: losing it invalidates every issued token. Keep it out of anything served publicly.

When several servers or containers serve one forum, `storage/oauth-provider/` must be shared between them and persist across deployments. Otherwise each one generates its own keys, and tokens issued by one are rejected by the others.
