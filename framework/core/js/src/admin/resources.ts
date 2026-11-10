import type ResourceRegistry from '../common/resource/ResourceRegistry';
import AccessTokensDefinition from './resource/definitions/AccessTokensDefinition';
import GroupsDefinition from './resource/definitions/GroupsDefinition';
import UsersDefinition from './resource/definitions/UsersDefinition';

/**
 * Registers core's admin resource definitions with `app.resources`.
 */
export default function (app: { resources: ResourceRegistry }) {
  app.resources.register('users', UsersDefinition).register('groups', GroupsDefinition).register('access-tokens', AccessTokensDefinition);
}
