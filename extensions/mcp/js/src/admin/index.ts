import app from 'flarum/admin/app';
import addPermissionSection from './addPermissionSection';

export { default as extend } from './extend';

app.initializers.add('flarum-mcp', () => {
  addPermissionSection();
});
