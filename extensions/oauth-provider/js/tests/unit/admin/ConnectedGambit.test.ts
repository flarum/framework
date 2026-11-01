import app from 'flarum/admin/app';
import ConnectedGambit from '../../../src/admin/query/users/ConnectedGambit';
import { bootAdmin } from './setup';

const CIMD = 'https://claude.ai/oauth/claude-code-client-metadata';

beforeAll(() => {
  bootAdmin();
  app.search.gambits.gambits.users.push(ConnectedGambit);
});

describe('the connected gambit', () => {
  it('finds the members who connected a client, even one whose id is a URL', () => {
    expect(app.search.gambits.apply('users', { q: 'connected:' + CIMD })).toEqual({ q: '', oauthClient: CIMD });
  });

  it('finds the members who did not', () => {
    expect(app.search.gambits.apply('users', { q: '-connected:dcr-1' })).toEqual({ q: '', '-oauthClient': 'dcr-1' });
  });
});
