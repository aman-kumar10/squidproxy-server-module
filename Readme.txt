Squid Proxy Server Module

Overview

This module is designed to integrate a Squid Proxy Server with a WHMCS product. When a user purchases a product linked to this module, they must select the number of proxies they require. Based on their selection, the system automatically allocates the corresponding IP addresses.

Features

Allows users to select the number of proxies during purchase.
Provides two configurable fields for the admin to enter API credentials (Username & Password).
Stores API credentials in the product's configuration options after successful validation.
Automatically creates a proxy account with a username concatenated with the client username and service ID.
Generates a random password for the user and stores proxy credentials in WHMCS database tables (tblcustomfields and tblcustomfieldsvalues).

Supports essential account management functions:
Suspend
Unsuspend
Terminate
Change Password

Deletes user-related custom field values upon termination.
Displays proxy allocation details on both the admin and user sides.
Allows password changes from both admin and client sides, updating them in the custom values table.

Installation & Configuration

Upload the module files to the appropriate WHMCS directory.
Navigate to Setup > Products/Services > Servers in WHMCS.
Add a new server and select this module.
Enter the API Username and Password in the module configuration.
Assign the module to the desired product.

Usage

Admin Side

Configure API credentials in Module Settings.
View and manage user proxy allocations.
Suspend, unsuspend, and terminate accounts.
Change and update user passwords.

Client Side

Select the number of proxies during purchase.
View assigned proxy details.
Change the proxy password when needed.

Database Structure

tblcustomfields: Stores custom field configurations for proxy credentials.
tblcustomfieldsvalues: Stores the actual username and password assigned to the user.
tblproducts: Store the API username and password

API Integration

The module interacts with a proxy API for:

Account creation
Credential storage and retrieval
Suspension and termination
Password changes

Future Enhancements

Implement logs for API transactions.
Add support for additional proxy providers.
Enhance security measures for stored credentials.


Developed by: [WHMCS Global Services]For support, contact [wgs@developers.com]
