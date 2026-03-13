<h2>Update Flarum</h2>

<?php if ($needsPassword): ?>
<p>Enter your database password to update Flarum. Before you proceed, you should <strong>back up your database</strong>. If you have any trouble, get help on the <a href="https://docs.flarum.org/update" target="_blank">Flarum website</a>.</p>
<?php else: ?>
<p>Click the button below to update Flarum. Before you proceed, you should <strong>back up your database</strong>. If you have any trouble, get help on the <a href="https://docs.flarum.org/update" target="_blank">Flarum website</a>.</p>
<?php endif; ?>

<form method="post">
  <div id="error" style="display:none"></div>

  <?php if ($needsPassword): ?>
  <div class="FormGroup">
    <div class="FormField">
      <label>Database Password</label>
      <input class="FormControl" type="password" name="databasePassword">
    </div>
  </div>
  <?php endif; ?>

  <div class="FormButtons">
    <button type="submit">Update Flarum</button>
  </div>
</form>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var input = document.querySelector('form input');
    if (input) input.select();

    document.querySelector('form').addEventListener('submit', function(e) {
      e.preventDefault();

      var button = this.querySelector('button');
      button.textContent = 'Please Wait...';
      button.disabled = true;

      fetch('', {
        method: 'POST',
        body: new FormData(this)
      })
        .then(response => {
          if (response.ok) {
            window.location.reload();
          } else {
            response.text().then(errorMessage => {
              var error = document.querySelector('#error');
              error.style.display = 'block';
              error.textContent = 'Something went wrong:\n\n' + errorMessage;
              button.disabled = false;
              button.textContent = 'Update Flarum';
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
        });

      return false;
    });
  });
</script>
