import Ember from 'ember';

export default Ember.Handlebars.makeBoundHelper(function(text, phrase) {
    if (phrase) {
        var words = phrase.split(' ');
        var replacement = function(matched) {
            return '<span class="highlight-keyword">'+matched+'</span>';
        };
        words.forEach(function(word) {
            text = text.replace(
                new RegExp("\\b"+word+"\\b", 'gi'),
                replacement
            );
        });
    }
    return new Ember.Handlebars.SafeString(text);
});

