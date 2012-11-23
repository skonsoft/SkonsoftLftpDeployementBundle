SkonsoftLftpDeployementBundle
=============================

The skonsoft:deploy command helps you to deploy your sources in your web server using LFTP.
By default, this command executes LFTP with your config information set under app/config/config.yml

#problem:

I had a subscription to a web host that does not allow SSH access or access git. Only FTP is allowed.
The work was hard to update the site with my modifications lovales.
That's why I tried something similar to rsync, but that is based on FTP. The solution was LFTP.

#LFTP:

[Lftp] (http://lftp.yar.ru/) is a FTP client to easily command line to transfer files (eg to update your website or locally modified to make a backup on your computer).

[Ubuntu Documentation] (http://doc.ubuntu-fr.org/lftp) 

#Prerequisites:

Install LFTP:

```
#(Ubuntu Users)
sudo apt-get install lftp
```

Modify LFTP Conf file:

sudo gedit /etc/lftp.conf

Update these lines:

    set ftp:anon-pass "mozilla@"
    set ftp:client ""
    set http:user-agent "Mozilla/4.7 [en] (WinNT; I)"

    set dns:order "inet6 inet"

    set ssl:verify-certificate no

#Install Bundle:

Installation is a quick 3 steps process:

1. Download SkonsoftLftpDeployementBundle
2. Enable the Bundle
3. Configure your application's config.yml
4. Enjoy !

### Step 1: Install SkonsoftLftpDeployementBundle

The preferred way to install this bundle is to rely on [Composer](http://getcomposer.org).
Just check on [Packagist](http://packagist.org/packages/friendsofsymfony/oauth-server-bundle) the version you want to install (in the following example, we used "dev-master") and add it to your `composer.json`:

``` js
{
    "require": {
        // ...
        "skonsoft/lftp-deployement-bundle": "dev-master"
    }
}
```

### Step 2: Enable the bundle

Enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Skonsoft\Bundle\SkonsoftLftpDeployementBundle(),
    );
}
```

### Step 3: Configure your config.yml

```
#app/config/config.yml

parameters:
    # ... other parameters
    skonsoft:
        lftp_deployement:
            prod:
                hostname: "FTP.mysite.com"
                path: "/www/" #the path to mirror in server. (eg /public_html/)
                port: "21" #default
                login: "Your FTP login"
                exclude_file: %kernel.root_dir%/config/skonsoft_lftp_exclude.txt # Contains all ignored files. See the doc folder, you will get an example of file
```

#Using
to make your sync, just type in terminal this command:
```
./app/console skonsoft:deploy --go

```

to get the list of options:
```
./app/console help skonsoft:deploy

```

#Finsih

I help this helps you :)
