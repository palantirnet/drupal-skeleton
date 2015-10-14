Vagrant.require_version ">= 1.7"

unless Vagrant.has_plugin?("vagrant-hostmanager")
    raise "vagrant-hostmanager plugin is not installed. Please install it with vagrant plugin install vagrant-hostmanager"
end

unless Vagrant.has_plugin?("vagrant-auto_network")
    raise "vagrant-auto_network plugin is not installed. Please install it with: vagrant plugin install vagrant-auto_network"
end


Vagrant.configure("2") do |config|

  # tunables
  env_prefix  = ENV['DRUPAL_VAGRANT_ENV_PREFIX'] || 'DRUPAL_VAGRANT'
  project     = ENV["#{env_prefix}_PROJECT"] || 'skeleton'
  # end tunables

  config.vm.box     = "palantir/ubuntu-default"
  path = "/var/www/sites/#{project}.local"

  config.vm.synced_folder ".", "/vagrant", :disabled => true
  config.vm.synced_folder ".", path, :nfs => true#, :mount_options => ['async','nolock','noatime','nodiratime','rw','hard','intr']
  config.vm.hostname = "#{project}.local"
  config.vm.network "private_network", :auto_network => true
  config.hostmanager.enabled = true
  config.hostmanager.manage_host = true

  config.ssh.forward_agent = true

  config.vm.provision :shell, inline: <<SCRIPT
  set -ex

  /opt/phantomjs --webdriver=8643 &> /dev/null &
  su vagrant -c 'cd #{path} && composer install;
  cd #{path} && [[ -f .env ]] && source .env || cp env.dist .env && source env.dist && build/install.sh'
SCRIPT
end
