<div id="paymentsense-message-wait" class="alert alert-info" style="display: none;"></div>
<form action="<?php echo $action; ?>" method="post" id="payment">
  <?php foreach ($fields as $key => $value) { ?>
    <input type="hidden" name="<?php echo $key; ?>" value="<?php echo $value; ?>" />
  <?php } ?>
</form>
<div class="buttons">
    <div class="pull-right">
        <input id="button-confirm" type="button" value="<?php echo $button_confirm; ?>" data-loading-text="<?php echo $text_loading; ?>" class="btn btn-primary" />
	</div>
</div>
<script type="text/javascript"><!--
  $('#button-confirm').click(function() {
    $('#button-confirm').button('loading');
    $('#paymentsense-message-wait').html('<i class="fa fa-info-circle"></i> <?php echo $text_wait; ?>').show();
    $('#payment').submit();
  });
  //--></script>
