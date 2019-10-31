#!/bin/bash -e

if [[ -f /opt/plesk/php/7.1/bin/php ]]; then
	sudo -u $1 bash -c "/opt/plesk/php/7.1/bin/php $2"
elif [[ -f /opt/plesk/php/7.2/bin/php ]]; then
	sudo -u $1 bash -c "/opt/plesk/php/7.2/bin/php $2"
elif [[ -f /opt/plesk/php/7.3/bin/php ]]; then
	sudo -u $1 bash -c "/opt/plesk/php/7.3/bin/php $2"
elif [[ -f /opt/plesk/php/5.6/bin/php ]]; then
	sudo -u $1 bash -c "/opt/plesk/php/5.6/bin/php $2"
fi