import { stop, animate, Promise, isAnimating, finish } from "vendor/liquid-fire";

export default function slide(oldView, insertNewView, dimension, direction, opts) {
  var oldParams = {},
      newParams = {},
      firstStep,
      property,
      measure;

  if (dimension.toLowerCase() === 'x') {
    property = 'translateX';
    measure = 'width';
  } else {
    property = 'translateY';
    measure = 'height';
  }

  if (isAnimating(oldView, 'moving-in')) {
    firstStep = finish(oldView, 'moving-in');
  } else {
    stop(oldView);
    firstStep = Promise.cast();
  }


  return firstStep.then(insertNewView).then(function(newView){
    // if (newView && newView.$() && oldView && oldView.$()) {
      // var sizes = [parseInt(newView.$().css(measure), 10),
                   // parseInt(oldView.$().css(measure), 10)];
      // var bigger = Math.max.apply(null, sizes);
      var bigger = 20;
      oldParams[property] = (bigger * direction) + 'px';
      newParams[property] = ["0px", (-1 * bigger * direction) + 'px'];
    // }
    //  else {
    //   oldParams[property] = (100 * direction) + '%';
    //   newParams[property] = ["0%", (-100 * direction) + '%'];
    // }

    oldParams['opacity'] = [0, 1];
    newParams['opacity'] = [1, 0];

    return Promise.all([
      animate(oldView, oldParams, opts),
      animate(newView, newParams, opts, 'moving-in')
    ]);
  });
}
