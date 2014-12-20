export default Ember.Mixin.create({
    // Find the DOM element of the item that is nearest to a post with a certain
    // number. This will either be another post (if the requested post doesn't
    // exist,) or a gap presumed to container the requested post.
    findNearestToNumber: function(number) {
        var nearestItem = $();
        $('.posts .item').each(function() {
            var $this = $(this),
                thisNumber = $this.data('number');
            if (thisNumber > number) {
                return false;
            }
            nearestItem = $this;
        });
        return nearestItem;
    },

    findNearestToIndex: function(index) {
        var nearestItem = $('.posts .item[data-start='+index+'][data-end='+index+']');

        if (! nearestItem.length) {
            $('.posts .item').each(function() {
                var $this = $(this);
                if ($this.data('start') <= index && $this.data('end') >= index) {
                    nearestItem = $this;
                    return false;
                }
            });
        }

        return nearestItem;
    }
});
