=== Plugin Name ===
Contributors: kubi23
Donate link: http://www.svenkubiak.de/nospamnx-en
Tested up to: 3.0.1
Stable tag: 3.18
Requires at least: 2.8
Tags: blog, wordpress, security, plugin, comment, comments, anti-spam, antispam, spam, spambot, spambots, protection, user, users, template, secure, hidden, yawasp, nospamnx

To protect your Blog from automated spambots, which fill you comments with junk, this plugin adds additional formfields (hidden to human-users) to your comment form. These Fields are checked every time a new comment is posted. 

== Description ==

There have been many new good ideas of fighting automated Spam in WordPress. Most of these Plugins (like the antecessor of NoSpamNX: Yawasp) change the name of one (or more) of your comment field. On the one hand, this is indeed more effective, but on the other hand, this goes to the expense of compatibility. Therefore, NoSpamNX does not change any of your comment fields, but still claims to be very effective. 

Many Antispam Plugins focus on user interaction, e.g. captcha or Math calculations to defend you against automated comment spambots. Some use JavaScript and/or Sessions, check each comment against common spam phrases or modify your comment template. NoSpamNX intend to handle automated comment-spam without these measures. It does not require JavaScript, Cookies, Sessions or a Database. It does not change any of your comment template fields, given you full compatibility with other WordPress- or Browser Plugins.

NoSpamNX automaticly adds additional formfields to your comment form, invisible to human users. If a spambot fills these fields blindly (which 99.9% of all spambots do), the comment will not be saved. You can decide if you want to block these spambots or mark them as spam. Furthermore, you can put common spam-phrases on a blacklist.
	
The user must no longer fill out any additional fields in the comment form, and you can focus on blogging and your readers comments!


= Requirements =

Make sure your theme loads <code>wp_head</code> and <code>comment_form</code> according to the WordPress Codex (see http://is.gd/1lezf), otherwise NoSpamNX will not work properly!


= Features in a nutshell =

* Easy installation (just activate the plugin)
* Does not require any modification on your comment template
* Does not change any of your comment formfields (giving you more compatibility with other plugins and templates)
* Does not require JavaScript, Cookies, Sessions or a Database
* Does not require any extra field for user input (e.g. Captcha, Pictures, etc.)
* No need to manage spambot comments
* No Database queries except for some WordPress Options
* Compatible with WPtouch (Mobile Theme)
* Optional Blacklist to block specific URLs, Domains, etc. (use it to block "handmade" Spam)
* False-positives are nearly impossible
* Less than 500 Lines of Code


= Available Languages  =

* German
* English
* Spanish (Thanks to Samuel Aguilera)
* Chinese (Thanks to Donald Z)
* Swedish (Thanks to Mats Bergsten)
* French (Thanks to Sylvain Ménard)
* Italian (Thanks to Gianni Diurno)

== Installation ==

1. Unzip Plugin
2. Copy the nospamnx folder to your wp-content/plugins folder
3. Activate plugin
4. (Optional) Adjust settings (Settings -> NoSpamNX)

Done!

== Frequently Asked Questions ==

= After I updated to Version x.x the Options are reseted! =

Update at least to Version 3.14. Since this Version the Options are only reseted if new options are available.

= When using WPtouch (Mobile Theme), the hidden fields are visible! =

WPtouch seems not to handle a custom CSS Name. Please use the default NoSpamNX CSS Name. 

= When I activate the plugin, the hidden fields are visible! =

Make sure that the template you are using calls <code>wp_head</code> before the closing HEAD tag. See http://is.gd/tazh for more information.

= My template does load <code>wp_head</code>, but the hidden fields are still visible! =

First, deactivate and re-active the Plugin. Then go to Settings -> NoSpamNX and reset the CSS Name.

= What is the difference to other Anti-Spambots plugins? =

Most "new" AntiSpam Plugin change the name of one (or more) of you comment field. On the one hand, this is indeed more effective, but on the other hand, this goes to the expense of compatibility. NoSpamNX does not change any of your comment field, but still is very effective. Furthermore Spambots are stopped within the plugin. You don't see them, and most important, you don't have to moderate them (if you don't want to)!

= Does the plugin block Ping-/Trackback Spam as well? =

No, the plugin focus on automated Spambots only and I have no plans of changing that. There are some really good Plugins in the WordPress Repository for that.

= What about false-positives? =

Due to the functionality of NoSpamNX false-positives are nearly impossible. There 'might' be problems when using WordPress Cache-Plugins, but none have ever been reported. If you are uncertain, mark Spambots as Spam instead of blocking them. 


== Screenshots ==

1. NoSpamNX statistic on Dashboard
2. NoSpamNX settings


== Changelog ==

= 3.18 =
* Minor Code cleanup
* Updated Language Files
* Updated Screenshots

= 3.17 =
* Removed Custom CSS Option (Fields are now hidden by display:none again)

= 3.16 =
* Fixed Bug when reseting CSS Name
* Fixed Bug when activating plugin
* Updated readme

= 3.15 =
* Fixed Bug when activating plugin
* Removed deactivation hook
* Plugin now requires at least WordPress 2.8
* Updated language files

= 3.14 =
* Added nonce fields
* Added check, that options are only reseted if option-fields have changed
* Updated language files
* Updated readme 

= 3.13 =
* Fixed Bug that could mark first comment after Plugin Update as Spam
* Added Support for SSL for Referer-Check
* Updated readme

= 3.12 =
* Changed Error- and Info-Message to new Stylesheets
* Updated readme

= 3.11 =
* Fixed Bug that could cause visibility of hidden fields
* Referer-Check checks now against 'siteurl' AND 'home'
* Code cleanup
* Updated language files

= 3.10 =
* Changed Referer-Check from 'siteurl' to 'home'
* Blacklist will only be deleted when plugin is uninstalled
* Removed WordPress MU support
* Removed comments-popup support
* Updated language files

= 3.9 =
* Fixed bug when displaying statistics
* Removed function that could caus false positives
* Updated spanish translation

= 3.8 =
* Quickfix for deactivation/activation Bug

= 3.7 =
* Fixed bug when plugin was deactivated
* Minor code improvements
* Updated language files

= 3.6 =
* Improved Referer-Check
* Added per Day Stats
* Updated language files

= 3.5 =
* Fixed Bug in plugin activation
* Added Italian Translation

= 3.4 =
* Fixed Bug with referer check
* Updated language files
* Removed uneccesary code comments
* Updated readme

= 3.3 =
* Re-Added old CSS-Style due to cache problems
* Fixed Bug on Options-Page
* Updated screenshots
* Updated german language file
* Updated readme

= 3.2 =
* Fixed Bug when including CSS

= 3.1 =
* Fixed Bug when including CSS

= 3.0 =
* Hidden field names now have a variable length
* Added option to include own stylesheet
* Removed option to moderate catched spambots
* Removed option to check logged in users (now built-in)
* Removed option to check registration and login form
* Removed all fuzzy translations
* Blacklist now searches for pattern in comment field
* Updated language files
* Updated readme

= 2.9 =
* Added Tags to hidden field for XHTML 1.0 Strict compatibility (Thanks to Pete Stephenson!)
* Updated Spanish Translation

= 2.8 =
* Fixed Bug when directly accessing wp-admin

= 2.7 =
* Fixed Bug when checking URL

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