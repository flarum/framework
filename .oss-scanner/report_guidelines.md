# How we would like reports written

## Layout and length
- Under one page, in plain Markdown, with these sections in this order: Summary, Affected code, Attacker,
  Reproduction, Impact, Severity, Suggested fix.
- Start the title with the package in brackets, as in our changelog: `(core)`, `(tags)`, `(mentions)`,
  `(oauth-provider)`. Then a short description of the weakness, for example
  `(tags) restricted-tag discussions listed to members through the include parameter`.
- One root cause per report. Several call sites with the same cause belong in one report; separate causes in
  separate reports.

## What every report should state
- The package, the file and the function or method at fault, with line numbers on the `2.x` commit scanned.
- The least privileged account that can exploit it (guest, unconfirmed or suspended user, member, moderator,
  admin) and any non-default setting or permission it needs.
- A reproducer that runs against the seeded forum in the image: `curl` commands using `flarum-token`, or with a
  session cookie and CSRF token where the issue depends on them, or a PHPUnit integration test in the package's
  `tests/integration`. Show the request and the response that proves the issue, trimmed to what matters.
- For anything involving SQL, which database drivers it reproduces on (MariaDB, PostgreSQL, SQLite) and which it
  does not.
- What an attacker gains, in one or two sentences, without speculating beyond what the reproducer shows.
- The CVSS 4.0 vector and the severity band it gives, as `threat_model.md` describes.
- Whether you confirmed the code path is the same in the `/src` checkout, not only on the running forum.

## Style
- Plain, factual wording. No marketing language, no exploit payloads beyond what the reproducer needs.
- Say when something is inferred from reading the code rather than shown by the reproducer.
- Name the Flarum functions and classes involved (`whereVisibleTo`, `Schema\Relationship\ToMany::visible()`,
  `TextFormatter`) rather than describing them.
