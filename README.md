moodle-tool_coursefields
========================

[![Moodle Plugin CI](https://github.com/moodleuulm/moodle-tool_coursefields/workflows/Moodle%20Plugin%20CI/badge.svg?branch=master)](https://github.com/moodleuulm/moodle-tool_coursefields/actions?query=workflow%3A%22Moodle+Plugin+CI%22+branch%3Amaster)

Moodle admin tool plugin which allows managers to set and overwrite custom course field values for all courses in a category, including subcategories. 


Requirements
------------

This plugin requires Moodle 3.11+


Motivation for this plugin
--------------------------

Since Moodle 3.7, Moodle core ships with support for custom course fields which can be defined globally by the admin and which can then be set on the course settings page of a particular course by teachers and managers.
However, setting or changing custom field values for a large number of existing courses manually on each course settings page is a tedious task.

As a solution, with this plugin, managers can set and overwrite custom course field values for whole course categories including their subcategories within one single bulk edit step.


Installation
------------

Install the plugin like any other plugin to folder
/admin/tool/coursefields

See http://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins


Usage & Settings
----------------

After installing the plugin, it is ready to use without the need for any configuration.

To use the plugin, administrators and users who have the tool/coursefields:setfields (assigned by default to the manager role archetype during plugin installation) will find a new menu item 'Set course fields' in the cog menu of each course category overview page. 


How this plugin works
---------------------

After submitting the form on the 'Set course fields' page, Moodle will create an 'adhoc task' to set all the course fields in the background. This requires that cron be enabled. 
After the next cron run, the course fields of all courses in the given course category are set to their new values.


Theme support
-------------

This plugin is developed and tested on Moodle Core's Boost theme.
It should also work with Boost child themes, including Moodle Core's Classic theme. However, we can't support any other theme than Boost.


Plugin repositories
-------------------

This plugin is published and regularly updated in the Moodle plugins repository:
http://moodle.org/plugins/view/tool_coursefields

The latest development version can be found on Github:
https://github.com/moodleuulm/moodle-tool_coursefields


Bug and problem reports / Support requests
------------------------------------------

This plugin is carefully developed and thoroughly tested, but bugs and problems can always appear.

Please report bugs and problems on Github:
https://github.com/moodleuulm/moodle-tool_coursefields/issues

We will do our best to solve your problems, but please note that due to limited resources we can't always provide per-case support.


Feature proposals
-----------------

Due to limited resources, the functionality of this plugin is primarily implemented for our own local needs and published as-is to the community. We are aware that members of the community will have other needs and would love to see them solved by this plugin.

Please issue feature proposals on Github:
https://github.com/moodleuulm/moodle-tool_coursefields/issues

Please create pull requests on Github:
https://github.com/moodleuulm/moodle-tool_coursefields/pulls

We are always interested to read about your feature proposals or even get a pull request from you, but please accept that we can handle your issues only as feature _proposals_ and not as feature _requests_.


Moodle release support
----------------------

Due to limited resources, this plugin is only maintained for the most recent major release of Moodle as well as the most recent LTS release of Moodle. Bugfixes are backported to the LTS release. However, new features and improvements are not necessarily backported to the LTS release.

Apart from these maintained releases, previous versions of this plugin which work in legacy major releases of Moodle are still available as-is without any further updates in the Moodle Plugins repository.

There may be several weeks after a new major release of Moodle has been published until we can do a compatibility check and fix problems if necessary. If you encounter problems with a new major release of Moodle - or can confirm that this plugin still works with a new major release - please let us know on Github.

If you are running a legacy version of Moodle, but want or need to run the latest version of this plugin, you can get the latest version of the plugin, remove the line starting with $plugin->requires from version.php and use this latest plugin version then on your legacy Moodle. However, please note that you will run this setup completely at your own risk. We can't support this approach in any way and there is an undeniable risk for erratic behavior.


Translating this plugin
-----------------------

This Moodle plugin is shipped with an english language pack only. All translations into other languages must be managed through AMOS (https://lang.moodle.org) by what they will become part of Moodle's official language pack.

As the plugin creator, we manage the translation into german for our own local needs on AMOS. Please contribute your translation into all other languages in AMOS where they will be reviewed by the official language pack maintainers for Moodle.


Right-to-left support
---------------------

This plugin has not been tested with Moodle's support for right-to-left (RTL) languages.
If you want to use this plugin with a RTL language and it doesn't work as-is, you are free to send us a pull request on Github with modifications.


PHP7 Support
------------

Since Moodle 3.4 core, PHP7 is mandatory. We are developing and testing this plugin for PHP7 only.


Copyright
---------

Ulm University
Communication and Information Centre (kiz)
Alexander Bias


Credits
-------
This plugin is heavily inspired by the existing plugin tool_coursedates by Charles Fulton which is kindly published on https://github.com/LafColITS/moodle-tool_coursedates.
