import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import dayjs from 'dayjs';
import flatten from 'flat';
import { readFileSync } from 'fs';
import jsYaml from 'js-yaml';
import mq from 'mithril-query';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

import ProcessErasureRequestModal from '../../../../src/forum/components/ProcessErasureRequestModal';

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

  // Only core's strings are loaded by the bootstrap, and a missing string
  // drops the date it was given.
  app.translator.addTranslations(flatten(jsYaml.load(readFileSync(resolve(testDir, '../../../../../resources/locale/en.yml'), 'utf8'))) as any);
});

const MINUTE = 60 * 1000;
const DAY = 24 * 60 * MINUTE;

/** The request's timestamps, as the modal shows them. */
function timestamps(createdAt: Date, userConfirmedAt: Date | null) {
  const request = { createdAt: () => createdAt, userConfirmedAt: () => userConfirmedAt, reason: () => null, user: () => app.session.user };
  const items = ProcessErasureRequestModal.prototype.fields.call({ attrs: { request }, request, comments: () => '' } as any);

  return mq({ view: () => items.get('timestamps') });
}

describe('ProcessErasureRequestModal', () => {
  it('shows the date a confirmed request becomes eligible for automatic processing', () => {
    const confirmedAt = new Date(Date.now() - 18 * MINUTE);

    // gdpr:process-erase-requests takes requests confirmed at least 30 days ago.
    const eligibleAt = dayjs(confirmedAt.getTime() + 30 * DAY);

    const times = timestamps(confirmedAt, confirmedAt).find('time');
    const eligible = times[times.length - 1];

    expect(eligible.getAttribute('datetime')).toBe(eligibleAt.format());
    expect(eligible.textContent).toBe(app.translator.formatDateTime(eligibleAt, 'core.lib.datetime_formats.fullTime'));
  });

  it('shows no eligibility date until the request is confirmed', () => {
    const createdAt = new Date(Date.now() - 18 * MINUTE);

    expect(timestamps(createdAt, null).find('time')).toHaveLength(1);
  });
});
