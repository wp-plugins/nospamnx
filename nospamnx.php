<?php
/*
Plugin Name: NoSpamNX
Plugin URI: http://www.svenkubiak.de/nospamnx-en
Description: To protect your Blog from automated spambots, which fill you comments with junk, this plugin adds additional formfields (hidden to human-users) to your comment form. These Fields are checked every time a new comment is posted. 
Version: 3.20
Author: Sven Kubiak
Author URI: http://www.svenkubiak.de

Copyright 2008-2010 Sven Kubiak

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
define('REQWP28', version_compare($wp_version, '2.8', '>='));
define('DEFAULTCSS', 'lotsensurrt');
define('UPDATEOPTIONS', false);

if (!class_exists('NoSpamNX'))
{
	Class NoSpamNX
	{	
		var $nospamnx_names;
		var $nospamnx_count;
		var $nospamnx_operate;
		var $nospamnx_blacklist;
		var $nospamnx_cssname;
		var $nospamnx_checkreferer;
		var $nospamnx_activated;
		var $nospamnx_dateformat;		
		var $nospamnx_home;
		var $nospamnx_siteurl;		
		var $nospamnx_version;
		
		function nospamnx() {		
			if (function_exists('load_plugin_textdomain'))
				load_plugin_textdomain('nospamnx', PLUGINDIR.'/nospamnx');
				
			if (REQWP28 != true) {
				add_action('admin_notices', array(&$this, 'wpVersionFail'));
				return;
			}

			//tell wp what to do when plugin is activated and uninstalled
			if (function_exists('register_activation_hook'))
				register_activation_hook(__FILE__, array(&$this, 'activate'));
			if (function_exists('register_uninstall_hook'))
				register_uninstall_hook(__FILE__, array(&$this, 'uninstall'));

			//load nospamnx options
			$this->getOptions();
			
			//add nospamnx wordpress actions	
			add_action('init', array(&$this, 'checkCommentForm'));		
			add_action('admin_menu', array(&$this, 'nospamnxAdminMenu'));		
			add_action('rightnow_end', array(&$this, 'nospamnxStats'));		
			add_action('comment_form', array(&$this, 'addHiddenFields'));	
		}

		function wpVersionFail() {
			$this->displayError(__('Your WordPress is to old. NoSpamNX requires at least WordPress 2.8!','nospamnx'));
		}
		
		function addHiddenFields() {	
			$nospamnx = $this->nospamnx_names;
			
			//output hidden fields to the comment form
			if (rand(1,2) == 1)
				echo '<p><input type="text" name="'.$nospamnx['nospamnx-1'].'" value="" style="display:none;" /><input type="text" name="'.$nospamnx['nospamnx-2'].'" value="'.$nospamnx['nospamnx-2-value'].'" style="display:none;" /></p>';
			else
				echo '<p><input type="text" name="'.$nospamnx['nospamnx-2'].'" value="'.$nospamnx['nospamnx-2-value'].'" style="display:none;" /><input type="text" name="'.$nospamnx['nospamnx-1'].'" value="" style="display:none;" /></p>';						
		}
		
		function checkCommentForm() {															
			//check if we are in wp-comments-post.php
			if (basename($_SERVER['PHP_SELF']) != 'wp-comments-post.php')
				return;
			else {		
				//perform blacklist check
				if ($this->blacklistCheck(
						trim($_POST['author']),
						trim($_POST['email']),
						trim($_POST['url']),
						$_POST['comment'],
						$_SERVER['REMOTE_ADDR']) == true)
					$this->birdbrained();
				
				//check if referer check is enabled and check referer
				if ($this->nospamnx_checkreferer == 1 && $this->checkReferer() == false)
					$this->birdbrained();
				
				//get hidden field names for check
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
		
		function birdbrained() {		
			//count spambot and save
			$this->nospamnx_count++;
			$this->setOptions();
			
			//check in which mode we are and block or mark as spam
			if ($this->nospamnx_operate == 'mark')
				add_filter('pre_comment_approved', create_function('$a', 'return \'spam\';'));
			else
				wp_die(__('Sorry, but your comment seems to be Spam.','nospamnx'));
		}	

		function checkReferer() {
			//check if referer isnt empty
			if (empty($_SERVER['HTTP_REFERER']))
				return false;

			//check if referer matches 'home' or 'siteurl' (http or https)
			if (preg_match("|https|",$_SERVER['HTTP_REFERER'])) {
				$homessl = preg_replace('/http/', 'https', $this->nospamnx_home);
				$siteurlssl = preg_replace('/http/', 'https', $this->nospamnx_siteurl);
				
				preg_match('@^(?:https://)?([^/]+)@i',$_SERVER['HTTP_REFERER'],$matchssl);
				if ($matchssl[0] != $homessl && $matchssl[0] != $siteurlssl)
					return false;	
			} else {
				preg_match('@^(?:http://)?([^/]+)@i',$_SERVER['HTTP_REFERER'],$match);
				if ($match[0] != $this->nospamnx_home && $match[0] != $this->nospamnx_siteurl) {
					return false;	
				}
			} 
			
			return true;
		}

		function blacklistCheck($author, $email, $url, $comment, $remoteip) {
			$blacklist = trim($this->nospamnx_blacklist);
			
			if ($blacklist == '' || empty($blacklist))
				return false;
		
			//split the values from each line
			$words = explode("\n", $blacklist);

			//loop through values and check if pattern matches
			foreach ((array)$words as $word ) {
				$word = trim($word);

				//skip through empty lines
				if (empty($word))
					continue;

				$word = preg_quote($word, '#');
				$pattern = "#$word#i";
			
				//check word against comment form values
				if (preg_match($pattern, $author)
					|| preg_match($pattern, $email)
					|| preg_match($pattern, $url)
					|| preg_match($pattern, $remoteip)
					|| preg_match($pattern, $comment))
				return true;
			}
			
			return false;
		}
		
		function generateNames() {		
			$nospamnx = array(
				'nospamnx-1'		=> $this->generateRandomString(),
				'nospamnx-2'		=> $this->generateRandomString(),
				'nospamnx-2-value'	=> $this->generateRandomString()		
			);

			return $nospamnx;
		}	
		
		function generateRandomString() {
			return substr(md5(uniqid(rand(), true)), rand(4, 23));
		}

		function nospamnxAdminMenu() {
			add_options_page('NoSpamNX', 'NoSpamNX', 8, 'nospamnx', array(&$this, 'nospamnxOptionPage'));	
		}
		
		function displayMessage($message) {
			echo "<div id='message' class='updated'><p>".$message."</p></div>";
		}
		
		function displayError($message) {
			echo "<div id='message' class='error'><p>".$message."</p></div>";
		}

		function nospamnxOptionPage() {	
			if (!current_user_can('manage_options'))
				wp_die(__('Sorry, but you have no permissions to change settings.','nospamnx'));
				
			//check referer if neccessary	
		    if ($_GET['refcheck'] == 1) {
				if ($this->checkReferer() == true)
					$this->displayMessage(__('Referer-Check successfull! You may turn on Referer-Check.','nospamnx'));
				else
					$this->displayError(__('Referer-Check failed! The referer does not match WordPress option "home" or "siteurl".','nospamnx'));
			}
			
			$nonce = $_REQUEST['_wpnonce'];
			//do we have to update general settings?
			if ($_POST['save_settings'] == 1 && $this->verifyNonce($nonce)) {
				//which operation mode do we have to save?
				switch($_POST['nospamnx_operate']) {
					case 'block':
						$this->nospamnx_operate = 'block';
					break;
					case 'mark':
						$this->nospamnx_operate = 'mark';
					break;
					default:
						$this->nospamnx_operate = 'block';		
				}	

				//do we have to check the http referer?
				($_POST['nospamnx_checkreferer'] == 1) ? $this->nospamnx_checkreferer = 1 : $this->nospamnx_checkreferer = 0;	
				
				//save options and display success message
				$this->setOptions();
				echo "<div id='message' class='updated fade'><p>".__('NoSpamNX settings were saved successfully.','nospamnx')."</p></div>";			
			}
			else if ($_POST['reset_counter'] == 1  && $this->verifyNonce($nonce)) {
				$this->nospamnx_count = 0;
				$this->setOptions();
				$this->displayMessage(__('NoSpamNX Counter was reseted successfully.','nospamnx'));			
			}
			else if ($_POST['update_blacklist'] == 1  && $this->verifyNonce($nonce)) {
				//order blacklist array
				$blacklist = explode("\n", $_POST['blacklist']);
				natcasesort($blacklist);
				$blacklist = implode("\n", $blacklist);
				
				$this->nospamnx_blacklist = trim($blacklist);
				$this->setOptions();
				$this->displayMessage(__('NoSpamNX Blacklist was updated successfully.','nospamnx'));
			}			
			
			//set checked values for radio buttons
			($this->nospamnx_checkreferer == 1)  ? 	$checkreferer = 'checked=checked' : $checkreferer = '';

			//set checked values for operating mode
			switch ($this->nospamnx_operate) {
				case 'block':
					$block = 'checked';
				break;
				case 'mark':
					$mark = 'checked';
				break;	
				default:
					$block = 'checked';
			}

			//set confirmation text for reseting the counter and nonce values
			$confirm = __('Are you sure you want to reset the counter?','nospamnx');		
			$nonce = wp_create_nonce('nospamnx-nonce');

			?>
							
			<div class="wrap">
				<div id="icon-options-general" class="icon32"></div>
				<h2><?php echo __('NoSpamNX Settings','nospamnx'); ?></h2>
			
				<div id="poststuff" class="ui-sortable meta-box-sortables">
					<div class="postbox opened">
						<h3><?php echo __('Statistic','nospamnx'); ?></h3>
						<div class="inside">
							<table class="form-table">
								<tr>
									<td width="500"><b><?php $this->nospamnxStats(); ?></b></td>
									<td>
									<script type="text/javascript">
									/* <![CDATA[ */
									    (function() {
									        var s = document.createElement('script'), t = document.getElementsByTagName('script')[0];
									        
									        s.type = 'text/javascript';
									        s.async = true;
									        s.src = 'http://api.flattr.com/js/0.6/load.js?mode=auto';
									        
									        t.parentNode.insertBefore(s, t);
									    })();
									/* ]]> */
									</script>
									<a class="FlattrButton" style="display:none;" href="http://www.svenkubiak.de/nospamnx/"></a>
									</td>
								</tr>
							</table>	
							<form action="options-general.php?page=nospamnx&_wpnonce=<?php echo $nonce ?>" method="post" onclick="return confirm('<?php echo $confirm; ?>');">
								<input type="hidden" value="1" name="reset_counter">			
								<p><input name="submit" class='button-primary' value="<?php echo __('Reset','nospamnx'); ?>" type="submit" /></p>
							</form>				
						</div>
					</div>
				</div>
			
				<div id="poststuff" class="ui-sortable meta-box-sortables">
					<div class="postbox opened">		
						<h3><?php echo __('Operating mode','nospamnx'); ?></h3>
						<div class="inside">							
								<p><?php echo __('By default all Spambots are marked as Spam, but the recommended Mode is "Block". If you want to see what might be blocked, select mark as spam.','nospamnx'); ?></p>
								<form action="options-general.php?page=nospamnx&_wpnonce=<?php echo $nonce ?>" method="post">
								<table class="form-table">						
										<tr>
											<th scope="row" valign="top"><b><?php echo __('Mode','nospamnx'); ?></b></th>
											<td>					
											<input type="hidden" value="true" name="nospamnx_mode">
											<input type="radio" name="nospamnx_operate" <?php echo $block; ?> value="block"> <?php echo __('Block (recommended)','nospamnx'); ?>
											<br />
											<input type="radio" <?php echo $mark; ?> name="nospamnx_operate" value="mark"> <?php echo __('Mark as Spam','nospamnx'); ?>
											</td>									
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
				
				<div id="poststuff" class="ui-sortable meta-box-sortables">
					<div class="postbox opened">
						<h3><?php echo __('Blacklist','nospamnx'); ?></h3>
						<div class="inside">
							<p><?php echo __('The NoSpamNX Blacklist is comparable to the WordPress Blacklist (it is based on the same code). However, the NoSpamNX Blacklist enables you to block comments containing certain values, instead of putting them in moderation queue. Thus, this option only makes sense when using NoSpamNX in blocking mode. The NoSpamNX Blacklist checks the given values against the ip address, the author, the E-Mail Address, the comment and the URL field of a comment. If a pattern matches, the comment will be blocked. Like the WordPress Blacklist the NoSpamNX Blacklist uses substrings, so if you put "foo" in the list "foobar" will be blocked as well. Please use one value per line.','nospamnx'); ?></p>
							<form action="options-general.php?page=nospamnx&_wpnonce=<?php echo $nonce ?>" method="post">
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
		
		function verifyNonce($nonce) {
			if (!wp_verify_nonce($nonce, 'nospamnx-nonce')) wp_die(__('Security-Check failed.','nospamnx'));
			
			return true;
		}
		
		function nospamnxStyle() {		
			$css = $this->nospamnx_siteurl . '/' . PLUGINDIR . '/nospamnx/nospamnx.css';		
			echo "<link rel=\"stylesheet\" href=\"$css\" type=\"text/css\" />\n";
		}
		
		function activate() {
			$options = array(
				'nospamnx_names' 		=> $this->generateNames(),
				'nospamnx_count'		=> 0,
				'nospamnx_operate'		=> 'mark',
				'nospamnx_checkreferer'	=> 0,	
				'nospamnx_cssname'		=> DEFAULTCSS,
				'nospamnx_activated'	=> time(),
				'nospamnx_dateformat'	=> get_option('date_format'),
				'nospamnx_home'			=> get_option('home'),
				'nospamnx_siteurl'		=> get_option('siteurl')								
			);
			
			if (UPDATEOPTIONS) {
				update_option('nospamnx-blacklist', get_option('nospamnx-blacklist'));
		    	update_option('nospamnx', $options);				
			} else {
				add_option('nospamnx-blacklist', get_option('nospamnx-blacklist'));
		    	add_option('nospamnx', $options);				
			}
		}	

		function uninstall() {
			delete_option('nospamnx');	
			delete_option('nospamnx-blacklist');	
		}
		
		function getOptions() {
			$options = get_option('nospamnx');
				
			$this->nospamnx_names 			= $options['nospamnx_names'];
			$this->nospamnx_count			= $options['nospamnx_count'];
			$this->nospamnx_operate			= $options['nospamnx_operate'];
			$this->nospamnx_cssname			= $options['nospamnx_cssname'];			
			$this->nospamnx_checkreferer	= $options['nospamnx_checkreferer'];
			$this->nospamnx_activated		= $options['nospamnx_activated'];
			$this->nospamnx_dateformat		= $options['nospamnx_dateformat'];
			$this->nospamnx_home			= $options['nospamnx_home'];
			$this->nospamnx_siteurl			= $options['nospamnx_siteurl'];			
			$this->nospamnx_version			= $options['nospamnx_version'];
			$this->nospamnx_blacklist		= get_option('nospamnx-blacklist');
		}
		
		function setOptions() {
			$options = array(
				'nospamnx_names'		=> $this->nospamnx_names,
				'nospamnx_count'		=> $this->nospamnx_count,
				'nospamnx_operate'		=> $this->nospamnx_operate,
				'nospamnx_cssname'		=> $this->nospamnx_cssname,		
				'nospamnx_checkreferer'	=> $this->nospamnx_checkreferer,
				'nospamnx_activated'	=> $this->nospamnx_activated,
				'nospamnx_dateformat'	=> $this->nospamnx_dateformat,
				'nospamnx_home'			=> $this->nospamnx_home,
				'nospamnx_siteurl'		=> $this->nospamnx_siteurl,			
			);
			
			update_option('nospamnx-blacklist', $this->nospamnx_blacklist);
		    update_option('nospamnx', $options);
		}
		
		function nospamnxStats() {	
			$this->displayStats(true);		
		}	
		
		function getStatsPerDay() {
			$secs = time() - $this->nospamnx_activated;
			$days = ($secs / (24*3600));

			($days <= 1) ? $days = 1 : $days = floor($days);

			return ceil($this->nospamnx_count / $days);
		}
		
		function displayStats($dashboard=false) {
			if ($dashboard) {echo "<p>";}

			if ($this->nospamnx_count <= 0)
				echo __("NoSpamNX has stopped no birdbrained Spambots yet.", 'nospamnx');
			else {
					printf(__ngettext(
						"Since its last activation on %s %s has stopped %s birdbrained Spambot (%s per Day).",
						"Since its last activation on %s %s has stopped %s birdbrained Spambots (%s per Day).",
						$this->nospamnx_count, 'nospamnx'),
						date_i18n($this->nospamnx_dateformat, $this->nospamnx_activated),
						'<a href="http://www.svenkubiak.de/nospamnx">NoSpamNX</a>',
						$this->nospamnx_count,
						$this->getStatsPerDay()
					);
			}
			
			if ($dashboard) {echo "</p>";}			
		}
	}
	$nospamnx = new NoSpamNX();
}
?>