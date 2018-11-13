<h2>Hold Up!</h2>

<p>These problems must be resolved before you can continue the installation. If you're having trouble, get help on the <a href="https://flarum.org/docs/install.html" target="_blank">Flarum website</a>.</p>

<div class="Problems">
  <?php foreach ($problems as $problem): ?>
    <div class="Problem">
      <h3 class="Problem-message"><?php echo $problem['message']; ?></h3>
      <?php if (! empty($problem['detail'])): ?>
        <p class="Problem-detail"><?php echo $problem['detail']; ?></p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
