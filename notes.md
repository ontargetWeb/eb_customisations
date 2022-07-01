Activate Certificate on EB joomla 3.10.10 and EB 4.2.0
======================================
Configuration > Turn on Certificate (See ecsligo) Turn on Check-in
Update cert_blank FULL.png with centre logo

https://github.com/ontargetWeb/eb_customisations/blob/main/certificate_layout.html

Emails and Messages > 
======================================
Certificate for [EVENT_TITLE] 

Dear [FIRST_NAME] [LAST_NAME],

Please see your certificate for [EVENT_TITLE] on  [EVENT_DATE], attached.

Thank you for attending,

Add Custom field
======================================
Custom field > Registrant Type >  Show in List > Make Filterable
Add Custom Field > List> Attended Hours
Show On Registrants Management = Yes
Populate From Previous Registration = no
CSS Class = uk-hidden
Size = 0
Hide on Email = Yes

All
0.5
1
1.5
2
2.5
3
3.5
4
4.5
5
5.5
6
6.5
7
7.5
8
8.5
9
9.5
10
10.5
11
11.5
12
12.5
13
13.5
14
14.5
15
15.5
16
16.5
17
17.5
18
18.5
19
19.5
20

Update Language Strings:
======================================
https://github.com/ontargetWeb/eb_customisations/blob/main/public_html/administrator/language/overrides/en-GB.override.ini


Update Admin Menu
======================================
https://github.com/ontargetWeb/eb_customisations/blob/main/admin_menu.html

In usermenu
======================================
Add > My Courses and Certs (New!)


Add Printable CSS for attendance list
======================================
public_html/administrator/templates/isis/html/com_eventbooking/common/registrants_pdf.php

https://github.com/ontargetWeb/eb_customisations/blob/main/public_html/administrator/templates/isis/html/com_eventbooking/common/registrants_pdf.php
