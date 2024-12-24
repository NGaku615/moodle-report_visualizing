Moodle: Log visualization report
==================================

This [Moodle](http://moodle.org) add-on produces various site and course report
charts.  The code has been designed in a way that makes adding more reports
easy.

For producing the graphs, D3.js(v7) is used.

We will visualize users' page transitions within Moodle based on access logs. By retrieving the accessed modules and timestamps, we will create a scatter plot where points represent module access events. Connecting the points with lines will illustrate the transitions from one page to another.

Author
------
This add-on is currently maintained by [NGaku615](https://github.com/NGaku615).
It was written by Gaku Nakao @moodle.com