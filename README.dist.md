# { Your Project Here. }
## { Some lengthier description of the project. }

##Requirements

------------
* [virtualBox](https://www.virtualbox.org/wiki/Downloads) >= 4.3.x
* [vagrant](http://downloads.vagrantup.com/) >= 1.6.x
* [vagrant-hostmanager](https://github.com/smdahlen/vagrant-hostmanager) `vagrant plugin install vagrant-hostmanager`
* Make sure your `conf` directory is writable.

## Getting Started

------------------

1. From inside the project root, run `vagrant up`
2. You will be prompted for the administration password on your host machine. Obey.
3. Visit `{your-project-name}.local` in your browser of choice.

## How do I work on this?

------------------

1. From inside the project root `vagrant ssh`
2. Navigate to `/var/www/sites/{your-project-name}.local`

There is your project. Run `drush` commands from the `www` directory just like you would without a VB.

## Troubleshooting

------------------

* If you get the error `Command: ["hostonlyif", "create"]`, you need to restart VirtualBox.

```
sudo /Library/Application\ Support/VirtualBox/LaunchDaemons/VirtualBoxStartup.sh restart
```

* If you vagrant up and note that there are no files in your /var/www/sites/SITE, it is very likely that your NFS did not mount correctly. The solution is to halt the box, remove your exports file, and reboot the box. If this was not the problem, no harm will be done.
```
vagrant halt;
sudo rm /etc/exports;
vagrant up;
```
