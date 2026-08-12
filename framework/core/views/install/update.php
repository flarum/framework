<h2>Update Flarum</h2>

<?php if ($usesDatabaseName): ?>
<p>Enter your database name to update Flarum. Before you proceed, you should <strong>back up your database</strong>. If you have any trouble, get help on the <a href="https://docs.flarum.org/update" target="_blank">Flarum website</a>.</p>
<?php else: ?>
<p>Enter your database username and password to update Flarum. Before you proceed, you should <strong>back up your database</strong>. If your database has no password, leave that field blank. If you have any trouble, get help on the <a href="https://docs.flarum.org/update" target="_blank">Flarum website</a>.</p>
<?php endif; ?>

<form method="post">
  <div id="error" style="display:none"></div>

  <?php if ($usesDatabaseName): ?>
  <div class="FormGroup">
    <div class="FormField">
      <label>Database Name</label>
      <input class="FormControl" type="text" name="databaseName" autocomplete="off">
    </div>
  </div>
  <?php else: ?>
  <div class="FormGroup">
    <div class="FormField">
      <label>Database Username</label>
      <input class="FormControl" type="text" name="databaseUsername" autocomplete="off">
    </div>
  </div>

  <div class="FormGroup">
    <div class="FormField">
      <label>Database Password</label>
      <input class="FormControl" type="password" name="databasePassword" autocomplete="off">
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
