=== Plugin Name ===
Contributors: kubi23
Donate link: http://www.svenkubiak.de/nospamnx-en/#donate
Tested up to: 2.7.1
Stable tag: 1.0
Requires at least: 2.6
Tags: wordpress, security, plugin, comment, comments, anti-spam, antispam, spam, spambot, spambots, protection

To protect your Blog from automated spambots, which fill you comments with junk, this plugin adds additional formfields to your comment template, which are checked every time a comment is posted.

== Description ==

Most anti-comment-spam-plugins focus on user interaction, e.g. captcha or math comment spam protection. Some use JavaScript and/or Sessions, check each comment against common spam phrases or modify your comment template. NoSpamNX focuses on handling automated comment-spam without these measures. It does not require JavaScript, Cookies or Sessions. It does not change your comment template in any, given you full compatibility with other WordPress Plugins. The Plugin adds additional formfields to your comment form, invisible to the users. If a spambot fills this fields blindly (which 99.9% of all spambots do), the comment will not be saved. You can decide if you want to block these spambots, mark them as spam (Akismet or similar required) or put them in moderation queue. Furhtermore, the ip address of catched spambots can be storede and blocked for 1 hour, 24 hours or indefinitly.
	
The user must no longer fill out any additional fields in the comment form, and you can focus on blogging and your readers comments!

= Features in a nutshell =

* Easy installation (just activate the plugin!)
* Does not require any modification on your comment template
* Does not change any of your comment formfields (full compatibility with other plugins!)
* Does not require JavaScript, Cookies or Sessions
* No extra field for user input (e.g. Captcha) required
* False-positives are nearly impossible
* No need to manage spambot comments anymore 

= Available Languages  =

* German
* English

== Installation ==

1. Unzip Plugin
2. Copy the nospamnx folder to wp-content/plugins
3. Activate plugin
4. Feel free to adjust settings (WP-Admin -> Settings -> NoSpamNX)

Done!

== Frequently Asked Questions ==

= What is the difference to other anti-comment-spam plugins ? =

Spambots are blocked within the plugin. You don't see it and you don't have to moderate it.

= Does the plugin block Ping-/Trackback Spam as well ? =

No, the plugin focuses on Spambot-comment-spam only.

= What about false-positives ? =

Due to the core functionality of NoSpamNX false-postives are nearly impossible. There might be problems when using Cache-Plugins, but none have ever been reported. If you are uncertain, try puting catched Spambots in moderation queue or mark as Spam (Akismet or similar required). 


== Screenshots ==

1. NoSpamNX statistic on Dashboard
2. NoSpamNX settings

== Version History ==

* Version 1.0
    * Initial release