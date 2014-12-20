import Ember from 'ember';

function HSVtoRGB(h, s, v) {
    var r, g, b, i, f, p, q, t;
    if (h && s === undefined && v === undefined) {
        s = h.s, v = h.v, h = h.h;
    }
    i = Math.floor(h * 6);
    f = h * 6 - i;
    p = v * (1 - s);
    q = v * (1 - f * s);
    t = v * (1 - (1 - f) * s);
    switch (i % 6) {
        case 0: r = v, g = t, b = p; break;
        case 1: r = q, g = v, b = p; break;
        case 2: r = p, g = v, b = t; break;
        case 3: r = p, g = q, b = v; break;
        case 4: r = t, g = p, b = v; break;
        case 5: r = v, g = p, b = q; break;
    }
    return {
        r: Math.floor(r * 255),
        g: Math.floor(g * 255),
        b: Math.floor(b * 255)
    };
}

export default Ember.Handlebars.makeBoundHelper(function(user, options) {
    if (!user) return;

    var number;
    if (number = user.get('avatarNumber')) {
        number = number + '';
        var filename = number.length >= 3 ? number : new Array(3 - number.length + 1).join('0') + number;
        return new Handlebars.SafeString('<img src="/packages/flarum/core/avatars/'+filename+'.jpg" class="avatar '+options.hash.class+'">');
    }

	var username = user.get('username');
    if (!username) username = '?';

	var letter = username.charAt(0).toUpperCase();

	var num = 0;
	for (var i = 0; i < username.length; i++) {
		num += username.charCodeAt(i) * 13;
	}

	var hue = num % 360;
	var rgb = HSVtoRGB(hue / 360, 100 / 255, 200 / 255);
	var bg = ''+rgb.r.toString(16)+rgb.g.toString(16)+rgb.b.toString(16);
	return new Handlebars.SafeString('<span class="avatar '+options.hash.class+'" style="background:#'+bg+'" title="'+username+'">'+letter+'</span>');
});

