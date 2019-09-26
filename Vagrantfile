Vagrant.configure("2") do |config|

    config.vm.box = "ubuntu/bionic64"
	config.vm.provider "virtualbox" do |vb|
	#	vb.customize [ "modifyvm", :id, "--uartmode1", "disconnected" ]
	    vb.memory = 3048
        vb.cpus = 2
    end

    config.vm.network "private_network", ip: "192.168.33.16"
    #config.vm.network "forwarded_port", guest: 3306, host: 3316
    config.vm.hostname = "seodomains.local"
    config.vm.synced_folder ".", "/var/www/site", :mount_options => ["dmode=777", "fmode=777"], owner: "vagrant", group: "vagrant"  #возможно owner = www-data
	
    config.vm.provision :shell, path: "script.sh"
    
end