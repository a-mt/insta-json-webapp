
<?php if(!empty($extraData['GraphChallengePageBanner'])) { ?>
  <p class="title-error"><?= $extraData['GraphChallengePageBanner']['banner_text'] ?></p>
<?php } ?>

<div class="challenge form">

  <!-- Header, subheader, text -->
  <div class="header"><?= $extraData['GraphChallengePageHeader']['title'] ?></div>

  <?php if(!empty($extraData['GraphChallengePageSubheader'])) { ?>
    <div class="subheader"><?= $extraData['GraphChallengePageSubheader']['html'] ?></div>
  <?php }
  if(!empty($extraData['GraphChallengePageText'])) { ?>
    <p class="text"><?= $extraData['GraphChallengePageText']['text'] ?></p>
  <?php } ?>

  <!-- Choice -->
  <form method="POST">
    <input type="hidden" name="challenge" value="<?= $challengeType ?>">

  <?php foreach($extraData['GraphChallengePageForm']['fields'] as $_fields) {

    // ReviewLoginForm
    switch($_fields['input_type']) {
    case "choice_buttons": ?>

      <div class="fields">
      <?php foreach($_fields['values'] as $_field) { ?>
        <button class="btn <?= ($_field['selected'] ? 'active' : '') ?>" name="choice" value="<?= $_field['value'] ?>">
          <?= $_field['label'] ?>
        </button>

      <?php }
      print "</div>";
      break;

    // SelectVerificationMethodForm
    case "choice": ?>

      <div class="fields">
      <?php foreach($_fields['values'] as $i => $_field) { ?>
        <label for="choice_<?= $i ?>">
          <?= $_field['label'] ?>
          <input class="hidden" id="choice_<?= $i ?>" type="radio" <?= ($_field['selected'] ? 'checked="checked"' : '') ?>
                 name="choice" value="<?= $_field['value'] ?>">
        </label>

      <?php }
      print "</div>";
      break;

    // Confirm
    case "hidden":
      foreach($_fields['values'] as $i => $_field) { ?>
        <input type="hidden" name="<?= $_field['name'] ?>" value="<?= $_field['value'] ?>">
      <?php }
      break;

    case "text":
    case "number": ?>

      <div class="fields">
      <?php foreach($_fields['values'] as $i => $_field) {
        echo '<input';
        foreach($_field as $name => $value) {
          echo ' ' . $name . '="' . $value . '"';
        }
        echo '>';
      }
      print "</div>";
      break;
    }
  }

  if($extraData['GraphChallengePageForm']['call_to_action']) { ?>
      <div class="fields">
        <button class="btn active">
          <?= $extraData['GraphChallengePageForm']['call_to_action'] ?>
        </button>
      </div>
  <?php } ?>

  </form>
</div>