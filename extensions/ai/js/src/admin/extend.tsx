import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';
import extractText from 'flarum/common/utils/extractText';
import AiPage from './components/AiPage';

const item = (id: string, tab: string, label: string, help: string) => ({
  id: `flarum-ai-${id}`,
  label: extractText(app.translator.trans(label)),
  help: extractText(app.translator.trans(help)),
  link: app.route('extension', { id: 'flarum-ai', tab }),
});

export default [
  new Extend.Admin()
    .page(AiPage)
    // The page's settings aren't in the settings registry, so the admin search
    // is told about them here.
    .generalIndexItems('settings', () => [
      item('connections', 'connections', 'flarum-ai.admin.connections.heading', 'flarum-ai.admin.connections.help'),
      item('default', 'connections', 'flarum-ai.admin.connections.default_label', 'flarum-ai.admin.connections.default_help'),
      item('features', 'features', 'flarum-ai.admin.features.heading', 'flarum-ai.admin.features.help'),
      item('member-limit', 'usage', 'flarum-ai.admin.limits.member_daily_label', 'flarum-ai.admin.limits.member_daily_help'),
      item('guest-limit', 'usage', 'flarum-ai.admin.limits.guest_daily_label', 'flarum-ai.admin.limits.guest_daily_help'),
      item('usage', 'usage', 'flarum-ai.admin.tabs.usage', 'flarum-ai.admin.usage.help'),
    ]),
];
