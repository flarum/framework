# Changelog

## [Unreleased]

### Added

- Initial release. Turn Flarum into an OAuth 2.0 authorization server.
- Authorization code grant with optional PKCE (via league/oauth2-server).
- Refresh token grant.
- OpenID Connect-style `/oauth/userinfo` endpoint with `openid`, `profile`, and `email` scopes.
- Admin UI for managing OAuth client registrations.
- User consent screen before issuing authorization codes.
