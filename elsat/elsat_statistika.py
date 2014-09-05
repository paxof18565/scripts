#!/usr/bin/env python2
# -*- coding: utf-8 -*-

import httplib
import base64
import string
import os
import ConfigParser

def elsat_statistika():
	config = ConfigParser.ConfigParser()
	config.read( os.path.dirname(os.path.realpath(__file__)) + '/config.ini' )

	host = config.get('elsat_statistika', 'host')
	username = config.get('elsat_statistika', 'username')
	password = config.get('elsat_statistika', 'password')

	url = "/counter/" + username  + "/"
	message = 'some message'
	
	# base64 encode the username and password
	auth = base64.encodestring('%s:%s' % (username, password)).replace('\n', '')
	
	webservice = httplib.HTTP(host)
	# write your headers
	webservice.putrequest("POST", url)
	webservice.putheader("Host", host)
	webservice.putheader("User-Agent", "Python http auth")
	webservice.putheader("Content-type", "text/html; charset=\"UTF-8\"")
	webservice.putheader("Content-length", "%d" % len(message))
	webservice.putheader("Authorization", "Basic %s" % auth)
	
	webservice.endheaders()
	webservice.send(message)
	# get the response
	statuscode, statusmessage, header = webservice.getreply()
	#print "Response: ", statuscode, statusmessage
	#print "Headers: ", header
	res = webservice.getfile().read()

	from HTMLParser import HTMLParser

	class MLStripper(HTMLParser):
		def __init__(self):
			self.reset()
			self.fed = []
		def handle_data(self, d):
			self.fed.append(d)
		def get_data(self):
			return ''.join(self.fed)

	def strip_tags(html):
		s = MLStripper()
		s.feed(html)
		return s.get_data()

	notice = ''
	for line in res.split("\n"):
		if "celkem:" in line:
			ret = strip_tags(line.strip()).strip()
			ret = ret[ret.find(': ') + 1 : ret.find(' MB')]
			return float(ret)
		
	return -1

if __name__ == '__main__':
	print 'Data celkem: ' + str(elsat_statistika()) + ' MB'