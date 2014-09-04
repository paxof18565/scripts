#!/usr/bin/env python2
# -*- coding: utf-8 -*-

import urllib
import urllib2
import re
import os.path

url = 'http://www.mesto.vimperk.cz/audio/rozhlas/'
downloadDirectory = '/tmp/'

req = urllib2.Request(url)
response = urllib2.urlopen(req)
page = response.read()

regex = re.compile(r'"((\w|%)+\.wav)"', re.UNICODE)
matches = regex.findall(page)

for match in matches:
	file = match[0]

	if (not os.path.isfile(downloadDirectory + '/' + file)):
		testfile = urllib.URLopener()
		testfile.retrieve(url + file, downloadDirectory + '/' + file)