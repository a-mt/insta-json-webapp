
<form class="form-control form login" method="POST">
  <div>
    <label for="username">Username</label>
    <input type="text" name="username" id="username" required>
  </div>
  <div>
    <label for="password">Password</label>
    <input type="password" name="password" id="password" required>
  </div>
  <div>
    <button class="btn primary big" type="submit">Log in</button>
    <?php if($error) { ?><p class="error"><?= $error ?></p><?php } ?>
  </div>
</form>