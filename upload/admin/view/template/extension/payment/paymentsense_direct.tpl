<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
 <div class="page-header">
  <div class="container-fluid">
      <div class="pull-right">
       <button type="button" id="paymentsense_direct_submit" data-form="form-paymentsense-direct" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
       <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
      <h1><?php echo $heading_title; ?></h1>
  <ul class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
    <?php } ?>
   </ul>
  </div>
 </div>
    <div class="container-fluid">

        <div class="container-fluid module-controls-container">
            <div class="alert alert-notification alert-dismissible">
                <span class="alert-text"></span>
                <button type="button" class="close" data-hide="alert-notification">&times;</button>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><strong>Module Version:</strong> <?php echo $module_version; ?><br />
                        <strong>Release Date:</strong> <?php echo $module_date; ?></h3>
                </div>
                <div class="panel-body">
   
	<form data-action="<?php echo $action; ?>" data-method="post" enctype="multipart/form-data" id="form-paymentsense-direct" class="form-horizontal">
       
   <div class="form-group">
            <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status; ?></label>
            <div class="col-sm-10">
              <select name="paymentsense_direct_status" id="input-status" class="form-control">
                <?php if ($paymentsense_direct_status) { ?>
                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                <option value="0"><?php echo $text_disabled; ?></option>
                <?php } else { ?>
                <option value="1"><?php echo $text_enabled; ?></option>
                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                <?php } ?>
              </select>
            </div>
          </div>
          
     <div class="form-group required">
  	<label class="col-sm-2 control-label" for="input-merchant-id"><?php echo $entry_mid; ?></label>
    <div class="col-sm-10">
              <input type="text" name="paymentsense_direct_mid" value="<?php echo $paymentsense_direct_mid; ?>" placeholder="<?php echo $entry_mid; ?>" id="input-merchant-id" class="form-control" />
        <div id="mid"></div>
     </div>
     </div> 
     
         
     <div class="form-group required">
            <label class="col-sm-2 control-label" for="input-entry_pass"><?php echo $entry_pass; ?></label>
            <div class="col-sm-10">
              <input type="text" name="paymentsense_direct_pass" value="<?php echo $paymentsense_direct_pass; ?>" placeholder="<?php echo $entry_pass; ?>" id="input-entry_pass" class="form-control" />
                <div id="key"></div>
            </div>
    </div>   
    
    <div class="form-group">
            <label class="col-sm-2 control-label" for="input-entry_type"><?php echo $entry_type; ?></label>
        <div class="col-sm-10">
            <select name="paymentsense_direct_type" id="input-order-type" class="form-control">				
            <option value="SALE"<?php if ($paymentsense_direct_type == "SALE") { ?> selected="selected"<?php } ?>><?php echo $text_sale; ?></option>
            <option value="PREAUTH"<?php if ($paymentsense_direct_type == "PREAUTH") { ?> selected="selected"<?php } ?>><?php echo $text_preauth; ?></option>            
          </select>  
        </div>
    </div>
	
    <div class="form-group">
            <label class="col-sm-2 control-label" for="input-order-status"><?php echo $entry_order_status; ?></label>
            <div class="col-sm-10">
            <select name="paymentsense_direct_order_status_id" id="input-order-status" class="form-control">
            <?php foreach ($order_statuses as $order_status) { ?>
            <?php if ($order_status['order_status_id'] == $paymentsense_direct_order_status_id) { ?>
            <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
            <?php } else { ?>
            <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
            <?php } ?>
            <?php } ?>
          </select>
         </div>
    </div>
    
   <div class="form-group">
            <label class="col-sm-2 control-label" for="input-failed-order-status"><?php echo $entry_failed_order_status; ?></label>
            <div class="col-sm-10">
              <select name="paymentsense_direct_failed_order_status_id" id="input-failed-order-status" class="form-control">
                <?php foreach ($order_statuses as $order_status) { ?>
            <?php if ($order_status['order_status_id'] == $paymentsense_direct_failed_order_status_id) { ?>
            <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
            <?php } else { ?>
            <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
            <?php } ?>
            <?php } ?>
              </select>
         </div>
    </div>
    
     <div class="form-group">
            <label class="col-sm-2 control-label" for="input-geo-zone"><?php echo $entry_geo_zone; ?></label>
            <div class="col-sm-10">
              <select name="paymentsense_direct_geo_zone_id" id="input-geo-zone" class="form-control">
                <option value="0"><?php echo $text_all_zones; ?></option>
            <?php foreach ($geo_zones as $geo_zone) { ?>
            <?php if ($geo_zone['geo_zone_id'] == $paymentsense_direct_geo_zone_id) { ?>
            <option value="<?php echo $geo_zone['geo_zone_id']; ?>" selected="selected"><?php echo $geo_zone['name']; ?></option>
            <?php } else { ?>
            <option value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?></option>
            <?php } ?>
            <?php } ?>
              </select>
            </div>
          </div> 
          
    <div> <label <strong>CV2 Policy Settings</strong> </label> </div>
    <div class="help">The CV2 Check will compare the signature strip digits provided by the customer, against that held by their card's issuing bank.<br />The following settings determine when the gateway will pass/fail a transaction based on the results of this check.<br /><strong>Note:</strong> These settings will override any settings already set in the MMS.</div>                  
    
	<div class="form-group">
            <label class="col-sm-2 control-label"  for="input-CV2-policy">CV2 Policy <h6>Whether the gateway will pass or fail a transaction if the CV2 check fails.</h6></label>
            <div class="col-sm-10">
              <select name="paymentsense_direct_cv2_policy_1" id="input-CV2-policy" class="form-control" >
                <option value="F"<?php if ($paymentsense_direct_cv2_policy_1 == "F") { ?> selected="selected"<?php } ?>>Fail Transaction on CV2 Failure (Recommended)</option>
            	<option value="P"<?php if ($paymentsense_direct_cv2_policy_1 == "P") { ?> selected="selected"<?php } ?>>Pass Transaction regardless</option>
			  </select>
            </div>
          </div> 
	 
	 <div class="form-group">
            <label class="col-sm-2 control-label" for="input-CV2-policy2">When CV2 Results Unknown <h6>Whether the gateway will pass or fail a transaction if the result of the CV2 check is Unknown.</h6></label>
            <div class="col-sm-10">
              <select name="paymentsense_direct_cv2_policy_2" id="input-CV2-policy2" class="form-control" >
                <option value="P"<?php if ($paymentsense_direct_cv2_policy_2 == "P") { ?> selected="selected"<?php } ?>>Pass Transaction (Recommended)</option>
				<option value="F"<?php if ($paymentsense_direct_cv2_policy_2 == "F") { ?> selected="selected"<?php } ?>>Fail Transaction</option>
			  </select>
            </div>
          </div>
          
      <div> <label  <strong>AVS (Address & Postcode check) Policy Settings</strong> </label> </div>
    <div class="help">The AVS Check will compare the billing house number and the billing post code provided by the customer, against that held by their card's issuing bank.<br />The following settings determine when the gateway will pass/fail a transaction based on the results of this check.<br /><strong>Note:</strong> These settings will override any settings already set in the MMS.</div>    

     <div class="form-group">
            <label class="col-sm-2 control-label" for="input-AVS-policy">AVS Policy <br/> <h6>Overall AVS Policy being used.</h6></label>
            <div class="col-sm-10">
              <select name="paymentsense_direct_avs_policy_1" id="input-AVS-policy" class="form-control">
                <option value="E"<?php if ($paymentsense_direct_avs_policy_1 == "E") { ?> selected="selected"<?php } ?>>Fail if Either Address or Post Code Fail (Recommended)</option>
            	<option value="B"<?php if ($paymentsense_direct_avs_policy_1 == "B") { ?> selected="selected"<?php } ?>>Fail Only If Both Address and Post Code Fail</option>
				<option value="A"<?php if ($paymentsense_direct_avs_policy_1 == "A") { ?> selected="selected"<?php } ?>>Fail Only If Address Fails</option>
				<option value="P"<?php if ($paymentsense_direct_avs_policy_1 == "P") { ?> selected="selected"<?php } ?>>Fail Only If Post Code Fails</option>
				<option value="N"<?php if ($paymentsense_direct_avs_policy_1 == "N") { ?> selected="selected"<?php } ?>>Do Not Fail</option>
			 </select>
            </div>
          </div>   
	 
	 <div class="form-group">
            <label class="col-sm-2 control-label" for="input-AVS-policy2">Treat Partial Address <h6>Whether the gateway will pass or fail a transaction if only part of the Address matches.</h6></label>
            <div class="col-sm-10">
              <select name="paymentsense_direct_avs_policy_2" id="input-AVS-policy2" class="form-control">
                 <option value="F"<?php if ($paymentsense_direct_avs_policy_2 == "F") { ?> selected="selected"<?php } ?>>Treat Partial Match As Fail (Recommended)</option>
                 <option value="P"<?php if ($paymentsense_direct_avs_policy_2 == "P") { ?> selected="selected"<?php } ?>>Treat Partial Match As Pass</option>
			 </select>
            </div>
          </div>   	  
	      
	  <div class="form-group">
            <label class="col-sm-2 control-label" for="input-AVS-policy3">Treat Partial Post Code <h6>Whether the gateway will pass or fail a transaction if only part of the Post Code matches.</h6></label>
            <div class="col-sm-10">
               <select name="paymentsense_direct_avs_policy_3" id="input-AVS-policy3" class="form-control">
                 <option value="F"<?php if ($paymentsense_direct_avs_policy_3 == "F") { ?> selected="selected"<?php } ?>>Treat Partial Match As Fail (Recommended)</option>
                 <option value="P"<?php if ($paymentsense_direct_avs_policy_3 == "P") { ?> selected="selected"<?php } ?>>Treat Partial Match As Pass</option>
			 </select>
            </div>
          </div>
          
      <div class="form-group">
            <label class="col-sm-2 control-label" for="input-AVS-policy4">When Results are Unknown <h6>Whether the gateway will pass or fail a transaction if the result of the AVS check is Unknown (normally occurs on a card issued by a non-UK bank).</h6></label>
            <div class="col-sm-10">
              <select name="paymentsense_direct_avs_policy_4" id="input-AVS-policy4" class="form-control">
                 <option value="P"<?php if ($paymentsense_direct_avs_policy_4 == "P") { ?> selected="selected"<?php } ?>>Pass Transaction (Recommended)</option>
                 <option value="F"<?php if ($paymentsense_direct_avs_policy_4 == "F") { ?> selected="selected"<?php } ?>>Fail Transaction</option>
			</select>
            </div>
          </div>   
  </div>
</form>
</div>
</div>
</div>
<?php echo $footer; ?>



<script type="text/javascript">

  function hideAlertNotification() {
    var $alertNotificationHolder = $('.module-controls-container').find('.alert-notification');
    $alertNotificationHolder.slideUp();
  }

  function displayAlertNotification(type, messageText) {
    var $alertNotificationHolder = $('.module-controls-container').find('.alert-notification');
    var alertNotificationClass = 'alert-' + type;
    var notificationTypes = [
      'info',
      'success',
      'warning',
      'danger'
    ];

    $alertNotificationHolder.find('.alert-text').html(messageText);

    $.each(notificationTypes, function(index, key) {
      $alertNotificationHolder.removeClass('alert-' + key);
    });

    $alertNotificationHolder.addClass(alertNotificationClass).slideDown();
  }

  $(function() {

    hideAlertNotification();

<?php if ($warning_insecure_connection) { ?>
    displayAlertNotification('warning', '<?php echo $warning_insecure_connection;?>');
<?php } ?>

    $("[data-hide]").on("click", function(){
      $("." + $(this).attr("data-hide")).slideUp();
    });

    $('#paymentsense_direct_submit').click(function() {
      var $form = $('#' + $(this).attr('data-form'));

      hideAlertNotification();

      $('#mid').html('');
      $('#pass').html('');

      $.ajax({
        url:    $form.attr('data-action'),
        type:   $form.attr('data-method'),
        data:   $form.serialize(),
        beforeSend: function () {
          $('#paymentsense_direct_submit').attr('disabled', 'disabled');
        },
        complete: function() {
          $('#paymentsense_direct_submit').removeAttr('disabled').blur();
        },
        success: function (data) {
          if (data.result == 0) {
            displayAlertNotification('success', '<?php echo $text_success;?>');
          } else if (data.result == 1) {
            displayAlertNotification('danger', '<?php echo $error_permission;?>');
          } else if (data.messages !== undefined) {
            for (var key in data.messages) {
              $('#'+key).addClass("text-danger").html(data.messages[key]);
            }
            displayAlertNotification('danger', '<?php echo $error_required;?>');
          } else {
            displayAlertNotification('danger', '<?php echo $error_failed;?>');
          }
        },
        error: function(xhr) {
	        displayAlertNotification('danger', '<?php echo $error_failed;?>' + ' (Status: ' +xhr.status + ', StatusText: ' + xhr.statusText + ', ResponseText: ' + xhr.responseText +')');
        }
      });
    });
  });

</script>
