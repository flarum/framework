export default class PostStream {
  constructor(discussion) {
    this.discussion = discussion
    this.ids = this.discussion.data().links.posts.linkage.map((link) => link.id)

    var item = this.makeItem(0, this.ids.length - 1)
    item.loading = true
    this.content = [item]

    this.postLoadCount = 20
  }

  count() {
    return this.ids.length;
  }

  loadedCount() {
    return this.content.filter((item) => item.post).length;
  }

  loadRange(start, end, backwards) {
    // Find the appropriate gap objects in the post stream. When we find
    // one, we will turn on its loading flag.
    this.content.forEach(function(item) {
      if (!item.post && ((item.start >= start && item.start <= end) || (item.end >= start && item.end <= end))) {
        item.loading = true
        item.direction = backwards ? 'up' : 'down'
      }
    });

    // Get a list of post numbers that we'll want to retrieve. If there are
    // more post IDs than the number of posts we want to load, then take a
    // slice of the array in the appropriate direction.
    var ids = this.ids.slice(start, end + 1);
    var limit = this.postLoadCount
    ids = backwards ? ids.slice(-limit) : ids.slice(0, limit)

    return this.loadPosts(ids)
  }

  loadPosts(ids) {
    if (!ids.length) {
      return m.deferred().resolve().promise;
    }

    return app.store.find('posts', ids).then(this.addPosts.bind(this));
  }

  loadNearNumber(number) {
    // Find the item in the post stream which is nearest to this number. If
    // it turns out the be the actual post we're trying to load, then we can
    // return a resolved promise (i.e. we don't need to make an API
    // request.) Or, if it's a gap, we'll switch on its loading flag.
    var item = this.findNearestToNumber(number)
    if (item) {
      if (item.post && item.post.number() === number) {
        return m.deferred().resolve([item.post]).promise;
      } else if (!item.post) {
        item.direction = 'down'
        item.loading = true;
      }
    }

    var stream = this
    return app.store.find('posts', {
      discussions: this.discussion.id(),
      near: number,
      count: this.postLoadCount
    }).then(this.addPosts.bind(this))
  }

  loadNearIndex(index, backwards) {
    // Find the item in the post stream which is nearest to this index. If
    // it turns out the be the actual post we're trying to load, then we can
    // return a resolved promise (i.e. we don't need to make an API
    // request.) Or, if it's a gap, we'll switch on its loading flag.
    var item = this.findNearestToIndex(index)
    if (item) {
      if (item.post) {
        return m.deferred().resolve([item.post]).promise;
      }
      return this.loadRange(Math.max(item.start, index - this.postLoadCount / 2), item.end, backwards);
    }
  }

  addPosts(posts) {
    posts.forEach(this.addPost.bind(this))
  }

  addPost(post) {
    var index = this.ids.indexOf(post.id())
    var content = this.content
    var makeItem = this.makeItem

    // Here we loop through each item in the post stream, and find the gap
    // in which this post should be situated. When we find it, we can replace
    // it with the post, and new gaps either side if appropriate.
    content.some(function(item, i) {
      if (item.start <= index && item.end >= index) {
        var newItems = []
        if (item.start < index) {
          newItems.push(makeItem(item.start, index - 1))
        }
        newItems.push(makeItem(index, index, post))
        if (item.end > index) {
          newItems.push(makeItem(index + 1, item.end))
        }
        var args = [i, 1].concat(newItems);
        [].splice.apply(content, args)
        return true
      }
    })
  }

  addPostToEnd(post) {
    var index = this.ids.length
    this.ids.push(post.id())
    this.content.push(this.makeItem(index, index, post))
  }

  removePost(post) {
    this.ids.splice(this.ids.indexOf(post.id()), 1);
    this.content.some((item, i) => {
      if (item.post === post) {
        this.content.splice(i, 1);
        return true;
      }
    });
  }

  makeItem(start, end, post) {
    var item = {start, end}
    if (post) {
      item.post = post
    }
    return item
  }

  findNearestTo(index, property) {
    var nearestItem
    this.content.some(function(item) {
      if (property(item) > index) { return true }
      nearestItem = item
    })
    return nearestItem
  }

  findNearestToNumber(number) {
    return this.findNearestTo(number, (item) => item.post && item.post.number())
  }

  findNearestToIndex(index) {
    return this.findNearestTo(index, (item) => item.start)
  }
}
