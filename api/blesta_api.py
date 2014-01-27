#!/usr/bin/env python2.7

"""
Python-converted version of Blesta's API.

Original: https://github.com/phillipsdata/blesta_sdk/tree/master/api

Acts the same as the original.

## TODO:
#* Provide simple ORM-style interface to API
#* Clean up code to make it easier to read
#* Send to Blesta
"""

from json import loads as load
import httplib, urllib

"""
This is what the end-user gets as a return.

Access .response to get the response, .errors for any error messages.
"""
class BlestaResponse(object):
    
    def __init__(self, resp, code):
        self.raw = resp
        self.code = code
        print ">> Code: %s" % self.code
        print ">> Raw: %s" % self.raw
    
    @property
    def response(self):
        resp = self.format
        
        try:
            return resp['response']
        except:
            return None
    
    @property
    def respcode(self):
        return self.code
    
    @property
    def data(self):
        return self.raw
    
    @property
    def format(self):
        return load(str(self.raw))
    
    @property
    def errors(self):
        if self.code != 200:
            resp = self.format
            
            try:
                return resp['message']
            except KeyError:
                pass
        
        return None

"""
Call this to get a response.  BlestaAPI(url, username, API key)

Currently only works by calling .get()/post()/put()/delete() methods.
"""
class BlestaAPI(object):
    
    def __init__(self, url, user, pasw):
        self.port,self.url = url.split("://")
        self.user = user
        self.pasw = pasw
        self.host, self.uri = self.url.split("/", 1)
        
    def get(self, model, method, args={}, fmt="json"):
        self.model = model
        self.method = method
        self.fmt = fmt
        self.args = args
        self.action = "GET"
        
        return self.req()
    
    def post(self, model, method, args={}, fmt="json"):
        self.model = model
        self.method = method
        self.fmt = fmt
        self.args = args
        self.action = "POST"
        
        return self.req()
    
    def put(self, model, method, args={}, fmt="json"):
        self.model = model
        self.method = method
        self.fmt = fmt
        self.args = args
        self.action = "PUT"
        
        return self.req()
    
    def delete(self, model, method, args={}, fmt="json"):
        self.model = model
        self.method = method
        self.fmt = fmt
        self.args = args
        self.action = "DELETE"
        
        return self.req()
    
    def req(self):
        from base64 import encodestring as es
        
        url = "/%s%s/%s.%s" % (self.uri, self.model, self.method, self.fmt)
        self.last_req = {'url' : url, 'args' : self.args}
        
        if self.action == "GET":
            url = "%s?%s" % (url, urllib.urlencode(self.args))
            self.args = None
        
        if self.args is not None:
            self.args = urllib.urlencode(self.args)
        
        if self.port == "http":
            ws = httplib.HTTPConnection(self.host)
        elif self.port == "https":
            ws = httplib.HTTPSConnection(self.host)
        else:
            raise Exception("Protocol '%s' unknown or unsupported." % (port))
        
        auth = es('%s:%s' % (self.user, self.pasw)).replace('\n', '')
        
        headers = {"Authorization" : "Basic %s" % auth}
        
        ws.request(self.action, url, self.args, headers)
        
        resp = ws.getresponse()
        
        data = resp.read()
        
        ws.close()
        
        return BlestaResponse(data, resp.status)
    
    @property
    def last_request(self):
        return self.last_req
