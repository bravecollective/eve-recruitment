#!/bin/bash

if [ -f /swapfile ]; then
	echo "/swapfile found"
	exit;
fi

/bin/dd if=/dev/zero of=/swapfile bs=1M count=512
/bin/chmod 600 /swapfile
/sbin/mkswap /swapfile
/sbin/swapon /swapfile
