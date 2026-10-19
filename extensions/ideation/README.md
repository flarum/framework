# Ideation

`flarum/ideation`: a public feature-request board for Flarum 2, with a roadmap at `/roadmap`. Members post ideas
and vote for them; moderators move them through **Under review → Planned → In
progress → Shipped** (or **Declined**), and everyone who voted is told when an
idea ships.

- **Every idea is a discussion.** Replies, mentions, search, tags, subscriptions,
  approval, hiding and every permission a discussion has keep working, because
  nothing is reinvented. A moderator can put any existing discussion on the
  roadmap.
- **A board and a list.** The board has a column per status, most-voted first
  (Shipped shows the most recent first), with drag and drop for moderators,
  by mouse or by finger. The list shows every idea with its status, filtered
  by status and sorted by votes, newest or recent activity. Both are drawn by
  Ideation itself, so a theme that restyles discussion lists cannot hide them.
- **Votes on every list.** An idea shows its vote button next to its reply
  count wherever discussions are listed, and in the sidebar of its discussion.
- **Notified on ship.** Everyone who voted gets an alert and an email (each
  member can switch either off). A voter who can no longer see the idea is not
  told. Shipping the same idea twice does not notify twice.
- **History in the discussion.** Each move is an event post ("Ernest moved
  this to Planned"), merged the way core merges renames.
- **Merge duplicates.** Votes move to the idea you keep; someone who voted for
  both keeps one vote.
- **Permissions:** *Post ideas*, *Vote* and *Move, merge and decline ideas*,
  in the permission grid. Members can post and vote, moderators can move.
- **GitHub, optionally.** Moderators link an idea to GitHub issues, pull
  requests and milestones, and everyone sees their live state on the idea and
  its card ("PR #5034 merged", "2.1 · 70%"). Optional rules move an idea to
  In progress when a linked pull request opens, and ship it (telling its
  voters) when everything linked is done.

## GitHub integration

Off until an administrator switches it on (Extensions → Ideation).

- **Repositories:** only the ones listed (`owner/repo` or `owner/*`) can be
  linked. A link shows its title to everyone who can see the idea, and with a
  token the forum can read private repositories, so the list is what keeps a
  moderator from showing something private.
- **Token (optional):** for private repositories, and to raise GitHub's limit
  from 60 to 5,000 requests an hour. A fine-grained token with read-only Issues,
  Pull requests and Metadata is enough. It never leaves the server.
- **Webhook:** add one in each repository with payload URL
  `https://your-forum/api/ideation/github/webhook`, content type
  `application/json`, the secret from the settings, and the *Issues*,
  *Pull requests* and *Milestones* events. Deliveries are verified (HMAC-SHA256
  of the raw body, constant-time compare); without a secret every delivery is
  refused. A delivery older than what is stored is ignored.
- **Hourly refresh:** `php flarum ideation:github-sync` (scheduled) re-reads up
  to 50 links (20 without a token) the webhook has not confirmed in six hours,
  and stops for the hour the moment GitHub rate-limits it.
- **No page ever calls GitHub.** Links are read from the database, all of an
  idea list's links in one query.

## How it holds up at scale

- Status and the vote count are columns on `discussions`, with indexes on
  `(ideation_status, ideation_votes)` and `(ideation_status, ideation_status_changed_at)`.
  A board column is one indexed query however many ideas there are.
- "Have I voted?" costs **one query per response**, for any number of ideas,
  and none for guests or for pages without ideas. It is a deferred getter that
  batches every idea on the page.
- The count changes only by atomic increments, next to the vote row, by the
  number of rows actually written. A repeated vote cannot count twice.
- Shipped notifications run in the queue.
- The board and the list are the core discussions endpoint
  (`filter[ideation]=planned&sort=-ideationVotes`), so tag permissions, private
  discussions and full-text search apply without any extra code.

## Open questions for review

- Permissions are forum-wide, not per tag ("Restrict by Tag").
- Votes have no rate limit (a repeated vote cannot count twice, but rapid
  clicking is not slowed).
- Automatic moves are attributed to the moderator who linked the item; the
  event post says the move came from GitHub.

## API

| | |
|---|---|
| `GET /api/discussions?filter[ideation]=planned` | ideas in a status (`all` for every one still on the roadmap) |
| `sort=-ideationVotes` / `sort=-ideationStatusChangedAt` | most votes / most recently moved |
| `PATCH /api/discussions/{id}` `ideationVoted: true/false` | vote or take it back |
| `PATCH /api/discussions/{id}` `ideationStatus: "planned"` | move (moderators); `null` takes it off the roadmap |
| `PATCH /api/discussions/{id}` `ideationMergedIntoId: 12` | merge into idea 12 (moderators) |
| `POST /api/discussions` with `ideationStatus` | post an idea (it always starts Under review) |
| `PATCH /api/discussions/{id}` `ideationGithubLink: "owner/repo#12"` | link a GitHub issue, PR or milestone (moderators; also takes a URL) |
| `PATCH /api/discussions/{id}` `ideationGithubUnlink: 3` | remove link 3 (moderators) |
| `POST /api/ideation/github/webhook` | GitHub's webhook (signed) |

## Licence

MIT.
