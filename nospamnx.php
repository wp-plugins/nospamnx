<?php
/*
Plugin Name: NoSpamNX
Plugin URI: http://www.svenkubiak.de/nospamnx-en
Description: To protect your Blog from automated spambots, which fill you comments with junk, this plugin adds additional formfields to your comment template, which are checked every time a comment is posted. NOTE: If the hidden fields are displayed, make sure your theme does load wp_head()! 
Version: 1.7.1
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
define('NOSPAMNXREQWP26', version_compare($wp_version, '2.6', '>='));

Class NoSpamNX
{	
	var $nospamnx_names;
	var $nospamnx_checkip;
	var $nospamnx_count;
	var $nospamnx_operate;
	var $nospamnx_blocktime;
	var $nospamnx_checkuser;
	var $nospamnx_blockips;
	var $nospamnx_exclude;
	var $disabled = false;
	
	function nospamnx()
	{
		//load language strings
		if (function_exists('load_plugin_textdomain'))
			load_plugin_textdomain('nospamnx', PLUGINDIR.'/nospamnx');
			
		//check if wordpress is at least 2.6
		if (NOSPAMNXREQWP26 != true){
			add_action('admin_notices', array(&$this, 'wpVersionNotice'));
			return;
		}
		
		//check if required PHP functons are available
		if ($this->preFlight() === false)
			add_action('admin_notices', array(&$this, 'phpFail'));
		
		//add nospamnx wordpress actions	
		add_action('init', array(&$this, 'checkCommentForm'));		
		add_action('template_redirect', array(&$this, 'modifyTemplate'));	
		add_action('wp_head', array(&$this, 'nospamnxStyle'));
		add_action('admin_menu', array(&$this, 'nospamnxAdminMenu'));		
		add_action('rightnow_end', array(&$this, 'nospamnxStats'));
		
		//tell wp what to do when plugin is activated and deactivated
		register_activation_hook(__FILE__, array(&$this, 'activate'));
		register_deactivation_hook(__FILE__, array(&$this, 'deactivate'));		

		//load nospamnx options
		$this->loadOptions();
	}

	function wpVersionNotice()
	{
		echo "<div id='message' class='error fade'><p>".__('Your WordPress is to old. NoSpamNX requires at least WordPress 2.6!','nospamnx')."</p></div>";
	}

	function phpFail()
	{
		echo "<div id='message' class='error fade'><p>".__('NoSpamNX is currently disabled! Some required PHP functions are not available. See Settings -> NoSpamNX -> Information for more details.','nospamnx')."</p></div>";
	}	
	
	function modifyTemplate()
	{
		//check if required PHP functons are available otherwise disable plugin with returing
		if ($this->preFlight() === false)
			return;

		//get ids of pages and posts to exclude
		$exclude = explode(",",$this->nospamnx_exclude);

		//check if we are in a page that we should exclude
		for ($i=0; $i < count($exclude); $i++){
			if (!empty($exclude[$i]) && is_single($exclude[$i])){
				$this->disabled = true;
				return;
			}		
			else if (!empty($exclude[$i]) && is_page($exclude[$i])){
				$this->disabled = true;
				return;		
			}
		}
		
		//check if we only display the page/post
		if (is_single() || is_page())
			//start output buffer and add callback function for comment template
			ob_start(array(&$this, 'addHiddenFields'));
		else if (is_comments_popup())
			//start output buffer and add callback function for comment template
			ob_start(array(&$this, 'addHiddenFieldsPopup'));
	}

	function addHiddenFields($template)
	{	
		//get the formfields names and value from wp options
		$nospamnx = $this->nospamnx_names;
		
		//replace the textfields within the ouput buffer
		if (rand(1,2) == 1)
			return str_replace ('</textarea>', '</textarea><input type="text" name="'.$nospamnx['nospamnx-1'].'" value="" class="locktross" /><input type="text" name="'.$nospamnx['nospamnx-2'].'" value="'.$nospamnx['nospamnx-2-value'].'" class="locktross" />', $template);
		else
			return str_replace ('</textarea>', '</textarea><input type="text" name="'.$nospamnx['nospamnx-2'].'" value="'.$nospamnx['nospamnx-2-value'].'" class="locktross" /><input type="text" name="'.$nospamnx['nospamnx-1'].'" value="" class="locktross" />', $template);
	}
	
	function addHiddenFieldsPopup($template)
	{	
		//get the formfields names and value from wp options
		$nospamnx = $this->nospamnx_names;
		
		//replace the textfields within the ouput buffer
		if (rand(1,2) == 1)
			return str_replace ('</textarea>', '</textarea><input type="text" name="'.$nospamnx['nospamnx-1'].'" value="" style="display:none" /><input type="text" name="'.$nospamnx['nospamnx-2'].'" value="'.$nospamnx['nospamnx-2-value'].'" style="display:none" />', $template);
		else
			return str_replace ('</textarea>', '</textarea><input type="text" name="'.$nospamnx['nospamnx-2'].'" value="'.$nospamnx['nospamnx-2-value'].'" style="display:none" /><input type="text" name="'.$nospamnx['nospamnx-1'].'" value="" style="display:none" />', $template);
	}	
	
	function checkCommentForm()
	{													
		//check if logged in user does not require check
		if ($this->nospamnx_checkuser == 0 && is_user_logged_in())
			return;
		else if ($this->disabled == true)
			return;
		else if (basename($_SERVER['PHP_SELF']) != 'wp-comments-post.php')
			return;
		else
		{		
			//if ip lock is enabled, check if we have the spambot already catched
			if ($this->nospamnx_checkip == 1 && $this->checkIp() == true)
				$this->birdbrained(true);
			
			//get current formfield names from wp options
			$nospamnx = $this->nospamnx_names;

			//check if first hidden field is in $_POST data
			if (!array_key_exists($nospamnx['nospamnx-1'],$_POST))
				$this->birdbrained(false);
			//check if first hidden field is empty
			else if ($_POST[$nospamnx['nospamnx-1']] != "")
				$this->birdbrained(false);
			//check if second hidden field is in $_POST data
			else if (!array_key_exists($nospamnx['nospamnx-2'],$_POST))
				$this->birdbrained(false);
			//check if the value of the second formfield matches stored value
			else if ($_POST[$nospamnx['nospamnx-2']] != $nospamnx['nospamnx-2-value'])
				$this->birdbrained(false);	
		}
	}	
		
	function birdbrained($catched)
	{
		//lets cleanup some old blocked ips, the spambot has enoguh time
		($this->nospamnx_checkip == 1) ? $this->cleanup() : false;
		
		//send a report about the spambot
		$this->report();
		
		//check if we already catched the spambot (based on ip adress) or not
		if ($catched == false)
		{
			//save the spambots ip if option is enabled
			if ($this->nospamnx_checkip == 1 && $this->checkIp() === false)
				$this->saveIp();
			
			//count spambot
			$this->nospamnx_count++;
			$this->updateOptions();			
		}
		else
			wp_die(__('Sorry, but it seems you are a Spambot.','nospamnx'));
		
		//check in which mode we are and block, mark as spam or put in moderation queue
		if ($this->nospamnx_operate == 'mark')
			add_filter('pre_comment_approved', create_function('$a', 'return \'spam\';'));
		else if ($this->nospamnx_operate == 'moderate')
			add_filter('pre_comment_approved', create_function('$a', 'return \'0\';'));
		else
			wp_die(__('Sorry, but it seems you are a Spambot.','nospamnx'));
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
	
	function saveIp()
	{	
		//get the curren time
		$currentime = time();
		
		//get current list of blocked ips
		$blockips = $this->nospamnx_blockips;
		
		//do we have more than 100 entries in our databse?
		if (count($blockips) >= 100)
			return;		
		
		//set the time the ip will be blocked
		switch ($this->nospamnx_blocktime)
		{
			case 0:
				$until = 2147483647;
			break;
			case 1:
				$until = $currentime + 3600;
			break;
			case 24:
				$until = $currentime + 86400;
			break;
			default:
				$until = $currentime + 86400;
		}
		
		//lets generate a new entry
		$newentry = array(
			'remoteip' 	=> $_SERVER['REMOTE_ADDR'],
			'until' 	=> $until
		);
		
		//add the new entry to our list
		$blockips [] = $newentry;
		
		//now save the new entry
		$this->nospamnx_blockips = $blockips;
		$this->updateOptions();
 	}

 	/*
 	 * Returns true if we already catched the spambot
 	 * Returns false if we didnt catched the spambot yet
 	 */
 	
	function checkIp()
	{			
		//get current list of blocked ips				
		$blockips = $this->nospamnx_blockips;
			
		//get the current time
		$currenttime = time();
		
		//loop through all entries and check if the entry is already in our list
		for ($i = 0; $i <= count($blockips); $i++)
		{
			//check ip against entries
			if ($_SERVER['REMOTE_ADDR'] == $blockips [$i]['remoteip'])
			{
				//found the entry, but do we still block it?
				if ($blockips [$i]['until'] > $currenttime)
					return true;
			}
		}
		
		return false;
	}
	
	function preFlight()
	{
		if (function_exists('ob_start') && function_exists('str_replace'))
			return true;
		else
			return false;
	}
	
	function cleanup()
	{
		//get current list of blocked ips				
		$blockips = $this->nospamnx_blockips;
		
		//get the current time
		$currenttime = time();
		
		//loop through all entries and check if the time is up
		for ($i = 0; $i <= count($blockips); $i++)
		{
			//do we still have to block the ip?
			if ($blockips [$i]['until'] < $currenttime)
				//delete ip adress from entries
				unset($blockips [$i]);
		}
		
		//store the entries back to wp options
		$this->nospamnx_blockips = $blockips;
		$this->updateOptions();
	}
	
	function nospamnxOptionPage()
	{	
		if (!current_user_can('manage_options'))
			wp_die(__('Sorry, but you have no permissions to change settings.','nospamnx'));
			
		//set some variables for default form values
		$ipyes 	= '';
		$ipno 	= '';
		$blocktime0 = '';
		$blocktime1 = '';
		$blocktime24 = '';	
		$block = '';
		$mark = '';
		$moderate = '';	

		//do we have to update any settings?
		if ($_POST['save_settings'])
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
			
			if (!empty($_POST['nospamnx_blocktime']))
			{
				//how long will the ips be blocked?
				switch($_POST['nospamnx_blocktime'])
				{
					case 0:
						$this->nospamnx_blocktime = 0;
					break;
					case 1:
						$this->nospamnx_blocktime = 1;
					break;
					case 24;
						$this->nospamnx_blocktime = 24;
					break;
					default:				 	
						$this->nospamnx_blocktime = 1;
				}
			}

			//do we have to check logged in users?
			($_POST['nospamnx_checkuser'] == 1) ? $this->nospamnx_checkuser = 1 : $this->nospamnx_checkuser = 0;

			//do we have to save options for checking ips?
			($_POST['nospamnx_ip'] == 1) ? $this->nospamnx_checkip = 1 : $this->nospamnx_checkip = 0;	
			
			//save options and display message
			$this->updateOptions();
			echo "<div id='message' class='updated fade'><p>".__('NoSpamNX settings were saved successfully.','nospamnx')."</p></div>";			
		}
		else if ($_POST['reset_counter'])
		{
			//reset counter
			$this->nospamnx_count = 0;
			
			//save options and display message
			$this->updateOptions();
			echo "<div id='message' class='updated fade'><p>".__('NoSpamNX Counter was reseted successfully.','nospamnx')."</p></div>";			
		}
		else if ($_POST['update_exclude'])
		{
			//get id from textarea
			$this->nospamnx_exclude = $_POST['exclude_ids'];
			
			//save options and display message
			$this->updateOptions();
			echo "<div id='message' class='updated fade'><p>".__('NoSpamNX settings were saved successfully.','nospamnx')."</p></div>";
		}
		
		//set checked values for radio buttons
		($this->nospamnx_checkip == 1) ? $ipyes = 'checked' : $ipno = 'checked';	
		($this->nospamnx_checkuser == 1) ? $useryes = 'checked' : $userno = 'checked';
		
		//set checked values for block time
		switch ($this->nospamnx_blocktime)
		{
			case 0:
				$blocktime0 = 'checked';
			break;
			case 1:
				$blocktime1 = 'checked';
			break;	
			case 24:
				$blocktime24 = 'checked';
			break;
		}	

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
		}
		
		//get the entries from stored ips
		$entries = count($this->nospamnx_blockips);
		
		//confirmation text for reseting the counter
		$confirm 	=	__('Are you sure you want to reset the counter?','nospamnx');	
			
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
							<input type="hidden" value="true" name="reset_counter">			
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
										<td>
										<input type="radio" name="nospamnx_checkuser" <?php echo $useryes; ?> value="1"> <?php echo __('Yes','nospamnx'); ?> <input type="radio" <?php echo $userno; ?> name="nospamnx_checkuser" value="0"> <?php echo __('No','nospamnx'); ?>	
										</td>									
									</tr>
							</table>
							<h3><?php echo __('IP-Address Lockout','nospamnx'); ?></h3>
							<p><?php echo __('You can block an IP-Address of a catched Spambot for 1 hour, 24 hours or indefinitely. This IP-Address can not post any comments during this time.','nospamnx'); ?></p>
						    <table class="form-table">						
						    		<tr>
										<th scope="row" valign="top"><b><?php echo __('Block IP-Address','nospamnx'); ?></b></th>
										<td>
										<input type="radio" name="nospamnx_ip" <?php echo $ipyes; ?> value="1"> <?php echo __('Yes','nospamnx'); ?> <input type="radio" <?php echo $ipno; ?> name="nospamnx_ip" value="0"> <?php echo __('No','nospamnx'); ?>
										</td>									
									</tr>
									
									<?php if ($this->nospamnx_checkip == 1){ ?>
									
						    		<tr>
										<th scope="row" valign="top"><b><?php echo __('Block time','nospamnx'); ?></b></th>
										<td>
										<input type="radio" name="nospamnx_blocktime" <?php echo $blocktime1; ?> value="1"> <?php echo __('1 hour','nospamnx'); ?>
										<br />
										<input type="radio" name="nospamnx_blocktime" <?php echo $blocktime24; ?> value="24"> <?php echo __('24 hours','nospamnx'); ?>
										<br />
										<input type="radio" name="nospamnx_blocktime" <?php echo $blocktime0; ?> value="0"> <?php echo __('Indefinitely','nospamnx'); ?>
										</td>									
									</tr>	
						    		<tr>
										<th scope="row" valign="top"><b><?php echo __('Entries','nospamnx'); ?></b></th>
										<td>
										<?php printf(__ngettext(
											" Currently %s out of 100 entries is stored.",
											" Currently %s out of 100 entries are stored.",
											$entries, 'nospamnx'), $entries);
										?>	
										</td>									
									</tr>
									
									<?php } ?>	
											
							</table>
							<input type="hidden" value="1" name="save_settings">
							<p><input name="submit" class='button-primary' value="<?php echo __('Save','nospamnx'); ?>" type="submit" /></p>
							</form>		
							
					</div>							
				</div>
			</div>
			
			<div id="poststuff" class="ui-sortable">
				<div class="postbox opened">
					<h3><?php echo __('Exclude page/post','nospamnx'); ?></h3>
					<div class="inside">
						<p><?php echo __('If you have Problems with NoSpamNX on a certain page/post you can exclude this page/post from NoSpamNX. Just enter the ID(s) of the page/post (use comma-separated values like 4,8,15,16,23,42)','nospamnx'); ?></p>
						<form action="options-general.php?page=nospamnx" method="post">
					    <table class="form-table">
					    	<tr>
								<th scope="row" valign="top">
								<b><?php echo __('IDs','nospamnx'); ?></b>	
								</th>
								<td>
								<textarea name="exclude_ids" cols="40" rows="4"><?php if (!empty($this->nospamnx_exclude)){echo $this->nospamnx_exclude;} ?></textarea>
								</td>
							</tr>
						</table>	
						<input type="hidden" value="1" name="update_exclude">
						<p><input name="submit" class='button-primary' value="<?php echo __('Save','nospamnx'); ?>" type="submit" /></p>
						</form>									
					</div>
				</div>
			</div>			
			
			<div id="poststuff" class="ui-sortable">
				<div class="postbox opened">
					<h3><?php echo __('Debug','nospamnx'); ?></h3>
					<div class="inside">
					    <table class="form-table">
					    	<tr>
								<th scope="row" valign="top">
								<b><?php echo __('PHP-Info','nospamnx'); ?></b>	
								</th>
								<td>
								<?php 
								
								$debug  = "PHP: ".phpversion()."\n";
								(function_exists('ob_start')) ? $debug .= "ob_start: true\n" : $debug .= "ob_start: false\n";
								(function_exists('str_replace')) ? $debug .= "str_replace: true\n" : $debug .= "str_replace: false\n";
								
								?>
								<textarea cols="40" rows="4"><?php echo $debug  ?></textarea>
								</td>
							</tr>
					    	<tr>
								<th scope="row" valign="top">
								<b><?php echo __('Support','nospamnx'); ?></b>	
								</th>
								<td><?php printf(__('Problems with NoSpamNX? Feel free to contact me via E-Mail (%s) or check out the comments at the Plugin homepage. Please add the debug information to your request.','nospamnx'),'nospamnx (at) svenkubiak.de'); ?>
								<br/>
								<a href="http://www.svenkubiak.de/nospamnx#comments">Plugin Homepage (DE)</a>
								<br/>
								<a href="http://www.svenkubiak.de/nospamnx-en#comments">Plugin Homepage (EN)</a>							
								</td>
							</tr>
							<tr>
								<th scope="row" valign="top">
								<b><?php echo __('Donation','nospamnx'); ?></b>	
								</th>
								<td><?php echo __('Developing, maintaining and supporting this plugin requires time. You can support further development with a donation. Thanks!','nospamnx'); ?>
								<br />
								<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
								<input type="hidden" name="cmd" value="_donations"/>
								<input type="hidden" name="business" value="sk@svenkubiak.de"/>
								<input type="hidden" name="lc" value="US"/>
								<input type="hidden" name="item_name" value="NoSpamNX (svenkubiak.de)"/>
								<input type="hidden" name="currency_code" value="EUR"/>
								<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHostedGuest"/>
								<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="Donate using PayPal"/>
								<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1"/>
								</form>
								</td>
							</tr>	
						</table>			
					</div>
				</div>
			</div>
		
	    </div>	
	    
	    <?php		
	}	
	
	function nospamnxStyle()
	{	
		//build url to nospamnx css file
		$nospamnxcssurl = (get_bloginfo('wpurl')."/".PLUGINDIR."/nospamnx/");
		
		//display link to nospamnx css in wordpress header
		echo "<!-- nospamnxcss -->\n";
		echo '<link rel="stylesheet" href="'.$nospamnxcssurl.'nospamnx.css" type="text/css" media="screen" />';
		echo "\n<!-- /nospamnxcss -->\n";
	}
	
	function activate()
	{	
		//delete old options from version 1.0
		delete_option('nospamnx_count_default');
		delete_option('nospamnx_count_emptyblank'); 	
		delete_option('nospamnx_count_ip'); 
		delete_option('nospamnx_strict'); 	
		delete_option('nospamnx_count_noblank'); 	
		delete_option('nospamnx_names'); 	
		delete_option('nospamnx_checkip'); 	
		delete_option('nospamnx_count'); 	
		delete_option('nospamnx_operate'); 	
		delete_option('nospamnx_blocktime'); 	
		delete_option('nospamnx_checkuser'); 	
		delete_option('nospamnx_blocked_ip');		
		
		//add nospamnx options
		$options = array(
			'nospamnx_names' 		=> $this->generateNames(),
			'nospamnx_checkip'		=> 0,
			'nospamnx_count'		=> 0,
			'nospamnx_operate'		=> 'block',
			'nospamnx_blocktime'	=> 1,
			'nospamnx_checkuser'	=> 1,
			'nospamnx_blockips'		=> array(),
			'nospamnx_exclude'		=> ''
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
		
		$this->nospamnx_names 		= $options['nospamnx_names'];
		$this->nospamnx_checkip		= $options['nospamnx_checkip'];
		$this->nospamnx_count		= $options['nospamnx_count'];
		$this->nospamnx_operate		= $options['nospamnx_operate'];
		$this->nospamnx_blocktime	= $options['nospamnx_blocktime'];
		$this->nospamnx_checkuser	= $options['nospamnx_checkuser'];
		$this->nospamnx_blockips	= $options['nospamnx_blockips'];
		$this->nospamnx_exclude		= $options['nospamnx_exclude'];
	}
	
	function updateOptions()
	{
		$options = array(
			'nospamnx_names'		=> $this->nospamnx_names,
			'nospamnx_checkip'		=> $this->nospamnx_checkip,
			'nospamnx_count'		=> $this->nospamnx_count,
			'nospamnx_operate'		=> $this->nospamnx_operate,
			'nospamnx_blocktime'	=> $this->nospamnx_blocktime,
			'nospamnx_checkuser'	=> $this->nospamnx_checkuser,
			'nospamnx_blockips'		=> $this->nospamnx_blockips,
			'nospamnx_exclude'		=> $this->nospamnx_exclude	
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

		if ($dashboard === true){echo "<p>";}
		echo '<a href="http://www.svenkubiak.de/nospamnx-en">NoSpamNX</a>';
		printf(__ngettext(
			" has stopped %s birdbrained Spambot since it last activation.",
			" has stopped %s birdbrained Spambots since it last activation.",
			$counter, 'nospamnx'), $counter);
		if ($dashboard === true){echo "</p>";}			
	}
	
	function report()
	{
		if (!function_exists('curl_init'))
			return;
		
		$url = "https://sslsites.de/nospamnx.svenkubiak.de/report.php";
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL,$url);

		curl_setopt($ch, CURLOPT_POST, 3);
		
		$token 		= "token=9Gl73TneIAet4R8dMpuuJ79qNpzAwXan";
		$remoteip 	= "&remoteip=".$_SERVER['REMOTE_ADDR'];
		$agent 		= "&remoteagent=".md5(strtolower(trim($_SERVER['HTTP_USER_AGENT'])));

		curl_setopt($ch, CURLOPT_POSTFIELDS,$token.$remoteip.$agent);

		curl_exec ($ch);
		curl_close ($ch);
	}
}
//initalize class
if (class_exists('NoSpamNX'))
	$nospamnx = new NoSpamNX();		
?>