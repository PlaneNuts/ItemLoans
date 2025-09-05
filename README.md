# ItemLoans GLPI plugin

This plugin was created out of the need for my team to be able to quickly loan and return large numbers of devices. This is my first attempt at a plugin so there may be some bugs.

## Features
* A table showing a history of all loans (closed and active) with the ability to use GLPIs powerful native search to find histories of loans by user or device
* Loaning and returning process allows you to quickly scan items into a temporary cart using the items Serial Number, Inventory Number, or Immobilization number
* When loaning items you can quickly set a new location and status for all of the items being loaned (leaving them blank will make no changes to the items)
* It's also possible to send optional notifications to users to ask them to confirm reception of their loans, and return reminders for loans that have a limited duration 
* 'Computer', 'Monitor', 'Phone', 'NetworkEquipment', 'Peripheral', 'Software', 'Printer' item types can be loaned as well as items created with the Generic Objects plugin
* Permissions can be granted to profiles allowing the user to only view, create loans, or return items
* End users with Self Service have the ability to see items currently loaned to them

## How to Install
* Download the files as a zip
* Extract the files to your /plugins directory
* In GLPI go to settings --> plugins and enable the plugin
* In your user profile set desired permissions for the plugin
  

## Contributing

* Open a ticket for each bug/feature so it can be discussed
* Follow [development guidelines](http://glpi-developer-documentation.readthedocs.io/en/latest/plugins/index.html)
* Refer to [GitFlow](http://git-flow.readthedocs.io/) process for branching
* Work on a new branch on your own fork
* Open a PR that will be reviewed by a developer
