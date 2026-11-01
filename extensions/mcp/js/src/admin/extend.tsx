import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import McpPage from './components/McpPage';
import { MCP_PERMISSION_TYPE } from './addPermissionSection';

export default [
  new Extend.Admin()
    .permission(
      () => ({
        icon: 'fas fa-robot',
        label: app.translator.trans('flarum-mcp.admin.permissions.use_label'),
        permission: 'mcp.use',
        allowGuest: true,
      }),
      MCP_PERMISSION_TYPE
    )
    .page(McpPage),
];
