Vagrant.configure("2") do |config|

  # tunables
  env_prefix  = ENV['DRUPAL_VAGRANT_ENV_PREFIX'] || 'DRUPAL_VAGRANT'
  ip          = ENV["#{env_prefix}_IP"] || '10.33.36.12'
  project     = ENV["#{env_prefix}_PROJECT"] || 'default'
  # end tunables

  config.vm.box     = "palantir/ubuntu-default"
  path = "/var/www/sites/#{project}.dev"

  config.vm.synced_folder ".", "/vagrant", :disabled => true
  config.vm.synced_folder ".", path, :nfs => true
  config.vm.hostname = "#{project}.dev"

  config.vm.network :private_network, ip: ip
  config.vm.network "forwarded_port", guest: 4000, host: 4000, guest_ip: '0.0.0.0:4000'

  config.vm.provision :shell, inline: <<SCRIPT
  set -ex
  /opt/phantomjs --webdriver=8643 &> /dev/null &
  su vagrant -c 'cd #{path} && composer install;
  cd #{path} && [[ -f .env ]] && source .env || cp env.dist .env && source env.dist && build/install.sh'
SCRIPT
end
