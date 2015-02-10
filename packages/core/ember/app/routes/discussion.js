import Ember from 'ember';

import PostStream from 'flarum/models/post-stream';

export default Ember.Route.extend({
  queryParams: {
    start: {replace: true}
  },

  model: function(params) {
    return this.store.findQueryOne('discussion', params.id, {
      include: 'posts',
      near: params.start
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
    controller.set('model', discussion);
    this.controllerFor('index/index').set('lastDiscussion', discussion);

    // Set up the post stream object. It needs to know about the discussion
    // it's representing the posts for, and we also need to inject the Ember
    // Data store.
    var stream = PostStream.create({
      discussion: discussion,
      store: this.store
    });
    controller.set('stream', stream);

    // Next, we need to make sure we have a list of the discussion's post
    // IDs. If we don't already have this information, we'll need to
    // reload the discussion model.
    var promise = discussion.get('posts') ? Ember.RSVP.resolve(discussion) : this.model({
      id: discussion.get('id'),
      start: controller.get('start')
    });

    // When we know we have the post IDs, we can set up the post stream with
    // them. Then we will tell the view that we have finished loading so that
    // it can scroll down to the appropriate post.
    promise.then(function(discussion) {
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

      // Clear the list of post IDs for this discussion (without
      // dirtying the record), so that next time we load the discussion,
      // the discussion details and post IDs will be refreshed.
      controller.store.push('discussion', {id: discussion.get('id'), posts: ''});

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
    }
  }
});
