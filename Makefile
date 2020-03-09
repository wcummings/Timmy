test:
	vagrant provision

vagrant-ip:
	vagrant ssh-config | grep HostName | awk '{print $$2}'

tail-errs:
	vagrant ssh -c 'tail -n 20 /var/log/apache2/error.log'
