# light-ca-gui

This project is a tool to create your Certificate Authority with step-ca anda Web User interface to create your certificates with your own Certificate Authority created with the ca.

We have :
 - 1 Dockerfile with all tools require to run an apache with all php tools and install step-ca.
 
 - A Web site with All tools to :
   . Download the CA files
   . Create certificate with th CA after asking for the password to sign with the certificate key
   . Authentification with Active Directory and .password file with login,password
   . A script to add users to the .password file

