<!DOCTYPE html>
<html>
	<head>
		<?if(isset($site_name)){?>
			<title><?
			if(isset($page_title) && $page_title){
				echo $site_name." - ".$page_title;
			} else if($site_name){
				echo $site_name;
			}
			?>
			</title>
			<?
		}
		?>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<?
		if(isset($css_includes) && $css_includes){
			echo $css_includes;
		}
		?>
	</head>
	<body>
		<script>var after = [];</script>
		<?$this->view($header)?>

		<?
		//needs to be customized foreach app of course
		if(isset($page)){
			echo $page;
		} else {
			//this should never be hit because of the 404 routing in the controller
			echo '404: sorry the page you were looking for could not be found';
		}
		?>

		<?$this->view($footer)?>

		<?$this->view($popups)?>
		<?
		if(isset($js_includes) && $js_includes){
			echo $js_includes;
		}
		?>
		<script>
			if(!window.app) app = {CONSTANTS:{}};

			app.CONSTANTS.authenticated = <?=$authenticated?1:0?>;
			app.CONSTANTS.user_id = <?=$user?$user['users_id']:0?>;
			app.CONSTANTS.role = <?=$user?$user['role_id']:0?>;
			while(after.length){(after.pop())()};
		</script>
	</body>

</html>

