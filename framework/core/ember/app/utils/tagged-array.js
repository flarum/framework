import Ember from 'ember';

export default Ember.ArrayProxy.extend({
    content: null,
	taggedObjects: null,

    init: function() {
        this.set('content', []);
        this.set('taggedObjects', {});
        this._super();
    },

    pushObjectWithTag: function(obj, tag) {
    	this.insertAtWithTag(this.get('length'), obj, tag);
    },

    insertAtWithTag: function(idx, obj, tag) {
    	this.insertAt(idx, obj);
    	this.get('taggedObjects')[tag] = obj;
    },

    insertAfterTag: function(anchorTag, obj, tag) {
    	var idx = this.indexOfTag(anchorTag);
    	this.insertAtWithTag(idx + 1, obj, newTag);
    },

    insertBeforeTag: function(anchorTag, obj, tag) {
    	var idx = this.indexOfTag(anchorTag);
    	this.insertAtWithTag(idx - 1, obj, tag);
    },

    removeByTag: function(tag) {
    	var idx = this.indexOfTag(tag);
    	this.removeAt(idx);
    	delete this.get('taggedObjects')[tag];
    },

    replaceByTag: function(tag, obj) {
    	var idx = this.indexOfTag(tag);
    	this.removeByTag(tag);
    	this.insertAtWithTag(idx, obj, tag);
    },

    moveByTag: function(tag, idx) {
    	var obj = this.objectByTag(tag);
    	this.removeByTag(tag);
    	this.insertAtWithTag(idx, obj, tag);
    },

    indexOfTag: function(tag) {
    	return this.indexOf(this.get('taggedObjects')[tag]);
    },

    objectByTag: function(tag) {
    	return this.get('taggedObjects')[tag];
    }
});
