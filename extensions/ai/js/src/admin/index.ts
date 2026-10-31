import app from 'flarum/admin/app';
import extractText from 'flarum/common/utils/extractText';
import { aiPayload } from './connections';

export { default as extend } from './extend';

app.initializers.add('flarum-ai', () => {
  const registry = app.registry.for('flarum-ai');

  // Each feature an extension registers has its own permission, listed with
  // the others a member uses to take part.
  aiPayload().features.forEach((feature) => {
    registry.registerPermission(
      {
        icon: 'fas fa-wand-magic-sparkles',
        label: extractText(app.translator.trans(feature.label)),
        permission: feature.permission,
      },
      'reply'
    );
  });
});
