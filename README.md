
LTI-Enabled APT GRADER
======================
**Zachary Marion and Sean Hudson**
*with help from Dr. Chuck*

Link to our [project reflection](/project_reflection/summary_reflection.md)

![apt grader](tsugi_apt.png)

Description
----------------------

The LTI-Enabled APT Grader is an LTI compliant module based on a [Tsugi](http://csev.github.io/tsugi/) framework, which allows a LMS to easily incorporate the tool into its system. This tool grades APT submissions and immediately returns the grade result into the student's gradebook as well as provides aggregate graphical data for the instructor.

Setup
----------------------
You need to download and install the [Tsugi Developer/Administrator Console](https://github.com/csev/tsugi) to be able to setup database tables, test your software, and configure your keys.

Once that is installed, you can clone this repository:

    https://github.com/zacharyfmarion/apt_files

The folder comes with a `config.php` that assumes that it is installed in the same htdocs folder as the Tsugi Console.  This is a quick way to get this program up and running for testing. A key element of the configuration is to include this line as part of the configuration to indicate to Tsugi that we are using cookie-based sessions.

```php
if ( !defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
```

Additionally, there is an array of problems:

```php
$problems = array();
```

This is generated from `apt.txt`, so if you add more problems to this file (and upgrade the database tables via the Admin console in the Tsugi Dev console) the problems should now be saved to the database.

If you have the Tsugi management console running on the same server, you can make it so developer mode can test this application by updating the tool folder list:

```php
$CFG->tool_folders = array("admin", "samples", ... ,
    "exercises", "../apt_files");
```

With that the tool will be easily testable from the Tsugi management console (although this is certainly not necessary). As mentioned above, you need to have set up Tsugi as per the instructions on their repo...a sample config.php file for the tsugi console is provided for convenience (the file is called `tsugi_config.php`). For use in production I would change the client and secret to something a bit more secure (changing the accompanying cliend and secret in the LMS link), and also turn developer mode off:

```php
$CFG->DEVELOPER = false;
```

If for production purposes and you need to run your application on a server without installing the Tsugi management console, see the more advanced configuration instructions below.

Additionally, If you are adding a new APT problem to the server, you are going to need to edit the Tester.py file to get the filter funcionality and not see the green rows before seeing the actual data results.

Modify the following lines in the file:

```python
if a == None:
        break
self.done = True
sprinter.write(_TRSTART+str(count)+_TDEND)
```

```python
elif not p.ok():
    # printer.write(_TDFAIL+'</TR>\n')
    sprinter.write('<TD class="fail outcome">fail</TD>'+'<TD>expected<PRE> ')
```

```python
    sprinter.write(_TDEND+'\n')
else:
    correctCount += 1
    # printer.write(_TDPASS+"\n")
    sprinter.write('<TD class="pass outcome">pass</TD><TD>')
    sprinter.write('got<br><PRE> ')
```

Setting up on Sakai (11)
------------------------

Once you have a Sakai course set up, go to "Site Info" -> "External Tools" and "Install LTI 1.1 Tool". Fill in all the relevant fields, checking "Send User Names to External Tool", "Send Email Addresses to External Tool" and "Allow External Tool to return grades". Hit save and the tool should appear in the sidebar. To get the grading to work, click "Edit" and under "Routing Grades to the Gradebook" select the gradebook item you want the grade to be sent back to. Currently all the grader supports is sending an average of all the APT's submitted, although this may change in the future.

How Tsugi Uses Session
----------------------

if you look at `index.php`, you will see these three lines:

    require_once "config.php";
    use \Tsugi\Core\LTIX;

    $LAUNCH = LTIX::session_start();

This functions as an LTI-aware `session_start()` and can be a direct replacement
for `session_start()` in any of your PHP files.  In addition to starting the
session the LTIX version of `session_start()` does the following:

* Intercepts any LTI launch POSTs, validates them, updates the `lti_` database tables,
adds some LTI data to the session, and then redirects to a GET of the same URL.

* If the request is not an LTI launch (or a GET after LTI Launch POST), it looks in
the session to see if there is LTI data in the session and populates the $LAUNCH object
with as much of the User, Context, Link, and Result information as it can find.

Your code must not assume that these values are always set since there might be
more than one way to enter the application.  So code that might send a grade back
needs to protect itself and only call routines if sufficient LTI data is present.

    if ( isset($LAUNCH->result) ) {
        $LAUNCH->result->gradeSend(0.95);
    }

Limitations of Cookie-Based Sessions
------------------------------------

In standalone model, we will use cookies to manage the sessions.   Using cookies
limits the ability to embed the application in an iframe across two domains.
It also means that a single PHPSESSID value will exist for all non-incognito
windows and so if you do a launch on one tab as one user from a course
and then do another launch in a different tab as a different user from a different
course, the login settings will be changed in the first tab since they are
sharing a PHP session across tabs.

This also means that these **applications should be launched from the LMS in
a new window and not embedded in an iframe**.

The ability to have multiple simultaneous sessions and work seamlessly in an
iframe is one of the reasons that a lot of effort goes into using non-cookie
sessions in Tsugi Modules.  But since there are so many
existing applications that need an LTI integration that cannot be rewritten,
we accept these limitations in our Tsugi standalone approach.

Virtually all of the older LTI integrations based on `lti_util.php` or a similar
pattern have these exact same limitations since they use cookie-based sessions.

Tsugi Developer List
--------------------

Once you start developing Tsugi Applications or Modules, you should join the Tsugi
Developers list so you can get announcements when things change.

    https://groups.google.com/a/apereo.org/forum/#!forum/tsugi-dev

Once you have joined, you can send mail to tsugi-dev@apereo.org

Advanced Installation
---------------------

If you are going to install this tool in a web server that does not
already have an installed copy of the Tsugi management console,
it is a bit trickier.  There is no automatic connection between Tsugi developer
tools and Tsugi admin tools won't know about this tool.   
But it can run stand alone.

First install composer to include dependencies.

    http://getcomposer.org/

I just do this in the folder:

    curl -O https://getcomposer.org/composer.phar

Get a copy of the latest `composer.json` file from the
[Tsugi repository](https://github.com/csev/tsugi)
or a recent Tsugi installation and copy it into this folder.

To install the dependencies into the `vendor` area, do:

    php composer.phar install

If you want to upgrade dependencies (perhaps after a `git pull`) do:

    php composer.phar update

Note that the `composer.lock` file and `vendor` folder are
both in the `.gitignore` file and so they won't be checked into
any repo.

For advanced configuration, you need to retrieve a copy of
`config-dist.php` from the
[Tsugi repository](https://github.com/csev/tsugi)
or a copy of `config.php`
from a Tsugi install and place the file in this folder.

Then you will need to configure the database connection, etc for this
application by editing `config.php`.  

A key element of the configuration is to include this line as part
of the configuration to indicate to the Tsugi run-time that we
are using cookie-based sessions.

    if ( !defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);

The `config-dist.php` has a configuration line commented out to
serve as an example.

Running (Advanced Configuration)
--------------------------------

Once it is installed and configured, you can do an LTI launch to

    http://localhost:8888/tsugi-php-standalone/index.php
    key: 12345
    secret: secret

You can use your Tsugi installation or my test harness at:

    https://online.dr-chuck.com/sakai-api-test/lms.php

And it should work!

Upgrading the Library Code (Advanced Configuration)
---------------------------------------------------

From time to time the library code in

    https://github.com/csev/tsugi-php

Will be upgraded and pulled into Packagist:

    https://packagist.org/packages/tsugi/lib

To get the latest version from Packagist, edit `composer.json` and
update the commit hash to the latest hash on the `packagist.org` site
and run:

    php composer.phar update
