SkonsoftLftpDeployementBundle
=============================

The skonsoft:deploy command helps you to deploy your sources in your web server using LFTP.
By default, this command executes LFTP with your config information set under app/config/config.yml

problem:
========
I had a subscription to a web host that does not allow SSH access or access git. Only FTP is allowed.
The work was hard to update the site with my modifications lovales.
That's why I tried something similar to rsync, but that is based on FTP. The solution was LFTP.

LFTP:
=====
Lftp is a FTP client to easily command line to transfer files (eg to update your website or locally modified to make a backup on your computer).

Prerequisites:
==============
Install LFTP:
(Ubuntu Users)
sudo apt-get install lftp

Modify LFTP Conf file:

sudo gedit /etc/lftp.conf

Update these lines:

    set ftp:anon-pass "mozilla@"
    set ftp:client ""
    set http:user-agent "Mozilla/4.7 [en] (WinNT; I)"

    set dns:order "inet6 inet"

    set ssl:verify-certificate no