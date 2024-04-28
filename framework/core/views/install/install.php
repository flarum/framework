<h2>Install Flarum</h2>

<p>Set up your forum by filling out your details below. If you have any trouble, get help on the <a href="https://docs.flarum.org/install" target="_blank">Flarum website</a>.</p>

<form method="post">
  <div id="error" style="display:none"></div>

  <div class="FormGroup">
    <div class="FormField">
      <label>Forum Title</label>
      <input class="FormControl" name="forumTitle">
    </div>
  </div>

  <div class="FormGroup">
    <div data-group="sqlite" style="display:none">
      <div class="Alert Alert--warning">
        <strong>Warning:</strong> Please keep in mind that while Flarum supports SQLite, not all ecosystem extensions do. If you're planning to install extensions, you should expect some of them to not work properly or at all.
      </div>
    </div>
  </div>

  <div class="FormGroup">
    <div class="FormField">
      <label>Database Driver</label>
      <select class="FormControl" name="dbDriver">
        <option value="mysql">MySQL</option>
        <option value="sqlite">SQLite</option>
      </select>
    </div>

    <div class="FormField">
      <label>Database</label>
      <input class="FormControl" name="dbName" value="flarum">
    </div>

    <div data-group="mysql">
      <div class="FormField">
        <label>MySQL Host</label>
        <input class="FormControl" name="mysqlHost" value="localhost">
      </div>

      <div class="FormField">
        <label>MySQL Username</label>
        <input class="FormControl" name="mysqlUsername">
      </div>

      <div class="FormField">
        <label>MySQL Password</label>
        <input class="FormControl" type="password" name="mysqlPassword">
      </div>
    </div>

    <div class="FormField">
      <label>Table Prefix</label>
      <input class="FormControl" type="text" name="tablePrefix">
    </div>
  </div>

  <div class="FormGroup">
    <div class="FormField">
      <label>Admin Username</label>
      <input class="FormControl" name="adminUsername">
    </div>

    <div class="FormField">
      <label>Admin Email</label>
      <input class="FormControl" name="adminEmail">
    </div>

    <div class="FormField">
      <label>Admin Password</label>
      <input class="FormControl" type="password" name="adminPassword">
    </div>

    <div class="FormField">
      <label>Confirm Password</label>
      <input class="FormControl" type="password" name="adminPasswordConfirmation">
    </div>
  </div>

  <div class="FormButtons">
    <button type="submit">Install Flarum</button>
  </div>
</form>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('form input').select();

    document.querySelector('select[name="dbDriver"]').addEventListener('change', function() {
      document.querySelectorAll('[data-group]').forEach(function(group) {
        group.style.display = 'none';
      });

      const groups = document.querySelectorAll('[data-group="' + this.value + '"]');

      groups.forEach(function(group) {
        group.style.display = 'block';
      });
    });

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
              button.textContent = 'Install Flarum';
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

