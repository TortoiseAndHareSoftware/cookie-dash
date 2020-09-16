=== WP GTM Data Privacy ===
Contributors: tnhsaesop
Tags: Google Tag Manager, Tag Manager, GDPR, CCPA, Data Privacy
Requires at least: 5.2
Tested up to: 5.5.1
Requires PHP: 7.2
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A plugin for quickly deploying Google Tag Manager on WordPress, with a cookie consent popup that disables the container if consent is declined.

== Description ==
WP GTM Data Privacy is a plugin meant to help people deploy Google Tag manager on their WordPress websites in a broad data privacy compliant manner. 

Privacy law is complex and there are many more granular solutions for controlling the specific output of tags and managing consent.  For people who like to keep things simple we\'ve taken a different approach with this plugin.  A basic cookie consent collection popup is included with the plugin, and if consent is denied, the plugin will disable the outputting of the Google Tag manager scripts to the page.  

This plugin works under the assumption that all marketing tags for 3rd party tools that collect personal information are deployed through Google Tag Manager.

== Installation ==
1 Download the plugin
2 Upload the plugin zip file to your plugins directory 
3 Activate the plugin from your plugin page
4 Go to Settings > WP GTM Data Privacy
5 Enter Your Tag Manager Container ID With NO SPACES
6 Test your container output by using Google Tag Assistant https://chrome.google.com/webstore/detail/tag-assistant-by-google/kejbdjndbnbjgmefkgdddjlbokphdefk?hl=en