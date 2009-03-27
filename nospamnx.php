<?php
/*
Plugin Name: NoSpamNX
Plugin URI: http://www.svenkubiak.de/nospamnx
Description: To protect your Blog from automated spambots, which fill you comments with junk, this plugin adds automaticly additional formfields (hidden to a real user) to your comment template, which are checked every time a comment is posted. 
Version: 1.0
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
define('NOSPAMNXISWP26', version_compare($wp_version, '2.6', '>='));

Class NoSpamNX
{	
	function nospamnx()
	{
		//load language
		if (function_exists('load_plugin_textdomain'))
			load_plugin_textdomain('nospamnx', PLUGINDIR.'/nospamnx');
			
		//check if wordpress is at least 2.6
		if (NOSPAMNXISWP26 != true){
			add_action('admin_notices', array(&$this, 'wpOld'));
			return;
		}
		
		//add wp actions	
		add_action('init', array(&$this, 'checkCommentForm'));		
		add_action('template_redirect', array(&$this, 'modifyTemplate'));
		add_action('activate_nospamnx/nospamnx.php', array(&$this, 'activate'));			
		add_action('deactivate_nospamnx/nospamnx.php', array(&$this, 'deactivate'));
		add_action('wp_head', array(&$this, 'nospamnxStyle'));
		add_action('admin_menu', array(&$this, 'nospamnxAdminMenu'));		
		add_action('rightnow_end', array(&$this, 'nospamnxStats'));
	}

	function wpOld()
	{
		echo "<div id='message' class='error fade'><p>".__('Your WordPress is to old. NoSpamNX requires at least WordPress 2.6!','nospamnx')."</p></div>";
	}	
	
	function modifyTemplate()
	{
		//check if we only display the page/post
		if (is_singular())
		{
			//get the formfields names and value from wp options
			$nospamnx = unserialize(get_option('nospamnx'));
			
			//output hidden fields by modifing browser output
			if (rand(1,2) == 1)
			{
				ob_start(
					create_function(
						'$template',
						'return preg_replace("#</textarea>#", "</textarea>\n<input type=\"text\" name=\"'.$nospamnx['nospamnx-1'].'\" value=\"\" class=\"locktross\" />\n<input type=\"text\" name=\"'.$nospamnx['nospamnx-2'].'\" value=\"'.$nospamnx['nospamnx-2-value'].'\" class=\"locktross\" />", $template);'
					)
				);		
			}
			else
			{
				ob_start(
					create_function(
						'$template',
						'return preg_replace("#</textarea>#", "</textarea>\n<input type=\"text\" name=\"'.$nospamnx['nospamnx-2'].'\" value=\"'.$nospamnx['nospamnx-2-value'].'\" class=\"locktross\" />\n<input type=\"text\" name=\"'.$nospamnx['nospamnx-1'].'\" value=\"\" class=\"locktross\" />", $template);'
					)
				);
			}
		}
	}

	function checkCommentForm()
	{													
		//check if logged in user does not require check
		if (get_option('nospamnx_checkuser') == 1 && is_user_logged_in())
			return true;
		else
		{		
			//check if we are in wp-comments-post.php
			if (basename($_SERVER['PHP_SELF']) == 'wp-comments-post.php')
			{
				//if ip lock is enabled, check if we have the spambot already catched
				if (get_option('nospamnx_checkip') == 1 && $this->checkIp() === true)
					$this->birdbrained(true);
				
				//get current formfield names from wp options
				$nospamnx = get_option('nospamnx');
				(!is_array($nospamnx)) ? $nospamnx = unserialize($nospamnx) : false;

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
	}	
		
	function birdbrained($catched)
	{
		//lets cleanup some old blocked ips, the spambot has enoguh time
		$this->cleanup();
		
		//check if we already catched the spambot (based on ip adress) or not
		if ($catched == false)
		{
			//save the spambots ip if option is enabled
			if (get_option('nospamnx_checkip') == 1 && $this->checkIp() === false)
				$this->saveIp();
			
			//count spambot
			update_option('nospamnx_count', get_option('nospamnx_count') + 1);			
		}
		else
			wp_die(__('Sorry, but it seems you are a Spambot.','nospamnx'));
		
		//get the current operatin mode from wp options
		$mode = get_option('nospamnx_operate');
		 	
		//check in which mode we are and block, mark as spam or put in moderation queue
		if ($mode == 'mark')
			add_filter('pre_comment_approved', create_function('$a', 'return \'spam\';'));
		else if ($mode == 'moderate')
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

		return serialize($nospamnx);
	}	

	function nospamnxAdminMenu()
	{
		add_options_page('NoSpamNX', 'NoSpamNX', 8, 'nospamnx', array(&$this, 'nospamnxOptionPage'));	
	}
	
	function saveIp()
	{	
		//ip will be blocked occording to blocktime settings
		$currentime = time();
		
		//get current list of blocked ips
		$blockedip = unserialize(get_option('nospamnx_blocked_ip'));
		
		//do we have more than 100 entries in our databse?
		if (count($blockedip) >= 100)
			return;		
		
		//set the time the ip will be blocked
		switch (get_option('nospamnx_blocktime'))
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
		
		//add the new entry to out list
		$blockedip [] = $newentry;
		
		//now save the new entry
		update_option('nospamnx_blocked_ip', serialize($blockedip));
 	}

 	/*
 	 * Returns true if we already catched the spambot
 	 * Returns false if we didnt catched the spambot yet
 	 */
 	
	function checkIp()
	{			
		//get current list of blocked ips				
		$blockedips = unserialize(get_option('nospamnx_blocked_ip'));
		
		//get the current time
		$currenttime = time();
		
		//loop through all entries and check if the entry is already in our list
		for ($i = 0; $i <= count($blockedips); $i++)
		{
			//check agent and ip against database
			if ($_SERVER['REMOTE_ADDR'] == $blockedips [$i]['remoteip'])
			{
				//found the entry, but do we still block it?
				if ($blockedips [$i]['until'] > $currenttime)
					return true;
			}
		}
		
		return false;
	}
	
	function cleanup()
	{
		//get current list of blocked ips				
		$blockedips = unserialize(get_option('nospamnx_blocked_ip'));
		
		//get the current time
		$currenttime = time();
		
		//loop through all entries and check if the time is up
		for ($i = 0; $i <= count($blockedips); $i++)
		{
			//do we still have to block the ip?
			if ($blockedips [$i]['until'] < $currenttime)
				//delete ip adress from entries
				unset($blockedips [$i]);
		}
		
		//store the entries back to wp options
		update_option('nospamnx_blocked_ip', serialize($blockedips));
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
					update_option('nospamnx_operate', 'block');
				break;
				case 'mark':
					update_option('nospamnx_operate', 'mark');
				break;
				case 'moderate':
					update_option('nospamnx_operate', 'moderate');
				break;
				default:
					update_option('nospamnx_operate', 'block');		
			}
			
			//do we have to check logged in users?
			($_POST['nospamnx_checkuser'] == 1) ? update_option('nospamnx_checkuser',1) : update_option('nospamnx_checkuser',0);

			//do we have to save options for checking ips?
			if ($_POST['nospamnx_ip'] == 1)
				update_option('nospamnx_checkip', 1);					
			else if($_POST['nospamnx_ip'] == 0)
				update_option('nospamnx_checkip', 0);				
			
			//how long will the ips be blocked?
			switch($_POST['nospamnx_blocktime'])
			{
				case 0:
					update_option('nospamnx_blocktime', 0);
				break;
				case 1:
					update_option('nospamnx_blocktime', 1);
				break;
				case 24;
					update_option('nospamnx_blocktime', 24);
				break;
				default:				 	
					update_option('nospamnx_blocktime', 0);
			}
			echo "<div id='message' class='updated fade'><p>".__('NoSpamNX settings were saved successfully.','nospamnx')."</p></div>";			
		}
		else if ($_POST['reset_counter'])
		{
			update_option('nospamnx_count', 0);
			echo "<div id='message' class='updated fade'><p>".__('NoSpamNX Counter was reseted successfully.','nospamnx')."</p></div>";			
		}
		
		//set checked values for radio buttons
		(get_option('nospamnx_checkip') == 1) ? $ipyes = 'checked' : $ipno = 'checked';	
		(get_option('nospamnx_checkuser') == 1) ? $useryes = 'checked' : $userno = 'checked';
		
		//set checked values for block time
		switch (get_option('nospamnx_blocktime'))
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
		switch (get_option('nospamnx_operate'))
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
		$entries = count(unserialize(get_option('nospamnx_blocked_ip'))) - 1;
		
		//confirmation text for reseting the counter anf formfield names
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
										<input type="radio" <?php echo $mark; ?> name="nospamnx_operate" value="mark"> <?php echo __('Mark as Spam (Requires Akismet or similar plugin)','nospamnx'); ?>
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
							<h3><?php echo __('IP Lock','nospamnx'); ?></h3>
							<p><?php echo __('You can lock IP-Address of a catched Spambot for 1 hour, 24 hours or indefinitely. This IP-Address can not post any comments during this time.','nospamnx'); ?></p>
						    <table class="form-table">						
						    		<tr>
										<th scope="row" valign="top"><b><?php echo __('Save IP Adress','nospamnx'); ?></b></th>
										<td>
										<input type="radio" name="nospamnx_ip" <?php echo $ipyes; ?> value="1"> <?php echo __('Yes','nospamnx'); ?> <input type="radio" <?php echo $ipno; ?> name="nospamnx_ip" value="0"> <?php echo __('No','nospamnx'); ?>
										</td>									
									</tr>
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
							</table>
							<input type="hidden" value="1" name="save_settings">
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
		//build url to nospamnx css file
		$nospamnxcssurl = (get_bloginfo('wpurl')."/".PLUGINDIR."/nospamnx/");
		
		//display link to nospamnx css in wordpress header
		echo "<!-- nospamnxcss -->\n";
		echo '<link rel="stylesheet" href="'.$nospamnxcssurl.'nospamnx.css" type="text/css" media="screen" />';
		echo "\n<!-- /nospamnxcss -->\n";
	}
	
	function activate()
	{
		//add wp options
		add_option('nospamnx', $this->generateNames(), '', 'yes');	
		add_option('nospamnx_checkip', 0, '', 'yes');
		add_option('nospamnx_count', 0, '', 'yes');
		add_option('nospamnx_operate', 'block', '', 'yes');
		add_option('nospamnx_blocktime', 0, '', 'yes');
		add_option('nospamnx_checkuser', 1, '', 'yes');		
		add_option('nospamnx_blocked_ip', 0, '', 'yes');
	}	
	
	function deactivate()
	{
		//delete wp options
		delete_option('nospamnx');	
		delete_option('nospamnx_checkip');
		delete_option('nospamnx_count');
		delete_option('nospamnx_operate');
		delete_option('nospamnx_blocktime');
		delete_option('nospamnx_checkuser');		
		delete_option('nospamnx_blocked_ip');
	}
	
	function nospamnxStats()
	{	
		$this->displayStats(true);		
	}	
	
	function displayStats($dashboard=false)
	{
		//get counter in local number format
		$counter = number_format_i18n(get_option('nospamnx_count'));

		if ($dashboard === true){echo "<p>";}
		echo '<a href="http://www.svenkubiak.de/nospamnx">NoSpamNX</a>';
		printf(__ngettext(
			" has stopped %s birdbrained Spambot.",
			" has stopped %s birdbrained Spambots.",
			$counter, 'nospamnx'), $counter);
		if ($dashboard === true){echo "</p>";}			
	}
}
//initalize class
if (class_exists('NoSpamNX'))
	$nospamnx = new NoSpamNX();		
?>