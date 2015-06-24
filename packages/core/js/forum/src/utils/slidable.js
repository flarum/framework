export default function slidable(element) {
  var $slidable = $(element);

  var startX;
  var startY;
  var couldBeSliding = false;
  var isSliding = false;
  var threshold = 50;
  var pos = 0;

  var underneathLeft;
  var underneathRight;

  var animatePos = function(pos, options) {
    options = options || {};
    options.duration = options.duration || 'fast';
    options.step = function(pos) {
      $(this).css('transform', 'translate('+pos+'px, 0)');
    };

    $slidable.find('.slidable-slider').animate({'background-position-x': pos}, options);
  };

  var reset = function() {
    animatePos(0, {
      complete: function() {
        $slidable.removeClass('sliding');
        underneathLeft.hide();
        underneathRight.hide();
        isSliding = false;
      }
    });
  };

  $slidable.find('.slidable-slider')
    .on('touchstart', function(e) {
      underneathLeft = $slidable.find('.slidable-underneath-left:not(.disabled)');
      underneathRight = $slidable.find('.slidable-underneath-right:not(.disabled)');

      startX = e.originalEvent.targetTouches[0].clientX;
      startY = e.originalEvent.targetTouches[0].clientY;

      couldBeSliding = true;
      console.log('GO')
    })

    .on('touchmove', function(e) {
      var newX = e.originalEvent.targetTouches[0].clientX;
      var newY = e.originalEvent.targetTouches[0].clientY;

      if (couldBeSliding && Math.abs(newX - startX) > Math.abs(newY - startY)) {
        isSliding = true;
      }
      couldBeSliding = false;

      if (isSliding) {
        pos = newX - startX;

        if (underneathLeft.length)  {
          if (pos > 0 && underneathLeft.hasClass('elastic')) {
            pos -= pos * 0.5;
          }
          underneathLeft.toggle(pos > 0);
          underneathLeft.find('.icon').css('transform', 'scale('+Math.max(0, Math.min(1, (Math.abs(pos) - 25) / threshold))+')');
        } else {
          pos = Math.min(0, pos);
        }

        if (underneathRight.length)  {
          if (pos < 0 && underneathRight.hasClass('elastic')) {
            pos -= pos * 0.5;
          }
          underneathRight.toggle(pos < 0);
          underneathRight.find('.icon').css('transform', 'scale('+Math.max(0, Math.min(1, (Math.abs(pos) - 25) / threshold))+')');
        } else {
          pos = Math.max(0, pos);
        }

        $(this).css('transform', 'translate('+pos+'px, 0)');
        $(this).css('background-position-x', pos+'px');

        $slidable.toggleClass('sliding', !!pos);

        e.preventDefault();
      }
    })

    .on('touchend', function(e) {
      if (underneathRight.length && pos < -threshold) {
        underneathRight.click();
        underneathRight.hasClass('elastic') ? reset() : animatePos(-$slidable.width());
      } else if (underneathLeft.length && pos > threshold) {
        underneathLeft.click();
        underneathLeft.hasClass('elastic') ? reset() : animatePos(-$slidable.width());
      } else {
        reset();
      }
      couldBeSliding = false;
    });

  return {reset};
};
