import Ember from 'ember';

function hsvToRgb(h, s, v) {
  var r, g, b, i, f, p, q, t;
  if (h && s === undefined && v === undefined) {
    s = h.s; v = h.v; h = h.h;
  }
  i = Math.floor(h * 6);
  f = h * 6 - i;
  p = v * (1 - s);
  q = v * (1 - f * s);
  t = v * (1 - (1 - f) * s);
  switch (i % 6) {
    case 0: r = v; g = t; b = p; break;
    case 1: r = q; g = v; b = p; break;
    case 2: r = p; g = v; b = t; break;
    case 3: r = p; g = q; b = v; break;
    case 4: r = t; g = p; b = v; break;
    case 5: r = v; g = p; b = q; break;
  }
  return {
    r: Math.floor(r * 255),
    g: Math.floor(g * 255),
    b: Math.floor(b * 255)
  };
}

export default function(string) {
  var num = 0;
  for (var i = 0; i < string.length; i++) {
    num += string.charCodeAt(i);
  }
  var hue = num % 360;
  var rgb = hsvToRgb(hue / 360, 100 / 255, 200 / 255);
  return ''+rgb.r.toString(16)+rgb.g.toString(16)+rgb.b.toString(16);
};
