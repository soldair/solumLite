<div id="popup-holder" style="display:none;">
	<?
	if(isset($popups)){
		foreach($popups as $p){
			if($p) $this->view($p);
		}
	}
	?>
</div>
<div id="popup-wrap-template" style="display:none;">
	<div>
		<div class="title-box2 clearfix box-corner">

		</div>
		<div class="pop-close-link-holder" style="height:14px;margin-top:-14px;text-align:right;">
				<a class="popup-close-link maroon" href="#" style="position:relative;top:-40px;font-weight:bold;text-decoration:none;">Close</a>
		</div>
		<div class="clearfix box-content">

		</div>
	</div>
</div>