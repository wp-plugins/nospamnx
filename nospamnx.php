<?php
/*
Plugin Name: NoSpamNX
Plugin URI: http://www.svenkubiak.de/nospamnx-en
Description: To protect your Blog from automated spambots, which fill you comments with junk, this plugin adds additional formfields to your comment form, which are checked every time a new comment is posted. NOTE: If the hidden fields are displayed, make sure your theme does load wp_head()! 
Version: 2.3
Author: Sven Kubiak
Author URI: http://www.svenkubiak.de

Copyright 2009 Sven Kubiak

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
global $wp_version;
define('NOSPAMNXREQWP27', version_compare($wp_version, '2.7', '>='));

if (!class_exists('NoSpamNX'))
{

	Class NoSpamNX
	{	
		var $nospamnx_names;
		var $nospamnx_count;
		var $nospamnx_operate;
		var $nospamnx_checkuser;
		var $nospamnx_blacklist;
		var $nospamnx_checkreferer;
		
		function nospamnx()
		{		
			//load language strings
			if (function_exists('load_plugin_textdomain'))
				load_plugin_textdomain('nospamnx', PLUGINDIR.'/nospamnx');
				
			//check if wordpress is at least 2.6
			if (NOSPAMNXREQWP27 != true){
				add_action('admin_notices', array(&$this, 'wpVersionFail'));
				return;
			}
			
			//check if required PHP functons are available
			if ($this->preFlight() === false){
				add_action('admin_notices', array(&$this, 'phpFail'));
				return;
			}
			
			//add nospamnx wordpress actions	
			add_action('init', array(&$this, 'checkCommentForm'));		
			add_action('comment_form', array(&$this, 'addHiddenFields'));	
			add_action('wp_head', array(&$this, 'nospamnxStyle'));
			add_action('admin_menu', array(&$this, 'nospamnxAdminMenu'));		
			add_action('rightnow_end', array(&$this, 'nospamnxStats'));
			
			//tell wp what to do when plugin is activated and deactivated
			register_activation_hook(__FILE__, array(&$this, 'activate'));
			register_deactivation_hook(__FILE__, array(&$this, 'deactivate'));		

			//load nospamnx options
			$this->loadOptions();
		}

		function wpVersionFail()
		{
			echo "<div id='message' class='error'><p>".__('Your WordPress is to old. NoSpamNX requires at least WordPress 2.7!','nospamnx')."</p></div>";
		}

		function phpFail()
		{
			echo "<div id='message' class='error'><p>".__('NoSpamNX is currently inactive! Some required PHP functions are not available. See Settings -> NoSpamNX -> Information for more details.','nospamnx')."</p></div>";
		}	
		
		function addHiddenFields()
		{	
			//get the formfields names and value from wp options
			$nospamnx = $this->nospamnx_names;
			
			//add hidden fields to the comment form
			if (rand(1,2) == 1)
				echo '<input type="text" name="'.$nospamnx['nospamnx-1'].'" value="" class="locktross" /><input type="text" name="'.$nospamnx['nospamnx-2'].'" value="'.$nospamnx['nospamnx-2-value'].'" class="locktross" />';
			else
				echo '<input type="text" name="'.$nospamnx['nospamnx-2'].'" value="'.$nospamnx['nospamnx-2-value'].'" class="locktross" /><input type="text" name="'.$nospamnx['nospamnx-1'].'" value="" class="locktross" />';						
		}
		
		function checkCommentForm()
		{													
			//check if we are in wp-comments-post.php
			if (basename($_SERVER['PHP_SELF']) != 'wp-comments-post.php')		
				return;
			//check if user is logged in and does not require checking
			else if ($this->nospamnx_checkuser == 0 && is_user_logged_in())
				return;
			else
			{		
				//perform blacklist check
				if ($this->blacklistCheck(
					trim($_POST['author']),
					trim($_POST['email']),
					trim($_POST['url']),
					$_POST['comment'],
					$_SERVER['REMOTE_ADDR']) == true)
					$this->birdbrained();
				
				//check if referer check is enabled and check referer
				if ($this->nospamnx_checkreferer == 1)
				{
					//get the host name for referer check
					preg_match('@^(?:http://)?([^/]+)@i',$_SERVER['HTTP_REFERER'],$match);			
				
					//check if referer matches wordpress option siteurl
					if (!empty($match[0]) && $match[0] != get_option('siteurl'))
						$this->birdbrained();				
				}

				//get current formfield names from wp options
				$nospamnx = $this->nospamnx_names;

				//check if first hidden field is in $_POST data
				if (!array_key_exists($nospamnx['nospamnx-1'],$_POST))
					$this->birdbrained();
				//check if first hidden field is empty
				else if ($_POST[$nospamnx['nospamnx-1']] != "")
					$this->birdbrained();
				//check if second hidden field is in $_POST data
				else if (!array_key_exists($nospamnx['nospamnx-2'],$_POST))
					$this->birdbrained();
				//check if the value of the second hidden field matches stored value
				else if ($_POST[$nospamnx['nospamnx-2']] != $nospamnx['nospamnx-2-value'])
					$this->birdbrained();	
			}
		}
		
		function birdbrained()
		{		
			//count spambot and save count
			$this->nospamnx_count++;
			$this->updateOptions();
			
			//check in which mode we are and block, mark as spam or put in moderation queue
			if ($this->nospamnx_operate == 'mark')
				add_filter('pre_comment_approved', create_function('$a', 'return \'spam\';'));
			else if ($this->nospamnx_operate == 'moderate')
				add_filter('pre_comment_approved', create_function('$a', 'return \'0\';'));
			else
				wp_die(__('Sorry, but your comment seems to be Spam.','nospamnx'));
		}
		
		function blacklistCheck($author, $email, $url, $comment, $remoteip)
		{
			//get current blacklist
			$blacklist = trim($this->nospamnx_blacklist);
			
			//return if blacklist is empty
			if ($blacklist == '' || empty($blacklist))
				return false;
		
			//split the values from each line
			$words = explode("\n", $blacklist);

			//loop through values and check if pattern matches
			foreach ((array)$words as $word )
			{
				//remove emtpy spaces
				$word = trim($word);

				//skipp through empty lines
				if (empty($word))
					continue;

				$word = preg_quote($word, '#');
				$pattern = "#$word#i";
			
				//check word against comment values
				if (preg_match($pattern, $author)
					|| preg_match($pattern, $email)
					|| preg_match($pattern, $url)
					|| preg_match($pattern, $remoteip))
				return true;
			}
			return false;
		}	
		
		function generateNames()
		{
			//generate random names and value for the hidden formfields
			$nospamnx = array(
				'nospamnx-1'		=> md5(uniqid(rand(), true)),
				'nospamnx-2'		=> md5(uniqid(rand(), true)),
				'nospamnx-2-value'	=> md5(uniqid(rand(), true))		
			);

			return $nospamnx;
		}	

		function nospamnxAdminMenu()
		{
			add_options_page('NoSpamNX', 'NoSpamNX', 8, 'nospamnx', array(&$this, 'nospamnxOptionPage'));	
		}
			
		function preFlight()
		{
			//check if required functions are available
			if (function_exists('ob_start')
				&& function_exists('str_replace')
				&& function_exists('preg_match')
				&& function_exists('preg_quote'))
				return true;
			else
				return false;
		}

		function nospamnxOptionPage()
		{	
			if (!current_user_can('manage_options'))
				wp_die(__('Sorry, but you have no permissions to change settings.','nospamnx'));
				
			//do we have to test referer-check
			if ($_GET['refcheck'] == 1)
			{
				//get the host name for referer check
				preg_match('@^(?:http://)?([^/]+)@i',$_SERVER['HTTP_REFERER'],$match);	
				
				//check if referer matches siteurl
				if (!empty($match[0]) && ($match[0] == get_option('home')))
					echo "<div id='message' class='updated fade'><p>".__('Referer-Check successfull! You may turn on Referer-Check.','nospamnx')."</p></div>";
				else
					echo "<div id='message' class='error'><p>".__('Referer-Check failed! The referer does not match WordPress option "siteurl".','nospamnx')."</p></div>";		
			}

			//do we have to update any settings?
			if ($_POST['save_settings'] == 1)
			{
				//which operation mode do we have to save
				switch($_POST['nospamnx_operate'])
				{
					case 'block':
						$this->nospamnx_operate = 'block';
					break;
					case 'mark':
						$this->nospamnx_operate = 'mark';
					break;
					case 'moderate':
						$this->nospamnx_operate = 'moderate';
					break;
					default:
						$this->nospamnx_operate = 'block';		
				}

				//do we have to check logged in users?
				($_POST['nospamnx_checkuser'] == 1) ? $this->nospamnx_checkuser = 1 : $this->nospamnx_checkuser = 0;
				
				//do we have to check logged in users?
				($_POST['nospamnx_checkreferer'] == 1) ? $this->nospamnx_checkreferer = 1 : $this->nospamnx_checkreferer = 0;			
				
				//save options and display success message
				$this->updateOptions();
				echo "<div id='message' class='updated fade'><p>".__('NoSpamNX settings saved successfully.','nospamnx')."</p></div>";			
			}
			else if ($_POST['reset_counter'] == 1)
			{
				//reset counter
				$this->nospamnx_count = 0;
				
				//save options and display success message
				$this->updateOptions();
				echo "<div id='message' class='updated fade'><p>".__('NoSpamNX Counter was reseted successfully.','nospamnx')."</p></div>";			
			}
			else if ($_POST['update_blacklist'] == 1)
			{
				//set blacklist to class var
				$this->nospamnx_blacklist = $_POST['blacklist'];
				
				//save options and display message
				$this->updateOptions();
				echo "<div id='message' class='updated fade'><p>".__('NoSpamNX settings saved successfully.','nospamnx')."</p></div>";
			}
			
			//set checked values for radio buttons
			($this->nospamnx_checkuser == 1) ? 		$checkuser = 'checked=checked' : $checkuser = '';
			($this->nospamnx_checkreferer == 1) ? 	$checkreferer = 'checked=checked' : $checkreferer = '';

			//set checked values for operating mode
			switch ($this->nospamnx_operate)
			{
				case 'block':
					$block = 'checked';
				break;
				case 'mark':
					$mark = 'checked';
				break;	
				case 'moderate':
					$moderate = 'checked';
				break;
				default:
					$block = 'checked';
			}

			//confirmation text for reseting the counter
			$confirm = __('Are you sure you want to reset the counter?','nospamnx');

			?>
							
			<div class="wrap">
				<div id="icon-options-general" class="icon32"></div>
				<h2><?php echo __('NoSpamNX Settings','nospamnx'); ?></h2>
			
				<div id="poststuff" class="ui-sortable">
					<div class="postbox opened">
						<h3><?php echo __('Statistic','nospamnx'); ?></h3>
						<div class="inside">
							<table class="form-table">
								<tr>
									<th scope="row" valign="top">
									<b><?php echo __('Stopped Spambots','nospamnx'); ?></b>	
									</th>
									<td><?php $this->nospamnxStats(); ?></td>
								</tr>
							</table>	
							<form action="options-general.php?page=nospamnx" method="post" onclick="return confirm('<?php echo $confirm; ?>');">
								<input type="hidden" value="1" name="reset_counter">			
								<p><input name="submit" class='button-primary' value="<?php echo __('Reset','nospamnx'); ?>" type="submit" /></p>
							</form>				
						</div>
					</div>
				</div>
			
				<div id="poststuff" class="ui-sortable">
					<div class="postbox opened">		
						<h3><?php echo __('Operating mode','nospamnx'); ?></h3>
						<div class="inside">							
								<p><?php echo __('By default all Spambots will be blocked. If you want to see what is blocked, select moderate or mark as spam. Catched Spambots will we be marked as Spam or put in moderation queue. Furthermore you can enable or disable if NoSpamNX should perfom its checks, if a user is logged in.','nospamnx'); ?></p>
								<form action="options-general.php?page=nospamnx" method="post">
								<table class="form-table">						
										<tr>
											<th scope="row" valign="top"><b><?php echo __('Mode','nospamnx'); ?></b></th>
											<td>					
											<input type="hidden" value="true" name="nospamnx_mode">
											<input type="radio" name="nospamnx_operate" <?php echo $block; ?> value="block"> <?php echo __('Block (recommended)','nospamnx'); ?>
											<br />
											<input type="radio" <?php echo $mark; ?> name="nospamnx_operate" value="mark"> <?php echo __('Mark as Spam','nospamnx'); ?>
											<br />
											<input type="radio" <?php echo $moderate; ?> name="nospamnx_operate" value="moderate"> <?php echo __('Moderate','nospamnx'); ?>
											</td>									
										</tr>
										<tr>
											<th scope="row" valign="top"><b><?php echo __('Check logged in User','nospamnx'); ?></b></th>
											<td valign="top"><input type="checkbox" name="nospamnx_checkuser" value="1" <?php echo $checkuser; ?> /><br/><?php echo __('If disabled, NoSpamNX will add no hidden fields or perform any checks on logged in users.','nospamnx'); ?></td>									
										</tr>
										<tr>
											<th scope="row" valign="top"><b><?php echo __('Check HTTP Referer','nospamnx'); ?></b></th>
											<td valign="top"><input type="checkbox" name="nospamnx_checkreferer" value="1"  <?php echo $checkreferer; ?> /><br /><?php echo __('If enabled, NoSpamNX checks if the referer of a comment matches your Blog-URL. Please check the correct functionality of this feature, using the following Link.','nospamnx'); ?> <a href="options-general.php?page=nospamnx&refcheck=1">Referer-Check</a></td>									
										</tr>									
								</table>
								<input type="hidden" value="1" name="save_settings">
								<p><input name="submit" class='button-primary' value="<?php echo __('Save','nospamnx'); ?>" type="submit" /></p>							
								</form>
						</div>							
					</div>
				</div>
				
				<div id="poststuff" class="ui-sortable">
					<div class="postbox opened">
						<h3><?php echo __('Blacklist','nospamnx'); ?></h3>
						<div class="inside">
							<p><?php echo __('The NoSpamNX Blacklist is comparable to the WordPress Blacklist (it is based on the same code). However, the NoSpamNX Blacklist enables you to block comments containing certain values, instead of putting them in moderation queue. Thus, this option only makes sense when using NoSpamNX in blocking mode. The NoSPamNX Blacklist checks the given values against the ip address, the author, the E-Mail Address and the URL of a comment. If a pattern mateches, the comment will be blocked. Like the WordPress Blacklist the NoSpamNX Blacklist uses substrings, so if you put "foo" in the list "foobar" will be blocked as well. Please use one value per line.','nospamnx'); ?></p>
							<form action="options-general.php?page=nospamnx" method="post">
							<table class="form-table">					    
								<tr>
									<td>
									<textarea name="blacklist" class="large-text code" cols="50" rows="10"><?php echo $this->nospamnx_blacklist; ?></textarea>
									</td>
								</tr>
							</table>	
							<input type="hidden" value="1" name="update_blacklist">
							<p><input name="submit" class='button-primary' value="<?php echo __('Save','nospamnx'); ?>" type="submit" /></p>
							</form>									
						</div>
					</div>
				</div>			
						
			</div>	
			<?php		
		}	
		
		function nospamnxStyle()
		{			
			$css = get_option( 'siteurl' ) . '/' . PLUGINDIR . '/nospamnx/nospamnx.css';		
			echo "<link rel=\"stylesheet\" href=\"$css\" type=\"text/css\" />\n";
		}
		
		function activate()
		{	
			//add nospamnx options
			$options = array(
				'nospamnx_names' 		=> $this->generateNames(),
				'nospamnx_count'		=> 0,
				'nospamnx_operate'		=> 'mark',
				'nospamnx_blacklist'	=> '',
				'nospamnx_checkuser'	=> 1,
				'nospamnx_checkreferer'	=> 0,
			);
			
			add_option('nospamnx', $options, '', 'yes');
		}	
		
		function deactivate()
		{
			delete_option('nospamnx');	
		}
		
		function loadOptions()
		{
			$options = get_option('nospamnx');
			
			$this->nospamnx_names 			= $options['nospamnx_names'];
			$this->nospamnx_count			= $options['nospamnx_count'];
			$this->nospamnx_operate			= $options['nospamnx_operate'];
			$this->nospamnx_checkuser		= $options['nospamnx_checkuser'];
			$this->nospamnx_blacklist		= $options['nospamnx_blacklist'];
			$this->nospamnx_checkreferer	= $options['nospamnx_checkreferer'];
		}
		
		function updateOptions()
		{
			$options = array(
				'nospamnx_names'		=> $this->nospamnx_names,
				'nospamnx_count'		=> $this->nospamnx_count,
				'nospamnx_operate'		=> $this->nospamnx_operate,
				'nospamnx_checkuser'	=> $this->nospamnx_checkuser,
				'nospamnx_blacklist'	=> $this->nospamnx_blacklist,
				'nospamnx_checkreferer'	=> $this->nospamnx_checkreferer
			);
			
			update_option('nospamnx', $options);
		}
		
		function nospamnxStats()
		{	
			$this->displayStats(true);		
		}	
		
		function displayStats($dashboard=false)
		{
			//get counter in local number format
			$counter = number_format_i18n($this->nospamnx_count);

			if ($dashboard == true)
				echo "<p>";
				
			echo '<a href="http://www.svenkubiak.de/nospamnx-en">NoSpamNX</a>';
			printf(__ngettext(
				" has stopped %s birdbrained Spambot since it last activation.",
				" has stopped %s birdbrained Spambots since it last activation.",
				$counter, 'nospamnx'), $counter);
			
			if ($dashboard == true)
				echo "</p>";			
		}
	}
	
	$nospamnx = new NoSpamNX();
}
?>