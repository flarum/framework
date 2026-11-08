import app from 'flarum/admin/app';
import { extend } from 'flarum/common/extend';
import PermissionGrid from 'flarum/admin/components/PermissionGrid';
import ExtensionPermissionGrid from 'flarum/admin/components/ExtensionPermissionGrid';
import type { PermissionType } from 'flarum/admin/components/PermissionGrid';

/**
 * The permission type MCP permissions are registered under. Core knows four
 * types; this one gets its own "MCP" section in the grid.
 */
export const MCP_PERMISSION_TYPE = 'mcp' as PermissionType;

/**
 * Adds the "MCP" section to the permission grid. On an extension's page the
 * grid shows only that extension's MCP permissions, so the section stays off
 * every other extension's page.
 */
export default function addPermissionSection() {
  extend(PermissionGrid.prototype, 'permissionItems', function (this: PermissionGrid, items) {
    const children =
      this instanceof ExtensionPermissionGrid
        ? app.registry.getExtensionPermissions((this as unknown as { extensionId: string }).extensionId, MCP_PERMISSION_TYPE).toArray()
        : app.registry.getAllPermissions(MCP_PERMISSION_TYPE).toArray();

    if (children.length) {
      items.add('mcp', { label: app.translator.trans('flarum-mcp.admin.permissions.heading'), children }, 60);
    }
  });
}
