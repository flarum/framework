import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import DashboardPage from '../../../../src/admin/components/DashboardPage';
import { app } from '../../../../src/admin';
import { streamingCheck } from '../../../../src/admin/utils/streamingCheck';
import mq from 'mithril-query';

beforeAll(() => bootstrapAdmin());

describe('DashboardPage', () => {
  beforeAll(() => {
    app.boot();
  });

  beforeEach(() => {
    // Checked already this session, so the page doesn't ask the server.
    streamingCheck.status = 'streamed';
    streamingCheck.verdict = 'streamed';
  });

  test('it renders', () => {
    const page = mq(DashboardPage);

    expect(page).toHaveElement('.DashboardPage');
    expect(page).not.toHaveElement('.StreamingWarningWidget');
  });

  test('it warns when the server holds streamed responses back', () => {
    streamingCheck.status = 'buffered';
    streamingCheck.verdict = 'buffered';

    expect(mq(DashboardPage)).toHaveElement('.StreamingWarningWidget');
  });

  test('it keeps the warning while checking again', () => {
    streamingCheck.status = 'checking';
    streamingCheck.verdict = 'buffered';

    expect(mq(DashboardPage)).toHaveElement('.StreamingWarningWidget');
  });
});
