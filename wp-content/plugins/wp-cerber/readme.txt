=== Cerber Limit Login Attempts ===
Contributors: gioni
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=SR8RJXFU35EW8
Tags: security, access control, authentication, limit, login, access, admin, users, protect, protection, brute force, bruteforce, activity, log, logging, block, hide wp-admin, wp-login, wp-admin, fail2ban, monitoring, rename wp login, whitelist, blacklist, wordpress security, xmlrpc, user enumeration, hardening
Requires at least: 3.3
Tested up to: 4.4.2
Stable tag: 2.7.2
License: GPLv2

Protects site against brute force attacks. Restrict login by IP access lists. Limit login attempts. Comprehensive control of user activity.

== Description ==

Limit the number of login attempts through the login form, XML-RPC requests or using auth cookies.
Restrict access with Black IP Access List and White IP Access List.
Track user and intruder activity.
Hardening WordPress.

**Features you will love**

* **Limit login attempts** when logging in by IP address or subnet Class C.
* Monitors logins made by login forms, XML-RPC requests or auth cookies.
* Permit or restrict logins by **White Access list** and **Black Access List** with IP or subnet.
* Log all activities related to the logging in/out process.
* Hide wp-login.php from possible attacks and return 404 HTTP Error.
* Hide wp-admin (dashboard) and return 404 HTTP Error when a user isn't logged in.
* Make **custom URL for logging in** ([rename wp-login.php](http://wpcerber.com/how-to-rename-wp-login-php/)).
* Immediately block IP or subnet when attempting to log in with **non-existent username**.
* Disable automatic redirecting to login page.
* Disable XML-RPC (block access to the XML-RPC server including Pingbacks and Trackbacks)
* Disable feeds (block access to the RSS, Atom and RDF feeds)
* Disable WP REST API
* Restrict access to the XML-RPC, REST API and feeds by **White Access list** with IP or subnet.
* **Stop user enumeration** (block access to the pages like /?author=n)
* Proactively **block IP subnet class C** for intruder's IP.
* Citadel mode for **massive brute force attack**.
* [Play nice with **fail2ban**](http://wpcerber.com/how-to-protect-wordpress-with-fail2ban/): write failed attempts to the syslog or custom log file.
* View and filter out activities list by IP, username or particular event.
* Handles site/server behind reverse proxy.
* Optional admin notifications by email.
* WP Cerber doesn't rely on any external service (unlike other similar plugins) and doesn't send any data outside to work.

**How does WP Cerber protect sites?**

By default WordPress allows unlimited login attempts either through the login form or by sending special cookies. This allows passwords to be cracked with relative ease via brute force attack.

WP Cerber blocks intruders by IP or subnet from making further attempts after a specified limit on retries is reached, making brute force attacks or distributed brute force attacks from botnet impossible.

You will be able to create a **Black  Access List** or **White Access List** to block or allow logins from particular IP.

Moreover, you can create your custom login page and forget about automatic attacks to the default wp-login.php, which takes your attention and consumes a lot of server resources. If an attacker tries to access wp-login.php they will get a 404 Error response.

WP Cerber tracks time, IP addresses and usernames for successful and failed login attempts, logins, logouts, password changes, blocked IP and actions taken by itself.

You can **hide WordPress dashboard** (/wp-admin/) when a user isn't logged in. If a user isn't logged in and they attempt to access the dashboard by requesting /wp-admin/, WP Cerber will return a 404 Error.

Massive botnet brute force attack? That's no longer a problem. **Citadel mode** will automatically be activated for awhile and prevent your site from making further attempts to log in with any username.

**Translations**

* English
* Spanish, thanks to Ismael
* Deutsche, thanks to mario and Mike
* Dutch, thanks to [Bernardo](https://twitter.com/bernardohulsman)
* Français, thanks to [hardesfred](https://profiles.wordpress.org/hardesfred/)
* Czech, thanks to [Hrohh](https://profiles.wordpress.org/hrohh/)
* Український
* Русский

I am passionate about building neat and reliable solutions so, please, [write your review or even give a five-star rating here](https://wordpress.org/support/view/plugin-reviews/wp-cerber).

Have a question? [Get help here](http://wordpress.org/support/plugin/wp-cerber)!

Do you have a suggestion? [Help me improve WP Cerber!](http://wpcerber.com/new-feature-request/)

There are semi-similar security plugins: Login LockDown, Login Security Solution,
BruteProtect, Ajax Login & Register, Lockdown WP Admin,
BulletProof Security, SiteGuard WP Plugin, All In One WP Security & Firewall, Brute Force Login Protection

**Another reliable plugins from the trusted author**

* [Plugin Inspector reveals issues with installed plugins](https://wordpress.org/plugins/plugin-inspector/)

Checks plugins for deprecated WordPress functions, known security vulnerabilities and some unsafe PHP function

* [Translate sites with Google Translate Widget](https://wordpress.org/plugins/goo-translate-widget/)

Make your website instantly available in 90+ languages with Google Translate Widget. Add the power of Google automatic translations with one click.

== Installation ==

1. Upload the WP Cerber folder to the plugins directory in your WordPress installation.
2. Activate the plugin through the WordPress admin interface.
3. Plugin is now active and has started protecting you site right out of the box.
4. If you want to customize the set of security settings, you can fine tune it on the settings page.
5. If you set up a Custom login URL and you are using a caching plugin like **W3 Total Cache** or **WP Super Cache** you should add the slug of the new login URL to the list of pages not to cache.

== Frequently Asked Questions ==

= Is this plugin compatible with Multisite mode? =

Yes. All settings apply to all sites in the network simultaneously. You have to activate the plugin in the Network Admin area on the Plugins page. Just click on the Network Activate link.

= Is WP Cerber compatible with bbPress? =

Yes. [Compatibility notes](http://wpcerber.com/compatibility/).

= Is this plugin compatible with WooCommerce? =

Yes. [Compatibility notes](http://wpcerber.com/compatibility/).

= Can I change login URL (rename wp-login.php)? =

Yes, easily. Know more: [How to rename wp-login.php](http://wpcerber.com/how-to-rename-wp-login-php/)

= Can this plugin works together with Limit Login Attempts? =

No. WP Cerber is a drop in replacement for it.

= Can WP Cerber protect my site from DDoS attacks? =

No. This plugin protects your site from Brute force attacks or distributed Brute force attacks. By default WordPress allows unlimited login attempts either through the login form or by sending special cookies. This allows passwords to be cracked with relative ease via a brute force attack. To prevent from such a bad situation use WP Cerber.

= Is there any WordPress plugin to protect my site from DDoS attacks? =

No. This hard task cannot be done via a plugin. That may be done by using special hardware from your hosting provider.

= What is the goal of Citadel mode? =

Citadel mode is intended to block massive, distributed botnet attacks and also slow attacks. The last type of attack has a large range of inrtuder IPs with a small number of attempts to login per each.

= How to turn off Citadel mode completely? =

Set Threshold fields to 0 or leave them empty.

= What is the goal of using Fail2Ban? =

With Fail2Ban you can protect site on the OS level with iptables. See details here: [http://wpcerber.com/how-to-protect-wordpress-with-fail2ban/](http://wpcerber.com/how-to-protect-wordpress-with-fail2ban/)

= Do I need using Fail2Ban to get the plugin working? =

No. It is optional.

= Can I use this plugin on the WP Engine hosting? =

Yes! WP Cerber is not on the list of disallowed plugins. There are no limitation on the hosting providers. You can use it even on the shared hosting. Plugin consumes minimum resources and does not impact server performance or response time.

= Can I rename wp-admin folder? =

No. It's not possible and not recommended for compatibility reason.

= Can this plugin works together with Limit Login Attempts? =

No. WP Cerber is a drop in replacement for it.

= I can't login / I'm locked out of my site =

**How to get access (log in) to the dashboard?**

There is special version of plugin called **WP Cerber Reset**. This version perform only one task. It will reset all WP Cerber settings to initial values (excluding Access Lists) and then will deactivate itself.

To get access to you dashboard you need to copy WP Cerber Reset folder to the plugins folder. Follow those simple steps.

1. Download wp-cerber-reset.zip archive to your computer using this link: [http://wpcerber.com/downloads/wp-cerber-reset.zip](http://wpcerber.com/downloads/wp-cerber-reset.zip)
2. Unpack wp-cerber folder from archive.
3. Upload wp-cerber folder to the plugins folder of your site using FTP. If you'll see a question about overwriting file, click Yes.
4. Log in to the your site as usually. Now WP Cerber disabled completely.
5. Reinstall WP Cerber again. You need to do it, because WP Cerber Reset can't acting as normal plugin.

== Screenshots ==

1. Main screen settings are: Limit login attempts, Custom login page, Proactive security rules, Citadel mode, Write to syslog option.
2. Use IP Access Lists to block or allow logins from a particular IP address or subnet class C. Additionally check for the activity of a particular entry. For instance, you can whitelisting your IP address and blacklisting intruders IP or network.
3. Check Activity List to know what is going on. You can see what happens and when it happened with a particular IP or username, when IP reaches the limit of login attempts and when it was blocked.
4. Lockouts is a list of blocked IP addresses and subnets at the moment. You can see when lockout will expire. You can remove lockout for particular IP address.
5. WP Cerber adds four new columns on the WordPress Users screen: Date of registration, Date of last login, Number of failed login attempts and Number of comments. To view the details just click on the appropriate cell.
6. You can export and import security settings and IP Access Lists on the Tools screen.
7. Beautiful widget for the dashboard to keep an eye on things. Get quick analytic with trends over 24 hours.

== Changelog ==

= 2.7.2 =
* Fixed bug for non-English WordPress configuration: the plugin is unable to block IP in some server environment. If you have configured language other than English you have to install this release.

= 2.7.1 =
* Fixed two small bugs related to 1) unable to remove IP subnet from the Access Lists and 2) getting IP address in case of reverse proxy doesn't work properly.

= 2.7 =

* Important Note: This release brings a lot of changes to the code - don't hesitate to contact me if something goes wrong: [http://wpcerber.com/support/](http://wpcerber.com/support/). You can roll back to the last stable version here: http://wpcerber.com/download/
* New: Now you can view extra WHOIS information for IP addresses in the activity log including country, network info, abuse contact, etc.
* New: Added ability to disable WP REST API, see [Hardening WordPress](http://wpcerber.com/hardening-wordpress/)
* New: Added ability to add IP address to the Black List from the Activity tab. Nail it!
* New: Added Spanish translation, thanks to Ismael.
* New: Added ability to set numbers of displayed rows (lines) on the Activity and Lockout tabs. Click Screen Options on the top-right.
* Fixed minor security issue: Actions to remove IP on the Access Lists tab were not protected against CSRF attacks. Thanks to Gerard.
* Update: Small changes on the dashboard widget.
* Update: Action taken by the plugin (plugin makes a decision) now marked with dark vertical bar on the right side of the labels (Activity tab).

= 2.0.1.6 =
* New: Added Reason column on the Lockouts screen which will display cause of blocking particular IP.
* New: Added Hardening WP with options: disable XML-RPC completely, disable user enumeration, disable feeds (RSS, Atom, RSD).
* New: Added Custom email address for notifications.
* New: Added Dutch and Czech translations.
* New: Added Quick info about IP on Activity tab.
* Update: Removed option 'Allow whitelist in Citadel mode'. Now this whitelist is enabled by default all the time.
* Update: For notifications on the multisite installation the admin email address from the Network Settings will be used.
* Fixed Bug: Disable wp-login.php doesn't work for subfolder installation.
* Fixed Bug: Custom login URL doesn't work without trailing slash.
* Fixed Bug: Any request to wp-signup.php reveal hidden Custom login URL.

= 1.9 =
* Code refactoring and cleaning up.
* Unlocalized strings was localized.

= 1.8.1 =
* Fixed minor bug: no content (empty cells) in the custom colums added by other plugins on the Users screen in the Dashboard.

= 1.8 =
* New! added Hostname column for the Activity and Lockouts tabs.
* New! added ability to write failed login attempts to the specified file or to the syslog file. Use it to protect site with fail2ban.
* Added Ukrainian translation (Український переклад).

= 1.7 =
* Added ability to remove old records from the user activity log. Log will be cleaned up automatically. Check out new Keep records for field on the settings page.
* Added pagination for the Activity and Lockouts tabs.
* Added German (Deutsch) translation, thanks to mario.
* Added ability to reset settings to the recommended defaults at any time.

= 1.6 =
* New: beautiful widget for the dashboard to keep an eye on things. Get quick analytic with trends over 24 hours and ability to manually deactivate Citadel mode.
* French translation added, thanks to hardesfred.
* Hardening WordPress. Removed automatically redirection from /login/ to the login page, from /admin/ and /dashboard/ to the dashboard.
* Fixed issue with lost password link in the multisite mode.
* Now compatible with User Switching plugin.
* Added ability to manually deactivate Citadel mode, once it automatically switches on.

= 1.5 =
* New feature: importing and exporting settings and access lists from/to the file.
* Limited notifications in the dashboard.

= 1.4 =
* Added support Multisite mode for limit login attempts.
* Added Number of comments column on the Users screen in dashboard.
* Updated notification settings.
* Updated languages files.

= 1.3 =
* Fixed issue with hanging up during redirect to /wp-admin/ on some circumstance.
* Fixed minor issue with limit login attempts for non-admin users.
* Added Date of registration column on the Users screen in dashboard.
* Some UI improvements on access-list screen.
* Performance optimization & code refactoring.

= 1.2 =
* Added localization & internationalization files. You can use Loco Translate plugin to make your own translation.
* Added Russian translation.
* Added headers for failed attempts to use such headers with [fail2ban](http://www.fail2ban.org).

= 1.1 =
* Added ability to filter out Activity List by IP, username or particular event. You can see what happens and when it happened with particular IP or username. When IP reaches limit login attempts and when it was blocked.
* Added protection from adding to the Black IP Access List subnet belongs to current user's session IP.
* Added option to work with site/server behind reverse proxy.
* Update installation instruction.

= 1.0 =
* Initial version

== Other Notes ==

**Deutsche**
Schützt vor Ort gegen Brute-Force-Attacken. Umfassende Kontrolle der Benutzeraktivität. Beschränken Sie die Anzahl der Anmeldeversuche durch die Login-Formular, XML-RPC-Anfragen oder mit Auth-Cookies. Beschränken Sie den Zugriff mit Schwarz-Weiß-Zugriffsliste Zugriffsliste. Track Benutzer und Einbruch Aktivität.

**Français**
Protège site contre les attaques par force brute. Un contrôle complet de l'activité de l'utilisateur. Limiter le nombre de tentatives de connexion à travers les demandes formulaire de connexion, XML-RPC ou en utilisant auth cookies. Restreindre l'accès à la liste noire accès et blanc Liste d'accès. L'utilisateur de la piste et l'activité anti-intrusion.

**Український**
Захищає сайт від атак перебором. Обмежте кількість спроб входу через запити ввійти форми, XML-RPC або за допомогою авторизації в печиво. Обмежити доступ з чорний список доступу і список білий доступу. Користувач трек і охоронної діяльності.

**What does "Cerber" mean?**

Cerber is derived from the name Cerberus. In Greek and Roman mythology, Cerberus is a multi-headed dog with a serpent's tail, a mane of snakes, and a lion's claws. Nobody can bypass this angry dog. Now you can order WP Cerber to guard the entrance to your site too.


