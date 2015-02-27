# RFF
## Resources For the Future

A new Drupal site and migration from SharePoint and WordPress for http://rff.org/.

## Requirements

* [virtualBox](https://www.virtualbox.org/wiki/Downloads) >= 4.3.x
* [vagrant](http://downloads.vagrantup.com/) >= 1.6.x

## Getting Started

1. From inside the project root, run `vagrant up` 
2. You will be prompted for the administration password on your host machine.
3. Open your hosts file on your host (e.g., `sudo vim /etc/hosts`), and add the following line:
> 10.33.36.12 rff.dev

4. Visit `rff.dev` in your browser of choice.

## How do I work on this?

1. From inside the project root `vagrant ssh`
2. Navigate to `/var/www/sites/rff.dev`

There is your project. Run `drush` commands from the `www` directory just like you would without a VB.

## Troubleshooting

* If on `vagrant up` NFS mounting just hangs, halt the process, remove `exports` (`rm /etc/exports`), and run `vagrant up` again.
* If you get the error `Command: ["hostonlyif", "create"]`, you need to restart VirtualBox:

```
sudo /Library/Application\ Support/VirtualBox/LaunchDaemons/VirtualBoxStartup.sh restart
```
