import Ember from 'ember';

export default Ember.View.extend({

	classNameBindings: ['pinned'],

    pinned: function() {
        return this.get('controller.panePinned');
    }.property('controller.panePinned'),

	didInsertElement: function() {

		var view = this;

		this.$().find('.discussions-pane').on('mouseenter', function() {
			if (! $(this).hasClass('paned')) return;
			clearTimeout(view.get('controller.paneTimeout'));
	        view.set('controller.paneShowing', true);
		}).on('mouseleave', function() {
            view.set('controller.paneShowing', false);
		});

		if (this.get('controller.test') !== null) {
			var row = this.$().find('li[data-id='+this.get('controller.controllers.application.resultStream.currentResult.id')+']');
			if (row.length) {
				row.addClass('highlight');
			}
			// TODO: work out if the highlighted row is in view of the saved scroll position.
			// If it isn't, don't use the saved scroll position - generate a new one.
			$(window).scrollTop(this.get('controller.test'));
			this.set('controller.test', null);
		}

		var self = this;

		$(window).on('scroll.loadMore', function() {
			if (self.get('controller.loadingMore') || ! self.get('controller.moreResults')) {
				return;
			}

			var w = $(window),
			    d = $('.discussions'),
			    curPos = w.scrollTop() + w.height(),
			    endPos = d.offset().top + d.height() - 200;

			if (curPos > endPos) {
				self.get('controller').send('loadMore');
			}
		});
	},

	willDestroyElement: function() {
		this.set('controller.test', $(window).scrollTop());
		$(window).off('scroll.loadMore');
	}

});
