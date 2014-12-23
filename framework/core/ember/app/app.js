import Ember from 'ember';
import Resolver from 'ember/resolver';
import loadInitializers from 'ember/load-initializers';
import config from './config/environment';

Ember.MODEL_FACTORY_INJECTIONS = true;

var App = Ember.Application.extend({
  modulePrefix: config.modulePrefix,
  podModulePrefix: config.podModulePrefix,
  Resolver: Resolver
});

loadInitializers(App, config.modulePrefix);

//-----------------------------------------
// TODO: Move all this to an initializer

/* 
import User from 'flarum/models/user';

// Authentication

import BaseAuthenticator from 'simple-auth/authenticators/base';

var FlarumAuthenticator = BaseAuthenticator.extend({
	restore: function(data) {
		// return Ember.RSVP.Promise.resolve(data);
	},
	authenticate: function(credentials) {
		return new Ember.RSVP.Promise(function(resolve, reject) {
			Ember.$.ajax({
				url:  'http://localhost/public/Flarum/flarum/public/api/auth',
				type: 'POST',
				data: { type: 'password', identification: credentials.identification, password: credentials.password }
			}).then(function(response) {
				resolve({ token: response.token, userId: response.user.id });
			}, function(xhr, status, error) {
				reject(xhr.responseText);
			});
		});
	},
	// invalidate: function() {
	// 	return Ember.RSVP.Promise.resolve();
	// }
});

import BaseAuthorizer from 'simple-auth/authorizers/base';

var FlarumAuthorizer = BaseAuthorizer.extend({

});

App.initializer({
	name: 'authentication',
	initialize: function(container, application) {
		container.register('authenticator:flarum', FlarumAuthenticator);
		container.register('authorizer:flarum', FlarumAuthorizer);

		// customize the session so that it allows access to the account object
		Ember.SimpleAuth.Session.reopen({
			user: function() {
				var userId = this.get('userId');
				if (!userId) return;
				return container.lookup('store:main').find('user', userId);
			}.property('userId')
		});

		Ember.SimpleAuth.setup(container, application, {
			authorizerFactory: 'authorizer:flarum',
			routeAfterAuthentication: 'discussions'
		});
	}
});
*/

export default App;
