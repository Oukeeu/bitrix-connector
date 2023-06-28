# bitrix-connector
Web application-connector on Bitrix.ru for offline uploading data to the database once at a specified time and communication with looker studio for reporting

This work is a web application connector between bitrix24 and looker studio, which offline unloads data from the CRM system portal bitrix24 in the database once in a certain, specified time.

Features:
1) Configure the date intervals for which to unload.
2) Set the interval, once at what time to produce an upload of data.
3) The program creates a mysql database for each user separately and provides data for connecting to it via looker studio.
4) It is possible to specify the data for your database, which needs to be unloaded.

As a result, the application unloads data from CRM bitrix24 data in the database MySQL on the specified parameters.

Stack:
1) Cron
2) Deploying a web server on a VPS
3) Installing LAMP on the web server
4) MySQL
5) PHP
6) JS
7) HTML
8) CSS
9) API/rest
