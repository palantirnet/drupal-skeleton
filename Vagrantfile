Vagrant.require_version ">= 1.7.2"

unless Vagrant.has_plugin?("vagrant-hostmanager")
      raise "vagrant-hostmanager plugin is not installed"
end

Vagrant.configure("2") do |config|

  # tunables
  env_prefix  = ENV['DRUPAL_VAGRANT_ENV_PREFIX'] || 'DRUPAL_VAGRANT'
  ip          = ENV["#{env_prefix}_IP"] || '10.33.36.12'
  project     = ENV["#{env_prefix}_PROJECT"] || 'skeleton'
  # end tunables

  config.vm.box     = "palantir/ubuntu-default"
  path = "/var/www/sites/#{project}.local"

  config.vm.synced_folder ".", "/vagrant", :disabled => true
  config.vm.synced_folder ".", path, :nfs => true
  config.vm.hostname = "#{project}.local"
  config.vm.network :private_network, ip: ip
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
