import app from 'flarum/admin/app';
import AlertWidget from 'flarum/admin/components/AlertWidget';
import DashboardPage from 'flarum/admin/components/DashboardPage';
import { extend as extendMethod } from 'flarum/common/extend';
import Link from 'flarum/common/components/Link';
import extractText from 'flarum/common/utils/extractText';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import { aiPayload } from './utils/settings';

export { default as extend } from './extend';

app.initializers.add('flarum-ai', () => {
  const registry = app.registry.for('flarum-ai');

  // Each feature an extension registers has its own permission, listed with
  // the others a member uses to take part. Features can be called for guests,
  // who share their own daily limit, so guests can be granted it too.
  aiPayload().features.forEach((feature) => {
    registry.registerPermission(
      {
        icon: 'fas fa-wand-magic-sparkles',
        label: extractText(app.translator.trans('flarum-ai.admin.permissions.use_feature_label', { feature: app.translator.trans(feature.label) })),
        permission: feature.permission,
        allowGuest: true,
      },
      'reply'
    );
  });

  // A model a feature uses going away stops the feature, so the admin is told
  // as soon as they sign in, not only on the AI page.
  extendMethod(DashboardPage.prototype, 'availableWidgets', function (items: ItemList<Mithril.Children>) {
    const issues = aiPayload().modelIssues || [];

    if (!issues.length) return;

    const broken = issues.some((issue) => issue.status !== 'deprecated');

    items.add(
      'flarum-ai-models',
      <AlertWidget
        alert={{
          type: broken ? 'error' : 'warning',
          dismissible: false,
          controls: [
            <Link className="Button Button--link" href={app.route('extension', { id: 'flarum-ai', tab: 'features' })}>
              {app.translator.trans('flarum-ai.admin.models.dashboard_button')}
            </Link>,
          ],
        }}
      >
        {app.translator.trans(broken ? 'flarum-ai.admin.models.dashboard_broken_text' : 'flarum-ai.admin.models.dashboard_deprecated_text', {
          count: issues.length,
        })}
      </AlertWidget>,
      100
    );
  });
});
