import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import extractText from 'flarum/common/utils/extractText';
import flatten from 'flat';
import { readFileSync } from 'fs';
import jsYaml from 'js-yaml';
import mq from 'mithril-query';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

import AuditItem from '../../../../src/common/components/AuditItem';

const testDir = dirname(fileURLToPath(import.meta.url));
const coreJsDir = resolve(testDir, '../../../../../../../framework/core/js');

beforeAll(() => {
  const cwd = process.cwd();

  try {
    process.chdir(coreJsDir);
    bootstrapForum();
  } finally {
    process.chdir(cwd);
  }

  app.boot();

  // Only core's strings are loaded by the bootstrap.
  app.translator.addTranslations(flatten(jsYaml.load(readFileSync(resolve(testDir, '../../../../../locale/en.yml'), 'utf8'))) as any);
});

const commandLine = () => extractText(app.translator.trans('flarum-audit.lib.browser.client.cli'));

/** An entry logged from the command line, by the given actor or none. */
function render(actorId: string | null, action = 'user.gdpr_anonymized', payload: Record<string, unknown> = { user_id: 2 }) {
  const actor = actorId ? app.store.getById('users', actorId) : null;

  const log = {
    action: () => action,
    actorId: () => actorId,
    actor: () => actor,
    client: () => 'cli',
    createdAt: () => new Date(),
    ipAddress: () => null,
    payload: () => payload,
    discussion: () => null,
    newDiscussion: () => null,
    post: () => null,
    tag: () => null,
    user: () => null,
  };

  return mq(AuditItem, { log, changeQuery: () => {} });
}

/** The entry's rows: who and what, the details, then how and when. */
const rows = (out: ReturnType<typeof mq>) => out.find('.AuditItemRow').map((row: Element) => row.textContent);

describe('AuditItem', () => {
  it('names the actor of an entry logged from the command line on their behalf', () => {
    // E.g. a queued job a moderator started.
    const out = render('1');

    expect(out.has('.AuditItemAvatar .Avatar')).toBe(true);
    expect(out.has('.AuditItemAvatar .fa-terminal')).toBe(false);
    expect(rows(out)[0]).toContain('Admin');
    expect(rows(out)[0]).not.toContain(commandLine());
  });

  it('still says the entry was logged from the command line', () => {
    expect(rows(render('1'))[2]).toContain(commandLine());
  });

  it('shows the command line itself for an entry without an actor', () => {
    // E.g. a scheduled task.
    const out = render(null);

    expect(out.has('.AuditItemAvatar .fa-terminal')).toBe(true);
    expect(rows(out)[0]).toContain(commandLine());
  });

  describe("an extension's own action", () => {
    beforeAll(() => {
      // An extension describes the actions it logs in its own locale file.
      app.translator.addTranslations({
        'flarum-audit.lib.browser.acme.widget_created': 'Created widget {widget_id} for {username}',
        'flarum-audit.lib.browser.acme.widgets_merged': 'Merged widgets {widget_ids} using {options}',
      } as any);
    });

    const text = (action: string, payload: Record<string, unknown>) => rows(render('1', action, payload)).join(' ');

    it('fills a placeholder from a plain value in the payload', () => {
      expect(text('acme.widget_created', { widget_id: 42 })).toContain('Created widget 42');
    });

    it("keeps core's own placeholder when the payload has a value of the same name", () => {
      expect(text('acme.widget_created', { widget_id: 42, username: 'Mallory' })).not.toContain('Mallory');
    });

    it('leaves lists and objects in the payload to the raw view', () => {
      const sentence = text('acme.widgets_merged', { widget_ids: [1, 2], options: { keep: true } });

      expect(sentence).not.toContain('1,2');
      expect(sentence).not.toContain('[object Object]');
    });
  });

  describe('an entry about an email address', () => {
    const text = (action: string, payload: Record<string, unknown>) => rows(render(null, action, payload)).join(' ');

    it('describes a bounce for an account', () => {
      const sentence = text('user.email_bounced', { matched: true, user_id: 2, reason: 'No such mailbox' });

      expect(sentence).toContain('bounced: No such mailbox');
      expect(sentence).not.toContain('no matching account');
    });

    it('describes a bounce for an address with no account', () => {
      expect(text('user.email_bounced', { matched: false, email: 'nobody@example.com', reason: 'No such mailbox' })).toContain(
        'Mail to nobody@example.com bounced (no matching account): No such mailbox'
      );
    });

    it('describes a spam complaint for an address with no account', () => {
      expect(text('user.email_complained', { matched: false, email: 'nobody@example.com' })).toContain(
        'Mail to nobody@example.com was marked as spam (no matching account)'
      );
    });

    it('still describes a password reset for an address with no account', () => {
      expect(text('user.password_reset_attempted', { matched: false, email: 'nobody@example.com' })).toContain(
        'Requested a password reset for nobody@example.com (no matching account)'
      );
    });
  });
});
