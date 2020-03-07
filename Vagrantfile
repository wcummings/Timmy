Vagrant.configure("2") do |config|

  config.vm.box = "generic/ubuntu1804"

  config.vm.provision "ansible" do |ansible|
    ansible.verbose = "v"
    ansible.playbook = "playbook.yml"
    ansible.extra_vars = {
      app_user: "vagrant"
    }
  end
  
end
