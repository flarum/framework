import Ember from 'ember';

// The post stream is an object which represents the posts in a discussion as
// they are displayed on the discussion page, from top to bottom. ...

export default Ember.ArrayProxy.extend(Ember.Evented, {

	// An array of all of the post IDs, in chronological order, in the discussion.
	ids: null,

	content: null,

	store: null,
	discussion: null,

	postLoadCount: 20,

	count: Ember.computed.alias('ids.length'),

	loadedCount: function() {
		return this.get('content').filterBy('content').length;
	}.property('content.@each'),

	firstLoaded: function() {
		var first = this.objectAt(0);
		return first && first.content;
	}.property('content.@each'),

	lastLoaded: function() {
		var last = this.objectAt(this.get('length') - 1);
		return last && last.content;
	}.property('content.@each'),

	init: function() {
		this._super();
		this.set('ids', Ember.A());
		this.clear();
	},

	setup: function(ids) {
		// Set our ids to the array provided and reset the content of the
		// stream to a big gap that covers the amount of posts we now have.
		this.set('ids', ids);
		this.clear();
	},

	// Clear the contents of the post stream, resetting it to one big gap.
	clear: function() {
		var content = Ember.A();
		content.clear().pushObject(this.makeItem(0, this.get('count') - 1).set('loading', true));
		this.set('content', content);
	},

	loadRange: function(start, end, backwards) {
		var limit = this.get('postLoadCount');

		// Find the appropriate gap objects in the post stream. When we find
		// one, we will turn on its loading flag.
		this.get('content').forEach(function(item) {
			if (! item.content && ((item.indexStart >= start && item.indexStart <= end) || (item.indexEnd >= start && item.indexEnd <= end))) {
				item.set('loading', true);
				item.set('direction', backwards ? 'up' : 'down');
			}
		});

		// Get a list of post numbers that we'll want to retrieve. If there are
		// more post IDs than the number of posts we want to load, then take a
		// slice of the array in the appropriate direction.
		var ids = this.get('ids').slice(start, end + 1);
		ids = backwards ? ids.slice(-limit) : ids.slice(0, limit);

		return this.loadPosts(ids);
	},

	loadPosts: function(ids) {
		if (! ids.length) {
			return Ember.RSVP.resolve();
		}

		var stream = this;
		return this.store.find('post', {ids: ids}).then(function(posts) {
			stream.addPosts(posts);
		});
	},

	loadNearNumber: function(number) {
		// Find the item in the post stream which is nearest to this number. If
		// it turns out the be the actual post we're trying to load, then we can
		// return a resolved promise (i.e. we don't need to make an API
		// request.) Or, if it's a gap, we'll switch on its loading flag.
		var item = this.findNearestToNumber(number);
		if (item) {
			if (item.get('content.number') == number) {
				return Ember.RSVP.resolve([item.get('content')]);
			} else if (! item.content) {
				item.set('direction', 'down').set('loading', true);
			}
		}

		var stream = this;
		return this.store.find('post', {
			discussions: this.get('discussion.id'),
			near: number,
			count: this.get('postLoadCount')
		}).then(function(posts) {
			stream.addPosts(posts);
		});
	},

	loadNearIndex: function(index, backwards) {
		// Find the item in the post stream which is nearest to this index. If
		// it turns out the be the actual post we're trying to load, then we can
		// return a resolved promise (i.e. we don't need to make an API
		// request.) Or, if it's a gap, we'll switch on its loading flag.
		var item = this.findNearestToIndex(index);
		if (item) {
			if (item.content) {
				return Ember.RSVP.resolve([item.get('content')]);
			}
			return this.loadRange(Math.max(item.indexStart, index - this.get('postLoadCount') / 2), item.indexEnd, backwards);
		}

		return Ember.RSVP.reject();
	},

	addPosts: function(posts) {
		this.trigger('postsLoaded', posts);

		var stream = this;
		var content = this.get('content');
		content.beginPropertyChanges();
		posts.forEach(function(post) {
			stream.addPost(post);
		});
		content.endPropertyChanges();

		this.trigger('postsAdded');
	},

	addPost: function(post) {
		var index = this.get('ids').indexOf(post.get('id'));
		var content = this.get('content');
		var makeItem = this.makeItem;

		// Here we loop through each item in the post stream, and find the gap
		// in which this post should be situated. When we find it, we can replace
		// it with the post, and new gaps either side if appropriate.
		content.some(function(item, i) {
			if (item.indexStart <= index && item.indexEnd >= index) {
				var newItems = [];
				if (item.indexStart < index) {
					newItems.push(makeItem(item.indexStart, index - 1));
				}
				newItems.push(makeItem(index, index, post));
				if (item.indexEnd > index) {
					newItems.push(makeItem(index + 1, item.indexEnd));
				}
				content.replace(i, 1, newItems);
				return true;
			}
		});
	},

	addPostToEnd: function(post) {
		this.get('ids').pushObject(post.get('id'));
		var index = this.get('count') - 1;
        this.get('content').pushObject(this.makeItem(index, index, post));
	},

	makeItem: function(indexStart, indexEnd, post) {
		var item = Ember.Object.create({
			indexStart: indexStart,
			indexEnd: indexEnd
		});
		if (post) {
			item.setProperties({
				content: post,
				component: 'discussions/post-'+post.get('type')
			});
		}
		return item;
	},

	findNearestTo: function(index, property) {
		var nearestItem;
        this.get('content').some(function(item) {
            if (item.get(property) > index) {
                return true;
            }
            nearestItem = item;
        });
        return nearestItem;
	},

	findNearestToNumber: function(number) {
		return this.findNearestTo(number, 'content.number');
    },

    findNearestToIndex: function(index) {
    	return this.findNearestTo(index, 'indexStart');
    }
});
