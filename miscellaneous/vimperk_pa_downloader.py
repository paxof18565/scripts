#!/usr/bin/env python2
# -*- coding: utf-8 -*-

import urllib2
import re
import os

paURL = "http://www.mesto.vimperk.cz/audio/rozhlas/"
downloadDirectory = '/tmp/vimperk_pa/'

def downloadFromURL(url, directory):
	if (not os.path.exists(directory)):
		os.makedirs(directory)

	req = urllib2.Request(url)
	response = urllib2.urlopen(req)
	page = response.read()

	regex = re.compile(r'"((\w|%)+\.wav)"', re.UNICODE)
	matches = regex.findall(page)

	for match in matches:
		file = match[0]

		if (not os.path.isfile(directory + '/' + file)):
			remote_file = urllib2.urlopen(url + file)
			local_file = open(directory + '/' + file, "wb")
			local_file.write(remote_file.read())

downloadFromURL(paURL, downloadDirectory)
downloadFromURL(paURL + "2015/", downloadDirectory + "2015/")