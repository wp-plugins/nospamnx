=== Plugin Name ===
Contributors: kubi23
Donate link: http://www.svenkubiak.de/nospamnx-en/#donate
Tested up to: 2.8.4
Stable tag: 2.6
Requires at least: 2.7
Tags: wordpress, security, plugin, comment, comments, anti-spam, antispam, spam, spambot, spambots, protection, login, register, user

To protect your Blog from automated spambots, which fill you comments with junk, this plugin adds additional formfields to your comment form, which are checked every time a new comment is posted. PLEASE NOTE: Make sure your theme loads wp_head() and comment_form() according to the WordPress Codex (see http://is.gd/1lezf), othweise NoSpamNX will not work properly! 

== Description ==

Most Antispam Plugins focus on user interaction, e.g. captcha or Math calculations to defend you against automated comment spambots. Some use JavaScript and/or Sessions, check each comment against common spam phrases or modify your comment template. NoSpamNX intend to handle automated comment-spam without these measures. It does not require JavaScript, Cookies or Sessions. It does not change your comment template in any way, given you full compatibility with other WordPress- or Browser Plugins.

NoSpamNX automaticly adds additional formfields to your comment form, invisible to the users. If a spambot fills these fields blindly (which 99.9% of all spambots do), the comment will not be saved. You can decide if you want to block these spambots, mark them as spam or put them in moderation queue. Furthermore, you can put common spam-phrases on a blacklist.
	
The user must no longer fill out any additional fields in the comment form, and you can focus on blogging and your readers comments!

Since Version 2.6 NoSpmaNX can use the same mechanics to protect your Registration and Login Form against automated access. 


= Requirements =

Make sure your theme loads <code>wp_head</code> and <code>comment_form</code> according to the WordPress Codex (see http://is.gd/1lezf), otherwise NoSpamNX will not work properly!


= Features in a nutshell =

* Easy installation (just activate the plugin)
* Does not require any modification on your comment template
* Does not change any of your comment formfields (giving you more compatibility with other plugins and templates)
* Does not require JavaScript, Cookies or Sessions
* Does not require any extra field for user input (e.g. Captcha)
* No need to manage spambot comments anymore (if you don't want to)
* Compatible with comments-popup
* Compatible with WordPress MU
* False-positives are nearly impossible
* NEW: Protect your Registration and Login Form against automated access (since 2.6)


= Available Languages  =

* German
* English
* French
* Italian (Translation: Gianni Diurno)
* Spanish (Translation: Samuel Aguilera)
* Chinese (Translation: Donald Z)
* Polish
* Swedish (Translation: Mats Bergsten)

== Installation ==

1. Unzip Plugin
2. Copy the nospamnx folder to wp-content/plugins
3. Activate plugin
4. Feel free to adjust settings (WP-Admin -> Settings -> NoSpamNX)

Done!

== Frequently Asked Questions ==

= When i activate the plugin, the hidden fields are visible! =

Make sure that the template you are using calls <code>wp_head</code> before the closing HEAD tag. See http://is.gd/tazh for more information.

= What is the difference to other anti-comment-spam plugins? =

Spambots are stopped within the plugin. You don't see them and most important you don't have to moderate them!

= Does the plugin block Ping-/Trackback Spam as well? =

No, the plugin focus on automated spambots only.

= What about false-positives? =

Due to the functionality of NoSpamNX false-positives are nearly impossible. There 'might' be problems when using WordPress Cache-Plugins, but none have ever been reported. If you are uncertain, try puting catched Spambots in moderation queue or mark as Spam. 


== Screenshots ==

1. NoSpamNX statistic on Dashboard
2. NoSpamNX settings


== Changelog ==

= 2.6 =
* Added optional check for Registration and Login Form
* Updated language files
* Updated readme 

= 2.5 =
* Fixed bug when displaying statistics 

= 2.4 =
* Plugin is now compatible with WordPress MU 
* Added Swedish Translation
* Updated Chinese Translation
* Updated readme

= 2.3 =
* Optimized class loading
* Removed all output buffer calls in favor of comment_form hook
* Removed debug information on settings page

= 2.2 =
* Modified loading of Stylesheet
* Added new WordPress Plugin changelog

= 2.1 =
* Updated Spanish translation

= 2.0 =
* Added Blacklist
* Re-Added HTTP-Referer-Check, but now optional
* Changed pacing of hidden fields
* Changed all Radio-Buttons to Checkboxes
* Changed default operating to mark as spam
* Removed IP-Lock due to new Blacklist
* Removed option to deactivate Plugin on certain pages/posts due to new placing
* Requires now at least WordPress 2.7 
* Updated Screenshot
* Updated readme
* Updated language files
 
= 1.10 =
* Removed referer check temporarily
 
= 1.9 =
* Fixed bug with referer check

= 1.8 =
* Improved function when using comments popup
* Added referer check
* Optimized function that blocks the spambots

= 1.7 =
* Fixed Bug when disabling NoSpamNX on certain pages/posts
* Optimized function that blocks the spambots
    
= 1.6 =
* Added feature to disable NoSpamNX on certain pages/posts
* Fixed Bug that displayed hidden fields in comments popup
* Added Polish translation
* Update language files

= 1.5 =
* Increased compatibility with different PHP configurations
* Added information tab in seetings
* Updated language files
* Updated FAQ

= 1.4 =
* Added Russian translation
* Added Chinese translation (Simplified Chinese)
* Modified function that changes the template
* Updated FAQ

= 1.3 =
* Added full compatibility with comments-popup
* Added Spanish translation
* Akismet or similar is not require any more to mark comment as spam
* Updated language files
* Updated Screenshot  
* Updated readme.txt

= 1.2 =
* Added French translation
* Added Italian translation
* Minor code optimization
* Default blocktime for ip address set to 1 hour

= 1.1 =
* Optimized function that adds the hidden fields
* Removed all serialize/unserialize functions
* Changed activate/deactivate to wp-hooks 
* Completly changed handling of options
* Updated language file

= 1.0 =
* Initial release