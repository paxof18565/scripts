#!/usr/bin/env python
# -*- coding: utf-8 -*-

import os
import re

import requests
import requests.auth
import yaml


def elsat_statistics(host, username, password):

	url = host + '/' + username + '/'

	response = requests.get(url, auth=requests.auth.HTTPBasicAuth(username, password))

	regex = re.compile(r'<b>Data celkem: </b>(.*)<br/><br/>', re.UNICODE)

	for line in response.content.decode(response.encoding).split("\n"):
		if 'Data celkem:' in line:
			matches = regex.findall(line)

			usage = matches[0].replace(' MB', '')

			return float(usage)

	return False


if __name__ == '__main__':
	script_directory = os.path.dirname(os.path.abspath(__file__))

	with open(os.path.join(script_directory, 'config.yaml'), 'r') as f:
		config = yaml.load(f)

	config = config['elsat_statistics']

	usage = elsat_statistics(config['host'], str(config['username']), str(config['password']))
	print('Current usage: ' + str(usage) + ' MB')
