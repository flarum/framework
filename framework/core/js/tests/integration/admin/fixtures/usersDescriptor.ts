import type { ResourceDescriptor } from '../../../../src/common/resource/types';
import descriptor from '../../../fixtures/descriptors/users.json';

/**
 * `GET /api/resource-schemas/users` as an admin, core only (no extensions), generated from the server.
 * `tests/integration/api/resource_schemas/UsersSchemaTest.php` asserts the committed JSON equals the live descriptor; to regenerate it, run from framework/core:
 *
 *   FLARUM_WRITE_DESCRIPTOR_FIXTURES=1 php ../../vendor/bin/phpunit -c tests/phpunit.integration.xml \
 *     --filter descriptor_matches_the_js_fixture tests/integration/api/resource_schemas
 *
 * Each call returns a fresh deep copy, so a test may mutate it freely.
 */
export function usersDescriptor(): ResourceDescriptor {
  return JSON.parse(JSON.stringify(descriptor)) as ResourceDescriptor;
}
