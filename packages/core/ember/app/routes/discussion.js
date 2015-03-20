import Ember from 'ember';

import PostStream from 'flarum/models/post-stream';
import PushesHistory from 'flarum/mixins/pushes-history';

export default Ember.Route.extend(PushesHistory, {
  historyKey: 'discussion',

  queryParams: {
    start: {replace: true}
  },

  discussion: function(id, start) {
    return this.store.findQueryOne('discussion', id, {
      include: 'posts',
      near: start
    });
  },

  // When we fetch the discussion from the model hook (i.e. on a fresh page
  // load), we'll wrap it in an object proxy and set a `loaded` flag to true
  // so that it won't be reloaded later on.
  model: function(params) {
    return this.discussion(params.id, params.start).then(function(discussion) {
      return Ember.ObjectProxy.create({content: discussion, loaded: true});
    });
  },

  resetController: function(controller) {
    // Whenever we exit the discussion view, or transition to a different
    // discussion, we want to reset the query params so that they don't stick.
    controller.set('start', '1');
    controller.set('searchQuery', '');
    controller.set('loaded', false);
    controller.set('stream', null);
  },

  setupController: function(controller, discussion) {
    this._super(controller, discussion);
    this.controllerFor('index/index').set('lastDiscussion', discussion);

    // Set up the post stream object. It needs to know about the discussion
    // it's representing the posts for, and we also need to inject the Ember
    // Data store.
    var stream = PostStream.create({
      discussion: discussion,
      store: this.store
    });
    controller.set('stream', stream);

    // We need to make sure we have an up-to-date list of the discussion's
    // post IDs. If we didn't enter this route using the model hook (like if
    // clicking on a discussion in the index), then we'll reload the model.
    var promise = discussion.get('loaded') ?
      Ember.RSVP.resolve(discussion.get('content')) :
      this.discussion(discussion.get('id'), controller.get('start'));

    // When we know we have the post IDs, we can set up the post stream with
    // them. Then we will tell the view that we have finished loading so that
    // it can scroll down to the appropriate post.
    promise.then(function(discussion) {
      controller.set('model', discussion);
      var postIds = discussion.get('postIds');
      stream.setup(postIds);

      // A page of posts will have been returned as linked data by this
      // request, and automatically loaded into the store. In turn, we
      // want to load them into the stream. However, since there is no
      // way to access them directly, we need to retrieve them based on
      // the requested start number. This code finds the post for that
      // number, gets its index, slices an array of surrounding post
      // IDs, and finally adds these posts to the stream.
      var posts = discussion.get('loadedPosts');
      var startPost = posts.findBy('number', parseInt(controller.get('start')));
      if (startPost) {
        var startIndex = postIds.indexOf(startPost.get('id'));
        var count = stream.get('postLoadCount');
        startIndex = Math.max(0, startIndex - count / 2);
        var loadIds = postIds.slice(startIndex, startIndex + count);
        stream.addPosts(posts.filter(function(item) {
          return loadIds.indexOf(item.get('id')) !== -1;
        }));
      }

      // It's possible for this promise to have resolved but the user
      // has clicked away to a different discussion. So only if we're
      // still on the original one, we will tell the view that we're
      // done loading.
      if (controller.get('model') === discussion) {
        controller.set('loaded', true);
        Ember.run.scheduleOnce('afterRender', function() {
          controller.trigger('loaded');
        });
      }
    });
  },

  actions: {
    queryParamsDidChange: function(params) {
      this._super(params);

      // If the ?start param has changed, we want to tell the view to
      // tell the streamContent component to jump to this start point.
      // We postpone running this code until the next run loop because
      // when transitioning directly from one discussion to another,
      // queryParamsDidChange is fired before the controller is reset.
      // Thus, controller.loaded would still be true and the
      // startWasChanged event would be triggered inappropriately.
      var newStart = parseInt(params.start) || 1;
      var controller = this.controllerFor('discussion');
      var oldStart = parseInt(controller.get('start'));
      Ember.run.next(function() {
        if (controller.get('loaded') && newStart !== oldStart) {
          controller.trigger('startWasChanged', newStart);
        }
      });
    },

    didTransition: function() {
      // When we transition into a new discussion, we want to hide the
      // discussions list pane. This means that when the user selects a
      // different discussion within the pane, the pane will slide away.
      // We also minimize the composer.
      this.controllerFor('index')
        .set('paned', true)
        .set('paneShowing', false);
      this.controllerFor('composer').send('minimize');

      var application = this.controllerFor('application');
      if (!application.get('backButtonTarget')) {
        application.set('backButtonTarget', this.controllerFor('index'));
      }
    }
  }
});
