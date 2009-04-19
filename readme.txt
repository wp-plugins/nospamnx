=== Plugin Name ===
Contributors: kubi23
Donate link: http://www.svenkubiak.de/nospamnx-en/#donate
Tested up to: 2.7.1
Stable tag: 1.5
Requires at least: 2.6
Tags: wordpress, security, plugin, comment, comments, anti-spam, antispam, spam, spambot, spambots, protection

To protect your Blog from automated spambots, which fill you comments with junk, this plugin adds additional formfields to your comment template, which are checked every time a comment is posted. NOTE: If the hidden fields are displayed, make sure your theme does load wp_head()! 

== Description ==

Most anti-comment-spam-plugins focus on user interaction, e.g. captcha or math-comment-spam-protection to defend you against automated comment spambots. Some use JavaScript and/or Sessions, check each comment against common spam phrases or modify your comment template. NoSpamNX focuses on handling automated comment-spam without these measures. It does not require JavaScript, Cookies or Sessions. It does not change your comment template in any, given you full compatibility with other WordPress. or Browser Plugins. NoSpamNX adds additional formfields to your comment form, invisible to the users. If a spambot fills this fields blindly (which 99.9% of all spambots do), the comment will not be saved. You can decide if you want to block these spambots, mark them as spam or put them in moderation queue. Furhtermore, the ip address of catched spambots can be stored and blocked for 1 hour, 24 hours or indefinitly.
	
The user must no longer fill out any additional fields in the comment form, and you can focus on blogging and your readers comments!

= Features in a nutshell =

* Easy installation (just activate the plugin!)
* Does not require any modification on your comment template
* Does not change any of your comment formfields (giving you full compatibility with other plugins or templates!)
* Does not require JavaScript, Cookies or Sessions
* No extra field for user input (e.g. Captcha) required
* No need to manage spambot comments anymore 
* Fully compatible with comments-popup
* False-positives are nearly impossible


= Available Languages  =

* German
* English
* French
* Italian
* Spanish
* Russian
* Chinese

== Installation ==

1. Unzip Plugin
2. Copy the nospamnx folder to wp-content/plugins
3. Activate plugin
4. Feel free to adjust settings (WP-Admin -> Settings -> NoSpamNX)

Done!

== Frequently Asked Questions ==

= When i activate the plugin, the hidden fields are visible! =

Make sure that the template you are using calls wp_head before the closing HEAD tag (</head>). See http://is.gd/tazh for more information.

= What is the difference to other anti-comment-spam plugins? =

Spambots are stopped within the plugin. You don't see them and most important you don't have to moderate them!

= Does the plugin block Ping-/Trackback Spam as well? =

No, the plugin focus on automated spambots only.

= What about false-positives? =

Due to the functionality of NoSpamNX false-positives are nearly impossible. There 'might' be problems when using WordPress Cache-Plugins, but none have ever been reported. If you are uncertain, try puting catched Spambots in moderation queue or mark as Spam. 


== Screenshots ==

1. NoSpamNX statistic on Dashboard
2. NoSpamNX settings

== Version History ==

* Version 1.5 (18-04-09)
    * Increased compatibility with different PHP configurations
    * Added information tab in seetings
    * Updated language files
    * Updated FAQ
* Version 1.4 (18-04-09)
    * Added Russian translation
    * Added Chinese translation (Simplified Chinese)
    * Modified function that changes the template
    * Updated FAQ
* Version 1.3 (10-04-09)
    * Added full compatibility with comments-popup
    * Added Spanish translation
    * Akismet or similar is not require any more to mark comment as spam
	* Updated language files
	* Updated Screenshot  
	* Updated readme.txt
* Version 1.2 (04-04-09)
    * Added French translation
    * Added Italian translation
    * Minor code optimization
    * Default blocktime for ip address set to 1 hour
* Version 1.1 (29-03-09)
	* Optimized function that adds the hidden fields
    * Removed all serialize/unserialize functions
    * Changed activate/deactivate to wp-hooks 
    * Completly changed handling of options
    * Updated language file
* Version 1.0 (27-03-09)
    * Initial release