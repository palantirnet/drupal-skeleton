# { Your Project Here. }
## { Some lengthier description of the project. }

##Requirements

------------
* [virtualBox](https://www.virtualbox.org/wiki/Downloads) >= 4.3.x
* [vagrant](http://downloads.vagrantup.com/) >= 1.6.x
* [vagrant-hostsupdater](https://github.com/cogitatio/vagrant-hostsupdater) `vagrant plugin install vagrant-hostsupdater`

## Getting Started

------------------

1. From inside the project root, run `vagrant up`
2. You will be prompted for the administration password on your host machine. Obey.
3. Visit `{your-project-name}.dev` in your browser of choice.

## How do I work on this?

------------------

1. From inside the project root `vagrant ssh`
2. Navigate to `/var/www/sites/{your-project-name}.dev`

There is your project. Run `drush` commands from the `www` directory just like you would without a VB.

## Troubleshooting

------------------

* If you get the error `Command: ["hostonlyif", "create"]`, you need to restart VirtualBox.

```
sudo /Library/Application\ Support/VirtualBox/LaunchDaemons/VirtualBoxStartup.sh restart
```


