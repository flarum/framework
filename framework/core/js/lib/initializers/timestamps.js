import humanTime from 'flarum/utils/human-time';

export default function(app) {
  setInterval(function() {
    $('[data-humantime]').each(function() {
      var $this = $(this);
      $this.html(humanTime($this.attr('datetime')));
    });
  }, 1000);
}
