test:
	vagrant provision

vagrant-ip:
	vagrant ssh-config | grep HostName | awk '{print $$2}'

tail-errs:
	vagrant ssh -c 'tail -n 20 /var/log/apache2/error.log'

tail-axs:
	vagrant ssh -c 'tail -n 20 /var/log/apache2/access.log'

deploy:
	ansible-playbook playbook.yml --extra-vars "variable_host=timmy"
