<form class="form-horizontal">
  <fieldset id="payment">
    <legend><?php echo $text_credit_card; ?></legend>
    
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-owner"><?php echo $entry_cc_owner; ?></label>
      <div class="col-sm-10">
        <input type="text" name="cc_owner" value="" placeholder="<?php echo $entry_cc_owner; ?>" id="input-cc-owner" class="form-control" />
      </div>
    </div>
    
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-number"><?php echo $entry_cc_number; ?></label>
      <div class="col-sm-10">
        <input type="text" name="cc_number" value="" placeholder="<?php echo $entry_cc_number; ?>" id="input-cc-number" class="form-control" maxlength="16"/>
      </div>
    </div>
    
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-expire-date"><?php echo $entry_cc_expire_date; ?></label>
      <div class="col-sm-3">
        <select name="cc_expire_date_month" id="input-cc-expire-date" class="form-control">
          <option value="">--</option>
          <?php foreach ($cc_exp_months as $month) { ?>
          <option value="<?php echo $month['value']; ?>"><?php echo $month['text']; ?></option>
          <?php } ?>
        </select>
      </div>
      
      <div class="col-sm-3">
        <select name="cc_expire_date_year" class="form-control">
          <option value="">--</option>	
          <?php foreach ($cc_exp_years as $year) { ?>
          <option value="<?php echo $year['value']; ?>"><?php echo $year['text']; ?></option>
          <?php } ?>
        </select>
      </div>
    </div>
    
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-cvv2"><?php echo $entry_cc_cvv2; ?></label>
      <div class="col-sm-10">
        <input type="text" name="cc_cvv2" value="" placeholder="<?php echo $entry_cc_cvv2; ?>" id="input-cc-cvv2" class="form-control" maxlength="4"/>    
      </div>
    </div>
    
    <div class="form-group">
      <label class="col-sm-2 control-label" for="input-cc-issue"><?php echo $entry_cc_issue; ?></label>
      <div class="col-sm-10">
        <input type="text" name="cc_issue" value="" placeholder="<?php echo $entry_cc_issue; ?>" id="input-cc-issue" class="form-control" />
      	 <?php echo $text_issue; ?></td>
      </div>
    </div>
  </fieldset>
</form>
<div id="paymentsense-message-error" class="alert alert-danger" style="display: none;"></div>
<div class="buttons">
  <div class="pull-right">
    <input type="button" value="<?php echo $button_confirm; ?>" id="button-confirm" class="btn btn-primary" data-loading-text="<?php echo $text_loading; ?>" />
  </div>
</div>

<script type="text/javascript"><!--
  $('#button-confirm').bind('click', function() {
    $('#paymentsense-message-error').slideUp();
    $.ajax({
      type: 'POST',
      url: 'index.php?route=extension/payment/paymentsense_direct/process',
      data: $('#payment :input'),
      dataType: 'json',
      beforeSend: function() {
        $('#button-confirm').button('loading');
      },
      complete: function() {
        $('#button-confirm').button('reset');
      },
      success: function(json) {
        if (json['ACSURL']) {
          $('#3dauth').remove();

          html  = '<form action="' + json['ACSURL'] + '" method="post" id="3dauth">';
          html += '<input type="hidden" name="MD" value="' + json['MD'] + '" />';
          html += '<input type="hidden" name="PaReq" value="' + json['PaReq'] + '" />';
          html += '<input type="hidden" name="TermUrl" value="' + json['TermUrl'] + '" />';
          html += '</form>';

          $('#payment').after(html);

          $('#3dauth').submit();
        }

        if (json['error']) {
          $('#paymentsense-message-error').html('<i class="fa fa-minus-circle"></i> ' + json['error']).slideDown();
          $('#button-confirm').attr('disabled', false);
        }

        $('.attention').remove();

        if (json['success']) {
          location = json['success'];
        }
      },
      error: function(xhr) {
	      $('#paymentsense-message-error').html('<i class="fa fa-minus-circle"></i> ' + '<?php echo $error_failed;?>' + ' (Status: ' +xhr.status + ', StatusText: ' + xhr.statusText + ', ResponseText: ' + xhr.responseText +')').slideDown();
	      $('#button-confirm').attr('disabled', false);
      }
    });
  });
  //--></script>
