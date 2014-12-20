// TODO probably change this into an Ember object/merge it into discussion-scrollbar

var Scrollbar = function(element) {
    this.$ = $(element);
    this.count = 1;
    this.index = 0;
    this.visible = 1;
    this.disabled = false;
};

Scrollbar.prototype = {

    setIndex: function(index) {
        this.index = index;
    },

    setVisible: function(visible) {
        this.visible = visible;
    },

    setCount: function(count) {
        this.count = count;
    },

    setDisabled: function(disabled) {
        this.disabled = disabled;
    },

    percentPerPost: function() {
        // To stop the slider of the scrollbar from getting too small when there
        // are many posts, we define a minimum percentage height for the slider
        // calculated from a 50 pixel limit. Subsequently, we can calculate the
        // minimum percentage per visible post. If this is greater than the
        // actual percentage per post, then we need to adjust the 'before'
        // percentage to account for it.
        var minPercentVisible = 50 / this.$.outerHeight() * 100;
        var percentPerVisiblePost = Math.max(100 / this.count, minPercentVisible / this.visible);
        var percentPerPost = this.count == this.visible ? 0 : (100 - percentPerVisiblePost * this.visible) / (this.count - this.visible);

        return {
            index: percentPerPost,
            visible: percentPerVisiblePost
        };
    },

    update: function(animate) {
        var percentPerPost = this.percentPerPost();

        var before = percentPerPost.index * this.index,
            slider = Math.min(100 - before, percentPerPost.visible * this.visible),
            func = animate ? 'animate' : 'css';

        this.$.find('.scrollbar-before').stop(true)[func]({height: before+'%'}).css('overflow', 'visible');
        this.$.find('.scrollbar-slider').stop(true)[func]({height: slider+'%'}).css('overflow', 'visible');
        this.$.find('.scrollbar-after').stop(true)[func]({height: (100 - before - slider)+'%'}).css('overflow', 'visible');

        this.$.toggleClass('disabled', this.disabled || slider >= 100);
    }

};

export default Scrollbar;
