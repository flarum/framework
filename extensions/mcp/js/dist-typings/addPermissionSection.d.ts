import type { PermissionType } from 'flarum/admin/components/PermissionGrid';
/**
 * The permission type MCP permissions are registered under. Core knows four
 * types; this one gets its own "MCP" section in the grid.
 */
export declare const MCP_PERMISSION_TYPE: PermissionType;
/**
 * Adds the "MCP" section to the permission grid. On an extension's page the
 * grid shows only that extension's MCP permissions, so the section stays off
 * every other extension's page.
 */
export default function addPermissionSection(): void;
