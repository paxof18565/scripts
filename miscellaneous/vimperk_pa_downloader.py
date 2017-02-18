#!/usr/bin/env python
# -*- coding: utf-8 -*-

import os
import sys
import datetime
import logging
import re

import requests

logging.basicConfig(format='%(asctime)s %(levelname)s: %(message)s')
logger = logging.getLogger(__name__)
logger.setLevel(logging.INFO)

def vimperk_pa_download(year, directory):

	PA_URL = 'http://www.mesto.vimperk.cz/audio/rozhlas/'

	url = PA_URL + str(year) + '/'
	if year == 2014:
		url = PA_URL

	logger.info('Downloading from %s into %s', url, directory)

	if not os.path.exists(directory):
		logger.info('Creating directory %s', directory)
		os.makedirs(directory)

	response = requests.get(url)

	regex = re.compile(r'"((\w|%)+\.wav)"', re.UNICODE)
	matches = regex.findall(response.content.decode(encoding=response.encoding))

	logger.info('Found %s files', len(matches))

	for match in matches:
		file_item = match[0]
		local_filename = os.path.join(directory, file_item)

		logger.debug('Processing file %s', file_item)

		if not os.path.isfile(local_filename):
			logger.info('Downloading %s', file_item)

			remote_file = requests.get(url + file_item)
			local_file = open(local_filename, 'wb')
			local_file.write(remote_file.content)
		else:
			logger.debug('File %s already on local storage', file_item)


if __name__ == '__main__':

	download_directory = os.path.join(sys.argv[1], '') if len(sys.argv) == 2 else '/tmp/vimperk_pa/'

	for year in list(range(2014, datetime.date.today().year + 1)):
		vimperk_pa_download(year, download_directory + str(year) + '/')
